<?php
/**
 * Component fo draw map
 */

$mode              = $args['mode'] ?? ''; //'single' for single property
$property          = $args['property'] ?? '';
$properties        = $args['properties'] ?? array();
$show_sidebar      = $args['show_sidebar'] ?? 'false';
$class             = $args['class'] ?? '';
$search_by_address = $args['search_by_address'] ?? false;

get_component_template( 'properties/map',
	array(
		'mode'              => $mode,
		'property'          => $property,
		'properties'        => $properties,
		'show_sidebar'      => $show_sidebar,
		'class'             => $class,
		'search_by_address' => $search_by_address,
	)
);
