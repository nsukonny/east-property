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
 * Generation of the per-request location caches.
 *
 * Bumped by the flush so a write inside the same request — a migration, an
 * admin save — is seen by everything that already asked.
 *
 * @param bool $bump Increment instead of reading.
 *
 * @return int
 */
function core_location_cache_generation( bool $bump = false ): int {
	static $generation = 0;

	if ( $bump ) {
		++$generation;
	}

	return $generation;
}

/**
 * Meta holding the explicit kind of a location term.
 */
const CORE_LOCATION_TYPE_META = 'location_type';

/**
 * Kinds a location can be.
 *
 * @return array<string,string> Slug to label.
 */
function core_location_types(): array {
	return array(
		'country' => __( 'Country', 'east-property' ),
		'city'    => __( 'City', 'east-property' ),
		'area'    => __( 'Area', 'east-property' ),
	);
}

/**
 * Kind of a location term.
 *
 * Nothing is inferred from the tree: a term that was never classified stays
 * unknown, because a district saved without a parent used to be read as a
 * country and opened routes of its own.
 *
 * @param int $term_id Location term id.
 *
 * @return string One of the kinds, empty when the term was never classified.
 */
function core_location_type( int $term_id ): string {
	$stored = $term_id > 0 ? (string) get_term_meta( $term_id, CORE_LOCATION_TYPE_META, true ) : '';

	return isset( core_location_types()[ $stored ] ) ? $stored : '';
}

/**
 * Country terms, by explicit kind.
 *
 * @return int[]
 */
