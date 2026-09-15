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

	if ( '' !== $language ) {
		$sql      .= "
			JOIN {$wpdb->term_relationships} tr_l ON tr_l.object_id = u.ID
			JOIN {$wpdb->term_taxonomy} tt_l
				ON tt_l.term_taxonomy_id = tr_l.term_taxonomy_id AND tt_l.taxonomy = 'language'
			JOIN {$wpdb->terms} t_l ON t_l.term_id = tt_l.term_id AND t_l.slug = %s
		";
		$params[] = $language;
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
	$language  = function_exists( 'pll_current_language' ) ? (string) pll_current_language( 'slug' ) : '';
	$cache_key = 'search_tabs_data_' . $post_type . '_' . $listing_type . ( '' === $language ? '' : '_' . $language );

	$search_tabs_data = ! IS_DEV ? get_transient( $cache_key ) : false;
	if ( ! empty( $search_tabs_data ) ) {
		$search_tabs_data['filters']['beds'] = get_filter_beds_options();

		return $search_tabs_data;
	}

	if ( 'property' === $post_type ) {
		$search_tabs_data = get_properties_search_tabs_data();

		set_transient( $cache_key, $search_tabs_data, DAY_IN_SECONDS );

		return $search_tabs_data;
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

	if ( $is_all ) {
		$delivery_dates = array_merge(
			array(
				array(
					'value' => 'available_immediately',
					'label' => __( 'Available', 'east-property' ),
				),
				array(
					'value' => 'in_construction',
					'label' => __( 'In Construction', 'east-property' ),
				),
			),
			$delivery_dates
		);
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
			'options' => get_locations(),
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
					'label' => 'Any',
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
				'label' => (string) $choice_label,
			);
		}
	}

	$search_tabs_data['categories'] = array(
		array(
			'slug'     => 'available_immediately',
			'label'    => __( 'Available immediately', 'east-property' ),
			'defaults' => array(
				'beds'     => array(
					'label'   => __( 'Bedrooms', 'east-property' ),
					'options' => get_filter_beds_options(),
				),
				'location' => array(
					'label'   => __( 'District', 'east-property' ),
					'options' => get_locations(),
				),
			),
		),
		array(
			'slug'     => 'in_construction',
			'label'    => __( 'In construction', 'east-property' ),
			'defaults' => array(
				'beds'  => get_filter_beds_options(),
				'price' => $price_max,
			),
		),
	);

	set_transient( $cache_key, $search_tabs_data, DAY_IN_SECONDS );

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
 * Temp solution for get filters for properties //TODO rebuild after run
 *
 * @return array
 */
