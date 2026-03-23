<?php
/**
 * Template Name: Units Listing Page
 */
get_header( null, array( 'color' => 'sand' ) );

if ( 404 === get_query_var( 'pagename' ) || is_404() ) {
	get_template_part( '404' );

	return;
}

get_template_part( 'core/components/units/filter', null,
	array(
		'search_by' => array(
			'title'         => false,
			'location'      => true,
			'available'     => true,
			'price'         => true,
			'beds'          => true,
			'baths'         => false,
			'property_type' => false,
			'developer'     => false,
			'max_area'      => true,
		),
	)
);

get_template_part( 'template-parts/sections/index/about' );

get_footer();

