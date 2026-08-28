<?php
/**
 * Search units
 */

$search_by     = $args['search_by'] ?? array();
$card_template = $args['card_template'] ?? 'large-card';
$listing_type  = $args['listing_type'] ?? '';
$h2            = $args['h2'] ?? __( 'Available Units in UAE' , 'east-property' );

$posts_per_page = PROPERTIES_PER_PAGE ?? 20;
$units          = get_units( $listing_type, $posts_per_page );

get_component_template(
	'search-results/filters',
	array(
		'h2'           => $h2,
		'units'        => $units,
		'search_by'    => $search_by,
		'listing_type' => $listing_type,
	)
);

get_component_template( 'search-results/units-list',
	array(
		'h2'            => $units['total'] . ' ' . __( 'properties found' , 'east-property' ),
		'card_template' => $card_template,
		'units'         => $units,
	)
);
