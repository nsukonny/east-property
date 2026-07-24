<?php

/**
 * * Template Name: Properties Listing Page
 */
get_header( null, array( 'color' => 'sand' ) );

get_template_part( 'core/components/properties/filter',
	null,
	array(
		'search_by' => array(
			'title'         => false,
			'location'      => true,
			'available'     => true,
			'price'         => false,
			'beds'          => false,
			'baths'         => false,
			'property_type' => false,
			'developer'     => true,
			'max_area'      => false,
		),
	)
);
get_template_part( 'template-parts/sections/index/about' );

get_footer();

