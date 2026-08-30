<?php

/**
 * Register new taxonomy: Locations
 * This taxonomy will be used to categorize units and properties by their location (e.g., city, neighborhood).
 */
add_action( 'init', static function () {
	$labels = [
		'name'          => __( 'Locations' , 'east-property' ),
		'singular_name' => __( 'Location' , 'east-property' ),
		'search_items'  => __( 'Search Locations' , 'east-property' ),
		'all_items'     => __( 'All Locations' , 'east-property' ),
		'edit_item'     => __( 'Edit Location' , 'east-property' ),
		'update_item'   => __( 'Update Location' , 'east-property' ),
		'add_new_item'  => __( 'Add New Location' , 'east-property' ),
		'new_item_name' => __( 'New Location Name' , 'east-property' ),
		'menu_name'     => __( 'Locations' , 'east-property' ),
	];

	register_taxonomy( 'location', [ 'unit', 'property', 'developers' ], [
		'labels'            => $labels,
		'public'            => true,
		'hierarchical'      => true,
		'show_ui'           => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'query_var'         => true,
		'rewrite'           => [
			'slug'       => 'areas',
			'with_front' => false,
		],
	] );
}, 5 );

/**
 * Add opening location on projects page with pagination
 *
 * Old URL Example http://eastproperty.local/properties/business-bay/
 */
add_action( 'init', static function () {
	add_rewrite_rule(
		'^areas/(?!page-[0-9]+/?$)([^/]+)/?$',
		'index.php?location=$matches[1]',
		'top'
	);

	add_rewrite_rule(
		'^projects/(?!page-[0-9]+/?$)([^/]+)/?$',
		'index.php?location=$matches[1]',
		'top'
	);

	add_rewrite_rule(
		'^projects/(?!page-[0-9]+/?$)([^/]+)/page-([0-9]+)/?$',
		'index.php?location=$matches[1]&cur_page=$matches[2]',
		'top'
	);

	//Support old URL
	add_rewrite_rule(
		'^properties/(?!page-[0-9]+/?$)([^/]+)/?$',
		'index.php?taxonomy=location&term=$matches[1]&is_old_location_url=1',
		'top'
	);
	add_rewrite_tag( '%is_old_location_url%', '([^&]+)' );
} );

add_filter( 'query_vars', static function ( $vars ) {
	$vars[] = 'is_old_location_url';

	return $vars;
} );

/**
 * Redirect from old /properties/%location%/ to /projects/?location={location}
 *
 * Old URL Example http://eastproperty.local/properties/business-bay/
 */
add_action( 'template_redirect', static function () {
	if ( ! is_tax( 'location' ) ) {
		return;
	}

	$location_term = get_queried_object();
	if ( ! $location_term || is_wp_error( $location_term ) ) {
		return;
	}
	
	$is_old_location_url = get_query_var( 'is_old_location_url' );
	if ( 1 === (int) $is_old_location_url ) {
		wp_redirect( core_home_url( 'projects/?location=' . $location_term->slug ), 301 );
		exit;
	}
} );
