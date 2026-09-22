<?php

use Entities\Property;
use Entities\Unit;

/**
 * FROM and JOIN clauses shared by the filter aggregates.
 *
 * A unit keeps its project in its own `property` meta, but a translation often
 * has none and the project has to come from a sibling in the same Polylang
 * group — which is what Unit::get_property() does in PHP. Both paths are left
 * joined and COALESCE picks the unit's own value first.
 *
 * @param string $language Polylang slug, empty when the plugin is off.
 * @param string $listing_type Listing slug, or `all` for every listing at once.
 *
 * @return array{sql: string, params: array}
 */
function core_unit_filter_source( string $language, string $listing_type = 'all' ): array {
	global $wpdb;

	$sql    = "
		FROM {$wpdb->posts} u
			LEFT JOIN {$wpdb->postmeta} pm_own
				ON pm_own.post_id = u.ID AND pm_own.meta_key = 'property'
			LEFT JOIN {$wpdb->term_relationships} tr_g ON tr_g.object_id = u.ID
			LEFT JOIN {$wpdb->term_taxonomy} tt_g
				ON tt_g.term_taxonomy_id = tr_g.term_taxonomy_id AND tt_g.taxonomy = 'post_translations'
			LEFT JOIN {$wpdb->term_relationships} tr_s
				ON tr_s.term_taxonomy_id = tt_g.term_taxonomy_id
			LEFT JOIN {$wpdb->postmeta} pm_sib
				ON pm_sib.post_id = tr_s.object_id AND pm_sib.meta_key = 'property'
			JOIN {$wpdb->posts} p
				ON p.ID = COALESCE( NULLIF( pm_own.meta_value, '' ), pm_sib.meta_value )
				AND p.post_type = 'property' AND p.post_status = 'publish'
	";
	$params = array();

	if ( array_key_exists( $listing_type, core_get_listing_type_choices() ) ) {
		$sql      .= "
			JOIN {$wpdb->postmeta} pm_type
				ON pm_type.post_id = u.ID AND pm_type.meta_key = 'listing_type' AND pm_type.meta_value = %s
		";
		$params[] = $listing_type;
	}

	$language_id = '' === $language ? 0 : core_language_term_taxonomy_id( $language );

	if ( 0 !== $language_id ) {
		$sql      .= "
			JOIN {$wpdb->term_relationships} tr_l
				ON tr_l.object_id = u.ID AND tr_l.term_taxonomy_id = %d
		";
		$params[] = $language_id;
	}

	return array( 'sql' => $sql, 'params' => $params );
}

/**
 * Lowest and highest price and area across the listing.
 *
 * NULLIF drops zero and empty values, matching the `! empty()` the per-object
 * version applied after casting.
 *
 * @param string $post_type Post type holding the units.
 * @param string $language Polylang slug.
 * @param string $listing_type Listing slug.
 *
 * @return array{price_min: ?int, price_max: ?int, area_min: ?float, area_max: ?float}
 */
function core_unit_filter_ranges( string $post_type, string $language, string $listing_type ): array {
	global $wpdb;

	$source = core_unit_filter_source( $language, $listing_type );

	$sql = "
		SELECT
			MIN(NULLIF(pm_price.meta_value + 0, 0)) AS price_min,
			MAX(NULLIF(pm_price.meta_value + 0, 0)) AS price_max,
			MIN(NULLIF(pm_area.meta_value + 0, 0))  AS area_min,
			MAX(NULLIF(pm_area.meta_value + 0, 0))  AS area_max
		{$source['sql']}
			LEFT JOIN {$wpdb->postmeta} pm_price ON pm_price.post_id = u.ID AND pm_price.meta_key = 'price'
			LEFT JOIN {$wpdb->postmeta} pm_area ON pm_area.post_id = u.ID AND pm_area.meta_key = 'area_size'
		WHERE u.post_type = %s AND u.post_status = 'publish'
	";

	$params = array_merge( $source['params'], array( $post_type ) );
	$row    = $wpdb->get_row( $wpdb->prepare( $sql, $params ), ARRAY_A );

	return array(
		'price_min' => isset( $row['price_min'] ) ? (int) $row['price_min'] : null,
		'price_max' => isset( $row['price_max'] ) ? (int) $row['price_max'] : null,
		'area_min'  => isset( $row['area_min'] ) ? (float) $row['area_min'] : null,
		'area_max'  => isset( $row['area_max'] ) ? (float) $row['area_max'] : null,
	);
}

