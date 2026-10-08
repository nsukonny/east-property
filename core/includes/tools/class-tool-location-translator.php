<?php
/**
 * Machine translation of location descriptions.
 *
 * @package east-property
 */

namespace Tools;

use WP_Error;
use WP_Term;

/**
 * Fills the per-language description of a location from the default one.
 */
final class Location_Translator {

	/**
	 * Taxonomy this works on.
	 */
	public const TAXONOMY = 'location';

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
	 * Languages a description can be filled for.
	 *
	 * @return string[]
	 */
	public static function languages(): array {
		return function_exists( 'core_location_languages' ) ? core_location_languages() : array();
	}

	/**
	 * Locations whose description in a language is still missing.
	 *
	 * The taxonomy holds under two hundred terms, so the ids come in one query
	 * and the source text is checked in PHP: an empty description is not
	 * something the term query can filter on.
	 *
	 * @param string $language Language slug.
	 * @param int    $limit    Hard ceiling on the returned ids.
	 * @param int    $offset   Terms to pass over first.
	 * @param bool   $force    Include terms that already have the language.
	 *
	 * @return int[]
	 */
	public function find( string $language, int $limit, int $offset = 0, bool $force = false ): array {
		$args = array(
			'taxonomy'   => self::TAXONOMY,
			'hide_empty' => false,
			'fields'     => 'ids',
			'orderby'    => 'term_id',
			'order'      => 'ASC',
		);

		if ( ! $force ) {
			$key                = core_location_description_key( $language );
			$args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key'     => $key,
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => $key,
					'value'   => '',
					'compare' => '=',
				),
			);
		}

		$ids   = get_terms( $args );
		$found = array();

		if ( is_wp_error( $ids ) ) {
			return $found;
		}

		$passed = 0;

		foreach ( $ids as $id ) {
			$term = get_term( (int) $id, self::TAXONOMY );

			if ( ! $term instanceof WP_Term || '' === trim( (string) $term->description ) ) {
				continue;
			}

			if ( $passed < $offset ) {
				++ $passed;

				continue;
			}

			$found[] = (int) $id;

			if ( count( $found ) >= $limit ) {
				break;
			}
		}

		return $found;
	}

	/**
	 * What a location needs, without touching the API.
	 *
	 * @param int    $term_id
	 * @param string $language Language slug.
	 * @param bool   $force    Accept a term that already has the language.
	 *
	 * @return array|WP_Error Keys id, name, language, target, source.
	 */
	public function plan( int $term_id, string $language, bool $force = false ) {
		$term = get_term( $term_id, self::TAXONOMY );

		if ( ! $term instanceof WP_Term ) {
			return new WP_Error(
				'location_missing',
				sprintf( 'термина #%d в таксономии %s нет', $term_id, self::TAXONOMY )
			);
		}

		if ( ! in_array( $language, self::languages(), true ) ) {
			return new WP_Error(
				'location_language',
				sprintf( 'язык «%s» не из списка переводимых: %s', $language, implode( ', ', self::languages() ) )
			);
		}

		$target = DeepL_Client::target_language( $language );

		if ( '' === $target ) {
			return new WP_Error(
				'location_language_unsupported',
				sprintf( 'язык «%s» DeepL не поддерживает', $language )
			);
		}

		$source = trim( (string) $term->description );

		if ( '' === $source ) {
			return new WP_Error(
				'location_no_source',
				sprintf( 'у «%s» нет описания на языке по умолчанию', $term->name )
			);
		}

		$key = core_location_description_key( $language );

		if ( ! $force && '' !== trim( (string) get_term_meta( $term_id, $key, true ) ) ) {
			return new WP_Error(
				'location_filled',
				sprintf( 'у «%s» описание на %s уже есть', $term->name, strtoupper( $language ) )
			);
		}

		return array(
			'id'       => $term_id,
			'name'     => (string) $term->name,
			'language' => $language,
			'target'   => $target,
			'source'   => $source,
		);
	}

	/**
	 * Translate the description of a location and store it.
	 *
	 * @param int    $term_id
	 * @param string $language Language slug.
	 * @param bool   $force    Overwrite an existing description.
	 *
	 * @return int|WP_Error Characters stored.
	 */
	public function translate( int $term_id, string $language, bool $force = false ) {
		$plan = $this->plan( $term_id, $language, $force );

		if ( is_wp_error( $plan ) ) {
			return $plan;
		}

		$result = $this->client->translate(
			array( 'description' => $plan['source'] ),
			$plan['target']
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( empty( $result['description'] ) ) {
			return new WP_Error( 'location_empty', sprintf( 'перевод «%s» пришёл пустым', $plan['name'] ) );
		}

		$text   = trim( sanitize_textarea_field( $result['description'] ) );
		$stored = update_term_meta( $term_id, core_location_description_key( $language ), $text );

		if ( is_wp_error( $stored ) ) {
			return $stored;
		}

		clean_term_cache( $term_id, self::TAXONOMY );

		return mb_strlen( $text );
	}
}
