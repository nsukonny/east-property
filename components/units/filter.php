<?php
/**
 * Search system for properties
 *
 * @var \Entities\Unit $unit
 */

$h2 = $args['h2'] ?? __( 'Available Units in UAE' );

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

get_template_part( 'template-parts/sections/search-results/filters', null,
	array(
		'h2'    => $h2,
		'units' => $units,
	)
);

get_template_part( 'template-parts/sections/search-results/tabs', null,
	array(
		'h2'            => count( $units ) . ' ' . __( 'properties found' ),
		'cards'         => $cards,
		'card_template' => 'unit-card',
	)
);

//        @@include( 'sections/properties/filters/filters.html' )
//    @@include( 'sections/properties/tabs/tabs.html' )
//
