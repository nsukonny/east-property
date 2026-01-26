<?php
/**
 * Search system for properties
 *
 * @var \Entities\Property $property
 */

$properties = get_properties();
$cards      = array();
foreach ( $properties as $property ) {
	$cards[] = array(
		'title'             => $property->get_title(),
		'price'             => $property->get_price_html(),
		'location'          => $property->get_location(),
		'gallery'           => $property->get_gallery(),
		'labels'            => $property->get_labels(),
		'apartments_params' => $property->get_apartments_params(),
		'url'               => $property->get_url(),
	);
}

get_template_part( 'template-parts/sections/search-results/filters', null,
	array(
		'h2'         => __( 'Hidden title for seo' ),
		'properties' => $properties,
	)
);

get_template_part( 'template-parts/sections/search-results/tabs', null,
	array(
		'h2'    => count( $properties ) . ' ' . __( 'properties found' ),
		'cards' => $cards,
	)
);