/**
 * Developers of the projects the listing actually contains.
 *
 * @param string $post_type Post type holding the units.
 * @param string $language Polylang slug.
 * @param string $listing_type Listing slug.
 *
 * @return array List of value/label pairs sorted by label.
 */
function core_unit_filter_developers( string $post_type, string $language, string $listing_type ): array {
	global $wpdb;

	$source = core_unit_filter_source( $language, $listing_type );

	$sql = "
		SELECT DISTINCT d.ID, d.post_title
		{$source['sql']}
			JOIN {$wpdb->postmeta} pm_dev ON pm_dev.post_id = p.ID AND pm_dev.meta_key = 'developer_rel'
			JOIN {$wpdb->posts} d ON d.ID = pm_dev.meta_value AND d.post_type = 'developers'
		WHERE u.post_type = %s AND u.post_status = 'publish'
	";

	$params = array_merge( $source['params'], array( $post_type ) );
	$rows   = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );

	$developers = array();
	foreach ( (array) $rows as $row ) {
		$title = apply_filters( 'the_title', $row->post_title, (int) $row->ID );

		$developers[ (int) $row->ID ] = array(
			'value' => (string) (int) $row->ID,
			'label' => html_entity_decode( $title, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
		);
	}

	usort(
		$developers,
		static function ( array $a, array $b ): int {
			return strcasecmp( $a['label'], $b['label'] );
		}
	);

	return $developers;
}

/**
 * Handover years present in the listing.
 *
 * @param string $post_type Post type holding the units.
 * @param string $language Polylang slug.
 * @param string $listing_type Listing slug.
 *
 * @return string[] Years as strings, unordered.
 */
function core_unit_filter_delivery_years( string $post_type, string $language, string $listing_type ): array {
	global $wpdb;

	$source = core_unit_filter_source( $language, $listing_type );

	$sql = "
		SELECT DISTINCT YEAR(pm_date.meta_value) AS delivery_year
		{$source['sql']}
			JOIN {$wpdb->postmeta} pm_date
				ON pm_date.post_id = p.ID AND pm_date.meta_key = 'delivery_date' AND pm_date.meta_value <> ''
		WHERE u.post_type = %s AND u.post_status = 'publish'
		HAVING delivery_year IS NOT NULL
	";

	$params = array_merge( $source['params'], array( $post_type ) );

	return array_map( 'strval', (array) $wpdb->get_col( $wpdb->prepare( $sql, $params ) ) );
}

/**
 * Get grouped data for filters
 *
 * @param string $post_type unit or properties
 * @param string $listing_type off-plan or secondary
 */
function get_search_tabs_data( string $post_type = 'property', string $listing_type = 'off-plan' ): array {
	$language  = core_get_current_language();
	$cache_key = 'search_tabs_data_' . md5( (string) $post_type . (string) $listing_type . (string) $language );

	static $memo = array();
	if ( isset( $memo[ $cache_key ] ) ) {
		return $memo[ $cache_key ];
	}

	$search_tabs_data = wp_cache_get( $cache_key, 'search_tabs_data' );
	if ( false === $search_tabs_data ) {
		$search_tabs_data = core_build_search_tabs_data( $post_type, $language, $listing_type );
	}

	wp_cache_set( $cache_key, $search_tabs_data, 'search_tabs_data', DAY_IN_SECONDS );
	$memo[ $cache_key ] = $search_tabs_data;

	return $search_tabs_data;
}

/**
 * Filter tab data for one post type and listing, uncached.
 *
 * Split out of get_search_tabs_data() so the cache can rebuild it after the
 * response; the body is unchanged.
 *
 * @param string $post_type unit or property.
 * @param string $language Polylang slug.
 * @param string $listing_type Listing slug.
 *
 * @return array
 */
