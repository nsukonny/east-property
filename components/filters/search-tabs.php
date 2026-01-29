<?php
/**
 * Filters for search and filter properties on listing pages
 */

$form_action      = $args['form_action'] ?? home_url( '/units/' );
$search_tabs_data = get_search_tabs_data( 'unit' );

get_template_part( 'template-parts/filters/search-tabs', null,
	array(
		'class'            => 'hero-tabs',
		'search_tabs_data' => $search_tabs_data,
		'form_action'      => $form_action,
	)
);
