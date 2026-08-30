<?php
/**
 * Filters for search and filter properties on listing pages
 */

$form_action     = $args['form_action'] ?? core_home_url( '/projects/' );
$items_post_type = $args['post_type'] ?? 'unit';

$search_tabs_data = get_search_tabs_data( $items_post_type );

get_component_template(
	'filters/search-tabs',
	array(
		'class'            => 'hero-tabs',
		'search_tabs_data' => $search_tabs_data,
		'form_action'      => $form_action,
		'show_tabs'        => $args['show_tabs'] ?? true,
	)
);
