<?php
/**
 * Tool for creating property posts from a CSV export.
 *
 * @package east-property
 */

namespace Tools;

use WP_CLI;

/**
 * Creates property posts from a positional CSV export.
 */
final class Property_Importer {

	/**
	 * Column order of the export, which carries no header row.
	 *
	 * Positional by necessity: the file starts straight at the data, so the
	 * layout is the only contract there is. A row of a different width is
	 * rejected rather than guessed at.
	 */
	private const COLUMNS = array(
		0 => 'title_en',
		1 => 'title_ru',
		2 => 'developer',
		3 => 'latitude',
		4 => 'longitude',
		5 => 'ownership_type',
		6 => 'delivery_date',
		7 => 'property_type',
		8 => 'description_en',
		9 => 'description_ru',
	);

	/**
	 * Ties a post back to the row it came from, so a second run updates nothing
	 * by accident and skips what it already created.
	 */
	private const IMPORT_SLUG_META = '_core_import_slug';

	/**
	 * Marks a Russian post still carrying English text.
	 *
	 * Such a post is a placeholder, not a translation, and the flag is what makes
	 * the backlog findable — deliberately without a leading underscore, so it
	 * shows up in the editor and in a meta query alike:
	 *
	 *     wp post list --post_type=property --meta_key=need_translate --meta_value=1
	 *
	 * Set only where the text was copied, and removed once the row actually
	 * carries Russian, so the flag never outlives the work it describes.
	 */
	private const NEED_TRANSLATE_META = 'need_translate';

	/**
	 * Path of the file being read.
	 *
	 * @var string
	 */
	private string $file;

	/**
	 * Rows that passed validation, keyed by slug. Null until parse() has run.
	 *
	 * @var array|null
	 */
	private ?array $rows = null;

	/**
	 * Problems found while parsing or importing.
	 *
	 * @var array
	 */
	private array $errors = array();

	/**
	 * Bind the tool to one export.
	 *
	 * @param string $file Absolute path of the CSV file.
	 */
	public function __construct( string $file ) {
		$this->file = $file;
	}

	/**
	 * Read the file and reduce it to rows worth importing.
	 *
	 * The slug WordPress would build from the English title is the identity used
	 * throughout: it is what the URL is made of, so two rows that would collide
	 * there are the same project however differently they are spelled — straight
	 * versus curly quotes included.
	 *
	 * @return array Rows keyed by that slug.
	 */
	public function parse(): array {
		if ( null !== $this->rows ) {
			return $this->rows;
		}

		$this->rows = array();

		$handle = $this->open();
		if ( null === $handle ) {
			return $this->rows;
		}

		$line = 0;

		/*
		 * Every argument is spelled out: PHP 8.4 deprecates leaving $escape to its
		 * default, and the default itself is the non-standard one. An empty escape
		 * is RFC 4180 — a doubled quote is the only escape inside a quoted field,
		 * and a backslash is just a backslash. Verified against this export: the
		 * two settings parse all 2325 rows identically, so nothing shifts.
		 */
		while ( false !== ( $raw = fgetcsv( $handle, null, ',', '"', '' ) ) ) {
			++ $line;

			if ( count( $raw ) !== count( self::COLUMNS ) ) {
				$this->error( (string) $line,
					sprintf( 'expected %d columns, found %d', count( self::COLUMNS ), count( $raw ) ) );
				continue;
			}

			$row = array();
			foreach ( self::COLUMNS as $index => $name ) {
				$row[ $name ] = trim( (string) ( $raw[ $index ] ?? '' ) );
			}

			if ( '' === $row['title_en'] ) {
				$this->error( (string) $line, 'no English title' );
				continue;
			}

			$slug = sanitize_title( $row['title_en'] );
			if ( '' === $slug ) {
				$this->error( (string) $line, sprintf( 'title yields no slug: %s', $row['title_en'] ) );
				continue;
			}

			if ( isset( $this->rows[ $slug ] ) ) {
				$this->error( $slug, sprintf( 'line %d repeats a project already in the file', $line ) );
				continue;
			}

			$row['slug'] = $slug;
			$row['line'] = $line;

			$this->rows[ $slug ] = $row;
		}

		fclose( $handle );

		return $this->rows;
	}

