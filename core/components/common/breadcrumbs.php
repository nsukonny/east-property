<?php
/**
 * Breadcrumb component
 *
 * @var array $args
 */

if ( is_front_page() ) {
	return;
}

$force_links = $args['force_links'] ?? array();
$type        = $args['type'] ?? '';

if ( is_archive() ) {
	$current_page_title = post_type_archive_title( '', false );
} else {
	$current_page_title = get_the_title();
}

$links = $force_links ?: get_core_breadcrumbs_links();
$links = apply_filters( 'breadcrumb_links', $links );

get_component_template( 'common/breadcrumbs', array(
	'links' => $links,
	'type'  => $type,
) );