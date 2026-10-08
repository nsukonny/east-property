<?php
/**
 * Copies units and projects into a language that has none yet.
 *
 * @package east-property
 */

namespace Tools;

use WP_Error;
use WP_Post;

/**
 * Creates the missing translation of a post, carrying the source text.
 *
 * The copy is flagged need_translate, so `wp tools translate-units` picks it up
 * afterwards and fills it through DeepL.
 */
final class Language_Cloner {

	/**
	 * Post types a language can be opened for.
	 */
	public const POST_TYPES = array( 'unit', 'property' );

	/**
	 * How many ids are read from the database at a time.
	 */
	private const BATCH = 200;

	/**
	 * Types a copy may be made of, dependencies of the walked ones included.
	 */
	private const CLONE_TYPES = array( 'unit', 'property', 'developers' );

	/**
	 * SCF post_object fields pointing at another post, by owning type.
	 */
	private const LINKED_META = array(
		'unit'     => array( 'property' => 'property' ),
		'property' => array( 'developer_rel' => 'developers' ),
	);

	/**
	 * Meta WordPress and the editor own, which a copy must not carry.
	 */
	private const SKIP_META = array(
		'_edit_lock',
		'_edit_last',
		'_wp_old_slug',
		'_wp_desired_post_slug',
		'_wp_trash_meta_status',
		'_wp_trash_meta_time',
	);

	/**
	 * Taxonomies Polylang owns, which must never be copied by hand.
	 */
	private const SKIP_TAXONOMIES = array( 'language', 'post_translations' );

	/**
	 * Language slug for a locale, a slug, or a name.
	 *
	 * @param string $wanted Locale such as de_DE, or a slug such as de.
	 *
	 * @return string Empty when Polylang does not know it.
	 */
	public static function language( string $wanted ): string {
		if ( '' === trim( $wanted ) || ! function_exists( 'pll_languages_list' ) ) {
			return '';
		}

		$wanted  = strtolower( trim( $wanted ) );
		$slugs   = array_map( 'strval', (array) pll_languages_list() );
		$locales = array_map( 'strval', (array) pll_languages_list( array( 'fields' => 'locale' ) ) );

		foreach ( $slugs as $index => $slug ) {
			$locale = $locales[ $index ] ?? '';

			if ( $wanted === strtolower( $slug ) || $wanted === strtolower( $locale ) ) {
				return $slug;
			}
		}

		return '';
	}

	/**
	 * Locales Polylang knows, for an error message worth reading.
	 *
	 * @return string[]
	 */
	public static function known_languages(): array {
		if ( ! function_exists( 'pll_languages_list' ) ) {
			return array();
		}

		$slugs   = array_map( 'strval', (array) pll_languages_list() );
		$locales = array_map( 'strval', (array) pll_languages_list( array( 'fields' => 'locale' ) ) );
		$known   = array();

		foreach ( $slugs as $index => $slug ) {
			$known[] = sprintf( '%s (%s)', $slug, $locales[ $index ] ?? '?' );
		}

		return $known;
	}

	/**
	 * Default language of the site.
	 *
	 * @return string
	 */
	public static function default_language(): string {
		return function_exists( 'pll_default_language' ) ? (string) pll_default_language( 'slug' ) : 'en';
	}

	/**
	 * Posts in the default language that have no counterpart in a language yet.
	 *
	 * Only the default language is taken as a source, so every translation group
	 * gains exactly one copy however many languages it already has.
	 *
	 * @param string   $language   Target language slug.
	 * @param string[] $post_types Types to walk.
	 * @param string[] $statuses   Post statuses to walk.
	 * @param int      $limit      Hard ceiling on the returned ids.
	 * @param int      $offset     Candidates to pass over first.
	 *
	 * @return int[]
	 */
	public function find(
		string $language,
		array $post_types,
		array $statuses,
		int $limit,
		int $offset = 0
	): array {
		$post_types = array_values( array_intersect( $post_types ?: self::POST_TYPES, self::POST_TYPES ) );

		if ( empty( $post_types ) || '' === $language ) {
			return array();
		}

		$default = self::default_language();
		$found   = array();
		$passed  = 0;
		$page    = 0;

		while ( count( $found ) < $limit ) {
			$ids = get_posts(
				array(
					'post_type'        => $post_types,
					'post_status'      => $statuses,
					'posts_per_page'   => self::BATCH,
					'offset'           => $page * self::BATCH,
					'fields'           => 'ids',
					'orderby'          => 'ID',
					'order'            => 'ASC',
					'lang'             => '',
					'suppress_filters' => true,
					'no_found_rows'    => true,
				)
			);

			if ( empty( $ids ) ) {
				break;
			}

			foreach ( $ids as $id ) {
				$id = (int) $id;

				if ( $default !== (string) pll_get_post_language( $id, 'slug' ) ) {
					continue;
				}

				if ( pll_get_post( $id, $language ) ) {
					continue;
				}

				if ( $passed < $offset ) {
					++ $passed;

					continue;
				}

				$found[] = $id;

				if ( count( $found ) >= $limit ) {
					break;
				}
			}

			++ $page;
		}

		return $found;
	}

