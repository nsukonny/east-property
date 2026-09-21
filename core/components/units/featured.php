<?php
/**
 * Display featured units list
 */

$limit = $args['limit'] ?? 5;

//TODO Rebuild it to featured units for all users with personalization
$per_page    = defined( 'PROPERTIES_PER_PAGE' ) ? PROPERTIES_PER_PAGE : 20;
$fresh_units = get_units(
	'secondary',
	$per_page,
	array( 'galleries' => true )
);

shuffle( $fresh_units['items'] );
$units = array_slice( $fresh_units['items'], 0, $limit );

get_component_template(
	'units/featured',
	array(
		'h2'            => $args['h2'] ?? __( 'Featured new projects in the UAE', 'east-property' ),
		'title'         => $args['title'] ?? __( 'DISTRESS PROPERTIES', 'east-property' ),
		'h3'            => $args['h3'] ?? __( 'Don’t wait till it’s late', 'east-property' ),
		'description'   => $args['description'] ?? __( 'Be first to explore new off-plan launches — early access, new prices, and direct WhatsApp contact.',
				'east-property' ),
		'href'          => $args['href'] ?? core_home_url( '/projects' ),
		'show_all_link' => $args['show_all_link'] ?? core_home_url( '/projects' ),
		'link_text'     => $args['link_text'] ?? __( 'See all new projects', 'east-property' ),
		'units'         => $units,
		'card_template' => $args['card_template'] ?? 'unit-square-card',
		'border_top'    => $args['border_top'] ?? false,
		'before'        => $args['before'] ?? '<section class="properties"><div class="container">',
		'after'         => $args['after'] ?? '</div></section>',
		'button_text'   => $args['button_text'] ?? '',
		'button_url'    => $args['button_url'] ?? '',
	)
);