function core_build_search_tabs_data( string $post_type, string $language, string $listing_type ): array {
	if ( 'property' === $post_type ) {
		return get_properties_search_tabs_data();
	}

	$ranges     = core_unit_filter_ranges( $post_type, $language, $listing_type );
	$developers = core_unit_filter_developers( $post_type, $language, $listing_type );
	$price_min  = $ranges['price_min'];
	$price_max  = $ranges['price_max'];
	$area_min   = $ranges['area_min'];
	$area_max   = $ranges['area_max'];

	if ( null === $price_min && empty( $developers ) ) {
		return array();
	}

	$current_year   = date( 'Y' );
	$is_all         = 'all' === $listing_type;
	$is_off_plan    = 'off-plan' === $listing_type;
	$delivery_dates = array();

	foreach ( core_unit_filter_delivery_years( $post_type, $language, $listing_type ) as $year ) {
		$keep = ( $is_off_plan && $year > $current_year )
		        || 'secondary' === $listing_type
		        || 'distress' === $listing_type
		        || ( $is_all && $year >= $current_year );

		if ( $keep && ! isset( $delivery_dates[ $year ] ) ) {
			$delivery_dates[ $year ] = array(
				'value' => $year,
				'label' => $year,
			);
		}
	}

	$delivery_dates = array_values( $delivery_dates );

	$developers = array_merge(
		array(
			array(
				'value' => 'all',
				'label' => __( 'Any Developers', 'east-property' ),
			),
		),
		$developers
	);

	if ( $is_off_plan ) {
		sort( $delivery_dates );
	} else {
		rsort( $delivery_dates );
	}

	if ( ! $is_off_plan ) {
		$ready_options = array(
			array(
				'value' => 'ready',
				/* translators: option of the delivery date filter, projects already handed over. */
				'label' => _x( 'Ready', 'delivery filter', 'east-property' ),
			),
		);

		if ( $is_all ) {
			$ready_options[] = array(
				'value' => 'in_construction',
				'label' => __( 'In Construction', 'east-property' ),
			);
		}

		$delivery_dates = array_merge( $ready_options, $delivery_dates );
	}

	$delivery_dates = array_merge(
		array(
			array(
				'value' => 'all',
				'label' => __( 'All Years', 'east-property' ),
			),
		),
		$delivery_dates
	);

	$search_tabs_data            = array();
	$search_tabs_data['filters'] = array(
		'location'      => array(
			'label'   => __( 'Location', 'east-property' ),
			'options' => get_locations( $listing_type ),
		),
		'available'     => array(
			'label'   => 'off-plan' === $listing_type ? __( 'Handover Year Before',
				'east-property' ) : __( 'Completion Year After', 'east-property' ),
			'options' => $delivery_dates,
		),
		'price'         => array(
			'label'   => __( 'Price (AED)', 'east-property' ),
			'options' => array(
				'min' => $price_min,
				'max' => $price_max,
			),
		),
		'property_type' => array(
			'label'   => __( 'Property Type', 'east-property' ),
			'options' => array(
				array(
					'value' => 'all',
					'label' => __( 'Any', 'east-property' ),
				),
			),
		),
		'developer'     => array(
			'label'   => __( 'Developer', 'east-property' ),
			'options' => $developers,
		),
		'area'          => array(
			'label'   => __( 'Area', 'east-property' ),
			'options' => get_range_steps( $area_min, $area_max, 6, false, 100 ),
		),
		'beds'          => get_filter_beds_options(),
	);

	$all_units_types = get_field_object( 'field_694ea57c4ae1f' );
	if ( ! empty( $all_units_types ) ) {
		foreach ( $all_units_types['choices'] as $choice_value => $choice_label ) {
			$search_tabs_data['filters']['property_type']['options'][] = array(
				'value' => (string) $choice_value,
				'label' => core_property_choice_label( (string) $choice_value ),
			);
		}
	}

	$search_tabs_data['categories'] = array(
		array(
			'slug'       => date( 'Y' ),
			'label'      => _x( 'Available', 'search tab', 'east-property' ),
			'action_url' => core_home_url( 'secondary/' ),
			'defaults'   => array(
				'beds'     => array(
					'label'   => __( 'Bedrooms', 'east-property' ),
					'options' => get_filter_beds_options(),
				),
				'location' => array(
					'label'   => __( 'District', 'east-property' ),
					'options' => get_locations( $listing_type ),
				),
			),
		),
		array(
			'slug'       => date( 'Y' ),
			'label'      => _x( 'In construction', 'search tab', 'east-property' ),
			'action_url' => core_home_url( 'off-plan/' ),
			'defaults'   => array(
				'beds'  => get_filter_beds_options(),
				'price' => $price_max,
			),
		),
	);

	return $search_tabs_data;
}

/**
 * Get options for filter beds
 *
 * @return array
 */