function core_country_term_ids(): array {
	static $ids        = null;
	static $generation = -1;

	if ( null !== $ids && core_location_cache_generation() === $generation ) {
		return $ids;
	}

	$generation = core_location_cache_generation();

	$terms = get_terms(
		array(
			'taxonomy'   => 'location',
			'hide_empty' => false,
			'fields'     => 'ids',
			'meta_query'=> array(
				array(
					'key'   => CORE_LOCATION_TYPE_META,
					'value' => 'country',
				),
			),
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
	return 'country' === core_location_type( $term_id );
}

/**
 * Country a location belongs to, itself included.
 *
 * Walks up until a term is explicitly a country, rather than taking the top of
 * the tree: with a city in between, the topmost term is still the country, but
 * a branch rooted at an unclassified term has no country at all and must say so.
 *
 * @param int $term_id Location term id.
 *
 * @return int Country term id, 0 when the branch holds none.
 */
function core_location_country( int $term_id ): int {
	static $known      = array();
	static $generation = -1;

	if ( core_location_cache_generation() !== $generation ) {
		$known      = array();
		$generation = core_location_cache_generation();
	}

	if ( isset( $known[ $term_id ] ) ) {
		return $known[ $term_id ];
	}

	$known[ $term_id ] = 0;

	if ( $term_id < 1 ) {
		return 0;
	}

	$chain = array_merge( array( $term_id ), array_map( 'intval', (array) get_ancestors( $term_id, 'location', 'taxonomy' ) ) );

	foreach ( $chain as $candidate ) {
		if ( core_location_is_country( $candidate ) ) {
			$known[ $term_id ] = $candidate;

			break;
		}
	}

	return $known[ $term_id ];
}

/**
 * term_taxonomy ids of a location and everything under it.
 *
 * @param int $term_id Location term id.
 *
 * @return int[] Empty when the term does not exist.
 */
function core_location_subtree_tt_ids( int $term_id ): array {
	global $wpdb;

	static $cache      = array();
	static $generation = -1;

	if ( core_location_cache_generation() !== $generation ) {
		$cache      = array();
		$generation = core_location_cache_generation();
	}

	if ( isset( $cache[ $term_id ] ) ) {
		return $cache[ $term_id ];
	}

	$cache[ $term_id ] = array();

	if ( $term_id < 1 || ! term_exists( $term_id, 'location' ) ) {
		return $cache[ $term_id ];
	}

	$children = get_term_children( $term_id, 'location' );
	$terms    = array_merge(
		array( $term_id ),
		is_wp_error( $children ) ? array() : array_map( 'intval', $children )
	);

	$in = implode( ',', array_map( 'intval', $terms ) );

	$cache[ $term_id ] = array_map(
		'intval',
		(array) $wpdb->get_col(
			"SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy}
			 WHERE taxonomy = 'location' AND term_id IN ({$in})"
		)
	);

	return $cache[ $term_id ];
}

/**
 * term_taxonomy ids of a country and everything under it.
 *
 * @param string $slug Country slug.
 *
 * @return int[] Empty when the slug is not a country.
 */
function core_country_location_tt_ids( string $slug ): array {
	$country = '' === $slug ? false : get_term_by( 'slug', $slug, 'location' );

	if ( ! $country instanceof WP_Term || ! core_location_is_country( (int) $country->term_id ) ) {
		return array();
	}

	return core_location_subtree_tt_ids( (int) $country->term_id );
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

	// Термины с проектами берутся как есть, а страна у каждого ищется по
	// предкам: прежний IF( tt.parent = 0 ... ) знал только два уровня и для
	// района внутри города возвращал город.
	$rows = (array) $wpdb->get_col(
		"SELECT DISTINCT tt.term_id
		 FROM {$wpdb->term_relationships} AS tr
		 INNER JOIN {$wpdb->term_taxonomy} AS tt
		         ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'location'
		 INNER JOIN {$wpdb->posts} AS p
		         ON p.ID = tr.object_id AND p.post_type = 'property' AND p.post_status = 'publish'"
	);

	$slugs = array();

	foreach ( array_map( 'intval', $rows ) as $term_id ) {
		$country = core_location_country( $term_id );

		if ( 0 === $country ) {
			continue;
		}

		$term = get_term( $country, 'location' );

		if ( $term instanceof WP_Term ) {
			$slugs[ (string) $term->slug ] = true;
		}
	}

	$slugs = array_keys( $slugs );

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

/**
 * Whether a kind may sit under this parent.
 *
 * @param string $type   Kind being saved.
 * @param int    $parent Parent term id, 0 for a root term.
 *
 * @return string Empty when allowed, otherwise the reason.
 */
function core_location_type_conflict( string $type, int $parent ): string {
	if ( 'country' === $type && $parent > 0 ) {
		return __( 'A country has to be a root location.', 'east-property' );
	}

	if ( 'country' !== $type && $parent > 0 && 'country' === core_location_type( $parent ) ) {
		return '';
	}

	if ( 'city' === $type && $parent > 0 && ! in_array( core_location_type( $parent ), array( 'country', '' ), true ) ) {
		return __( 'A city belongs under a country.', 'east-property' );
	}

	return '';
}

/**
 * Kind selector on the location add and edit screens.
 *
 * @param WP_Term|string $term Term being edited, or the taxonomy on the add screen.
 *
 * @return void
 */
function core_location_type_field( $term ): void {
	$is_edit = $term instanceof WP_Term;
	$current = $is_edit ? core_location_type( (int) $term->term_id ) : '';

	wp_nonce_field( 'core_location_type', 'core_location_type_nonce' );

	$label = esc_html__( 'Location type', 'east-property' );
	$hint  = esc_html__( 'A country has to be a root location and is the only kind that opens a country address.', 'east-property' );

	$select = '<select name="' . esc_attr( CORE_LOCATION_TYPE_META ) . '" id="' . esc_attr( CORE_LOCATION_TYPE_META ) . '">';
	$select .= '<option value=""' . selected( $current, '', false ) . '>'
	           . esc_html__( '— not classified —', 'east-property' ) . '</option>';

	foreach ( core_location_types() as $value => $title ) {
		$select .= '<option value="' . esc_attr( $value ) . '"' . selected( $current, $value, false ) . '>'
		           . esc_html( $title ) . '</option>';
	}

	$select .= '</select>';

	if ( $is_edit ) {
		echo '<tr class="form-field"><th scope="row"><label for="' . esc_attr( CORE_LOCATION_TYPE_META ) . '">'
		     . $label . '</label></th><td>' . $select . '<p class="description">' . $hint . '</p></td></tr>';

		return;
	}

	echo '<div class="form-field"><label for="' . esc_attr( CORE_LOCATION_TYPE_META ) . '">' . $label . '</label>'
	     . $select . '<p class="description">' . $hint . '</p></div>';
}

add_action( 'location_add_form_fields', 'core_location_type_field' );
add_action( 'location_edit_form_fields', 'core_location_type_field' );

/**
 * Store the kind of a location.
 *
 * @param int $term_id Term being saved.
 *
 * @return void
 */
function core_location_save_type( int $term_id ): void {
	$nonce = isset( $_POST['core_location_type_nonce'] )
		? sanitize_key( wp_unslash( $_POST['core_location_type_nonce'] ) )
		: '';

	if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'core_location_type' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_term', $term_id ) || ! isset( $_POST[ CORE_LOCATION_TYPE_META ] ) ) {
		return;
	}

	$type = sanitize_key( wp_unslash( $_POST[ CORE_LOCATION_TYPE_META ] ) );

	if ( '' === $type || ! isset( core_location_types()[ $type ] ) ) {
		delete_term_meta( $term_id, CORE_LOCATION_TYPE_META );
		core_location_flush_type_caches();

		return;
	}

	$term     = get_term( $term_id, 'location' );
	$parent   = $term instanceof WP_Term ? (int) $term->parent : 0;
	$conflict = core_location_type_conflict( $type, $parent );

	if ( '' !== $conflict ) {
		set_transient( 'core_location_type_error_' . $term_id, $conflict, MINUTE_IN_SECONDS );

		return;
	}

	update_term_meta( $term_id, CORE_LOCATION_TYPE_META, $type );
	core_location_flush_type_caches();
}

add_action( 'created_location', 'core_location_save_type' );
add_action( 'edited_location', 'core_location_save_type' );

/**
 * Show why a location type was refused.
 *
 * @return void
 */
function core_location_type_notice(): void {
	$term_id = isset( $_GET['tag_ID'] ) ? (int) $_GET['tag_ID'] : 0;
	$error   = $term_id > 0 ? get_transient( 'core_location_type_error_' . $term_id ) : false;

	if ( ! is_string( $error ) || '' === $error ) {
		return;
	}

	delete_transient( 'core_location_type_error_' . $term_id );

	echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
}

add_action( 'admin_notices', 'core_location_type_notice' );

/**
 * Drop what the country list and the tree are remembered in.
 *
 * @return void
 */
function core_location_flush_type_caches(): void {
	core_location_cache_generation( true );
	delete_option( CORE_COUNTRY_SLUGS_OPTION );
	wp_cache_delete( 'countries_filled', 'properties' );
	clean_term_cache( array(), 'location' );
}

/**
 * SQL join narrowing a listing to a location and everything under it.
 *
 * Matches through a deduplicated set, so a record that ever carries two terms
 * of the same branch is still counted once.
 *
 * @param string $column  Primary key column of the outer table.
 * @param int    $term_id Location term id.
 *
 * @return string Empty string when the term holds nothing.
 */
function core_location_scope_join( string $column, int $term_id ): string {
	global $wpdb;

	$tt_ids = core_location_subtree_tt_ids( $term_id );

	if ( empty( $tt_ids ) ) {
		return '';
	}

	$in = implode( ',', $tt_ids );

	return "INNER JOIN (
				SELECT DISTINCT object_id
				FROM {$wpdb->term_relationships}
				WHERE term_taxonomy_id IN ({$in})
			) AS tr_location_scope ON tr_location_scope.object_id = {$column}";
}

/**
 * Location term a listing is narrowed to, by slug.
 *
 * @param string $slug Term slug from a filter or an archive.
 *
 * @return int Term id, 0 when the slug names nothing.
 */
function core_location_scope_term( string $slug ): int {
	$slug = sanitize_title( $slug );

	if ( '' === $slug || 'all' === $slug ) {
		return 0;
	}

	$term = get_term_by( 'slug', $slug, 'location' );

	return $term instanceof WP_Term ? (int) $term->term_id : 0;
}
