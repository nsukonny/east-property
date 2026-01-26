<?php
/**
 * Display featured properties list
 */

use Entities\Property;

$featured_properties = get_posts( array(
	'post_type'      => 'properties',
	'posts_per_page' => 3,
	'orderby'        => 'date',
	'order'          => 'DESC', //TODO Display featured properties based on a specific meta field
) );

if ( empty( $featured_properties ) ) {
	return;
}

$properties = array();
foreach ( $featured_properties as $property ) {
	$properties[] = new Property( $property );
}

get_template_part( 'template-parts/sections/index/properties', null, array(
	'h2'          => __( 'Featured new projects in the UAE' ),
	'description' => __( 'Aliquam lacinia diam quis lacus euismod' ),
	'href'        => home_url( '/properties/' ),
	'link_text'   => __( 'See all new properties' ),
	'properties'  => $properties,
) );