function get_filter_beds_options(): array {
	$selected_beds = ! empty( $_GET['beds'] ) ? explode( ',', sanitize_text_field( $_GET['beds'] ) ) : array();
	if ( ! is_array( $selected_beds ) ) {
		$selected_beds = array();
	}

	return array(
		'label'   => __( 'Beds', 'east-property' ),
		'options' => array(
			array(
				'value'  => 'studio',
				'label'  => __( 'Studio', 'east-property' ),
				'active' => in_array( 'studio', $selected_beds, true ),
			),
			array(
				'value'  => '1',
				'label'  => '1',
				'active' => in_array( '1', $selected_beds, true ),
			),
			array(
				'value'  => '2',
				'label'  => '2',
				'active' => in_array( '2', $selected_beds, true ),
			),
			array(
				'value'  => '3',
				'label'  => '3',
				'active' => in_array( '3', $selected_beds, true ),
			),
			array(
				'value'  => '4',
				'label'  => '4',
				'active' => in_array( '4', $selected_beds, true ),
			),
			array(
				'value'  => '5',
				'label'  => '5',
				'active' => in_array( '5', $selected_beds, true ),
			),
			array(
				'value'  => '6',
				'label'  => '6',
				'active' => in_array( '6', $selected_beds, true ),
			),
			array(
				'value'  => '7',
				'label'  => '7+',
				'active' => in_array( '7', $selected_beds, true ),
			),
		),
	);
}

/**
 * Get options for filter baths
 *
 * @return array
 */
function get_filter_baths_options(): array {
	return array(
		'label'   => __( 'Baths', 'east-property' ),
		'options' => array(
			array(
				'value'  => '1',
				'label'  => '1',
				'active' => false,
			),
			array(
				'value'  => '2',
				'label'  => '2',
				'active' => false,
			),
			array(
				'value' => '3',
				'label' => '3',
			),
			array(
				'value' => '4',
				'label' => '4+',
			),
		),
	);
}

/**
 * term_taxonomy_id of a Polylang language.
 *
 * Joining `term_relationships` to `term_taxonomy` and `terms` inside an
 * aggregate lets MySQL drive from the language term and cross join the tens of
 * thousands of rows it owns. One integer resolved up front turns every language
 * check into a primary key lookup instead.
 *
 * @param string $slug Language slug.
 *
 * @return int Zero when the language is unknown.
 */
function core_language_term_taxonomy_id( string $slug ): int {
	static $cache = array();

	if ( isset( $cache[ $slug ] ) ) {
		return $cache[ $slug ];
	}

	$term = get_term_by( 'slug', $slug, 'language' );

	$cache[ $slug ] = $term instanceof WP_Term ? (int) $term->term_taxonomy_id : 0;

	return $cache[ $slug ];
}

/**
 * Lowest and highest project price across the listing.
 *
 * A project's price is the average price of its units, the same
 * `round( sum / count )` Property::get_price() computes — units without a price
 * count as zero and still divide, so this is a plain sum over the group rather
 * than AVG over non-empty rows. Units are matched against the project and its
 * Polylang translations, which is what Property::linked_property_ids() does.
 *
 * @param string $language Polylang slug, empty when the plugin is off.
 *
 * @return array{min: ?int, max: ?int}
 */
function core_property_filter_price_range( string $language ): array {
	global $wpdb;

	$language_property = '';
	$language_unit     = '';
	$params            = array();
	$language_id       = '' === $language ? 0 : core_language_term_taxonomy_id( $language );

	if ( 0 !== $language_id ) {
		$language_property = "JOIN {$wpdb->term_relationships} tr_p
			ON tr_p.object_id = p.ID AND tr_p.term_taxonomy_id = %d";
		$language_unit     = "JOIN {$wpdb->term_relationships} tr_u
			ON tr_u.object_id = u.ID AND tr_u.term_taxonomy_id = %d";

		$params = array( $language_id, $language_id );
	}

	$sql = "
		SELECT MIN(avg_price) AS price_min, MAX(avg_price) AS price_max
		FROM (
			SELECT ROUND(SUM(unit_price) / COUNT(*)) AS avg_price
			FROM (
				SELECT p.ID AS property_id, u.ID AS unit_id,
					MAX(COALESCE(NULLIF(pm_price.meta_value, '') + 0, 0)) AS unit_price
				FROM {$wpdb->postmeta} pm_link
					JOIN {$wpdb->posts} u
						ON u.ID = pm_link.post_id AND u.post_type = 'unit' AND u.post_status = 'publish'
					JOIN {$wpdb->posts} r
						ON r.ID = pm_link.meta_value AND r.post_type = 'property'
					JOIN {$wpdb->term_relationships} tr_g ON tr_g.object_id = r.ID
					JOIN {$wpdb->term_taxonomy} tt_g
						ON tt_g.term_taxonomy_id = tr_g.term_taxonomy_id AND tt_g.taxonomy = 'post_translations'
					JOIN {$wpdb->term_relationships} tr_m ON tr_m.term_taxonomy_id = tt_g.term_taxonomy_id
					JOIN {$wpdb->posts} p
						ON p.ID = tr_m.object_id AND p.post_type = 'property' AND p.post_status = 'publish'
					LEFT JOIN {$wpdb->postmeta} pm_price
						ON pm_price.post_id = u.ID AND pm_price.meta_key = 'price'
					{$language_property}
					{$language_unit}
				WHERE pm_link.meta_key = 'property'
				GROUP BY p.ID, u.ID
			) pairs
			GROUP BY property_id
		) per_property
		WHERE avg_price > 0
	";

	$row = $wpdb->get_row( empty( $params ) ? $sql : $wpdb->prepare( $sql, $params ), ARRAY_A );

	return array(
		'min' => isset( $row['price_min'] ) ? (int) $row['price_min'] : null,
		'max' => isset( $row['price_max'] ) ? (int) $row['price_max'] : null,
	);
}

