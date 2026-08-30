<?php
/**
 * Search system for properties
 *
 * @var \Entities\Property $property
 */

$search_by     = $args['search_by'] ?? array();
$card_template = $args['card_template'] ?? 'large-card';

$posts_per_page = PROPERTIES_PER_PAGE ?? 20;
$properties     = get_properties( $posts_per_page );

get_component_template( 'search-results/filters',
	array(
		'h2'         => __( 'Buy properties in UAE' , 'east-property' ),
		'properties' => $properties,
		'search_by'  => $search_by,
	)
);

get_component_template( 'search-results/properties-list',
	array(
		'h2'            => $properties['total'] . ' ' . __( 'projects found' , 'east-property' ),
		'card_template' => $card_template,
		'properties'    => $properties,
	)
);