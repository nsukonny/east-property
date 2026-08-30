<?php
/**
 * Section for display count of properties by districts
 *
 * By default show 4 locations with biggest count of properties
 */

//get 4 terms taxonomy=location ordered by count of properties
$locations = get_terms(
	array(
		'taxonomy'   => 'location',
		'hide_empty' => false,
		'orderby'    => 'count',
		'order'      => 'DESC',
		'number'     => 4,
	)
);

$locations_ids = $locations ? wp_list_pluck( $locations, 'term_id' ) : array();
$units_count   = get_units_count_by_locations( $locations_ids );

$cards = array();
$i     = 1;
foreach ( $locations as $key => $location ) {
	$cards[] = array(
		'title' => $location->name,
		'href'  => core_home_url( '/projects/?location=' . $location->slug ),
		'count' => $units_count[ $location->term_id ] ?? 0,
		'image' => THEME_URL . '/assets/img/loc_' . $i . '.png', //TODO Move image from uploads
	);
	$i ++;
}

get_component_template(
	'sections/explore',
	array(
		'top_title' => array(
			'h2'   => __( 'Explore properties by district' , 'east-property' ),
			'desc' => __( 'Quick picks for you in UAE' , 'east-property' ),
			'href' => core_home_url( '/off-plan/' ),
			'link' => __( 'All Properties' , 'east-property' ),
		),
		'cards'     => $cards,
	)
);
