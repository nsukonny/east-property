<?php
/**
 * Country segment in URLs.
 *
 * The country is the top level of the location tree, so adding one means
 * adding a term — the rules below rebuild themselves from the taxonomy.
 * Polylang only prefixes the rules WordPress generates, so these are written
 * out by hand, with the languages and the countries as alternations rather
 * than one rule per pair.
 */

const CORE_COUNTRY_SLUGS_OPTION = 'core_country_slugs';

/**
 * Country slugs, in the order the rules list them.
 *
 * @return string[]
 */
function core_country_slugs(): array {
	static $slugs = null;

	if ( null !== $slugs ) {
		return $slugs;
	}

	$stored = get_option( CORE_COUNTRY_SLUGS_OPTION, null );

	if ( ! is_array( $stored ) ) {
		$stored = core_read_country_slugs();

		update_option( CORE_COUNTRY_SLUGS_OPTION, $stored, false );
	}

	$slugs = array_values( array_filter( array_map( 'strval', $stored ) ) );

	return $slugs;
}

/**
 * Country slugs straight from the taxonomy.
 *
 * @return string[]
 */
function core_read_country_slugs(): array {
	$terms = get_terms(
		array(
			'taxonomy'   => 'location',
			'parent'     => 0,
			'hide_empty' => false,
			'fields'     => 'slugs',
			'orderby'    => 'slug',
		)
	);

	return is_wp_error( $terms ) ? array() : array_map( 'strval', $terms );
}

/**
 * Default country: the one that carries no segment until the migration.
 *
 * @return string
 */
function core_default_country(): string {
	$slugs = core_country_slugs();
	$slug  = (string) get_option( 'core_default_country', 'uae' );

	return in_array( $slug, $slugs, true ) ? $slug : (string) ( $slugs[0] ?? '' );
}

/**
 * Country of the current request.
 *
 * @return string
 */
function core_current_country(): string {
	$country = (string) get_query_var( 'country' );

	return in_array( $country, core_country_slugs(), true ) ? $country : core_default_country();
}

/**
 * Refresh the cached country list and rebuild the rules when it changed.
 *
 * @return void
 */
function core_refresh_country_slugs(): void {
	$fresh = core_read_country_slugs();

	if ( $fresh === get_option( CORE_COUNTRY_SLUGS_OPTION, null ) ) {
		return;
	}

	update_option( CORE_COUNTRY_SLUGS_OPTION, $fresh, false );

	// Правила перечисляют страны поимённо, поэтому новая страна требует сброса.
	add_action( 'shutdown', 'flush_rewrite_rules' );
}

add_action( 'created_location', 'core_refresh_country_slugs' );
add_action( 'edited_location', 'core_refresh_country_slugs' );
add_action( 'delete_location', 'core_refresh_country_slugs' );

/**
 * Location term inside a country, by the slug the URL carries.
 *
 * WordPress keeps term slugs unique across the taxonomy, so a district whose
 * name is taken elsewhere is stored with the country appended. The address
 * stays clean and the stored slug is looked up here.
 *
 * @param string $slug    Slug from the URL.
 * @param string $country Country slug.
 *
 * @return string Stored slug, empty when nothing matches.
 */
function core_country_location_slug( string $slug, string $country ): string {
	if ( '' === $slug || '' === $country ) {
		return '';
	}

	$country_term = get_term_by( 'slug', $country, 'location' );

	if ( ! $country_term instanceof WP_Term ) {
		return '';
	}

	foreach ( array( $slug, $slug . '-' . $country ) as $candidate ) {
		$term = get_term_by( 'slug', $candidate, 'location' );

		if ( $term instanceof WP_Term && core_location_country( (int) $term->term_id ) === (int) $country_term->term_id ) {
			return (string) $term->slug;
		}
	}

	return '';
}

/**
 * Short forms that answer on a country's behalf.
 *
 * @return array<string,string> Alias to country slug.
 */
function core_country_aliases(): array {
	$known = core_country_slugs();

	return array_filter(
		array( 'ae' => 'uae' ),
		static function ( string $target ) use ( $known ): bool {
			return in_array( $target, $known, true );
		}
	);
}

/**
 * Regex alternation of the countries and their aliases.
 *
 * @return string Empty when no country exists yet.
 */
function core_country_rule_pattern(): string {
	$slugs = core_country_slugs();

	if ( empty( $slugs ) ) {
		return '';
	}

	$known = array_map( 'preg_quote', array_merge( array_keys( core_country_aliases() ), $slugs ) );

	return '(' . implode( '|', $known ) . ')';
}

/**
 * Regex alternation of the languages that carry a prefix.
 *
 * @return string Empty when Polylang is absent or holds one language.
 */
function core_language_rule_pattern(): string {
	$prefixes = array();

	foreach ( core_language_url_prefixes() as $prefix ) {
		if ( '' !== $prefix ) {
			$prefixes[] = preg_quote( rtrim( $prefix, '/' ) );
		}
	}

	return empty( $prefixes ) ? '' : '(' . implode( '|', $prefixes ) . ')';
}

