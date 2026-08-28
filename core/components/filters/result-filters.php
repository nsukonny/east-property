<?php
/**
 * Result filters component
 */

$post_type = $args['post_type'] ?? 'property';
/**
 * search_by supported
 * 'search_by' => array(
 *      'title'         => true,
 *      'location'      => true,
 *      'available'     => true,
 *      'price'         => true,
 *      'beds'          => true,
 *      'baths'         => true,
 *      'property_type' => true,
 *      'developer'     => true,
 *      'max_area'      => true,
 * )
 */
$search_by       = $args['search_by'] ?? array();
$default_filters = $args['default_filters'] ?? array();
$listing_type    = $args['listing_type'] ?? '';

if ( isset( $_GET['property_id'] ) ) {
	$default_filters['property_id'] = $_GET['property_id'];
}

$search_tabs_data = get_search_tabs_data( $post_type, $listing_type );

if ( empty( $search_tabs_data ) ) {
	return;
}

get_component_template(
	'filters/result-filters',
	array(
		'post_type'        => $post_type,
		'search_by'        => $search_by,
		'search_tabs_data' => $search_tabs_data,
		'default_filters'  => $default_filters,
		'listing_type'     => $listing_type,
	)
);
