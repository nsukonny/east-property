<?php
/**
 * Per-language descriptions of location terms.
 *
 * Polylang does not translate this taxonomy: one set of terms serves every
 * language. The term's own description field keeps the default language and
 * every other language keeps its text in term meta, so nothing has to be
 * migrated and the editor keeps the box the screen always had.
 */

/**
 * Languages that keep their description in term meta.
 *
 * @return string[] Language slugs, without the default one.
 */
function core_location_languages(): array {
	if ( ! function_exists( 'pll_languages_list' ) || ! function_exists( 'pll_default_language' ) ) {
		return array();
	}

	$default = (string) pll_default_language( 'slug' );

	return array_values(
		array_filter(
			array_map( 'strval', (array) pll_languages_list() ),
			static function ( string $slug ) use ( $default ): bool {
				return '' !== $slug && $slug !== $default;
			}
		)
	);
}

/**
 * Meta key holding the description of a location in one language.
 *
 * @param string $language Language slug.
 *
 * @return string
 */
function core_location_description_key( string $language ): string {
	return 'description_' . $language;
}

/**
 * Description of a location in the language being rendered.
 *
 * Only the text of that language is returned: an English paragraph on a Russian
 * page is exactly what the translation backlog exists to avoid.
 *
 * @param WP_Term|int|string $term     Term, its id, or an empty value.
 * @param string             $language Defaults to the current language.
 *
 * @return string
 */
function core_location_description( $term, string $language = '' ): string {
	if ( is_numeric( $term ) ) {
		$term = get_term( (int) $term, 'location' );
	}

	if ( ! $term instanceof WP_Term ) {
		return '';
	}

	if ( '' === $language ) {
		$language = function_exists( 'pll_current_language' ) ? (string) pll_current_language( 'slug' ) : '';
	}

	if ( '' === $language || ! in_array( $language, core_location_languages(), true ) ) {
		return (string) $term->description;
	}

	return (string) get_term_meta( $term->term_id, core_location_description_key( $language ), true );
}

/**
 * One textarea per extra language on the location edit screen.
 *
 * @param WP_Term $term
 *
 * @return void
 */
function core_location_description_fields( $term ): void {
	$languages = core_location_languages();

	if ( empty( $languages ) || ! $term instanceof WP_Term ) {
		return;
	}

	wp_nonce_field( 'core_location_descriptions', 'core_location_descriptions_nonce' );

	foreach ( $languages as $language ) {
		$key   = core_location_description_key( $language );
		$value = (string) get_term_meta( $term->term_id, $key, true );
		?>
		<tr class="form-field term-<?php echo esc_attr( $key ); ?>-wrap">
			<th scope="row">
				<label for="<?php echo esc_attr( $key ); ?>">
					<?php
					printf(
						/* translators: %s: language code. */
						esc_html__( 'Description (%s)', 'east-property' ),
						esc_html( strtoupper( $language ) )
					);
					?>
				</label>
			</th>
			<td>
				<textarea name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>"
						rows="5" cols="50" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'Shown on pages in this language. Left empty, the language shows no description.',
						'east-property' ); ?>
				</p>
			</td>
		</tr>
		<?php
	}
}

add_action( 'location_edit_form_fields', 'core_location_description_fields' );

/**
 * Store the per-language descriptions of a location.
 *
 * @param int $term_id
 *
 * @return void
 */
function core_location_save_descriptions( int $term_id ): void {
	$languages = core_location_languages();

	if ( empty( $languages ) ) {
		return;
	}

	$nonce = isset( $_POST['core_location_descriptions_nonce'] )
		? sanitize_key( wp_unslash( $_POST['core_location_descriptions_nonce'] ) )
		: '';

	if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'core_location_descriptions' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_term', $term_id ) ) {
		return;
	}

	foreach ( $languages as $language ) {
		$key = core_location_description_key( $language );

		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}

		$value = trim( sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) );

		if ( '' === $value ) {
			delete_term_meta( $term_id, $key );

			continue;
		}

		update_term_meta( $term_id, $key, $value );
	}
}

add_action( 'edited_location', 'core_location_save_descriptions' );

/**
 * Country terms: the top level of the location tree.
 *
 * @return int[]
 */
function core_country_term_ids(): array {
	static $ids = null;

	if ( null !== $ids ) {
		return $ids;
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'location',
			'parent'     => 0,
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);

	$ids = is_wp_error( $terms ) ? array() : array_map( 'intval', $terms );

	return $ids;
}

