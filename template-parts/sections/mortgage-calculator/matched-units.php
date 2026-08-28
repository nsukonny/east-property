<?php

use Entities\Unit;

$featured_units_posts = get_posts(
	array(
		'post_type'      => 'unit',
		'posts_per_page' => 3,
		'orderby'        => 'rand',
	)
);

if ( empty( $featured_units_posts ) ) {
	return;
}

$featured_units = array();
foreach ( $featured_units_posts as $unit_post ) {
	$featured_units[] = new Unit( $unit_post );
}

$all_units_link = core_home_url( '/off-plan' );

get_component_template(
	'units/featured',
	array(
		'h2'            => __( 'Objects that match your criteria', 'east-property' ),
		'href'          => $all_units_link,
		'show_all_link' => $all_units_link,
		'link_text'     => __( 'All properties', 'east-property' ),
		'units'         => $featured_units,
		'card_template' => 'unit-square-card',
		'border_top'    => true,
		'before'        => '<section class="properties white"><div class="container">',
		'after'         => '</div></section>',
	)
);
