<?php
/**
 * Single template for Properties (CPT: property)
 */

use Entities\Property;

get_header( null, array( 'color' => 'white' ) );

while ( have_posts() ) {
	the_post();

	$property = new Property( get_the_ID() );

	if ( $_GET['units'] && 'all' === $_GET['units'] ) {
		get_template_part(
			'core/components/properties/property-units-list',
			null,
			array(
				'property' => $property,
			)
		);
	} else {
		get_template_part(
			'core/components/properties/property-single',
			null,
			array(
				'property'          => $property,
				'quote_button_args' => array(
					'class' => 'orange sm request-quote',
					'text'  => __( 'Request quote' ),
					'modal' => 'quote-modal',
				),
			)
		);
	}
}

get_footer();
