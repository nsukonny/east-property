<?php
/**
 * Display similar units list
 */

$property_id = $args['property_id'] ?? null;
$unit_id     = $args['unit_id'] ?? null;

if ( empty( $property_id ) ) {
	// Polylang сужает выборку до текущего языка, и там юнитов может не быть вовсе.
	$random_unit_id = (int) ( get_posts(
		array(
			'post_type'      => 'unit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'post_status'    => 'publish',
			'orderby'        => 'rand',
		)
	)[0] ?? 0 );

	if ( $random_unit_id > 0 ) {
		$unit        = new Entities\Unit( $random_unit_id );
		$property    = $unit->get_property();
		$property_id = $property?->get_id();
	}
}

if ( empty( $property_id ) ) {
	return;
}

$all_property_units = get_units(
	'',
	25,
	array(
		'property_id' => $property_id,
		'galleries'   => true,
	)
);

if ( ! empty( $unit_id ) ) {
	foreach ( $all_property_units['items'] as $key => $item ) {
		if ( (int) $item['ID'] === (int) $unit_id ) {
			unset( $all_property_units['items'][ $key ] );
		}
	}
}

if ( empty( $all_property_units['items'] ) ) {
	return;
}

shuffle( $all_property_units['items'] );
$similar_units = array_slice( $all_property_units['items'], 0, 4 );

if ( empty( $similar_units ) ) {
	return;
}

$all_units_link = core_home_url( '/off-plan' );
get_component_template(
	'units/featured',
	array(
		'h2'            => $args['h2'] ?? __( 'More properties like this', 'east-property' ),
		'href'          => $args['href'] ?? $all_units_link,
		'show_all_link' => $args['show_all_link'] ?? $all_units_link,
		'link_text'     => $args['link_text'] ?? __( 'All properties', 'east-property' ),
		'units'         => $similar_units,
		'card_template' => $args['card_template'] ?? 'unit-square-card',
		'border_top'    => $args['border_top'] ?? false,
		'before'        => $args['before'] ?? '',
		'after'         => $args['after'] ?? '',
	)
);
