<?php

/**
 * Unit card component
 *
 * @var Entities\Unit $unit
 */

$unit = $args['unit'] ?? null;
if ( $unit === null || ! $unit->exists() ) {
	return;
}

$template = $args['template'] ?? 'unit-card';

$property      = $unit->get_property();
$property_name = ! empty( $property ) ? $property->get_title() : '';
$property_url  = ! empty( $property ) ? $property->get_url() : '';
$labels        = $unit->get_labels();
$image         = $unit->get_thumb( 'featured-card' );
$price         = $unit->get_price_html();
$title         = $unit->get_title();
$amenities     = $unit->get_amenities();

get_template_part( 'template-parts/cards/' . $template, null,
	array(
		'labels'        => $labels,
		'image'         => $image,
		'price'         => $price,
		'title'         => $title,
		'property_name' => $property_name,
		'property_url'  => $property_url,
		'amenities'     => $amenities,
	)
);