/**
 * Handover years of the published projects, this year and later.
 *
 * @param string $language Polylang slug, empty when the plugin is off.
 *
 * @return string[] Years as strings, ascending.
 */
function core_property_filter_delivery_years( string $language ): array {
	global $wpdb;

	$language_join = '';
	$params        = array();

	$language_id = '' === $language ? 0 : core_language_term_taxonomy_id( $language );

	if ( 0 !== $language_id ) {
		$language_join = "JOIN {$wpdb->term_relationships} tr_l
			ON tr_l.object_id = p.ID AND tr_l.term_taxonomy_id = %d";
		$params[]      = $language_id;
	}

	$params[] = (int) date( 'Y' );

	$sql = "
		SELECT DISTINCT YEAR(pm_date.meta_value) AS delivery_year
		FROM {$wpdb->posts} p
			JOIN {$wpdb->postmeta} pm_date
				ON pm_date.post_id = p.ID AND pm_date.meta_key = 'delivery_date' AND pm_date.meta_value <> ''
			{$language_join}
		WHERE p.post_type = 'property' AND p.post_status = 'publish'
		HAVING delivery_year >= %d
		ORDER BY delivery_year ASC
	";

	return array_map( 'strval', (array) $wpdb->get_col( $wpdb->prepare( $sql, $params ) ) );
}

/**
 * Temp solution for get filters for properties //TODO rebuild after run
 *
 * @return array
 */
function get_properties_search_tabs_data(): array {
	$language = function_exists( 'pll_current_language' ) ? (string) pll_current_language( 'slug' ) : '';
	$price    = core_property_filter_price_range( $language );
	$years    = core_property_filter_delivery_years( $language );

	$price_min = $price['min'];
	$price_max = $price['max'];

	// Declared by the original and never assigned, so every `defaults.available`
	// below has always been null. Kept as is rather than quietly changed.
	$delivery_date_max = null;

	if ( null === $price_min && empty( $years ) ) {
		return array();
	}

	$delivery_dates = array();
	foreach ( $years as $year ) {
		$delivery_dates[] = array(
			'value' => $year,
			'label' => $year,
		);
	}

	sort( $delivery_dates );
	$delivery_dates = array_merge(
		array(
			array(
				'value' => 'all',
				'label' => __( 'Any year', 'east-property' ),
			),
			array(
				'value' => 'ready',
				'label' => _x( 'Ready', 'delivery filter', 'east-property' ),
			),
			array(
				'value' => 'in_construction',
				'label' => __( 'In Construction', 'east-property' ),
			),
		),
		$delivery_dates
	);

	$search_tabs_data            = array();
	$search_tabs_data['filters'] = array(
		'location'      => array(
			'label'   => __( 'Location', 'east-property' ),
			'options' => get_locations(),
		),
		'available'     => array(
			'label'   => __( 'Available', 'east-property' ),
			'options' => $delivery_dates,
		),
		'price'         => array(
			'label'   => __( 'Price (AED)', 'east-property' ),
			'options' => array(
				'min' => $price_min,
				'max' => $price_max,
			),
		),
		'property_type' => array(
			'label'   => __( 'Property Type', 'east-property' ),
			'options' => array(
				array(
					'value' => 'all',
					'label' => __( 'Any', 'east-property' ),
				),
			),
		),
		'developer'     => array(
			'label'   => __( 'Developer', 'east-property' ),
			'options' => get_developers_list(),
		),
		'beds'          => get_filter_beds_options(),
	);

	$all_units_types = get_field_object( 'field_694ea57c4ae1f' );
	if ( ! empty( $all_units_types ) ) {
		foreach ( $all_units_types['choices'] as $choice_value => $choice_label ) {
			$search_tabs_data['filters']['property_type']['options'][] = array(
				'value' => (string) $choice_value,
				'label' => core_property_choice_label( (string) $choice_value ),
			);
		}
	}

	$search_tabs_data['categories'] = array(
		array(
			'slug'     => 'all',
			'label'    => __( 'Any', 'east-property' ),
			'defaults' => array(
				'available' => $delivery_date_max,
				'price'     => $price_max,
			),
		),
		array(
			'slug'     => 'apartment',
			'label'    => __( 'Apartments', 'east-property' ),
			'defaults' => array(
				'available' => $delivery_date_max,
				'price'     => $price_max,
			),
		),
		array(
			'slug'     => 'house',
			'label'    => __( 'Houses', 'east-property' ),
			'defaults' => array(
				'available' => $delivery_date_max,
				'price'     => $price_max,
			),
		),
		array(
			'slug'     => 'villa',
			'label'    => __( 'Villas', 'east-property' ),
			'defaults' => array(
				'available' => $delivery_date_max,
				'price'     => $price_max,
			),
		),
		array(
			'slug'     => 'office',
			'label'    => __( 'Offices', 'east-property' ),
			'defaults' => array(
				'available' => $delivery_date_max,
				'price'     => $price_max,
			),
		),
	);

	return $search_tabs_data;
}

