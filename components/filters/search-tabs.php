<?php
/**
 * Filters for search and filter properties on listing pages
 */

get_template_part( 'template-parts/filters/search-tabs', null,
	array(
		'class'            => 'hero-tabs',
		'search_tabs_data' => get_search_tabs_data(),
	)
);
