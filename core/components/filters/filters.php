<?php
/**
 * Filters for search and filter properties on listing pages
 */

$template     = $args['template'] ?? 'search-tabs';
$class        = $args['class'] ?? '';
$filters_data = get_filters_data();

get_template_part( 'template-parts/filters/' . $template, null,
	array(
		'class'        => $class,
		'filters_data' => $filters_data,
	)
);