/**
 * Get steps before min and max values for range filters
 */
function get_range_steps( $min = 0, $max = 0, $steps_count = 6, $is_price = false, $round_by = 0 ): array {
	$options = array(
		array(
			'value' => 'all',
			'label' => 'Any',
		),
	);

	if ( $min >= $max ) {
		return $options;
	}

	if ( empty( $min ) ) {
		return $options;
	}

	$step = ( $max - $min ) / $steps_count;
	if ( 1 > $step ) {
		$step = 1;
	}

	$values   = array();
	$values[] = $min;
	for ( $i = 0; $i < $steps_count; $i ++ ) {
		$min = round( $min + $step );
		if ( $min >= $max ) {
			break;
		}

		$values[] = $min;
	}
	$values[] = $max;

	$values = array_unique( $values, SORT_NUMERIC );

	foreach ( $values as $value ) {
		if ( 0 < $round_by ) {
			$value = (int) ( ceil( $value / $round_by ) * $round_by );
		}

		$options[] = array(
			'value' => (string) $value,
			'label' => $is_price ? number_format( (float) $value ) : (string) $value,
		);
	}

	return $options;
}

/**
 * Get list of posts with post_type developer
 */
function get_developers_list(): array {
	global $wpdb;

	$language_id        = function_exists( 'pll_current_language' )
		? core_language_term_taxonomy_id( (string) pll_current_language( 'slug' ) )
		: 0;
	$language_developer = '';
	$language_property  = '';
	$params             = array();

	if ( 0 !== $language_id ) {
		$language_developer = "JOIN {$wpdb->term_relationships} tr_d
			ON tr_d.object_id = d.ID AND tr_d.term_taxonomy_id = %d";
		$language_property  = "JOIN {$wpdb->term_relationships} tr_p
			ON tr_p.object_id = p.ID AND tr_p.term_taxonomy_id = %d";

		$params = array( $language_id, $language_id );
	}

	// CAST keeps the comparison between strings so the meta_key_value index
	// still applies; comparing the text column to a number makes MySQL scan
	// every row of postmeta once per developer.
	$sql = "
		SELECT d.ID, d.post_title
		FROM {$wpdb->posts} d
			{$language_developer}
		WHERE d.post_type = 'developers' AND d.post_status = 'publish'
			AND EXISTS (
				SELECT 1
				FROM {$wpdb->postmeta} pm
					JOIN {$wpdb->posts} p
						ON p.ID = pm.post_id AND p.post_type = 'property' AND p.post_status = 'publish'
					{$language_property}
				WHERE pm.meta_key = 'developer_rel' AND pm.meta_value = CAST( d.ID AS CHAR )
			)
		ORDER BY d.post_title ASC
	";

	$rows = $wpdb->get_results( empty( $params ) ? $sql : $wpdb->prepare( $sql, $params ) );

	$results = array(
		array(
			'value' => 'all',
			'label' => __( 'Any Developers', 'east-property' ),
		),
	);

	foreach ( (array) $rows as $row ) {
		$results[] = array(
			'value' => (string) (int) $row->ID,
			'label' => (string) apply_filters( 'the_title', $row->post_title, (int) $row->ID ),
		);
	}

	return $results;
}

