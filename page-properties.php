<?php
/**
 * * Template Name: Properties Listing Page
 */
get_header( null, array( 'color' => 'sand' ) );

get_template_part( 'core/components/properties/filter', null,
	array(
		'search_by' => array(
			'title'         => false,
			'location'      => true,
			'available'     => true,
			'price'         => true,
			'beds'          => true,
			'baths'         => true,
			'property_type' => true,
			'developer'     => true,
			'max_area'      => true,
		),
	)
);
get_template_part( 'template-parts/sections/index/about' );

get_footer();