/**
 * Whether the location term is a country.
 *
 * @param int $term_id Location term id.
 *
 * @return bool
 */
function core_location_is_country( int $term_id ): bool {
	return in_array( $term_id, core_country_term_ids(), true );
}

/**
 * Country the location belongs to; the term itself when it is one.
 *
 * @param int $term_id Location term id.
 *
 * @return int Term id, 0 when unknown.
 */
function core_location_country( int $term_id ): int {
	if ( $term_id < 1 ) {
		return 0;
	}

	$ancestors = get_ancestors( $term_id, 'location', 'taxonomy' );

	return empty( $ancestors ) ? $term_id : (int) end( $ancestors );
}

/**
 * term_taxonomy ids of a country and the districts under it.
 *
 * @param string $slug Country slug.
 *
 * @return int[] Empty when the slug is not a country.
 */
function core_country_location_tt_ids( string $slug ): array {
	global $wpdb;

	static $cache = array();

	if ( isset( $cache[ $slug ] ) ) {
		return $cache[ $slug ];
	}

	$country        = '' === $slug ? false : get_term_by( 'slug', $slug, 'location' );
	$cache[ $slug ] = array();

	if ( ! $country instanceof WP_Term || ! core_location_is_country( (int) $country->term_id ) ) {
		return $cache[ $slug ];
	}

	$children = get_term_children( (int) $country->term_id, 'location' );
	$terms    = array_merge(
		array( (int) $country->term_id ),
		is_wp_error( $children ) ? array() : array_map( 'intval', $children )
	);

	$in = implode( ',', array_map( 'intval', $terms ) );

	$cache[ $slug ] = array_map(
		'intval',
		(array) $wpdb->get_col(
			"SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy}
			 WHERE taxonomy = 'location' AND term_id IN ({$in})"
		)
	);

	return $cache[ $slug ];
}

/**
 * SQL join narrowing a listing to the country the request asks for.
 *
 * Built as one pass with a literal id list: a correlated subquery is evaluated
 * per row on the production MariaDB and has taken listings down before.
 *
 * @param string $column Primary key column of the outer table.
 * @param string $slug   Country slug; the requested one when left out.
 *
 * @return string Empty string when no country filter applies.
 */
function core_country_listing_join( string $column, string $slug = '' ): string {
	global $wpdb;

	$slug = '' === $slug ? core_requested_country() : sanitize_title( $slug );

	if ( '' === $slug ) {
		return '';
	}

	$tt_ids = core_country_location_tt_ids( $slug );

	if ( empty( $tt_ids ) ) {
		return '';
	}

	$in = implode( ',', $tt_ids );

	return "INNER JOIN {$wpdb->term_relationships} AS tr_country
				ON tr_country.object_id = {$column}
				AND tr_country.term_taxonomy_id IN ({$in})";
}

/**
 * Country the visitor filtered the listing by.
 *
 * @return string Empty when no filter or an unknown slug.
 */
function core_requested_country(): string {
	$requested = $_REQUEST['country'] ?? '';
	$requested = is_scalar( $requested ) ? sanitize_title( (string) $requested ) : '';

	if ( '' === $requested || 'all' === $requested ) {
		return '';
	}

	return in_array( $requested, core_country_slugs(), true ) ? $requested : '';
}

/**
 * Countries that hold at least one published project.
 *
 * @return string[] Country slugs.
 */
function core_countries_with_properties(): array {
	global $wpdb;

	static $slugs = null;

	if ( null !== $slugs ) {
		return $slugs;
	}

	$cache_key = core_generate_cache_key( 'countries_filled', 'properties_listings', array() );
	$cached    = wp_cache_get( $cache_key, 'properties' );

	if ( is_array( $cached ) ) {
		$slugs = $cached;

		return $slugs;
	}

	$rows = (array) $wpdb->get_col(
		"SELECT DISTINCT IF( tt.parent = 0, tt.term_id, tt.parent )
		 FROM {$wpdb->term_relationships} AS tr
		 INNER JOIN {$wpdb->term_taxonomy} AS tt
		         ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'location'
		 INNER JOIN {$wpdb->posts} AS p
		         ON p.ID = tr.object_id AND p.post_type = 'property' AND p.post_status = 'publish'"
	);

	$countries = core_country_term_ids();
	$slugs     = array();

	foreach ( array_map( 'intval', $rows ) as $term_id ) {
		if ( ! in_array( $term_id, $countries, true ) ) {
			continue;
		}

		$term = get_term( $term_id, 'location' );

		if ( $term instanceof WP_Term ) {
			$slugs[] = (string) $term->slug;
		}
	}

	sort( $slugs );

	wp_cache_set( $cache_key, $slugs, 'properties', DAY_IN_SECONDS );

	return $slugs;
}