	/**
	 * What copying one post would mean, without writing anything.
	 *
	 * @param int    $post_id
	 * @param string $language Target language slug.
	 *
	 * @return array|WP_Error Keys id, type, title, status, language, group.
	 */
	public function plan( int $post_id, string $language ) {
		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return new WP_Error( 'clone_missing', sprintf( 'записи #%d не существует', $post_id ) );
		}

		if ( ! in_array( $post->post_type, self::CLONE_TYPES, true ) ) {
			return new WP_Error(
				'clone_type',
				sprintf( '#%d — это %s, копии делаются для %s', $post_id, $post->post_type,
					implode( ' и ', self::CLONE_TYPES ) )
			);
		}

		if ( '' === self::language( $language ) ) {
			return new WP_Error(
				'clone_language',
				sprintf( 'Polylang не знает язык «%s», есть: %s', $language,
					implode( ', ', self::known_languages() ) )
			);
		}

		$own = (string) pll_get_post_language( $post_id, 'slug' );

		if ( '' === $own ) {
			return new WP_Error( 'clone_no_language', sprintf( 'у #%d нет языка Polylang', $post_id ) );
		}

		if ( $own === $language ) {
			return new WP_Error(
				'clone_same_language',
				sprintf( '#%d уже на языке %s', $post_id, $language )
			);
		}

		$existing = pll_get_post( $post_id, $language );

		if ( $existing ) {
			return new WP_Error(
				'clone_exists',
				sprintf( 'у #%d уже есть версия на %s — #%d', $post_id, $language, (int) $existing )
			);
		}

