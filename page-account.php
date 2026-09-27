<?php

/**
 * Template Name: Account
 */
get_header( null, array( 'color' => 'green' ) );

get_template_part( 'core/components/account/account' );

get_footer(
	null,
	array(
		'modals' => array(
			'broker-modal',
			'boost-modal',
			'boost-info-modal',
			'map-coords-picker',
		),
	)
);
