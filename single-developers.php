<?php
/**
 * Single template for Properties (CPT: properties)
 */

use Entities\Unit;

get_header( null, array( 'color' => 'sand' ) );

while ( have_posts() ) {
	the_post();

	$developer            = new Developer( get_the_ID() );
	$developer_properties = $developer->get_properties();

	$current_page = pagination_get_current_page() ?? 1;
	$limit        = PROPERTIES_PER_PAGE;
	$offset       = $current_page * $limit - $limit;
	$properties   = array(
		'items' => array_slice( $developer_properties, $offset, $limit ),
		'total' => count( $developer_properties ),
	);

	get_component_template(
		'search-results/filters',
		array(
			'h2'              => __( 'Buy properties from developer' ) . ' ' . $developer->get_title(),
			'properties'      => $properties,
			'search_by'       => array(
				'title'         => false,
				'location'      => true,
				'available'     => true,
				'price'         => true,
				'beds'          => false,
				'baths'         => false,
				'property_type' => false,
				'developer'     => false,
				'max_area'      => false,
			),
			'default_filters' => array(
				'developer' => $developer->get_id(),
			),
		)
	);

	get_component_template(
		'search-results/properties-list',
		array(
			'h2'            => $developer->get_title() . ': <span>' . $properties['total'] . ' ' . __( 'projects found' ) . '</span>',
			'description'   => get_the_content(),
			'card_template' => 'large-card',
			'properties'    => $properties,
		)
	);
}

get_footer();
