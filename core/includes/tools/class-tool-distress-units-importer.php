<?php
/**
 * Tool for moving units from distressuae.com into this site.
 *
 * @package east-property
 */

namespace Tools;

use WP_CLI;
use WP_Error;
use WP_Term;

/**
 * Creates unit posts from the JSON export produced on distressuae.com.
 *
 * Deliberately file-driven rather than reading the source database directly.
 * The export is a plain JSON file, so the same run can be rehearsed locally,
 * then on stage, then on production, and the exact input can be read by a
 * human before anything is written.
 */
final class Distress_Units_Importer {

	/**
	 * Ties a created post back to the record it came from.
	 *
	 * Value is `<host>#<source id>`, which makes a second run idempotent and,
	 * more importantly, makes the whole batch findable afterwards:
	 *
	 *     wp post list --post_type=unit --meta_key=_core_import_source \
	 *       --meta_compare=LIKE --meta_value=distressuae
	 *
	 *     Загрузить только 2 Юнита и пропустить загрузку медиа на случай есл это стейдж
	 *		ssh root@167.233.173.104 'wp --path=/var/www/stage.eastproperty.com/public tools import-distress-units --limit=2 --skip-media --yes --allow-root'
	 *
	 * That is the rollback handle: everything this tool created can be listed,
	 * unpublished or deleted without touching anything else.
	 */
	private const SOURCE_META = '_core_import_source';

	/**
	 * Same idea for attachments, so a repeated run re-uses the file it already
	 * fetched instead of downloading a second copy into the bucket.
	 */
	private const ATTACHMENT_SOURCE_META = '_core_import_source_attachment';

	/**
	 * Marks a Russian post still carrying English text.
	 *
	 * Without a leading underscore on purpose, matching the property importer:
	 * the flag has to be visible in the editor and queryable at once.
	 */
	public const NEED_TRANSLATE_META = 'need_translate';

	/**
	 * Every imported unit is a distress listing by definition of the task.
	 */
	private const LISTING_TYPE = 'distress';

	/**
	 * Meta keys the export carries. Anything else stays empty rather than
	 * being invented — floor_plans is skipped by decision, broker is set to
	 * the importing author instead of being copied.
	 */
	private const CARRIED_META = array(
		'price',
		'original_price',
		'discount',
		'bedrooms',
		'bathrooms',
		'area_size',
		'unit_type',
		'price_per_square_foot',
		'is_popular',
		'is_premium',
		'boost_score',
		'boost_end_date',
	);

	/**
	 * Path of the export being read.
	 *
	 * @var string
	 */
	private string $file;

	/**
	 * Decoded payload.
	 *
	 * @var array
	 */
	private array $payload = array();

	/**
	 * Problems collected during a run, keyed by source id.
	 *
	 * @var array<string,string[]>
	 */
	private array $errors = array();

	/**
	 * Old url => new url, for the 301 map on distressuae.
	 *
	 * @var array<string,string>
	 */
	private array $redirects = array();

	/**
	 * Slug => project post ids, filled lazily and extended during the run.
	 *
	 * @var array<string,int[]>|null
	 */
	private ?array $projects_cache = null;

	/**
	 * Slug => location term id, filled lazily.
	 *
	 * @var array<string,int>|null
	 */
	private ?array $terms_cache = null;

	/**
	 * ACF field key of the gallery field, discovered once per run.
	 *
	 * @var string|null
	 */
	private ?string $gallery_field_key = null;

	/**
	 * @param string $file Absolute path of the JSON export.
	 */
	public function __construct( string $file ) {
		$this->file = $file;
	}