add_filter(
	'query_vars',
	static function ( $vars ) {
		$vars[] = 'country';
		$vars[] = 'country_alias';

		return $vars;
	}
);

/**
 * Register the country-prefixed routes.
 *
 * Two rules per shape: one for the default language, one for the rest. The
 * match groups shift by one when the language prefix is present, so the query
 * strings are built from the same template with an offset.
 */
add_action(
	'init',
	static function () {
		$countries = core_country_rule_pattern();

		if ( '' === $countries ) {
			return;
		}

		$languages = core_language_rule_pattern();

		$shapes = array(
			// Хаб страны, страницы вперёд обычной формы.
			array( '/page-([0-9]+)/?$', 'location=%country%&cur_page=$matches[%1$d]' ),
			array( '/page/([0-9]+)/?$', 'location=%country%&cur_page=$matches[%1$d]' ),
			array( '/?$', 'location=%country%' ),
			// Проект.
			array( '/project/([^/]+)/?$', 'post_type=property&name=$matches[%1$d]' ),
			// Юнит внутри проекта.
			array( '/property/([^/]+)/([^/]+)/?$', 'post_type=unit&project_slug=$matches[%1$d]&name=$matches[%2$d]' ),
			// Юнит без проекта.
			array( '/property/([^/]+)/?$', 'post_type=unit&name=$matches[%1$d]' ),
			// Застройщик.
			array( '/developers/([^/]+)/?$', 'post_type=developers&name=$matches[%1$d]' ),
			// Район. Тема листает через page-N, WordPress и Yoast — через page/N.
			array( '/areas/([^/]+)/page-([0-9]+)/?$', 'location=$matches[%1$d]&cur_page=$matches[%2$d]' ),
			array( '/areas/([^/]+)/page/([0-9]+)/?$', 'location=$matches[%1$d]&cur_page=$matches[%2$d]' ),
			array( '/areas/([^/]+)/?$', 'location=$matches[%1$d]' ),
			array( '/projects/([^/]+)/page-([0-9]+)/?$', 'location=$matches[%1$d]&cur_page=$matches[%2$d]' ),
			array( '/projects/([^/]+)/page/([0-9]+)/?$', 'location=$matches[%1$d]&cur_page=$matches[%2$d]' ),
			array( '/projects/([^/]+)/?$', 'location=$matches[%1$d]' ),
		);

		foreach ( $shapes as $shape ) {
			list( $path, $query ) = $shape;

			add_rewrite_rule(
				'^' . $countries . $path,
				'index.php?country=$matches[1]&' . core_country_rule_query( $query, 1 ),
				'top'
			);

			if ( '' === $languages ) {
				continue;
			}

			add_rewrite_rule(
				'^' . $languages . '/' . $countries . $path,
				'index.php?lang=$matches[1]&country=$matches[2]&' . core_country_rule_query( $query, 2 ),
				'top'
			);
		}
	},
	20
);

/**
 * Fill a query template with match numbers offset by the leading groups.
 *
 * @param string $query  Template with %1$d style placeholders.
 * @param int    $offset Groups consumed before the path ones.
 *
 * @return string
 */
function core_country_rule_query( string $query, int $offset ): string {
	$query = str_replace( '%country%', '$matches[' . $offset . ']', $query );

	return sprintf( $query, $offset + 1, $offset + 2 );
}

/**
 * Resolve a district inside the country the address names.
 *
 * @param array $vars Query vars.
 *
 * @return array
 */
function core_country_filter_request( $vars ): array {
	$vars = (array) $vars;

	if ( empty( $vars['country'] ) ) {
		return $vars;
	}

	$country = (string) $vars['country'];
	$aliases = core_country_aliases();

	if ( isset( $aliases[ $country ] ) ) {
		if ( ( $vars['location'] ?? '' ) === $country ) {
			$vars['location'] = $aliases[ $country ];
		}

		$vars['country']       = $aliases[ $country ];
		$vars['country_alias'] = 1;
		$country               = $aliases[ $country ];
	}

	if ( empty( $vars['location'] ) || $vars['location'] === $country ) {
		return $vars;
	}

	$stored = core_country_location_slug( (string) $vars['location'], $country );

	if ( '' === $stored ) {
		$vars['core_force_404'] = 1;

		unset( $vars['location'] );

		return $vars;
	}

	$vars['location'] = $stored;

	return $vars;
}

add_filter( 'request', 'core_country_filter_request' );

/**
 * Country the address carries, as opposed to the one a filter asks for.
 *
 * @return string Empty when the path has no country segment.
 */
