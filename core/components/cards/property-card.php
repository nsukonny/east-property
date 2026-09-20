<?php
/**
 * Property card component
 *
 * @var array $property
 */

$property = $args['property'] ?? null;
if ( $property === null ) {
	return;
}

$template = $args['template'] ?? 'large-card';

$price     = $property['specifications']['min_price'] ?? null;
$labels    = \Entities\Property::build_labels( $property );
$is_author = ! empty( $property['author_id'] ) && 0 !== $property['author_id'] && (int) $property['author_id'] === get_current_user_id();

get_component_template(
	'cards/' . $template,
	array(
		'url'            => get_permalink( $property['ID'] ),
		'labels'         => $labels,
		'price'          => get_price_html( $price ),
		'pure_price'     => $price,
		'gallery'        => $property['gallery'] ?? array(),
		'title'          => $property['post_title'] ?? '',
		'location'       => $property['location_name'] ?? '',
		'edit_link'      => $is_author ? core_home_url( '/account?action=edit_property&id=' . $property['ID'] ) : '',
		'specifications' => $property['specifications'] ?? array(),
	)
);