/**
 * Locations that carry published units, for the district filter
 *
 * @param string $listing_type Listing slug; empty or 'all' counts every unit.
 *
 * @return array
 */
function get_locations( string $listing_type = '' ): array {
	$listing_type = '' !== $listing_type && 'all' !== $listing_type
		? core_sanitize_listing_type( $listing_type )
		: '';

	$language  = core_get_current_language();
	$cache_key = md5( 'locations_' . $language . '_' . $listing_type );

	static $memo = array();
	if ( ! isset( $memo[ $cache_key ] ) ) {
		$locations = wp_cache_get( $cache_key, 'locations' );

		if ( false === $locations ) {
			$locations = core_query_locations( $listing_type, $language );

			wp_cache_set( $cache_key, $locations, 'locations', DAY_IN_SECONDS );
		}

		$memo[ $cache_key ] = $locations;
	}

	$selected_location = $_REQUEST['location'] ?? null;
	$results           = array(
		array(
			'value' => 'all',
			'label' => __( 'Any Locations', 'east-property' ),
		),
	);

	foreach ( $memo[ $cache_key ] as $location ) {
		$location['selected'] = $location['value'] === $selected_location;
		$results[]            = $location;
	}

	return $results;
}

/**
 * The districts of a listing, uncached
 *
 * Split out of get_locations() so the cache keeps only what every visitor sees
 * alike: the chosen district and the translated "any" option are added after.
 *
 * @param string $listing_type Listing slug, already sanitized; empty counts every unit.
 * @param string $language Polylang slug; empty counts every language.
 *
 * @return array
 */
function core_query_locations( string $listing_type = '', string $language = '' ): array {
	global $wpdb;

	$listing_join  = '';
	$listing_where = '';
	$params        = array();

	$language_join = '';
	if ( '' !== $language ) {
		$language_join = "INNER JOIN {$wpdb->term_relationships} AS pll_language_relation
				ON pll_language_relation.object_id = u.ID
			INNER JOIN {$wpdb->term_taxonomy} AS pll_language_taxonomy
				ON pll_language_taxonomy.term_taxonomy_id = pll_language_relation.term_taxonomy_id
				AND pll_language_taxonomy.taxonomy = 'language'
			INNER JOIN {$wpdb->terms} AS pll_language
				ON pll_language.term_id = pll_language_taxonomy.term_id
				AND pll_language.slug = %s";
		$params[]      = $language;
	}

	if ( '' !== $listing_type ) {
		$listing_join  = "INNER JOIN {$wpdb->postmeta} AS pm_listing_type
				ON pm_listing_type.post_id = u.ID
				AND pm_listing_type.meta_key = 'listing_type'";
		$listing_where = 'AND pm_listing_type.meta_value = %s';
		$params[]      = $listing_type;
	}

	$sql = "SELECT DISTINCT
				t.term_id,
				t.name,
				t.slug,
				tt.taxonomy,
				tt.parent,
				tt.count
			FROM wp_posts AS u
			LEFT JOIN wp_postmeta AS pm_property
				ON pm_property.post_id = u.ID
				AND pm_property.meta_key = 'property'
			INNER JOIN wp_term_relationships AS tr
				ON tr.object_id = CAST(pm_property.meta_value AS UNSIGNED)
			INNER JOIN wp_term_taxonomy AS tt
				ON tt.term_taxonomy_id = tr.term_taxonomy_id
				AND tt.taxonomy = 'location'
			INNER JOIN wp_terms AS t
				ON t.term_id = tt.term_id
			{$language_join}
			{$listing_join}
			WHERE u.post_type = 'unit'
			  AND u.post_status = 'publish'
			  {$listing_where}
			ORDER BY t.name ASC;";

	$locations = $wpdb->get_results( empty( $params ) ? $sql : $wpdb->prepare( $sql, $params ), ARRAY_A );

	$results = array();
	foreach ( (array) $locations as $location ) {
		$results[] = array(
			'value' => $location['slug'],
			'label' => $location['name'],
		);
	}

	return $results;
}

/**
 * Current generation of the listing caches.
 *
 * @return int
 */
function core_get_listings_cache_version(): int {
	return max( 1, (int) get_option( 'core_listings_cache_version', 1 ) );
}

/**
 * Get updated filters and list of products
 * @throws JsonException
 */