	/**
	 * Report which rows are new without writing anything.
	 *
	 * @return array Counters plus the slugs of what would be created.
	 */
	public function preview(): array {
		$known = $this->existing_projects();
		$new   = array();

		foreach ( $this->parse() as $slug => $row ) {
			if ( isset( $known[ $slug ] ) ) {
				continue;
			}

			$new[] = $slug;
		}

		$russian = 0;
		foreach ( $this->parse() as $row ) {
			if ( $this->has_russian( $row ) ) {
				++ $russian;
			}
		}

		return array(
			'in_file'      => count( $this->parse() ),
			'existing'     => count( $this->parse() ) - count( $new ),
			'new'          => count( $new ),
			'with_russian' => $russian,
			'slugs'        => $new,
		);
	}

	/**
	 * Create the projects the database does not have yet.
	 *
	 * Options, all optional: dry_run writes nothing and is on by default, since
	 * the alternative creates thousands of posts; status sets what the created
	 * posts are saved as and defaults to publish; limit stops after that many;
	 * create_developers adds a
	 * developer post when the name matches none, and is off by default because
	 * the export spells one company several ways and each spelling would become
	 * a developer of its own.
	 *
	 * @param array $args Options as described above.
	 *
	 * @return array
	 */
	public function import( array $args = array() ): array {
		$args = wp_parse_args(
			$args,
			array(
				'dry_run'           => true,
				'status'            => 'publish',
				'limit'             => 0,
				'create_developers' => false,
				'on_progress'       => null,
			)
		);

		$progress = is_callable( $args['on_progress'] ) ? $args['on_progress'] : null;

		$known  = $this->existing_projects();
		$report = array(
			'created'             => 0,
			'translated'          => 0,
			'placeholder_ru'      => 0,
			'skipped'             => 0,
			'failed'              => 0,
			'without_developer'   => 0,
			'without_coordinates' => 0,
			'dry_run'             => (bool) $args['dry_run'],
		);

		foreach ( $this->parse() as $slug => $row ) {
			if ( isset( $known[ $slug ] ) ) {
				$filled = $this->fill_missing_translation( $row, $known[ $slug ], (bool) $args['dry_run'] );

				if ( $filled ) {
					++ $report['translated'];

					if ( ! $this->has_russian( $row ) ) {
						++ $report['placeholder_ru'];
					}
				} else {
					++ $report['skipped'];
				}

				if ( $progress ) {
					$progress( $slug, $filled ? 'translated' : 'skipped' );
				}

				continue;
			}

			if ( $args['limit'] > 0 && $report['created'] >= (int) $args['limit'] ) {
				break;
			}

			$developer = $this->resolve_developer( $row['developer'],
				(bool) $args['create_developers'],
				(bool) $args['dry_run'] );
			if ( 0 === $developer ) {
				++ $report['without_developer'];
			}

			if ( '' === $row['latitude'] || '' === $row['longitude'] ) {
				++ $report['without_coordinates'];
			}

			if ( $args['dry_run'] ) {
				++ $report['created'];
				WP_CLI::log( sprintf( 'Created:  %s', $slug ) );

				if ( $progress ) {
					$progress( $slug, 'created' );
				}

				continue;
			}

			if ( 0 === $this->write_row( $row, (string) $args['status'], $developer ) ) {
				++ $report['failed'];

				if ( $progress ) {
					$progress( $slug, 'failed' );
				}

				continue;
			}

			++ $report['created'];
			WP_CLI::log( sprintf( 'Created:  %s', $row['title_ru'] ?: $row['title_en'] ) );

			if ( ! $this->has_russian( $row ) ) {
				++ $report['placeholder_ru'];
			}

			if ( $progress ) {
				$progress( $slug, 'created' );
			}
		}

		return $report;
	}