/**
 * Country options for the listing filters.
 *
 * Hidden while one country holds everything: a single-choice filter is noise.
 *
 * @return array<int,array<string,string>> Empty when the filter is pointless.
 */
function core_country_filter_options(): array {
	$filled = core_countries_with_properties();

	if ( 2 > count( $filled ) ) {
		return array();
	}

	$options = array(
		array(
			'value' => 'all',
			'label' => __( 'All countries', 'east-property' ),
		),
	);

	foreach ( $filled as $slug ) {
		$term = get_term_by( 'slug', $slug, 'location' );

		$options[] = array(
			'value' => $slug,
			'label' => $term instanceof WP_Term ? (string) $term->name : $slug,
		);
	}

	return $options;
}

/**
 * Country term a post belongs to, through its location.
 *
 * @param int $post_id Unit or property id.
 *
 * @return WP_Term|null
 */
function core_post_country( int $post_id ): ?WP_Term {
	static $known = array();

	if ( array_key_exists( $post_id, $known ) ) {
		return $known[ $post_id ];
	}

	$terms = $post_id > 0 ? wp_get_post_terms( $post_id, 'location', array( 'fields' => 'ids' ) ) : array();
	$term  = is_wp_error( $terms ) || empty( $terms )
		? null
		: get_term( core_location_country( (int) $terms[0] ), 'location' );

	if ( ! $term instanceof WP_Term ) {
		// Промах не запоминаем: пермалинк строится раньше привязки терминов,
		// и запомненный null оставил бы запись без страны до конца запроса.
		return null;
	}

	$known[ $post_id ] = $term;

	return $term;
}

/**
 * Breadcrumb entry for the country a post sits in.
 *
 * @param int $post_id Unit or property id.
 *
 * @return array{url:?string,title:string}
 */
function core_country_breadcrumb( int $post_id ): array {
	$country = core_post_country( $post_id );
	$link    = null === $country ? '' : get_term_link( $country );

	return array(
		'url'   => is_string( $link ) && '' !== $link ? $link : core_home_url( '/projects/' ),
		'title' => null === $country
			/* translators: %s: country name. */
			? __( 'Projects', 'east-property' )
			: sprintf( __( 'Projects in %s', 'east-property' ), $country->name ),
	);
}

/**
 * Map centre and zoom of a country, from term meta or the built-in map.
 *
 * @param string $country Country slug.
 *
 * @return array{lat:float,lng:float,zoom:int}
 */
function core_country_map_view( string $country ): array {
	$defaults = array(
		'uae'          => array( 25.2048, 55.2708, 11 ),
		'saudi-arabia' => array( 24.7136, 46.6753, 10 ),
		'oman'         => array( 23.5880, 58.3829, 11 ),
	);

	$view = $defaults[ $country ] ?? $defaults['uae'];
	$term = '' === $country ? false : get_term_by( 'slug', $country, 'location' );

	if ( $term instanceof WP_Term ) {
		$lat  = get_term_meta( $term->term_id, 'map_lat', true );
		$lng  = get_term_meta( $term->term_id, 'map_lng', true );
		$zoom = get_term_meta( $term->term_id, 'map_zoom', true );

		if ( is_numeric( $lat ) && is_numeric( $lng ) ) {
			$view[0] = (float) $lat;
			$view[1] = (float) $lng;
		}

		if ( is_numeric( $zoom ) ) {
			$view[2] = (int) $zoom;
		}
	}

	return array(
		'lat'  => (float) $view[0],
		'lng'  => (float) $view[1],
		'zoom' => (int) $view[2],
	);
}

/**
 * Country slug from untrusted input.
 *
 * @param string $slug Requested slug.
 *
 * @return string Empty when unknown or "all".
 */
function core_sanitize_country( string $slug ): string {
	$slug = sanitize_title( $slug );

	if ( '' === $slug || 'all' === $slug ) {
		return '';
	}

	return in_array( $slug, core_country_slugs(), true ) ? $slug : '';
}