function core_country_from_path(): string {
	if ( '' === (string) get_query_var( 'country' ) ) {
		return '';
	}

	$path     = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
	$segments = explode( '/', trim( $path, '/' ) );
	$known    = array_merge( core_country_slugs(), array_keys( core_country_aliases() ) );

	foreach ( array_slice( $segments, 0, 2 ) as $segment ) {
		if ( in_array( $segment, $known, true ) ) {
			return $segment;
		}
	}

	return '';
}

/**
 * Whether the country segment is the canonical address shape.
 *
 * Until the migration the old addresses stay canonical and the country ones
 * only answer with a 301, so the two never compete in the index.
 *
 * @return bool
 */
function core_country_urls_enabled(): bool {
	return 'country' === get_option( 'core_country_urls', 'legacy' );
}

/**
 * Send a country address to the canonical one while the old shape rules.
 *
 * @return void
 */
function core_country_redirect_to_legacy(): void {
	// Страна из адреса, а не из фильтра: иначе /off-plan/?country=oman уводил бы
	// страницу саму на себя.
	if ( is_404() || '' === core_country_from_path() ) {
		return;
	}

	// Псевдоним не канонический адрес ни в одном режиме.
	if ( core_country_urls_enabled() && 1 !== (int) get_query_var( 'country_alias' ) ) {
		return;
	}

	$object = get_queried_object();
	$target = '';

	if ( $object instanceof WP_Post ) {
		$target = (string) get_permalink( $object );
	} elseif ( $object instanceof WP_Term ) {
		$link   = get_term_link( $object );
		$target = is_string( $link ) ? $link : '';
	}

	if ( '' === $target ) {
		return;
	}

	wp_safe_redirect( $target, 301 );
	exit;
}

add_action( 'template_redirect', 'core_country_redirect_to_legacy', 8 );

/**
 * Country segment of a post's address, with the trailing slash.
 *
 * @param int $post_id Unit, project or developer id.
 *
 * @return string Empty while the old addresses are canonical.
 */
function core_country_path( int $post_id ): string {
	if ( ! core_country_urls_enabled() ) {
		return '';
	}

	$country = core_post_country( $post_id );

	return ( null === $country ? core_default_country() : (string) $country->slug ) . '/';
}

/**
 * District slug as the address carries it, without the country suffix.
 *
 * @param WP_Term $term     District term.
 * @param string  $country  Country slug.
 *
 * @return string
 */
function core_public_area_slug( WP_Term $term, string $country ): string {
	$slug   = (string) $term->slug;
	$suffix = '-' . $country;

	return str_ends_with( $slug, $suffix ) ? substr( $slug, 0, -strlen( $suffix ) ) : $slug;
}

/**
 * Put the country into project and developer addresses.
 *
 * @param string  $permalink Address WordPress built.
 * @param WP_Post $post      Post being linked.
 *
 * @return string
 */
function core_country_post_type_link( $permalink, $post ) {
	$bases = array(
		'property'   => 'project',
		'developers' => 'developers',
	);

	if ( ! core_country_urls_enabled() || ! isset( $bases[ $post->post_type ] ) ) {
		return $permalink;
	}

	return core_home_url( '/' . core_country_path( (int) $post->ID ) . $bases[ $post->post_type ] . '/' . $post->post_name . '/' );
}

add_filter( 'post_type_link', 'core_country_post_type_link', 5, 2 );

/**
 * Put the country into district and country-hub addresses.
 *
 * @param string  $link     Address WordPress built.
 * @param WP_Term $term     Term being linked.
 * @param string  $taxonomy Taxonomy of the term.
 *
 * @return string
 */
function core_country_term_link( $link, $term, $taxonomy ) {
	if ( 'location' !== $taxonomy || ! core_country_urls_enabled() || ! $term instanceof WP_Term ) {
		return $link;
	}

	if ( core_location_is_country( (int) $term->term_id ) ) {
		return core_home_url( '/' . $term->slug . '/' );
	}

	$country = get_term( core_location_country( (int) $term->term_id ), 'location' );
	$slug    = $country instanceof WP_Term ? (string) $country->slug : core_default_country();

	return core_home_url( '/' . $slug . '/areas/' . core_public_area_slug( $term, $slug ) . '/' );
}

add_filter( 'term_link', 'core_country_term_link', 5, 3 );

/**
 * Send an address without the country to the one that carries it.
 *
 * @return void
 */
function core_country_redirect_to_country(): void {
	if ( ! core_country_urls_enabled() || is_404() || '' !== core_country_from_path() ) {
		return;
	}

	$target = '';

	if ( is_singular( array( 'unit', 'property', 'developers' ) ) ) {
		$target = (string) get_permalink( get_queried_object_id() );
	} elseif ( is_tax( 'location' ) ) {
		$link   = get_term_link( get_queried_object() );
		$target = is_string( $link ) ? $link : '';
	}

	if ( '' === $target ) {
		return;
	}

	wp_safe_redirect( $target, 301 );
	exit;
}

add_action( 'template_redirect', 'core_country_redirect_to_country', 8 );