	/**
	 * Problems found so far.
	 *
	 * @return array
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Give a project the site already has a Russian version.
	 *
	 * Only ever adds: a translation already on the site is left untouched, since
	 * the export repeats its English text in the Russian columns for all but a
	 * handful of rows and overwriting would be a downgrade far more often than an
	 * update. Where the export says nothing in Russian the English text stands in
	 * and the post is marked as a placeholder, so the Russian side of the site is
	 * complete and what still needs translating stays findable.
	 *
	 * @param array $row Parsed row.
	 * @param array $ids Posts sharing the row's slug.
	 * @param bool $dry_run Report what would happen and write nothing.
	 *
	 * @return bool Whether a translation was added, or would be.
	 */
	private function fill_missing_translation( array $row, array $ids, bool $dry_run ): bool {
		if ( ! function_exists( 'pll_get_post_translations' ) ) {
			return false;
		}

		$source = $this->source_post( $ids );
		if ( 0 === $source ) {
			return false;
		}

		// The whole group is read, not just the Russian slot: saving it back with
		// only two languages would drop any other translation it holds.
		$group = pll_get_post_translations( $source );

		if ( ! empty( $group['ru'] ) ) {
			return false;
		}

		if ( $dry_run ) {
			return true;
		}

		$source_post = get_post( $source );
		$slug        = $source_post && '' !== $source_post->post_name ? $source_post->post_name : $row['slug'];

		$translation = $this->write_post(
			'' !== $row['title_ru'] ? $row['title_ru'] : $row['title_en'],
			'' !== $row['description_ru'] ? $row['description_ru'] : $row['description_en'],
			(string) get_post_status( $source ),
			'ru',
			$slug
		);

		if ( 0 === $translation ) {
			return false;
		}

		// Taken from the post rather than resolved from the name again, so the
		// translation points at whatever developer the project already has.
		$this->write_fields( $translation, $row, (int) get_post_meta( $source, 'developer_rel', true ) );
		$this->flag_translation( $translation, $row );

		$group['ru'] = $translation;

		if ( function_exists( 'pll_save_post_translations' ) ) {
			pll_save_post_translations( $group );
		}

		if ( function_exists( 'core_sync_translation_slugs' ) ) {
			core_sync_translation_slugs( $group );
		}

		$this->assert_shared_slug( $slug, $group );

		return true;
	}

	/**
	 * Record whether a Russian post still needs a human.
	 *
	 * @param int $post_id Russian post.
	 * @param array $row Parsed row it was built from.
	 *
	 * @return void
	 */
	private function flag_translation( int $post_id, array $row ): void {
		if ( $this->has_russian( $row ) ) {
			delete_post_meta( $post_id, self::NEED_TRANSLATE_META );

			return;
		}

		update_post_meta( $post_id, self::NEED_TRANSLATE_META, 1 );
	}

	/**
	 * The post of the default language among those sharing a slug.
	 *
	 * A project and its translations answer to the same slug, and it is the
	 * original that owns the translation group.
	 *
	 * @param array $ids Post ids.
	 *
	 * @return int Zero when none of them is in the default language.
	 */
	private function source_post( array $ids ): int {
		$default = $this->default_language();

		foreach ( $ids as $id ) {
			$id = (int) $id;

			if ( ! function_exists( 'pll_get_post_language' ) ) {
				return $id;
			}

			if ( (string) pll_get_post_language( $id, 'slug' ) === $default ) {
				return $id;
			}
		}

		return 0;
	}

	/**
	 * Create one project together with its Russian version.
	 *
	 * The translation is created whether or not the export carries Russian text:
	 * without it the English text stands in, and the post records that it did.
	 *
	 * @param array $row Parsed row.
	 * @param string $row_status Status to publish under.
	 * @param int $developer Developer post id, zero when unknown.
	 *
	 * @return int Post id of the default language, zero on failure.
	 */
	private function write_row( array $row, string $row_status, int $developer ): int {
		$default = $this->default_language();

		$post_id = $this->write_post( $row['title_en'], $row['description_en'], $row_status, $default, $row['slug'] );
		if ( 0 === $post_id ) {
			return 0;
		}

		update_post_meta( $post_id, self::IMPORT_SLUG_META, $row['slug'] );
		$this->write_fields( $post_id, $row, $developer );

		$translation = $this->write_post(
			'' !== $row['title_ru'] ? $row['title_ru'] : $row['title_en'],
			'' !== $row['description_ru'] ? $row['description_ru'] : $row['description_en'],
			$row_status,
			'ru',
			$row['slug']
		);

		if ( 0 === $translation ) {
			return $post_id;
		}

		$this->write_fields( $translation, $row, $developer );
		$this->flag_translation( $translation, $row );

		$group = array(
			$default => $post_id,
			'ru'     => $translation,
		);

		if ( function_exists( 'pll_save_post_translations' ) ) {
			pll_save_post_translations( $group );
		}

		if ( function_exists( 'core_sync_translation_slugs' ) ) {
			core_sync_translation_slugs( $group );
		}

		$this->assert_shared_slug( $row['slug'], $group );

		return $post_id;
	}

