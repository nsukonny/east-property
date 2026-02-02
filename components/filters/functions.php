<?php
/**
 * Get grouped data for filters
 *
 * @param string $post_type unit or properties
 */
function get_search_tabs_data( string $post_type = 'properties' ): array { //TODO rebuild after run

//	$search_tabs_data = get_transient( 'search_tabs_data_' . $post_type );
//	if ( ! empty( $search_tabs_data ) ) {
//		return $search_tabs_data;
//	}

	if ( 'properties' === $post_type ) {
		$search_tabs_data = get_properties_search_tabs_data();

		set_transient( 'search_tabs_data_' . $post_type, $search_tabs_data, DAY_IN_SECONDS );

		return $search_tabs_data;
	}

	$units = get_posts( array(
		'post_type'      => $post_type,
		'posts_per_page' => - 1,
		'post_status'    => 'publish',
	) );

	if ( empty( $units ) ) {
		return array();
	}

	$area_min          = null;
	$area_max          = null;
	$developers        = array();
	$price_min         = null;
	$price_max         = null;
	$delivery_date_min = null;
	$delivery_date_max = null;

	foreach ( $units as $unit ) {
		$property = get_field( 'property', $unit->ID );
		if ( empty( $property ) ) {
			continue;
		}

		$developer = get_field( 'developer_rel', $property->ID );
		if ( ! empty( $developer ) && ! in_array( $developer, $developers, true ) ) {
			$developers[] = array(
				'value' => (string) $developer->ID,
				'label' => (string) $developer->post_title,
			);
		}

		$price = get_field( 'price', $unit->ID );
		if ( ! empty( $price ) && ( null === $price_min || $price < $price_min ) ) {
			$price_min = $price;
		}
		if ( ! empty( $price ) && ( null === $price_max || $price > $price_max ) ) {
			$price_max = $price;
		}

		$delivery_date = get_field( 'delivery_date', $property->ID );
		$delivery_date = ! empty( $delivery_date ) ? date( 'Y', strtotime( $delivery_date ) ) : null;
		if ( ! empty( $delivery_date ) && ( null === $delivery_date_min || $delivery_date_min > $delivery_date ) ) {
			$delivery_date_min = $delivery_date;
		}
		if ( ! empty( $delivery_date ) && ( null === $delivery_date_max || $delivery_date_max < $delivery_date ) ) {
			$delivery_date_max = $delivery_date;
		}

		$area = (int) get_field( 'area_size', $unit->ID );
		if ( ! empty( $area ) && ( null === $area_min || $area < $area_min ) ) {
			$area_min = $area;
		}
		if ( ! empty( $area ) && ( null === $area_max || $area > $area_max ) ) {
			$area_max = $area;
		}
	}

	$search_tabs_data            = array();
	$search_tabs_data['filters'] = array(
		'location'      => array(
			'label'   => __( 'Location', 'east' ),
			'options' => get_locations(),
		),
		'available'     => array(
			'label'   => __( 'Available', 'east' ),
			'options' => get_range_steps( $delivery_date_min, $delivery_date_max ),
		),
		'price'         => array(
			'label'   => __( 'Max price, AED', 'east' ),
			'options' => get_range_steps( $price_min, $price_max, 6, true, 100000 ),
		),
		'property_type' => array(
			'label'   => __( 'Property Type', 'east' ),
			'options' => array( array( 'value' => 'all', 'label' => 'All' ) ),
		),
		'developer'     => array(
			'label'   => __( 'Developer', 'east' ),
			'options' => get_developers_list(),
		),
		'area'          => array(
			'label'   => __( 'Area', 'east' ),
			'options' => get_range_steps( $area_min, $area_max ),
		),
		'beds'          => array(
			'label'   => __( 'Beds', 'east' ),
			'options' => array(
				array( 'value' => 'studio', 'label' => 'Studio' ),
				array( 'value' => '1', 'label' => '1', 'active' => true ),
				array( 'value' => '2', 'label' => '2', 'active' => true ),
				array( 'value' => '3', 'label' => '3' ),
				array( 'value' => '4', 'label' => '4' ),
				array( 'value' => '5', 'label' => '5' ),
				array( 'value' => '6', 'label' => '6' ),
				array( 'value' => '7', 'label' => '7+' ),
			),
		),
		'baths'         => array(
			'label'   => __( 'Baths', 'east' ),
			'options' => array(
				array( 'value' => '1', 'label' => '1' ),
				array( 'value' => '2', 'label' => '2', 'active' => true ),
				array( 'value' => '3', 'label' => '3' ),
				array( 'value' => '4', 'label' => '4' ),
				array( 'value' => '5', 'label' => '5+' ),
			),
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
			'label'    => __( 'All', 'east' ),
			'defaults' => array(
				'available' => $delivery_date_max,
				'price'     => $price_max,
			),
		),
		array(
			'slug'     => 'apartment',
			'label'    => __( 'Apartments', 'east' ),
			'defaults' => array(
				'available' => $delivery_date_max,
				'price'     => $price_max,
			),
		),
		array(
			'slug'     => 'house',
			'label'    => __( 'Houses', 'east' ),
			'defaults' => array(
				'available' => $delivery_date_max,
				'price'     => $price_max,
			),
		),
		array(
			'slug'     => 'villa',
			'label'    => __( 'Villas', 'east' ),
			'defaults' => array(
				'available' => $delivery_date_max,
				'price'     => $price_max,
			),
		),
		array(
			'slug'     => 'office',
			'label'    => __( 'Offices', 'east' ),
			'defaults' => array(
				'available' => $delivery_date_max,
				'price'     => $price_max,
			),
		),
	);

	set_transient( 'search_tabs_data_' . $post_type, $search_tabs_data, DAY_IN_SECONDS );

	return $search_tabs_data;
}