function get_properties_search_tabs_data(): array {
	/*
	 * Ids only. The loop needs a price and a year per property, and hydrating
	 * every post primed the meta cache for all fields of all 5 582 properties:
	 * 3.3 s and 600 MB before the loop even started. get_posts() still runs the
	 * query, so Polylang narrows it to the current language exactly as before.
	 *
	 * The developer of each property used to be looked up here too, one query
	 * each, into an array nothing read: the options come from
	 * get_developers_list().
	 */
	$property_ids = get_posts(
		array(
			'post_type'      => 'property',
			'posts_per_page' => - 1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		)
	);

	if ( empty( $property_ids ) ) {
		return array();
	}

	$delivery_dates    = array();
	$price_min         = null;
	$price_max         = null;
	$delivery_date_max = null;
	$current_year      = date( 'Y' );

	$prices    = core_property_average_prices( $property_ids );
	$raw_dates = core_first_meta_values( $property_ids, 'delivery_date' );
	$years     = array();

	foreach ( $property_ids as $property_id ) {
		$price = $prices[ $property_id ] ?? 0;
		if ( ! empty( $price ) && ( null === $price_min || $price < $price_min ) ) {
			$price_min = $price;
		}
		if ( ! empty( $price ) && ( null === $price_max || $price > $price_max ) ) {
			$price_max = $price;
		}

		// Property::get_delivery_date() and the year taken from it, worked out
		// once per stored value: there are a few dozen distinct dates in all.
		$raw = $raw_dates[ $property_id ] ?? '';
		if ( ! array_key_exists( $raw, $years ) ) {
			$date          = core_property_delivery_date( $raw );
			$years[ $raw ] = ! empty( $date ) ? date( 'Y', strtotime( $date ) ) : null;
		}

		$delivery_date = $years[ $raw ];
		if ( ! empty( $delivery_date ) && ! isset( $delivery_dates[ $delivery_date ] ) && $delivery_date >= $current_year ) {
			$delivery_dates[ $delivery_date ] = array(
				'value' => $delivery_date,
				'label' => $delivery_date,
			);
		}
	}

	sort( $delivery_dates );
	$delivery_dates = array_merge(
		array(
			array(
				'value' => 'all',
				'label' => __( 'Any year', 'east-property' ),
			),
			array(
				'value' => 'available_immediately',
				'label' => __( 'Available', 'east-property' ),
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
					'label' => 'Any',
				),
			),
		),
		'developer'     => array(
			'label'   => __( 'Developer', 'east-property' ),
			'options' => get_developers_list(),
		),
	);

	$all_units_types = get_field_object( 'field_694ea57c4ae1f' );
	if ( ! empty( $all_units_types ) ) {
		foreach ( $all_units_types['choices'] as $choice_value => $choice_label ) {
			$search_tabs_data['filters']['property_type']['options'][] = array(
				'value' => (string) $choice_value,
				'label' => (string) $choice_label,
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
 * First stored value of one meta key for many posts, in one query.
 *
 * The first row by meta_id, which is the row get_post_meta( $id, $key, true )
 * returns.
 *
 * @param int[]  $post_ids
 * @param string $meta_key
 *
 * @return array<int, string> post id => value; posts without the key are absent
 */
function core_first_meta_values( array $post_ids, string $meta_key ): array {
	global $wpdb;

	if ( empty( $post_ids ) ) {
		return array();
	}

	$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
	$rows         = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT post_id, meta_value FROM {$wpdb->postmeta}
				WHERE meta_key = %s AND post_id IN ($placeholders)
				ORDER BY meta_id ASC",
			array_merge( array( $meta_key ), array_map( 'intval', $post_ids ) )
		)
	);

	$values = array();
	foreach ( $rows as $row ) {
		$post_id = (int) $row->post_id;
		if ( ! isset( $values[ $post_id ] ) ) {
			$values[ $post_id ] = (string) $row->meta_value;
		}
	}

	return $values;
}

/**
 * What Property::get_delivery_date() returns, from the stored meta value.
 *
 * get_field() formats a date picker value with acf_format_date() and the
 * field's return format (Ymd), and get_delivery_date() then formats that with
 * the site's date format.
 *
 * @param string $raw
 *
 * @return string
 */
function core_property_delivery_date( string $raw ): string {
	if ( '' === $raw ) {
		return '';
	}

	$date = function_exists( 'acf_format_date' ) ? acf_format_date( $raw, 'Ymd' ) : $raw;
	if ( empty( $date ) ) {
		return '';
	}

	return date_i18n( get_option( 'date_format' ), strtotime( $date ) );
}

/**
 * Average unit price of many properties at once.
 *
 * Property::get_price() answers for one property with a units query and an
 * entity per unit - across the catalogue that was one query per property and
 * 7.9 s. This reads the same numbers in two queries, under the same rules:
 *
 *  - a unit belongs to a property when its 'property' meta holds the
 *    property's id or the id of one of its translations;
 *  - the units come from get_posts(), so Polylang keeps them to the current
 *    language;
 *  - a unit's price is its first 'price' value cast to int, zero when missing,
 *    and such a unit still counts towards the average;
 *  - the average is rounded.
 *
 * @param int[] $property_ids
 *
 * @return array<int, int> property id => average price; no units, no entry
 */
function core_property_average_prices( array $property_ids ): array {
	global $wpdb;

	$unit_ids = get_posts(
		array(
			'post_type'      => 'unit',
			'posts_per_page' => - 1,
			'fields'         => 'ids',
		)
	);

	if ( empty( $unit_ids ) || empty( $property_ids ) ) {
		return array();
	}

	$placeholders = implode( ',', array_fill( 0, count( $unit_ids ), '%d' ) );
	$rows         = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta}
				WHERE meta_key IN ( 'property', 'price' ) AND post_id IN ($placeholders)
				ORDER BY meta_id ASC",
			array_map( 'intval', $unit_ids )
		)
	);

	$units_by_target = array();
	$unit_prices     = array();
	foreach ( $rows as $row ) {
		$unit_id = (int) $row->post_id;
		if ( 'price' === $row->meta_key ) {
			if ( ! isset( $unit_prices[ $unit_id ] ) ) {
				$unit_prices[ $unit_id ] = (int) $row->meta_value;
			}
			continue;
		}

		$units_by_target[ (string) $row->meta_value ][ $unit_id ] = true;
	}

	// pll_get_post_translations() reads a term per post; load them together.
	if ( function_exists( 'pll_get_post_translations' ) ) {
		update_object_term_cache( $property_ids, 'property' );
	}

	$averages = array();
	foreach ( $property_ids as $property_id ) {
		$linked_ids = array( (int) $property_id );
		if ( function_exists( 'pll_get_post_translations' ) ) {
			foreach ( pll_get_post_translations( $property_id ) as $translation ) {
				$linked_ids[] = (int) $translation;
			}
		}

		$units = array();
		foreach ( array_unique( array_filter( $linked_ids ) ) as $linked_id ) {
			$units += $units_by_target[ (string) $linked_id ] ?? array();
		}

		if ( empty( $units ) ) {
			continue;
		}

		$sum = 0;
		foreach ( array_keys( $units ) as $unit_id ) {
			$sum += $unit_prices[ $unit_id ] ?? 0;
		}

		$averages[ (int) $property_id ] = (int) round( $sum / count( $units ) );
	}

	return $averages;
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
	$developers = get_posts(
		array(
			'post_type'      => 'developers',
			'posts_per_page' => - 1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	$results = array(
		array(
			'value' => 'all',
			'label' => __( 'Any Developers', 'east-property' ),
		),
	);
	/*
	 * Developer::get_properties_count() for every developer in one query rather
	 * than one each: the same language-filtered properties, counted per
	 * 'developer_rel' value.
	 */
	global $wpdb;

	$counts       = array();
	$property_ids = get_posts(
		array(
			'post_type'      => 'property',
			'posts_per_page' => - 1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		)
	);
	if ( ! empty( $property_ids ) ) {
		$placeholders = implode( ',', array_fill( 0, count( $property_ids ), '%d' ) );
		$counts       = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_value, COUNT(DISTINCT post_id) AS properties FROM {$wpdb->postmeta}
					WHERE meta_key = 'developer_rel' AND post_id IN ($placeholders)
					GROUP BY meta_value",
				array_map( 'intval', $property_ids )
			),
			OBJECT_K
		);
	}

	foreach ( $developers as $dev ) {
		$developer      = new Developer( $dev );
		$projects_count = (int) ( $counts[ (string) $developer->get_id() ]->properties ?? 0 );
		if ( 0 === $projects_count ) {
			continue;
		}

		$results[] = array(
			'value' => (string) $developer->get_id(),
			'label' => (string) $developer->get_title(),
		);
	}

	return $results;
}