	/**
	 * Check that every language of a project answers to one slug.
	 *
	 * The two are meant to differ only by the /ru/ prefix, which is what makes
	 * the hreflang pair read cleanly. It holds because the language is assigned
	 * before the slug — the other way round wp_unique_post_slug() reads the
	 * sibling as a clash and quietly appends -2. Cheap to verify and worth
	 * verifying: over thousands of rows a silent suffix would go unnoticed until
	 * the URLs stopped pairing up.
	 *
	 * @param string $expected Slug the row asked for.
	 * @param array $group Language slug to post id.
	 *
	 * @return void
	 */
	private function assert_shared_slug( string $expected, array $group ): void {
		$slugs = array();

		foreach ( $group as $language => $post_id ) {
			$post = get_post( (int) $post_id );

			if ( $post ) {
				$slugs[ $language ] = $post->post_name;
			}
		}

		if ( count( array_unique( $slugs ) ) < 2 ) {
			return;
		}

		$pairs = array();
		foreach ( $slugs as $language => $slug ) {
			$pairs[] = $language . '=' . $slug;
		}

		$this->error(
			$expected,
			sprintf( 'the languages ended up on different slugs: %s', implode( ', ', $pairs ) )
		);
	}

	/**
	 * Insert one language of a project.
	 *
	 * Inserted as a draft without a slug so the language can be assigned first:
	 * the other way round, wp_unique_post_slug() reads the sibling translation as
	 * a clash and appends -2 to a slug that was free.
	 *
	 * @param string $title Post title.
	 * @param string $content Post content.
	 * @param string $status Final status.
	 * @param string $language Language slug.
	 * @param string $slug Slug shared by every language of the project.
	 *
	 * @return int Zero on failure.
	 */
	private function write_post( string $title, string $content, string $status, string $language, string $slug ): int {
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'property',
				'post_status'  => 'draft',
				'post_name'    => '',
				'post_title'   => wp_slash( sanitize_text_field( $title ) ),
				'post_content' => wp_slash( wp_kses_post( $content ) ),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			$this->error( $slug, sprintf( '%s: %s', $language, $post_id->get_error_message() ) );

			return 0;
		}

		if ( function_exists( 'pll_set_post_language' ) ) {
			pll_set_post_language( (int) $post_id, $language );
		}

		$updated = wp_update_post(
			array(
				'ID'          => (int) $post_id,
				'post_status' => $status,
				'post_name'   => $slug,
			),
			true
		);

		if ( is_wp_error( $updated ) ) {
			$this->error( $slug, sprintf( '%s: %s', $language, $updated->get_error_message() ) );
		}