	/**
	 * Read and validate the export.
	 *
	 * @return array Units as the file lists them.
	 */
	public function parse(): array {
		if ( ! is_readable( $this->file ) ) {
			WP_CLI::error( sprintf( 'Файл не читается: %s', $this->file ) );
		}

		$raw = (string) file_get_contents( $this->file );
		if ( '' === trim( $raw ) ) {
			WP_CLI::error( 'Файл пустой.' );
		}

		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) || ! isset( $decoded['units'] ) || ! is_array( $decoded['units'] ) ) {
			WP_CLI::error( 'Не похоже на выгрузку: нет массива units.' );
		}

		$this->payload = $decoded;

		return $decoded['units'];
	}

	/**
	 * What the run would touch, without touching it.
	 *
	 * @return array
	 */
	public function preview(): array {
		$units = $this->parse();

		$known_projects = $this->existing_projects();
		$new_projects   = array();
		$already        = 0;
		$no_project     = 0;
		$gallery_files  = 0;
		$no_gallery     = 0;
		$unknown_terms  = array();
		$known_terms    = $this->existing_terms();

		foreach ( $units as $unit ) {
			if ( $this->find_imported( (int) ( $unit['source_id'] ?? 0 ) ) ) {
				++$already;
			}

			$frames = count( (array) ( $unit['gallery'] ?? array() ) );

			$gallery_files += $frames;

			if ( 0 === $frames ) {
				++$no_gallery;
			}

			$slug = (string) ( $unit['project']['slug'] ?? '' );
			if ( '' === $slug ) {
				++$no_project;
			} elseif ( ! isset( $known_projects[ $slug ] ) ) {
				$new_projects[ $slug ] = (string) ( $unit['project']['title'] ?? $slug );
			}

			foreach ( (array) ( $unit['locations'] ?? array() ) as $location ) {
				$term_slug = (string) ( $location['slug'] ?? '' );
				if ( '' !== $term_slug && ! isset( $known_terms[ $term_slug ] ) ) {
					$unknown_terms[ $term_slug ] = true;
				}
			}
		}

		return array(
			'source'        => (string) ( $this->payload['source'] ?? '?' ),
			'exported_at'   => (string) ( $this->payload['exported_at'] ?? '?' ),
			'author_login'  => (string) ( $this->payload['author']['login'] ?? '' ),
			'author_name'   => (string) ( $this->payload['author']['display'] ?? '' ),
			'units'         => count( $units ),
			'already'       => $already,
			'gallery_files' => $gallery_files,
			'no_gallery'    => $no_gallery,
			'no_project'    => $no_project,
			'new_projects'  => $new_projects,
			'unknown_terms' => array_keys( $unknown_terms ),
			'warnings'      => (array) ( $this->payload['warnings'] ?? array() ),
		);
	}

	/**
	 * Do the work.
	 *
	 * @param array $args {
	 *     @type bool   $dry_run        Report only.
	 *     @type int    $limit          Stop after this many units, zero for all.
	 *     @type string $status         Status for created units.
	 *     @type string $project_status Status for project stubs.
	 *     @type int    $author         User id to own the units and act as broker.
	 *     @type bool   $skip_media     Do not fetch gallery images.
	 *     @type bool   $with_russian   Create the Russian counterpart.
	 * }
	 *
	 * @return array Report.
	 */
	public function import( array $args = array() ): array {
		$dry_run        = ! empty( $args['dry_run'] );
		$limit          = (int) ( $args['limit'] ?? 0 );
		$status         = (string) ( $args['status'] ?? 'publish' );
		$project_status = (string) ( $args['project_status'] ?? 'publish' );
		$author         = (int) ( $args['author'] ?? 0 );
		$skip_media     = ! empty( $args['skip_media'] );
		$with_russian   = ! empty( $args['with_russian'] );

		$units  = $this->parse();
		$report = array(
			'created'          => 0,
			'created_ru'       => 0,
			'skipped_existing' => 0,
			'projects_created' => 0,
			'media_fetched'    => 0,
			'media_reused'     => 0,
			'terms_assigned'   => 0,
			'failed'           => 0,
		);

		$processed = 0;

		foreach ( $units as $unit ) {
			if ( $limit > 0 && $processed >= $limit ) {
				break;
			}

			++$processed;

			$source_id = (int) ( $unit['source_id'] ?? 0 );
			if ( $source_id <= 0 ) {
				$this->error( 'нет id', 'запись выгрузки без source_id' );
				++$report['failed'];
				continue;
			}

			$existing = $this->find_imported( $source_id );
			if ( $existing ) {
				++$report['skipped_existing'];
				$this->remember_redirect( $unit, $existing );
				continue;
			}

			$project_id = $this->resolve_project( $unit, $project_status, $dry_run, $report );

			if ( $dry_run ) {
				++$report['created'];
				if ( $with_russian ) {
					++$report['created_ru'];
				}
				continue;
			}

			$unit_id = $this->write_unit( $unit, $status, $author, $this->default_language() );
			if ( ! $unit_id ) {
				++$report['failed'];
				continue;
			}

			++$report['created'];

			$this->write_meta( $unit_id, $unit, $project_id, $author );
			$report['terms_assigned'] += $this->assign_terms( $unit_id, $unit );

			$gallery_ids = array();

			if ( ! $skip_media && ! empty( $unit['gallery'] ) ) {
				$gallery_ids = $this->attach_gallery( $unit_id, (array) $unit['gallery'], $report );
			}

			if ( $with_russian ) {
				$ru_id = $this->write_russian(
					$unit,
					$unit_id,
					$status,
					$author,
					$project_id,
					$gallery_ids,
					$report
				);

				if ( $ru_id ) {
					++$report['created_ru'];
				}
			}

			$this->remember_redirect( $unit, $unit_id );
		}

		$report['redirects'] = $this->redirects;

		return $report;
	}

	/**
	 * Problems collected so far.
	 *
	 * @return array<string,string[]>
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Old url => new url for every unit the run saw.
	 *
	 * @return array<string,string>
	 */
	public function get_redirects(): array {
		return $this->redirects;
	}

	/**
	 * Find the post a previous run created for this source record.
	 *
	 * @param int $source_id Id on the source site.
	 *
	 * @return int Post id, zero when absent.
	 */
	private function find_imported( int $source_id ): int {
		if ( $source_id <= 0 ) {
			return 0;
		}

		global $wpdb;

		$found = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta}
				 WHERE meta_key = %s AND meta_value = %s
				 LIMIT 1",
				self::SOURCE_META,
				$this->source_key( $source_id )
			)
		);

		return (int) $found;
	}

	/**
	 * Stable identity of a source record.
	 *
	 * @param int $source_id Id on the source site.
	 *
	 * @return string
	 */
	private function source_key( int $source_id ): string {
		$host = (string) wp_parse_url( (string) ( $this->payload['source'] ?? '' ), PHP_URL_HOST );

		return ( '' !== $host ? $host : 'distressuae.com' ) . '#' . $source_id;
	}

	/**
	 * Project the unit belongs to, creating a stub when it is missing.
	 *
	 * A unit's permalink carries its project slug, and a unit without a project
	 * falls back to the shorter /property/%unit%/ with no building on the page.
	 *
	 * The stub has to be published, counter-intuitive as that is for an empty
	 * page: Unit::resolve_property_id() accepts a project only in `publish`, so
	 * under a draft stub the unit counts as having no project at all. Measured,
	 * not assumed — a draft stub was tried first and the unit lost its project
	 * segment. Back then that address answered 404 as well; it renders now, but
	 * the unit still shows up stripped of its building.
	 *
	 * Thin content is handled the right way instead, with a robots meta: the
	 * stub is excluded from the index and the sitemap until someone fills it
	 * in, while the unit under it stays reachable.
	 *
	 * @param array  $unit           Export record.
	 * @param string $project_status Status for a created stub.
	 * @param bool   $dry_run        Report only.
	 * @param array  $report         Counters, by reference.
	 *
	 * @return int Project post id, zero when unknown.
	 */
	private function resolve_project( array $unit, string $project_status, bool $dry_run, array &$report ): int {
		$slug = (string) ( $unit['project']['slug'] ?? '' );
		if ( '' === $slug ) {
			$this->error( (string) $unit['source_id'], 'юнит без проекта — привязка пропущена' );

			return 0;
		}

		$known = $this->existing_projects();
		if ( isset( $known[ $slug ] ) ) {
			return $this->prefer_language( $known[ $slug ], $this->default_language() );
		}

		if ( $dry_run ) {
			return 0;
		}

		$title      = (string) ( $unit['project']['title'] ?? $slug );
		$content    = (string) ( $unit['project']['content'] ?? '' );
		$project_id = $this->write_post( 'property', $title, $content, '', $project_status, $this->default_language(), $slug );

		if ( ! $project_id ) {
			return 0;
		}

		update_post_meta( $project_id, self::SOURCE_META, $this->source_key( (int) ( $unit['project']['source_id'] ?? 0 ) ) );

		// Заготовка пустая, поэтому закрываем её от индексации до наполнения.
		// Флаг читают core_noindex_import_stubs() и
		// core_exclude_import_stubs_from_sitemap() в SEO-слое темы: они
		// работают на выводе и не зависят от кэша indexables Yoast, тогда как
		// правка _yoast_wpseo_meta-robots-noindex во время импорта эффекта не
		// даёт до перестройки индекса — проверено, страница отдавала
		// index, follow при любом значении.
		//
		// Снять после наполнения: wp post meta delete <id> _core_import_stub
		update_post_meta( $project_id, '_core_import_stub', '1' );

		++$report['projects_created'];

		// Кэш соответствий держится статически, поэтому новый проект надо
		// в него положить сразу: следующий юнит того же проекта не должен
		// создать вторую копию.
		$this->remember_project( $slug, $project_id );

		return $project_id;
	}

	/**
	 * Create the unit itself.
	 *
	 * The language is set between the insert and the final update on purpose:
	 * Polylang scopes slug uniqueness per language, so assigning the language
	 * after the slug makes wp_unique_post_slug() append "-2" to a slug that was
	 * in fact free.
	 *
	 * @param array  $unit     Export record.
	 * @param string $status   Final status.
	 * @param int    $author   Owner.
	 * @param string $language Language slug.
	 *
	 * @return int Post id, zero on failure.
	 */
	private function write_unit( array $unit, string $status, int $author, string $language ): int {
		return $this->write_post(
			'unit',
			(string) ( $unit['title'] ?? '' ),
			(string) ( $unit['content'] ?? '' ),
			(string) ( $unit['excerpt'] ?? '' ),
			$status,
			$language,
			(string) ( $unit['slug'] ?? '' ),
			$author
		);
	}

	/**
	 * Insert a post, assign its language, then give it its status and slug.
	 *
	 * @param string $post_type Post type.
	 * @param string $title     Title.
	 * @param string $content   Content.
	 * @param string $excerpt   Excerpt.
	 * @param string $status    Final status.
	 * @param string $language  Language slug.
	 * @param string $slug      Desired slug.
	 * @param int    $author    Owner, zero to leave the default.
	 *
	 * @return int Post id, zero on failure.
	 */
	private function write_post(
		string $post_type,
		string $title,
		string $content,
		string $excerpt,
		string $status,
		string $language,
		string $slug,
		int $author = 0
	): int {
		$data = array(
			'post_type'    => $post_type,
			'post_status'  => 'draft',
			'post_name'    => '',
			'post_title'   => wp_slash( sanitize_text_field( $title ) ),
			'post_content' => wp_slash( wp_kses_post( $content ) ),
			'post_excerpt' => wp_slash( sanitize_textarea_field( $excerpt ) ),
		);

		if ( $author > 0 ) {
			$data['post_author'] = $author;
		}

		$post_id = wp_insert_post( $data, true );

		if ( is_wp_error( $post_id ) ) {
			$this->error( $slug, sprintf( '%s: %s', $language, $post_id->get_error_message() ) );

			return 0;
		}

		WP_CLI::log( sprintf( 'Создан %s #%d "%s"', $post_type, $post_id, $title ) );

		if ( function_exists( 'pll_set_post_language' ) && '' !== $language ) {
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
	 * Fill the unit's fields.
	 *
	 * listing_type is forced rather than copied: the whole point of the move is
	 * that these listings are distress deals on this site.
	 *
	 * @param int   $unit_id    Unit.
	 * @param array $unit       Export record.
	 * @param int   $project_id Project, zero when unknown.
	 * @param int   $author     User acting as broker.
	 *
	 * @return void
	 */
	private function write_meta( int $unit_id, array $unit, int $project_id, int $author ): void {
		$meta = (array) ( $unit['meta'] ?? array() );

		foreach ( self::CARRIED_META as $key ) {
			if ( isset( $meta[ $key ] ) && '' !== $meta[ $key ] ) {
				update_post_meta( $unit_id, $key, $meta[ $key ] );
			}
		}

		update_post_meta( $unit_id, 'listing_type', self::LISTING_TYPE );

		if ( $project_id > 0 ) {
			update_post_meta( $unit_id, 'property', $project_id );
		}

		if ( $author > 0 ) {
			update_post_meta( $unit_id, 'broker', $author );
		}

		update_post_meta( $unit_id, self::SOURCE_META, $this->source_key( (int) $unit['source_id'] ) );
		update_post_meta( $unit_id, '_core_import_source_url', esc_url_raw( (string) ( $unit['source_url'] ?? '' ) ) );
	}

	/**
	 * Attach the location terms the export lists, matching by slug then name.
	 *
	 * Missing terms are reported rather than created: the taxonomy on this site
	 * is curated, and inventing a district from a foreign slug would quietly
	 * pollute it.
	 *
	 * @param int   $unit_id Unit.
	 * @param array $unit    Export record.
	 *
	 * @return int Number of terms assigned.
	 */
	private function assign_terms( int $unit_id, array $unit ): int {
		$known = $this->existing_terms();
		$ids   = array();

		foreach ( (array) ( $unit['locations'] ?? array() ) as $location ) {
			$slug = (string) ( $location['slug'] ?? '' );
			$name = (string) ( $location['name'] ?? '' );

			if ( '' !== $slug && isset( $known[ $slug ] ) ) {
				$ids[] = $known[ $slug ];
				continue;
			}

			$by_name = '' !== $name ? get_term_by( 'name', $name, 'location' ) : false;
			if ( $by_name instanceof WP_Term ) {
				$ids[] = (int) $by_name->term_id;
				continue;
			}

			$this->error( (string) $unit['source_id'], sprintf( 'локация не найдена: %s (%s)', $slug, $name ) );
		}

		if ( ! $ids ) {
			return 0;
		}

		wp_set_post_terms( $unit_id, array_values( array_unique( $ids ) ), 'location' );

		return count( array_unique( $ids ) );
	}

	/**
	 * Fetch the gallery and store it the way the ACF field expects.
	 *
	 * The field holds an ordered array of attachment ids, and the order matters
	 * — the export preserves it, so the import must not shuffle it. Written with
	 * plain update_post_meta, matching how the property importer writes its ACF
	 * fields on this site.
	 *
	 * A frame repeated across units costs nothing extra: fetch_attachment()
	 * recognises it by its source id and re-uses the file already downloaded.
	 *
	 * @param int   $post_id Post to fill.
	 * @param array $gallery Gallery records from the export.
	 * @param array $report  Counters, by reference.
	 *
	 * @return int[] Attachment ids, in order.
	 */
	private function attach_gallery( int $post_id, array $gallery, array &$report ): array {
		$ids = array();

		foreach ( $gallery as $frame ) {
			if ( ! is_array( $frame ) ) {
				continue;
			}

			$attachment_id = $this->fetch_attachment( $frame, $post_id, $report );

			if ( $attachment_id > 0 && ! in_array( $attachment_id, $ids, true ) ) {
				$ids[] = $attachment_id;
			}
		}

		$this->write_gallery_meta( $post_id, $ids );

		return $ids;
	}

	/**
	 * Give a post a gallery that was already fetched for its counterpart.
	 *
	 * Used for the Russian version: the files are the same, so only the meta
	 * needs writing and nothing is downloaded a second time.
	 *
	 * @param int   $post_id Post to fill.
	 * @param int[] $ids     Attachment ids, in order.
	 *
	 * @return void
	 */
	private function mirror_gallery( int $post_id, array $ids ): void {
		$this->write_gallery_meta( $post_id, $ids );
	}

	/**
	 * Store the gallery exactly the way ACF stores it.
	 *
	 * Two details matter, and both were found by comparing a record that works
	 * with one this importer had written:
	 *
	 *  - the ids are **strings**, not integers, in the serialised array;
	 *  - a companion `_gallery` meta holds the ACF field key.
	 *
	 * Without the companion meta ACF does not recognise the field, get_field()
	 * hands back the raw value, the template finds no ['sizes'] and renders
	 * nothing — only the featured image showed up, one frame instead of three.
	 *
	 * The field key is read from live data rather than hard-coded, so it stays
	 * correct if the field group is ever rebuilt.
	 *
	 * @param int   $post_id Post to fill.
	 * @param int[] $ids     Attachment ids, in order.
	 *
	 * @return void
	 */
	private function write_gallery_meta( int $post_id, array $ids ): void {
		if ( ! $ids ) {
			return;
		}

		update_post_meta( $post_id, 'gallery', array_map( 'strval', $ids ) );

		$key = $this->gallery_field_key();

		if ( '' !== $key ) {
			update_post_meta( $post_id, '_gallery', $key );

			return;
		}

		$this->error(
			(string) $post_id,
			'не найден ключ поля ACF для gallery: галерея записана, но может не отобразиться'
		);
	}

	/**
	 * ACF field key of the gallery field, discovered from existing records.
	 *
	 * @return string Key, empty string when nothing on the site uses the field.
	 */
	private function gallery_field_key(): string {
		if ( null !== $this->gallery_field_key ) {
			return $this->gallery_field_key;
		}

		global $wpdb;

		$key = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->postmeta}
				 WHERE meta_key = %s AND meta_value LIKE %s
				 LIMIT 1",
				'_gallery',
				$wpdb->esc_like( 'field_' ) . '%'
			)
		);

		$this->gallery_field_key = (string) $key;

		return $this->gallery_field_key;
	}

	/**
	 * Download one file, or re-use the copy a previous run already fetched.
	 *
	 * Goes through media_sideload_image so the whole normal pipeline runs: the
	 * S3 offload picks the file up, and every registered size — including the
	 * theme's own product-thumb, featured-card and unit-card — is generated.
	 * A missing "thumbnail" size makes the card render empty, so the sizes are
	 * not optional.
	 *
	 * @param array $record  Attachment record from the export.
	 * @param int   $post_id Post to attach to.
	 * @param array $report  Counters, by reference.
	 *
	 * @return int Attachment id, zero on failure.
	 */
	private function fetch_attachment( array $record, int $post_id, array &$report ): int {
		$url       = (string) ( $record['url'] ?? '' );
		$source_id = (int) ( $record['source_id'] ?? 0 );

		if ( '' === $url ) {
			return 0;
		}

		$existing = $this->find_attachment( $source_id );
		if ( $existing ) {
			++$report['media_reused'];

			return $existing;
		}

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = media_sideload_image( $url, $post_id, (string) ( $record['descr'] ?? '' ), 'id' );

		if ( $attachment_id instanceof WP_Error ) {
			$this->error(
				(string) $post_id,
				sprintf( 'файл не скачался (%s): %s', $url, $attachment_id->get_error_message() )
			);

			return 0;
		}

		$attachment_id = (int) $attachment_id;

		wp_update_post(
			array(
				'ID'           => $attachment_id,
				'post_title'   => wp_slash( sanitize_text_field( (string) ( $record['title'] ?? '' ) ) ),
				'post_excerpt' => wp_slash( sanitize_textarea_field( (string) ( $record['caption'] ?? '' ) ) ),
			)
		);

		if ( ! empty( $record['alt'] ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( (string) $record['alt'] ) );
		}

		if ( $source_id > 0 ) {
			update_post_meta( $attachment_id, self::ATTACHMENT_SOURCE_META, $this->source_key( $source_id ) );
		}

		++$report['media_fetched'];

		return $attachment_id;
	}

	/**
	 * Attachment a previous run already fetched for this source file.
	 *
	 * @param int $source_id Attachment id on the source site.
	 *
	 * @return int
	 */
	private function find_attachment( int $source_id ): int {
		if ( $source_id <= 0 ) {
			return 0;
		}

		global $wpdb;

		$found = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta}
				 WHERE meta_key = %s AND meta_value = %s
				 LIMIT 1",
				self::ATTACHMENT_SOURCE_META,
				$this->source_key( $source_id )
			)
		);

		return (int) $found;
	}

	/**
	 * Create the Russian counterpart, carrying the English text.
	 *
	 * Matches what the property importer does: a Russian post always exists, is
	 * flagged with need_translate, and shares the English slug. The gallery is
	 * re-used rather than downloaded twice.
	 *
	 * @param array $unit       Export record.
	 * @param int   $source_id  English post just created.
	 * @param string $status    Final status.
	 * @param int   $author     Owner.
	 * @param int   $project_id  Project.
	 * @param int[] $gallery_ids Gallery already fetched, in order.
	 * @param array $report      Counters, by reference.
	 *
	 * @return int Russian post id, zero on failure.
	 */
	private function write_russian(
		array $unit,
		int $source_id,
		string $status,
		int $author,
		int $project_id,
		array $gallery_ids,
		array &$report
	): int {
		if ( ! function_exists( 'pll_save_post_translations' ) ) {
			return 0;
		}

		$slug  = get_post_field( 'post_name', $source_id );
		$ru_id = $this->write_post(
			'unit',
			(string) ( $unit['title'] ?? '' ),
			(string) ( $unit['content'] ?? '' ),
			(string) ( $unit['excerpt'] ?? '' ),
			$status,
			'ru',
			(string) $slug,
			$author
		);

		if ( ! $ru_id ) {
			return 0;
		}

		$this->write_meta( $ru_id, $unit, $project_id, $author );
		$this->assign_terms( $ru_id, $unit );

		// Файлы те же, поэтому только мета — ни одной повторной закачки.
		$this->mirror_gallery( $ru_id, $gallery_ids );

		// Русский текст здесь английский, и это надо пометить, иначе бэклог
		// перевода нечем будет найти.
		update_post_meta( $ru_id, self::NEED_TRANSLATE_META, 1 );

		pll_save_post_translations(
			array(
				$this->default_language() => $source_id,
				'ru'                      => $ru_id,
			)
		);

		$ru_slug = get_post_field( 'post_name', $ru_id );
		if ( $ru_slug !== $slug ) {
			$this->error(
				(string) $unit['source_id'],
				sprintf( 'slug разошёлся: en=%s ru=%s', $slug, $ru_slug )
			);
		}

		return $ru_id;
	}

	/**
	 * Record the redirect this move makes necessary.
	 *
	 * @param array $unit    Export record.
	 * @param int   $post_id Post on this site.
	 *
	 * @return void
	 */
	private function remember_redirect( array $unit, int $post_id ): void {
		$old = (string) ( $unit['source_url'] ?? '' );
		if ( '' === $old ) {
			return;
		}

		$path = (string) wp_parse_url( $old, PHP_URL_PATH );
		$new  = get_permalink( $post_id );

		if ( '' !== $path && $new ) {
			$this->redirects[ $path ] = $new;
		}
	}

	/**
	 * Slug => post ids for every project on this site.
	 *
	 * Cached on the instance rather than statically, so a project created
	 * mid-run can be added to the lookup: two units of the same new project
	 * must land on one stub, not on two.
	 *
	 * @return array<string,int[]>
	 */
	private function existing_projects(): array {
		if ( null !== $this->projects_cache ) {
			return $this->projects_cache;
		}

		global $wpdb;

		$this->projects_cache = array();

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

				$this->projects_cache[ $slug ][] = (int) $row->ID;
			}
		}

		return $this->projects_cache;
	}

	/**
	 * Add a freshly created project to the lookup.
	 *
	 * @param string $slug       Project slug.
	 * @param int    $project_id Post id.
	 *
	 * @return void
	 */
	private function remember_project( string $slug, int $project_id ): void {
		$this->existing_projects();

		$this->projects_cache[ $slug ][] = $project_id;
	}

	/**
	 * Slug => term id for the location taxonomy.
	 *
	 * @return array<string,int>
	 */
	private function existing_terms(): array {
		if ( null !== $this->terms_cache ) {
			return $this->terms_cache;
		}

		$known = array();

		$terms = get_terms(
			array(
				'taxonomy'   => 'location',
				'hide_empty' => false,
			)
		);

		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( $term instanceof WP_Term ) {
					$known[ $term->slug ] = (int) $term->term_id;
				}
			}
		}

		$this->terms_cache = $known;

		return $this->terms_cache;
	}

	/**
	 * Of several posts sharing a slug, prefer the one in the given language.
	 *
	 * @param int[]  $ids      Candidates.
	 * @param string $language Wanted language.
	 *
	 * @return int
	 */
	private function prefer_language( array $ids, string $language ): int {
		if ( ! $ids ) {
			return 0;
		}

		if ( function_exists( 'pll_get_post_language' ) ) {
			foreach ( $ids as $id ) {
				if ( (string) pll_get_post_language( (int) $id, 'slug' ) === $language ) {
					return (int) $id;
				}
			}
		}

		return (int) reset( $ids );
	}

	/**
	 * Default language of this site.
	 *
	 * @return string
	 */
	private function default_language(): string {
		return function_exists( 'pll_default_language' ) ? (string) pll_default_language( 'slug' ) : 'en';
	}

	/**
	 * Record a problem.
	 *
	 * @param string $id      Source id or slug it belongs to.
	 * @param string $message What went wrong.
	 *
	 * @return void
	 */
	private function error( string $id, string $message ): void {
		$this->errors[ $id ][] = $message;
	}
}
