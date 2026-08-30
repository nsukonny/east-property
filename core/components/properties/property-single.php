<?php
/**
 *
 * old template themes/east-property/east-property/dev/src/html/sections/project/items/items.html
 *
 * @var $property Entities\Property
 **/

use Entities\Unit;

$property          = $args['property'] ?? null;
$quote_button_args = $args['quote_button_args'] ?? array();

if ( $property === null || ! $property->exists() ) {
	return;
}

$whatsapp_share_text = 'https://wa.me/?text=' . rawurlencode( sprintf(
		'%s | %s | %s View %s',
		$property->get_title(),
		$property?->get_location()->name ?? '',
		$property->get_price_html(),
		get_permalink( $property->get_id() )
	) );

$property_units = $property->get_units();
$featured_units = ! empty( $property_units ) ? array_slice( $property_units, 0, 6 ) : array();
$payment_plans  = $property->get_payment_plans();
$all_units_link = get_permalink( $property->get_id() ) . '?units=all';

get_component_template(
	'properties/property-single',
	array(
		'title'                => $property->get_title(),
		'labels'               => $property->get_labels(),
		'whatsapp_share_text'  => $whatsapp_share_text,
		'whats_app_link'       => WHATS_APP_LINK,
		'property_information' => $property->get_key_information(),
		'location'             => $property?->get_location() ?? '',
		'developer'            => $property?->get_developer() ?? '',
		'gallery'              => $property->get_gallery(),
		'units'                => $featured_units,
		'all_units_link'       => $all_units_link,
		'href'                 => $all_units_link,
		'quote_button_args'    => $quote_button_args,
		'units_by_beds'        => $property->get_units_by_beds(),
		'amenities'            => $property->get_amenities(),
		'latitude'             => $property?->get_latitude() ?? '',
		'longitude'            => $property?->get_longitude() ?? '',
		'payment_plans'        => $payment_plans[0]['items'] ?? array(),
		'button_text'          => __( 'Show all properties in this project' , 'east-property' ),
		'button_url'           => $all_units_link,
	)
);
