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

$property       = $unit->get_property();
$property_name  = ! empty( $property ) ? $property->get_title() : '';
$property_url   = ! empty( $property ) ? $property->get_url() : '';
$developer      = $unit->get_developer();
$developer_name = ! empty( $developer ) ? $developer->get_title() : '';
$labels         = $unit->get_labels();
$gallery        = $unit->get_gallery();
$image          = $unit->get_thumb( 'featured-card' );
$price          = $unit->get_price_html();
$has_discount   = $unit->has_discount();
$original_price = $has_discount ? $unit->get_original_price_html() : '';
$discount       = $has_discount ? $unit->get_discount() : '';
$title          = $unit->get_title();
$amenities      = $unit->get_amenities();
$is_author      = 0 !== $unit->get_author_id() && $unit->get_author_id() === get_current_user_id();
$is_draft       = 'draft' === $unit->get_status();

get_component_template(
	'cards/' . $template,
	array(
		'unit_id'        => $unit->get_id(),
		'url'            => ! $is_draft ? $unit->get_url() : '',
		'labels'         => $labels,
		'image'          => $image,
		'price'          => $price,
		'original_price' => $original_price,
		'discount'       => $discount,
		'gallery'        => $gallery,
		'title'          => $title,
		'property_name'  => $property_name,
		'property_url'   => $property_url,
		'developer_name' => $developer_name,
		'amenities'      => $amenities,
		'edit_link'      => $is_author ? core_home_url( '/account?action=edit_unit&id=' . $unit->get_id() ) : '',
		'is_can_boost'   => $is_author && ! $is_draft,
		'is_favorite'    => $unit->is_favorite(),
		'broker'         => $unit->get_broker(),
	)
);