/**
 * Get a list of all parent 0 categories instead of uncategorized
 */
function get_locations(): array {
	global $wpdb;

	$selected_location = $_REQUEST['location'] ?? null;

	$results = array(
		array(
			'value' => 'all',
			'label' => __( 'Any Locations', 'east-property' ),
		),
	);

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
			WHERE u.post_type = 'unit'
			  AND u.post_status = 'publish'
			ORDER BY t.name ASC;";

	$locations = $wpdb->get_results( $sql, ARRAY_A );
	if ( empty( $locations ) ) {
		return $results;
	}

	foreach ( $locations as $location ) {
		$results[] = array(
			'value'    => $location['slug'],
			'label'    => $location['name'],
			'selected' => $location['slug'] === $selected_location,
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
 * Retire the cached listings after the catalogue changed.
 *
 * properties_* and units_* are keyed by the visitor's filters, so their keys
 * cannot be enumerated — behind an external object cache they cannot even be
 * scanned. Bumping the version that build_filters_hash() mixes in retires them
 * all at once, on any backend. The fixed key caches are deleted outright.
 *
 * @return void
 */
function core_flush_listing_caches(): void {
	//TODO Recheck after cloude
	return;
	update_option( 'core_listings_cache_version', core_get_listings_cache_version() + 1, false );

	$keys = array(
		'all_properties_specifications',
		'properties_by_count_of_units',
		'units_count_by_locations',
	);

	$listing_types = array_merge(
		array( '', 'all' ),
		function_exists( 'core_get_listing_type_choices' ) ? array_keys( core_get_listing_type_choices() ) : array()
	);

	$languages   = function_exists( 'pll_languages_list' ) ? (array) pll_languages_list() : array();
	$languages[] = '';

	foreach ( array( 'property', 'unit' ) as $post_type ) {
		$keys[] = 'search_tabs_data_' . $post_type;
		foreach ( $listing_types as $listing_type ) {
			$base = 'search_tabs_data_' . $post_type . '_' . $listing_type;

			foreach ( $languages as $language ) {
				$keys[] = $base . ( '' === $language ? '' : '_' . $language );
			}
		}
	}

	foreach ( $listing_types as $listing_type ) {
		foreach ( $languages as $language ) {
			// Mirrors the key built in get_units_count_by_bedrooms().
			$keys[] = 'units_count_by_bedrooms_' . md5( $listing_type . '|' . $language );
		}
	}

	// Mirrors the key built in get_properties_by_count_of_units(). Listing them
	// explicitly matters on production: the LIKE sweep below only runs without
	// an external object cache, and production has Redis, so the explicit list
	// is the only thing that clears anything there.
	foreach ( $languages as $language ) {
		$keys[] = 'properties_by_count_of_units' . ( '' === $language ? '' : '_' . $language );
	}

	foreach ( array_unique( $keys ) as $key ) {
		delete_transient( $key );
	}

	// The retired entries would otherwise sit in the options table until they
	// expire. An external object cache evicts on its own.
	if ( ! wp_using_ext_object_cache() ) {
		global $wpdb;

		foreach ( array( 'properties_', 'units_' ) as $prefix ) {
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
					$wpdb->esc_like( '_transient_' . $prefix ) . '%',
					$wpdb->esc_like( '_transient_timeout_' . $prefix ) . '%'
				)
			);
		}
	}
}

/**
 * Build hash for filters request to understand is filters changed or not
 *
 * @param array $request
 *
 * @return string
 */
function build_filters_hash( array $request, array $args = array() ): string {
	unset(
		$request['_wpnonce'],
		$request['_wp_http_referer'],
		$request['paged'],
		$request['page']
	);

	$request = array_merge( $request, $args );

	// Retiring the whole generation of cached listings is a matter of bumping
	// this: the keys depend on the visitor's filters and cannot be enumerated.
	$request['__cache_version'] = core_get_listings_cache_version();

	$normalize = function ( &$array ) use ( &$normalize ) {
		if ( ! is_array( $array ) ) {
			return;
		}

		ksort( $array );

		foreach ( $array as &$value ) {
			if ( is_array( $value ) ) {
				$normalize( $value );
			}
		}
	};

	$normalize( $request );

	$json = wp_json_encode( $request );

	return md5( $json );
}

/**
 * Get updated filters and list of products
 */
function ajax_get_property(): void {
	//check_ajax_referer( 'filterjdj3' ); //TODO Check, maybe javascript was cached it

	$posts_per_page = PROPERTIES_PER_PAGE ?? 20;
	$properties     = get_properties( $posts_per_page );

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

	$map_properties = get_map_properties_json( $properties['items'] ?? array(), true );
	$total_found    = $properties['total'] ?? count( $properties );

	wp_send_json_success(
		array(
			'properties'       => $properties_html,
			'map_properties'   => $map_properties,
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
 */
function get_map_properties_json( array $properties, bool $skip_empty = false ): string {
	$properties_json = array();
	foreach ( $properties as $property ) {
		$units_available = $property->get_units_count();
		if ( $skip_empty && 0 === $units_available ) {
			continue;
		}

		$properties_json[] = array(
			'id'              => $property->get_id(),
			'name'            => $property->get_title(),
			'url'             => $property->get_url(),
			'units_available' => $units_available,
			'longitude'       => $property?->get_longitude() ?? '',
			'latitude'        => $property?->get_latitude() ?? '',
		);
	}

	return json_encode( $properties_json );
}

add_action( 'wp_ajax_nopriv_get_property', 'ajax_get_property' );
add_action( 'wp_ajax_get_property', 'ajax_get_property' );

/**
 * Get units list for search results
 */
function ajax_get_unit(): void {
	//check_ajax_referer( 'get_filtered_properties' ); //TODO Check, maybe javascript was cached it

	$posts_per_page = PROPERTIES_PER_PAGE ?? 20;
	$units          = get_units( $posts_per_page );

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
