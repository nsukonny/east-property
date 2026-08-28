<?php

use Entities\Property;
use Entities\Unit;

/**
 * Get grouped data for filters
 *
 * @param string $post_type unit or properties
 * @param string $listing_type off-plan or secondary
 */
function get_search_tabs_data( string $post_type = 'property', string $listing_type = 'off-plan' ): array {
	$search_tabs_data = ! IS_DEV ? get_transient( 'search_tabs_data_' . $post_type . '_' . $listing_type ) : false;
	if ( ! empty( $search_tabs_data ) ) {
		$search_tabs_data['filters']['beds'] = get_filter_beds_options();

		return $search_tabs_data;
	}

	if ( 'property' === $post_type ) {
		$search_tabs_data = get_properties_search_tabs_data();

		set_transient( 'search_tabs_data_' . $post_type . '_' . $listing_type, $search_tabs_data, DAY_IN_SECONDS );

		return $search_tabs_data;
	}

	$units_posts = get_posts(
		array(
			'post_type'      => $post_type,
			'posts_per_page' => - 1,
			'post_status'    => 'publish',
		)
	);

	if ( empty( $units_posts ) ) {
		return array();
	}

	$area_min       = null;
	$area_max       = null;
	$developers     = array();
	$delivery_dates = array();
	$price_min      = null;
	$price_max      = null;
	$current_year   = date( 'Y' );

	$selected_location = $_REQUEST['location'] ?? null;

	foreach ( $units_posts as $unit_post ) {
		$unit     = new Unit( $unit_post );
		$property = $unit->get_property();
		if ( null === $property ) {
			continue;
		}

		$developer = $property->get_developer();
		if ( null !== $developer && ! isset( $developers[ $developer->get_id() ] ) ) {
			$developers[ $developer->get_id() ] = array(
				'value' => (string) $developer->get_id(),
				'label' => html_entity_decode( $developer->get_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
			);
		}

		$price = $unit->get_price();
		if ( ! empty( $price ) && ( null === $price_min || $price < $price_min ) ) {
			$price_min = $price;
		}
		if ( ! empty( $price ) && ( null === $price_max || $price > $price_max ) ) {
			$price_max = $price;
		}

		$delivery_date = $property->get_delivery_date();
		$delivery_date = ! empty( $delivery_date ) ? date( 'Y', strtotime( $delivery_date ) ) : null;
		$is_off_plan   = ( 'off-plan' === $listing_type && $delivery_date > $current_year );
		$is_secondary  = ( 'secondary' === $listing_type && $delivery_date <= $current_year );
		$is_all        = ( 'all' === $listing_type && $delivery_date >= $current_year );
		// A distress deal can be off-plan or ready, so every year is on the table.
		$is_distress = ( 'distress' === $listing_type );
		if ( ! empty( $delivery_date )
		     && ! isset( $delivery_dates[ $delivery_date ] )
		     && ( $is_off_plan || $is_secondary || $is_all || $is_distress )
		) {
			$delivery_dates[ $delivery_date ] = array(
				'value' => $delivery_date,
				'label' => $delivery_date,
			);
		}

		$area = $unit->get_area();
		if ( ! empty( $area ) && ( null === $area_min || $area < $area_min ) ) {
			$area_min = $area;
		}
		if ( ! empty( $area ) && ( null === $area_max || $area > $area_max ) ) {
			$area_max = $area;
		}
	}

	usort(
		$developers,
		static function ( array $a, array $b ): int {
			return strcasecmp( $a['label'], $b['label'] );
		}
	);
	$developers = array_merge(
		array(
			array(
				'value' => 'all',
				'label' => __( 'Any Developers', 'east-property' ),
			),
		),
		$developers
	);

	if ( 'off-plan' === $listing_type ) {
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
	set_transient( 'search_tabs_data_' . $post_type, $search_tabs_data, DAY_IN_SECONDS );

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
	$properties = get_posts(
		array(
			'post_type'      => 'property',
			'posts_per_page' => - 1,
			'post_status'    => 'publish',
		)
	);

	if ( empty( $properties ) ) {
		return array();
	}

	$developers        = array();
	$delivery_dates    = array();
	$price_min         = null;
	$price_max         = null;
	$delivery_date_max = null;
	$current_year      = date( 'Y' );

	foreach ( $properties as $property_post ) {
		$property  = new Entities\Property( $property_post );
		$developer = $property->get_developer();
		if ( null !== $developer && ! isset( $developers[ $developer->get_id() ] ) ) {
			$developers[ $developer->get_id() ] = array(
				'value' => $developer->get_id(),
				'label' => $developer->get_title(),
			);
		}

		$price = $property->get_price();
		if ( ! empty( $price ) && ( null === $price_min || $price < $price_min ) ) {
			$price_min = $price;
		}
		if ( ! empty( $price ) && ( null === $price_max || $price > $price_max ) ) {
			$price_max = $price;
		}

		$delivery_date = $property->get_delivery_date();
		$delivery_date = ! empty( $delivery_date ) ? date( 'Y', strtotime( $delivery_date ) ) : null;
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
	foreach ( $developers as $dev ) {
		$developer      = new Developer( $dev );
		$projects_count = $developer->get_properties_count();
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

	foreach ( array( 'property', 'unit' ) as $post_type ) {
		$keys[] = 'search_tabs_data_' . $post_type;
		foreach ( $listing_types as $listing_type ) {
			$keys[] = 'search_tabs_data_' . $post_type . '_' . $listing_type;
		}
	}

	$languages   = function_exists( 'pll_languages_list' ) ? (array) pll_languages_list() : array();
	$languages[] = '';

	foreach ( $listing_types as $listing_type ) {
		foreach ( $languages as $language ) {
			// Mirrors the key built in get_units_count_by_bedrooms().
			$keys[] = 'units_count_by_bedrooms_' . md5( $listing_type . '|' . $language );
		}
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
