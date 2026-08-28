<?php
/**
 * Thumbs slider
 */

$gallery  = $args['gallery'] ?? array();
$template = $args['template'] ?? 'unit-thumbs-slider';

if ( empty( $gallery ) ) {
	return;
}

get_component_template(
	'sliders/' . $template,
	array(
		'gallery' => $gallery,
	)
);