function ajax_get_property(): void {
	//check_ajax_referer( 'filterjdj3' ); //TODO Check, maybe javascript was cached it

	$posts_per_page = PROPERTIES_PER_PAGE ?? 20;
	$properties     = get_properties( $posts_per_page, false, array(
		'specifications' => true,
		'galleries'      => true,
	) );

	ob_start();

	if ( ! empty( $properties ) ) {
		foreach ( $properties['items'] as $property ) {
			get_template_part(
				'core/components/cards/property-card',
				null,
				array(
					'property' => $property,
					'template' => 'large-card',
				)
			);
		}

		get_template_part(
			'core/components/common/pagination',
			null,
			array(
				'total_items'    => $properties['total'] ?? count( $properties ),
				'items_per_page' => PROPERTIES_PER_PAGE,
				'current_href'   => $_REQUEST['current_href'],
			)
		);
	} else {
		_e( 'Items not found', 'east-property' );
	}

	$properties_html = ob_get_clean();
	$total_found     = $properties['total'] ?? count( $properties );

	wp_send_json_success(
		array(
			'properties'       => $properties_html,
			'map_properties'   => get_map_properties_json( get_map_properties(), true ),
			'properties_found' => sprintf(
				_n( '%s property found', '%s properties found', $total_found, 'east-property' ),
				$total_found
			),
		)
	);
}

/**
 * Get properties json array for display on the map
 *
 * @param Property[] $properties
 * @param bool $skip_empty
 *
 * @return string
 * @throws JsonException
 */
function get_map_properties_json( array $properties, bool $skip_empty = false ): string {
	$properties_json = array();

	foreach ( $properties as $property ) {
		$is_empty        = $skip_empty && 0 >= (int) $property['units_count'];
		$is_empty_coords = empty( $property['latitude'] ) || empty( $property['longitude'] );
		if ( $is_empty || $is_empty_coords ) {
			continue;
		}

		$properties_json[] = array(
			'id'              => $property['ID'],
			'name'            => $property['post_title'] ?? '',
			'url'             => $property['url'] ?? '',
			'units_available' => $property['units_count'] ?? '',
			'longitude'       => $property['longitude'],
			'latitude'        => $property['latitude'],
		);
	}

	return json_encode( $properties_json, JSON_THROW_ON_ERROR );
}

add_action( 'wp_ajax_nopriv_get_property', 'ajax_get_property' );
add_action( 'wp_ajax_get_property', 'ajax_get_property' );

/**
 * Get units list for search results
 */
function ajax_get_unit(): void {
	//check_ajax_referer( 'get_filtered_properties' ); //TODO Check, maybe javascript was cached it

	$posts_per_page = PROPERTIES_PER_PAGE ?? 20;
	if ( ! empty( $_REQUEST['listing_type'] ) ) {
		$listing_type = sanitize_text_field( wp_unslash( $_REQUEST['listing_type'] ) );
	}
	$units = get_units( $listing_type, $posts_per_page, array(
		'galleries' => true,
	) );

	ob_start();

	if ( ! empty( $units['items'] ) ) {
		$is_cta_showed = false;
		$limit         = $posts_per_page;
		foreach ( $units['items'] as $unit ) {
			get_template_part(
				'core/components/cards/unit-card',
				null,
				array(
					'unit'     => $unit,
					'template' => 'unit-card',
				)
			);

			if ( ! $is_cta_showed ) {
				get_template_part( '/template-parts/components/cards/cta-card' );
				$is_cta_showed = true;
			}

			-- $limit;
			if ( $limit <= 0 ) {
				break;
			}
		}

		get_template_part(
			'core/components/common/pagination',
			null,
			array(
				'total_items'    => $units['total'] ?? count( $units ),
				'items_per_page' => $posts_per_page,
				'current_href'   => $_REQUEST['current_href'],
			)
		);
	} else {
		_e( 'Properties not found', 'east-property' );
	}

	$properties_html = ob_get_clean();

	$total_found = $units['total'] ?? count( $units );

	wp_send_json_success(
		array(
			'properties'       => $properties_html,
			'map_properties'   => array(),
			'properties_found' => sprintf(
				_n( '%s property found', '%s properties found', $total_found, 'east-property' ),
				$total_found
			),
			'current_url'      => get_permalink( get_page_by_path( 'search' ) ),
		)
	);
}

add_action( 'wp_ajax_nopriv_get_unit', 'ajax_get_unit' );
add_action( 'wp_ajax_get_unit', 'ajax_get_unit' );