		return array(
			'id'       => $post_id,
			'type'     => (string) $post->post_type,
			'title'    => (string) $post->post_title,
			'status'   => (string) $post->post_status,
			'language' => $language,
			'group'    => pll_get_post_translations( $post_id ),
			'links'    => self::links_plan( $post_id, $language ),
		);
	}

	/**
	 * Posts a post links to through its SCF post_object fields.
	 *
	 * Read from the meta rather than through get_field(), which goes to
	 * WP_Query and comes back empty whenever Polylang narrows the query to a
	 * language the linked post is not in.
	 *
	 * @param int $post_id
	 *
	 * @return int[] Meta key to linked post id; empty when nothing is linked.
	 */
	private static function links_of( int $post_id ): array {
		$fields = self::LINKED_META[ (string) get_post_type( $post_id ) ] ?? array();
		$links  = array();

		foreach ( $fields as $meta_key => $post_type ) {
			$linked = (int) get_post_meta( $post_id, $meta_key, true );

			if ( $post_type === get_post_type( $linked ) ) {
				$links[ $meta_key ] = $linked;
			}
		}

		return $links;
	}

	/**
	 * What every link of a post resolves to in a language, without writing.
	 *
	 * A link the language has none of brings its own links along, so a dry run
	 * names the whole chain a copy would create.
	 *
	 * @param int    $post_id
	 * @param string $language Target language slug.
	 * @param int[]  $seen     Ids already described, guarding against a cycle.
	 *
	 * @return array[] Keys meta, type, source, translated.
	 */
	private static function links_plan( int $post_id, string $language, array $seen = array() ): array {
		$plan   = array();
		$seen[] = $post_id;

		foreach ( self::links_of( $post_id ) as $meta_key => $linked ) {
			$translated = (int) pll_get_post( $linked, $language );

			$plan[] = array(
				'owner'      => $post_id,
				'meta'       => $meta_key,
				'type'       => (string) get_post_type( $linked ),
				'source'     => $linked,
				'translated' => $translated,
			);

			if ( 0 === $translated && ! in_array( $linked, $seen, true ) ) {
				$plan = array_merge( $plan, self::links_plan( $linked, $language, $seen ) );
			}
		}

		return $plan;
	}

	/**
	 * Ids a copy must point at, cloning what the language has none of.
	 *
	 * @param int    $post_id  Post being copied.
	 * @param string $language Target language slug.
	 * @param string $status   Status to give a post created here.
	 *
	 * @return array|WP_Error Meta key to id in the target language.
	 */
	private function links_for_copy( int $post_id, string $language, string $status ) {
		$links = array();

		foreach ( self::links_of( $post_id ) as $meta_key => $linked ) {
			$translated = (int) pll_get_post( $linked, $language );

			if ( 0 === $translated ) {
				$translated = $this->clone_post( $linked, $language, $status );

				if ( is_wp_error( $translated ) ) {
					return $translated;
				}
			}

			$links[ $meta_key ] = (int) $translated;
		}

		return $links;
	}

	/**
	 * Create the copy of a post in a language.
	 *
	 * Polylang is kept out of the way for the whole write: its meta
	 * synchronisation would otherwise push the copy's values back over the
	 * source and spread need_translate across the whole group. The slug is set
	 * only after the group is linked, because wp_unique_post_slug() would
	 * otherwise read the source as a clash and append -2. Links to other posts
	 * are repointed at the target language, cloning what it has none of.
	 *
	 * @param int    $post_id
	 * @param string $language Target language slug.
	 * @param string $status   Status to give the copy; the source status when empty.
	 *
	 * @return int|WP_Error Id of the copy.
	 */
	public function clone_post( int $post_id, string $language, string $status = '' ) {
		$plan = $this->plan( $post_id, $language );

		if ( is_wp_error( $plan ) ) {
			return $plan;
		}

		$links = $this->links_for_copy( $post_id, $language, $status );

		if ( is_wp_error( $links ) ) {
			return $links;
		}

		global $wp_filter;

		$source  = get_post( $post_id );
		$no_sync = static function (): array {
			return array();
		};

		add_filter( 'pll_copy_post_metas', $no_sync, 999 );

		$pll_save_post = $wp_filter['pll_save_post'] ?? null;
		unset( $wp_filter['pll_save_post'] );

		kses_remove_filters();

		$copy_id = wp_insert_post(
			wp_slash(
				array(
					'post_type'      => $source->post_type,
					'post_status'    => 'draft',
					'post_name'      => '',
					'post_title'     => $source->post_title,
					'post_content'   => $source->post_content,
					'post_excerpt'   => $source->post_excerpt,
					'post_author'    => $source->post_author,
					'post_date'      => $source->post_date,
					'post_date_gmt'  => $source->post_date_gmt,
					'menu_order'     => $source->menu_order,
					'comment_status' => $source->comment_status,
					'ping_status'    => $source->ping_status,
				)
			),
			true
		);

		if ( ! is_wp_error( $copy_id ) ) {
			$copy_id = (int) $copy_id;

			pll_set_post_language( $copy_id, $language );

			self::copy_metas( $post_id, $copy_id );
			self::copy_terms( $post_id, $copy_id );

			foreach ( $links as $meta_key => $linked ) {
				update_post_meta( $copy_id, $meta_key, $linked );
			}

			update_post_meta( $copy_id, Distress_Units_Importer::NEED_TRANSLATE_META, 1 );

			$group              = $plan['group'];
			$group[ $language ] = $copy_id;

			pll_save_post_translations( $group );

			if ( function_exists( 'core_sync_translation_slugs' ) ) {
				core_sync_translation_slugs( $group );
			}

			wp_update_post(
				array(
					'ID'          => $copy_id,
					'post_status' => '' !== $status ? $status : $source->post_status,
					'edit_date'   => true,
				)
			);

			clean_post_cache( $copy_id );
			clean_post_cache( $post_id );
		}

		kses_init();

		if ( null !== $pll_save_post ) {
			$wp_filter['pll_save_post'] = $pll_save_post;
		}

		remove_filter( 'pll_copy_post_metas', $no_sync, 999 );

		return $copy_id;
	}

	/**
	 * Copy every meta of a post, apart from the ones a copy must not inherit.
	 *
	 * @param int $from
	 * @param int $to
	 *
	 * @return void
	 */
	private static function copy_metas( int $from, int $to ): void {
		$metas = get_post_meta( $from );

		if ( ! is_array( $metas ) ) {
			return;
		}

		foreach ( $metas as $key => $values ) {
			$key = (string) $key;

			if ( in_array( $key, self::SKIP_META, true )
				|| Distress_Units_Importer::NEED_TRANSLATE_META === $key ) {
				continue;
			}

			delete_post_meta( $to, $key );

			foreach ( (array) $values as $value ) {
				add_post_meta( $to, $key, wp_slash( maybe_unserialize( $value ) ) );
			}
		}
	}

	/**
	 * Copy the terms of every taxonomy the type has, Polylang's own aside.
	 *
	 * @param int $from
	 * @param int $to
	 *
	 * @return void
	 */
	private static function copy_terms( int $from, int $to ): void {
		$post = get_post( $from );

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		foreach ( get_object_taxonomies( $post->post_type ) as $taxonomy ) {
			if ( in_array( $taxonomy, self::SKIP_TAXONOMIES, true ) ) {
				continue;
			}

			$terms = wp_get_object_terms( $from, $taxonomy, array( 'fields' => 'ids' ) );

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}

			wp_set_object_terms( $to, array_map( 'intval', $terms ), $taxonomy );
		}
	}
}
