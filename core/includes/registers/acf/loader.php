<?php

add_filter( 'acf/settings/save_json', function ( $path ) {
	return THEME_PATH . '/acf-json';
} );

add_filter( 'acf/settings/load_json', function ( $paths ) {

	$paths[] = THEME_PATH . '/acf-json';

	return $paths;
} );