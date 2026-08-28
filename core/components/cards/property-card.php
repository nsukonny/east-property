<?php
/**
 * Property card component
 *
 * @var \Entities\Property $property
 */

$property = $args['property'] ?? null;
if ( $property === null || ! $property->exists() ) {
	return;
}

$template = $args['template'] ?? 'large-card';

$title          = $property->get_title();
$price          = $property->get_price_html();
$pure_price     = $property->get_price();
$location       = $property->get_location()->name;
$gallery        = $property->get_gallery();
$labels         = $property->get_labels();
$url            = $property->get_url();
$is_author      = 0 !== $property->get_author_id() && $property->get_author_id() === get_current_user_id();
$specifications = $property->get_specifications();

get_component_template(
	'cards/' . $template,
	array(
		'url'            => $url,
		'labels'         => $labels,
		'price'          => $price,
		'pure_price'     => $pure_price,
		'gallery'        => $gallery,
		'title'          => $title,
		'location'       => $location,
		'edit_link'      => $is_author ? core_home_url( '/account?action=edit_property&id=' . $property->get_id() ) : '',
		'specifications' => $specifications,
	)
);
