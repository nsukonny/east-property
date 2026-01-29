<?php
/**
 * Display featured properties list
 */

use Entities\Unit;

$featured_units = get_posts( array(
	'post_type'      => 'unit',
	'posts_per_page' => 3,
	'orderby'        => 'date',
	'order'          => 'DESC', //TODO Display featured properties based on a specific meta field
) );

if ( empty( $featured_units ) ) {
	return;
}

$units = array();
foreach ( $featured_units as $unit ) {
	$units[] = new Unit( $unit );
}

get_template_part( 'template-parts/sections/index/properties', null, array(
	'h2'            => __( 'Featured new projects in the UAE' ),
	'description'   => __( 'Aliquam lacinia diam quis lacus euismod' ),
	'href'          => home_url( '/properties/' ),
	'link_text'     => __( 'See all new properties' ),
	'units'         => $units,
	'card_template' => 'property-card',
) );