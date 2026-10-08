<?php
/**
 * Machine translation of units and projects flagged for it.
 *
 * @package east-property
 */

namespace Tools;

use WP_Error;
use WP_Post;

/**
 * Fills a flagged post with DeepL output and clears the flag.
 */
final class Unit_Translator {

	/**
	 * Post types the flag is used on.
	 */
	public const POST_TYPES = array( 'unit', 'property' );

	/**
	 * Post fields carrying the copy, in the order they are reported.
	 *
	 * post_content is the description and post_excerpt the short one; neither
	 * lives in a custom field.
	 */
	public const FIELDS = array( 'post_title', 'post_content', 'post_excerpt' );

	/**
	 * Fields whose value is markup and needs DeepL tag handling.
	 */
	private const HTML_FIELDS = array( 'post_content' );

	/**
	 * Amenity list of a project, one per line.
	 */
	private const AMENITIES_META = 'amenities';

	/**
	 * Payment plans repeater of a project.
	 */
	private const PLANS_META = 'payment_plans';

	/**
	 * How many ids are read from the database at a time.
	 */
	private const BATCH = 100;

	/**
	 * @var DeepL_Client
	 */
	private DeepL_Client $client;

	/**
	 * @param DeepL_Client|null $client Injected in tests.
	 */
	public function __construct( ?DeepL_Client $client = null ) {
		$this->client = $client ?? new DeepL_Client();
	}

	/**
	 * Whether the API key is in place.
	 *
	 * @return bool
	 */
	public function ready(): bool {
		return $this->client->has_key();
	}

	/**
	 * Language slug Polylang holds for a post.
	 *
	 * @param int $post_id
	 *
	 * @return string Empty when Polylang is absent or the post has no language.
	 */
	public static function language_of( int $post_id ): string {
		return function_exists( 'pll_get_post_language' )
			? (string) pll_get_post_language( $post_id, 'slug' )
			: '';
	}

	/**
	 * Polylang language the site treats as the source.
	 *
	 * @return string
	 */
	public static function default_language(): string {
		return function_exists( 'pll_default_language' )
			? (string) pll_default_language( 'slug' )
			: 'en';
	}

	/**
	 * Whether the flag on a post is set and truthy.
	 *
	 * @param int $post_id
	 *
	 * @return bool
	 */
	public static function needs_translation( int $post_id ): bool {
		return (bool) get_post_meta( $post_id, Distress_Units_Importer::NEED_TRANSLATE_META, true );
	}

	/**
	 * Published posts still flagged for translation.
	 *
	 * Read in batches and filtered in PHP, because Polylang cannot narrow a
	 * query that has to see every language at once. Posts in the default
	 * language are left out: their text is the source, so the limit would
	 * otherwise be spent on records that can only be skipped.
	 *
	 * @param int      $limit      Hard ceiling on the returned ids.
	 * @param int      $offset     Flagged posts to pass over first.
	 * @param string   $language   Restrict to one Polylang slug.
	 * @param string[] $post_types Types to walk; defaults to all supported.
	 *
	 * @return int[]
	 */
	public function find( int $limit, int $offset = 0, string $language = '', array $post_types = array() ): array {
		$post_types = array_values( array_intersect( $post_types ?: self::POST_TYPES, self::POST_TYPES ) );

		if ( empty( $post_types ) ) {
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
					'post_status'      => 'publish',
					'posts_per_page'   => self::BATCH,
					'offset'           => $page * self::BATCH,
					'fields'           => 'ids',
					'orderby'          => 'ID',
					'order'            => 'ASC',
					'lang'             => '',
					'suppress_filters' => true,
					'no_found_rows'    => true,
					'meta_query'       => array(
						array(
							'key'     => Distress_Units_Importer::NEED_TRANSLATE_META,
							'compare' => 'EXISTS',
						),
					),
				)
			);

			if ( empty( $ids ) ) {
				break;
			}

