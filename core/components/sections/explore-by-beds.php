<?php
/**
 * Section for display count of properties by bedroom
 */

$units_count = get_units_count_by_bedrooms( 'secondary' );

$cards       = array();
$first_count = ( $units_count[0] ?? 0 ) + ( $units_count[1] ?? 0 );
$cards[]     = array(
	'title' => __( 'Studio or 1 bedroom', 'east-property' ),
	'href'  => core_home_url( '/secondary/?beds=studio,1' ),
	'count' => $first_count,
	'image' => THEME_URL . '/assets/img/beds_10.png',
	'label' => ( 1 < $first_count ) ? __( 'Properties', 'east-property' ) : __( 'Property', 'east-property' ),
);

$cards[] = array(
	'title' => __( '2 bedrooms', 'east-property' ),
	'href'  => core_home_url( '/secondary/?beds=2' ),
	'count' => $units_count[2] ?? 0,
	'image' => THEME_URL . '/assets/img/beds_20.png',
	'label' => ( 1 < ( $units_count[2] ?? 0 ) ) ? __( 'Properties', 'east-property' ) : __( 'Property',
		'east-property' ),
);

$cards[] = array(
	'title' => __( '3 bedrooms', 'east-property' ),
	'href'  => core_home_url( '/secondary/?beds=3' ),
	'count' => $units_count[3] ?? 0,
	'image' => THEME_URL . '/assets/img/beds_30.png',
	'label' => ( 1 < ( $units_count[3] ?? 0 ) ) ? __( 'Properties', 'east-property' ) : __( 'Property',
		'east-property' ),
);

$last_count = ( $units_count[4] ?? 0 ) + ( $units_count[5] ?? 0 ) + ( $units_count[6] ?? 0 );
$cards[]    = array(
	'title' => __( '3+ bedrooms', 'east-property' ),
	'href'  => core_home_url( '/secondary/?beds=4,5,6' ),
	'count' => $last_count,
	'image' => THEME_URL . '/assets/img/beds_40.png',
	'label' => ( 1 < $last_count ) ? __( 'Properties', 'east-property' ) : __( 'Property', 'east-property' ),
);

get_component_template(
	'sections/explore',
	array(
		'top_title' => array(
			'h2'   => __( 'Explore properties by number of bedrooms', 'east-property' ),
			'desc' => __( 'Quick picks for you in UAE', 'east-property' ),
			'href' => core_home_url( '/secondary/' ),
			'link' => __( 'All Properties', 'east-property' ),
		),
		'cards'     => $cards,
	)
);