/**
 * Temp solution for get filters for properties //TODO rebuild after run
 *
 * @return array
 */
function get_properties_search_tabs_data(): array {

	$properties = get_posts( array(
		'post_type'      => 'properties',
		'posts_per_page' => - 1,
		'post_status'    => 'publish',
	) );

	if ( empty( $properties ) ) {
		return array();
	}

	$area_min          = null;
	$area_max          = null;
	$developers        = array();
	$price_min         = null;
	$price_max         = null;
	$delivery_date_min = null;
	$delivery_date_max = null;

	foreach ( $properties as $property_post ) {
		$property  = new \Entities\Property( $property_post );
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
		if ( ! empty( $delivery_date ) && ( null === $delivery_date_min || $delivery_date_min > $delivery_date ) ) {
			$delivery_date_min = $delivery_date;
		}
		if ( ! empty( $delivery_date ) && ( null === $delivery_date_max || $delivery_date_max < $delivery_date ) ) {
			$delivery_date_max = $delivery_date;
		}
	}

	$search_tabs_data            = array();
	$search_tabs_data['filters'] = array(
		'location'      => array(
			'label'   => __( 'Location', 'east' ),
			'options' => get_locations(),
		),
		'available'     => array(
			'label'   => __( 'Available', 'east' ),
			'options' => get_range_steps( $delivery_date_min, $delivery_date_max ),
		),
		'price'         => array(
			'label'   => __( 'Max price, AED', 'east' ),
			'options' => get_range_steps( $price_min, $price_max, 6, true, 100000 ),
		),
		'property_type' => array(
			'label'   => __( 'Property Type', 'east' ),
			'options' => array( array( 'value' => 'all', 'label' => 'All' ) ),
		),
		'developer'     => array(
			'label'   => __( 'Developer', 'east' ),
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
			'label'    => __( 'All', 'east' ),
			'defaults' => array(
				'available' => $delivery_date_max,
				'price'     => $price_max,
			),
		),
		array(
			'slug'     => 'apartment',
			'label'    => __( 'Apartments', 'east' ),
			'defaults' => array(
				'available' => $delivery_date_max,
				'price'     => $price_max,
			),
		),
		array(
			'slug'     => 'house',
			'label'    => __( 'Houses', 'east' ),
			'defaults' => array(
				'available' => $delivery_date_max,
				'price'     => $price_max,
			),
		),
		array(
			'slug'     => 'villa',
			'label'    => __( 'Villas', 'east' ),
			'defaults' => array(
				'available' => $delivery_date_max,
				'price'     => $price_max,
			),
		),
		array(
			'slug'     => 'office',
			'label'    => __( 'Offices', 'east' ),
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

	$options = array( array( 'value' => 'all', 'label' => 'All' ) );

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
	$developers = get_posts( array(
		'post_type'      => 'developers',
		'posts_per_page' => - 1,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );

	$results = array( array( 'value' => 'all', 'label' => __( 'All Developers' ) ) );
	foreach ( $developers as $developer ) {
		$results[] = array(
			'value' => (string) $developer->ID,
			'label' => (string) $developer->post_title,
		);
	}

	return $results;
}

/**
 * Get list of all parent 0 categories instead of uncategorized
 *
 */
function get_locations(): array {
	$locations = get_terms( array(
		'taxonomy'   => 'category',
		'hide_empty' => true,
		//'parent'     => 0,
	) );

	$results = array( array( 'value' => 'all', 'label' => __( 'All Locations' ) ) );
	foreach ( $locations as $location ) {
		$results[] = array(
			'value' => $location->slug,
			'label' => $location->name,
		);
	}

	return $results;
}

/**
 * Get updated filters and list of products
 */
function ajax_get_properties(): void {
	check_ajax_referer( 'get_filtered_properties' ); // 2-й параметр = имя поля

	$properties = get_properties();
	$cards      = array();
	foreach ( $properties as $property ) {
		$cards[] = array(
			'title'             => $property->get_title() . ' updated',
			'price'             => $property->get_price_html(),
			'location'          => $property->get_location(),
			'gallery'           => $property->get_gallery(),
			'labels'            => $property->get_labels(),
			'apartments_params' => $property->get_apartments_params(),
			'url'               => $property->get_url(),
		);
	}

	ob_start();

	if ( ! empty( $cards ) ) {
		foreach ( $cards as $card ) {
			get_template_part( 'template-parts/cards/large-card', null,
				array(
					'title'     => $card['title'],
					'price'     => $card['price'],
					'location'  => $card['location'],
					'gallery'   => $card['gallery'],
					'labels'    => $card['labels'] ?? array(),
					'amenities' => $card['amenities'] ?? array(),
					'url'       => $card['url'],
				)
			);
		}
	} else {
		_e( 'Items not found' );
	}

	$properties_html = ob_get_clean();

	ob_start();

	if ( ! empty( $cards ) ) {
		foreach ( $cards as $card ) {
			get_template_part( 'template-parts/cards/small-card', null,
				array(
					'title'     => $card['title'],
					'price'     => $card['price'],
					'location'  => $card['location'],
					'gallery'   => $card['gallery'],
					'labels'    => $card['labels'] ?? array(),
					'amenities' => $card['amenities'] ?? array(),
					'url'       => $card['url'],
				)
			);
		}
	}

	$map_properties = ob_get_clean();

	wp_send_json_success(
		array(
			'properties'       => $properties_html,
			'map_properties'   => $map_properties,
			'properties_found' => count( $cards ) . ' ' . __( 'properties found' ),
		)
	);
}

add_action( 'wp_ajax_nopriv_get_properties', 'ajax_get_properties' );
add_action( 'wp_ajax_get_properties', 'ajax_get_properties' );

/**
 * Get units list for search results
 */
function ajax_get_unit(): void {
	check_ajax_referer( 'get_filtered_properties' ); // 2-й параметр = имя поля

	$units = get_units();
	$cards = array();
	foreach ( $units as $unit ) {
		$property  = $unit->get_property();
		$developer = $unit->get_developer();

		$cards[] = array(
			'title'          => $unit->get_title(),
			'price'          => $unit->get_price_html(),
			'property_name'  => $property !== null ? $property->get_title() : '',
			'property_url'   => $property !== null ? $property->get_url() : '',
			'developer_name' => $developer ? $developer->get_title() : '',
			'gallery'        => $unit->get_gallery(),
			'labels'         => $unit->get_labels(),
			'amenities'      => $unit->get_amenities(),
			'url'            => $unit->get_url(),
		);
	}

	ob_start();

	if ( ! empty( $cards ) ) {
		foreach ( $cards as $card ) {
			get_template_part( 'template-parts/cards/unit-card', null,
				array(
					'title'          => $card['title'],
					'price'          => $card['price'],
					'location'       => $card['location'],
					'gallery'        => $card['gallery'],
					'labels'         => $card['labels'] ?? array(),
					'amenities'      => $card['amenities'] ?? array(),
					'property_name'  => $card['property_name'] ?? '',
					'property_url'   => $card['property_url'] ?? '',
					'developer_name' => $card['developer_name'] ?? '',
					'url'            => $card['url'],
				)
			);
		}
	} else {
		_e( 'Properties not found' );
	}

	$properties_html = ob_get_clean();

	ob_start();

	if ( ! empty( $cards ) ) {
		foreach ( $cards as $card ) {
			get_template_part( 'template-parts/cards/small-card', null,
				array(
					'title'     => $card['title'],
					'price'     => $card['price'],
					'location'  => $card['location'],
					'gallery'   => $card['gallery'],
					'labels'    => $card['labels'] ?? array(),
					'amenities' => $card['amenities'] ?? array(),
					'url'       => $card['url'],
				)
			);
		}
	}

	$map_properties = ob_get_clean();

	wp_send_json_success(
		array(
			'properties'       => $properties_html,
			'map_properties'   => $map_properties,
			'properties_found' => count( $cards ) . ' ' . __( 'properties found' ),
		)
	);
}

add_action( 'wp_ajax_nopriv_get_unit', 'ajax_get_unit' );
add_action( 'wp_ajax_get_unit', 'ajax_get_unit' );