			foreach ( $ids as $id ) {
				$id = (int) $id;

				if ( ! self::needs_translation( $id ) ) {
					continue;
				}

				$own = self::language_of( $id );

				if ( '' === $own || $own === $default ) {
					continue;
				}

				if ( '' !== $language && $language !== $own ) {
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
	 * What a unit needs, without touching the API.
	 *
	 * @param int  $post_id
	 * @param bool $force Accept a unit that carries no flag.
	 *
	 * @return array|WP_Error Keys id, language, target, texts.
	 */
	public function plan( int $post_id, bool $force = false ) {
		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return new WP_Error( 'unit_missing', sprintf( 'записи #%d не существует', $post_id ) );
		}

		if ( ! in_array( $post->post_type, self::POST_TYPES, true ) ) {
			return new WP_Error(
				'unit_type',
				sprintf( '#%d — это %s, перевод поддержан для %s', $post_id, $post->post_type,
					implode( ' и ', self::POST_TYPES ) )
			);
		}

		if ( ! $force && ! self::needs_translation( $post_id ) ) {
			return new WP_Error( 'unit_not_flagged', sprintf( '#%d не помечен need_translate', $post_id ) );
		}

		$language = self::language_of( $post_id );

		if ( '' === $language ) {
			return new WP_Error( 'unit_no_language', sprintf( 'у #%d нет языка Polylang', $post_id ) );
		}

		if ( $language === self::default_language() ) {
			return new WP_Error(
				'post_default_language',
				sprintf( '#%d на языке по умолчанию (%s) — текст уже исходный, флаг снимать нечем', $post_id, $language )
			);
		}

		$target = DeepL_Client::target_language( $language );

		if ( '' === $target ) {
			return new WP_Error(
				'unit_language_unsupported',
				sprintf( 'язык «%s» DeepL не поддерживает', $language )
			);
		}

		$strings = array();
		$summary = array();

		foreach ( self::FIELDS as $field ) {
			$value = (string) $post->{$field};

			if ( '' !== trim( $value ) ) {
				$strings[ 'post:' . $field ] = $value;
				$summary[]                   = $field;
			}
		}

		$amenities = self::amenity_lines( $post_id );
		$filled    = 0;

		foreach ( $amenities as $index => $line ) {
			if ( '' !== trim( $line ) ) {
				$strings[ 'amenity:' . $index ] = $line;
				++ $filled;
			}
		}

		if ( $filled > 0 ) {
			$summary[] = sprintf( '%s (%d)', self::AMENITIES_META, $filled );
		}

		$rows = self::plan_rows( $post_id );

		foreach ( $rows as $key => $value ) {
			$strings[ 'meta:' . $key ] = $value;
		}

		if ( ! empty( $rows ) ) {
			$summary[] = sprintf( '%s (%d)', self::PLANS_META, count( $rows ) );
		}

		return array(
			'id'        => $post_id,
			'language'  => $language,
			'target'    => $target,
			'strings'   => $strings,
			'amenities' => $amenities,
			'summary'   => $summary,
		);
	}

	/**
	 * Amenity lines of a post, blank ones kept so the list can be rebuilt.
	 *
	 * @param int $post_id
	 *
	 * @return string[]
	 */
	private static function amenity_lines( int $post_id ): array {
		$raw = (string) get_post_meta( $post_id, self::AMENITIES_META, true );

		return '' === trim( $raw ) ? array() : explode( "\n", $raw );
	}

	/**
	 * Translatable rows of the payment plans repeater.
	 *
	 * Read through the meta API rather than by SQL so the repeater keeps
	 * whatever storage the site uses, and only name and description are taken:
	 * the other sub-fields hold ids, percentages and year counts.
	 *
	 * @param int $post_id
	 *
	 * @return array<string, string> Meta key to value.
	 */
	private static function plan_rows( int $post_id ): array {
		$rows  = array();
		$metas = get_post_meta( $post_id );

		if ( ! is_array( $metas ) ) {
			return $rows;
		}

		$pattern = '~^' . preg_quote( self::PLANS_META, '~' ) . '_\d+(_items_\d+)?_(name|description)$~';

		foreach ( $metas as $key => $values ) {
			if ( ! preg_match( $pattern, (string) $key ) ) {
				continue;
			}

			$value = is_array( $values ) ? (string) reset( $values ) : (string) $values;

			if ( '' !== trim( $value ) ) {
				$rows[ (string) $key ] = $value;
			}
		}

		ksort( $rows );

		return $rows;
	}

	/**
	 * Translate a post and save it.
	 *
	 * Every string is translated before anything is written, so a failing call
	 * leaves the post exactly as it was, flag included.
	 *
	 * The strings go in three portions: the repeating field values, which are
	 * the only ones worth caching, then the post's own text, plain and with
	 * markup. Once the field values are in the cache that portion costs no
	 * request at all, so a run settles at two calls per post.
	 *
	 * @param int  $post_id
	 * @param bool $force Accept a post that carries no flag.
	 *
	 * @return array|WP_Error Names of the saved fields.
	 */
	public function translate( int $post_id, bool $force = false ) {
		$plan = $this->plan( $post_id, $force );

		if ( is_wp_error( $plan ) ) {
			return $plan;
		}

		if ( empty( $plan['strings'] ) ) {
			return new WP_Error( 'unit_no_text', sprintf( 'у #%d нет текста для перевода', $post_id ) );
		}

		$html_keys = array_map(
			static function ( string $field ): string {
				return 'post:' . $field;
			},
			self::HTML_FIELDS
		);

		$markup = array_intersect_key( $plan['strings'], array_flip( $html_keys ) );
		$prose  = array_diff_key(
			array_filter(
				$plan['strings'],
				static function ( $value, $key ): bool {
					return str_starts_with( (string) $key, 'post:' );
				},
				ARRAY_FILTER_USE_BOTH
			),
			$markup
		);
		$fields = array_diff_key( $plan['strings'], $markup, $prose );

		$translated = array();

		$portions = array(
			array( $fields, false, true ),
			array( $prose, false, false ),
			array( $markup, true, false ),
		);

		foreach ( $portions as $portion ) {
			if ( empty( $portion[0] ) ) {
				continue;
			}

			$result = $this->client->translate( $portion[0], $plan['target'], $portion[1], $portion[2] );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$translated += $result;
		}

		if ( count( $translated ) !== count( $plan['strings'] ) ) {
			return new WP_Error( 'unit_incomplete', sprintf( 'у #%d перевелись не все строки', $post_id ) );
		}

		return $this->save( $post_id, $translated, $plan['amenities'] );
	}

	/**
	 * Write every translated string, then clear the flag.
	 *
	 * The pll_save_post handlers and kses are lifted the way the floor cleaner
	 * does it, and pll_copy_post_metas is emptied for the whole write: Polylang
	 * syncs metas through the add/update/delete hooks too, so without that the
	 * Russian text would be copied onto the English sibling and clearing the
	 * flag would clear it there as well, dropping a post that still needs
	 * translating out of the backlog. The slug is left alone on purpose, so no
	 * public URL changes here.
	 *
	 * @param int      $post_id
	 * @param array    $translated Prefixed key to translated value.
	 * @param string[] $amenities  Original amenity lines, for an exact rebuild.
	 *
	 * @return array|WP_Error Names of the saved fields.
	 */
	private function save( int $post_id, array $translated, array $amenities ) {
		global $wp_filter;

		$postarr = array(
			'ID'        => $post_id,
			'edit_date' => true,
		);

		$metas = array();
		$saved = array();

		foreach ( $translated as $key => $value ) {
			if ( str_starts_with( $key, 'post:' ) ) {
				$field = substr( $key, 5 );

				if ( 'post_content' === $field ) {
					$postarr[ $field ] = wp_kses_post( $value );
				} elseif ( 'post_excerpt' === $field ) {
					$postarr[ $field ] = sanitize_textarea_field( $value );
				} else {
					$postarr[ $field ] = sanitize_text_field( $value );
				}

				$saved[] = $field;

				continue;
			}

			if ( str_starts_with( $key, 'amenity:' ) ) {
				$amenities[ (int) substr( $key, 8 ) ] = sanitize_text_field( $value );

				continue;
			}

			if ( str_starts_with( $key, 'meta:' ) ) {
				$metas[ substr( $key, 5 ) ] = sanitize_text_field( $value );
			}
		}

		$has_amenities = (bool) preg_grep( '~^amenity:~', array_keys( $translated ) );

		if ( $has_amenities ) {
			$metas[ self::AMENITIES_META ] = implode( "\n", $amenities );
			$saved[]                       = self::AMENITIES_META;
		}

		if ( preg_grep( '~^meta:~', array_keys( $translated ) ) ) {
			$saved[] = self::PLANS_META;
		}

		$no_sync = static function (): array {
			return array();
		};

		add_filter( 'pll_copy_post_metas', $no_sync, 999 );

		$pll_save_post = $wp_filter['pll_save_post'] ?? null;
		unset( $wp_filter['pll_save_post'] );

		kses_remove_filters();

		$result = count( $postarr ) > 2 ? wp_update_post( wp_slash( $postarr ), true ) : $post_id;

		if ( ! is_wp_error( $result ) ) {
			foreach ( $metas as $meta_key => $meta_value ) {
				update_post_meta( $post_id, $meta_key, wp_slash( $meta_value ) );
			}

			delete_post_meta( $post_id, Distress_Units_Importer::NEED_TRANSLATE_META );
			clean_post_cache( $post_id );
		}

		kses_init();

		if ( null !== $pll_save_post ) {
			$wp_filter['pll_save_post'] = $pll_save_post;
		}

		remove_filter( 'pll_copy_post_metas', $no_sync, 999 );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $saved;
	}
}