		return (int) $post_id;
	}

	/**
	 * Fill the fields the export actually carries, and only those.
	 *
	 * Everything else the group defines — floors, payment plans, gallery — stays
	 * empty rather than being invented.
	 *
	 * @param int $post_id Post to fill.
	 * @param array $row Parsed row.
	 * @param int $developer Developer post id, zero when unknown.
	 *
	 * @return void
	 */
	private function write_fields( int $post_id, array $row, int $developer ): void {
		if ( '' !== $row['latitude'] && '' !== $row['longitude'] ) {
			update_post_meta( $post_id, 'latitude', $row['latitude'] );
			update_post_meta( $post_id, 'longitude', $row['longitude'] );
		}

		if ( '' !== $row['ownership_type'] ) {
			update_post_meta( $post_id, 'ownership_type', sanitize_text_field( $row['ownership_type'] ) );
		}

		// property_type is a checkbox field, so its value is a list even at one item.
		if ( '' !== $row['property_type'] ) {
			update_post_meta( $post_id, 'property_type', array( sanitize_key( $row['property_type'] ) ) );
		}

		$delivery = $this->normalise_delivery_date( $row['delivery_date'] );
		if ( '' !== $delivery ) {
			update_post_meta( $post_id, 'delivery_date', $delivery );
		}

		if ( $developer > 0 ) {
			update_post_meta( $post_id, 'developer_rel', $developer );
		}
	}

	/**
	 * Bring a delivery date into the Ymd form the date picker stores.
	 *
	 * The export mixes three shapes: an ISO date, the word "ready", and the
	 * occasional Ymd already. "ready" carries no date, so it is dropped rather
	 * than turned into one.
	 *
	 * @param string $value Raw value.
	 *
	 * @return string Empty when there is no date to store.
	 */
	private function normalise_delivery_date( string $value ): string {
		if ( '' === $value || 'ready' === strtolower( $value ) ) {
			return '';
		}

		if ( preg_match( '~^\d{8}$~', $value ) ) {
			return $value;
		}

		$timestamp = strtotime( $value );

		return false === $timestamp ? '' : gmdate( 'Ymd', $timestamp );
	}

	/**
	 * Find the developer a name refers to.
	 *
	 * @param string $name Developer name from the export.
	 * @param bool $create Create the post when nothing matches.
	 * @param bool $dry_run Report only.
	 *
	 * @return int Zero when unknown.
	 */
	private function resolve_developer( string $name, bool $create, bool $dry_run ): int {
		if ( '' === $name ) {
			return 0;
		}

		static $cache = array();

		$slug = sanitize_title( $name );
		if ( '' === $slug ) {
			return 0;
		}

		if ( isset( $cache[ $slug ] ) ) {
			return $cache[ $slug ];
		}

		$found = get_posts(
			array(
				'post_type'        => 'developers',
				'post_status'      => array( 'publish', 'draft' ),
				'name'             => $slug,
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'suppress_filters' => true,
				'lang'             => '',
			)
		);

		if ( $found ) {
			$cache[ $slug ] = (int) $found[0];

			return $cache[ $slug ];
		}

		if ( ! $create || $dry_run ) {
			$cache[ $slug ] = 0;

			return 0;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'developers',
				'post_status' => 'draft',
				'post_title'  => wp_slash( sanitize_text_field( $name ) ),
				'post_name'   => $slug,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			$this->error( $slug, sprintf( 'developer: %s', $post_id->get_error_message() ) );
			$cache[ $slug ] = 0;

			return 0;
		}

		if ( function_exists( 'pll_set_post_language' ) ) {
			pll_set_post_language( (int) $post_id, $this->default_language() );
		}

		$cache[ $slug ] = (int) $post_id;

		return $cache[ $slug ];
	}

	/**
	 * Projects the site already has, keyed by the slug they answer to.
	 *
	 * Both the stored slug and the one the title would produce are collected: a
	 * project saved before the transliteration filter existed can carry a
	 * percent-encoded slug while its title still resolves to the clean one.
	 *
	 * The value is the list of posts behind that slug — a project and its
	 * translations share one, and filling a missing translation needs the ids.
	 *
	 * @return array Slug to a list of post ids.
	 */
	private function existing_projects(): array {
		global $wpdb;

		static $known = null;

		if ( null !== $known ) {
			return $known;
		}

		$known = array();

		$rows = $wpdb->get_results(
			"SELECT ID, post_name, post_title
			 FROM {$wpdb->posts}
			 WHERE post_type = 'property'
			   AND post_status IN ( 'publish', 'draft', 'pending', 'future', 'private' )"
		);

		foreach ( $rows as $row ) {
			foreach ( array( $row->post_name, sanitize_title( $row->post_title ) ) as $slug ) {
				if ( '' === $slug ) {
					continue;
				}

				$known[ $slug ][] = (int) $row->ID;
			}
		}

		return $known;
	}

	/**
	 * Whether the row says anything in Russian at all.
	 *
	 * The export repeats the English text in the Russian columns for most rows,
	 * so a filled column is not a translation by itself.
	 *
	 * @param array $row Parsed row.
	 *
	 * @return bool
	 */
	private function has_russian( array $row ): bool {
		foreach ( array( 'title_ru', 'description_ru' ) as $field ) {
			if ( '' === $row[ $field ] ) {
				continue;
			}

			$english = 'title_ru' === $field ? $row['title_en'] : $row['description_en'];

			if ( $row[ $field ] !== $english && preg_match( '~\p{Cyrillic}~u', $row[ $field ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Open the file, stepping over a byte order mark when there is one.
	 *
	 * @return resource|null
	 */
	private function open() {
		if ( ! is_readable( $this->file ) ) {
			$this->error( '', sprintf( 'file not readable: %s', $this->file ) );

			return null;
		}

		$handle = fopen( $this->file, 'r' );
		if ( false === $handle ) {
			$this->error( '', 'file could not be opened' );

			return null;
		}

		$bom = fread( $handle, 3 );
		if ( "\xEF\xBB\xBF" !== $bom ) {
			rewind( $handle );
		}

		return $handle;
	}

	/**
	 * Language every project is created in first.
	 *
	 * @return string
	 */
	private function default_language(): string {
		return function_exists( 'pll_default_language' ) ? (string) pll_default_language( 'slug' ) : 'en';
	}

	/**
	 * Record a problem against a row.
	 *
	 * @param string $id Slug or line number the problem belongs to.
	 * @param string $message What went wrong.
	 *
	 * @return void
	 */
	private function error( string $id, string $message ): void {
		$this->errors[] = array(
			'id'      => $id,
			'message' => $message,
		);
	}
}
