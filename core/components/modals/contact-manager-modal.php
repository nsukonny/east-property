<?php

if ( ! empty( $_POST['action'] ) && 'contact_manager_callback_form' === $_POST['action'] ) {
	$phone_number  = sanitize_text_field( $_POST['phone_number'] );
	$user_location = sanitize_text_field( $_POST['user_location'] );

	$file_path    = wp_get_upload_dir()['basedir'] . '/contact_manager_leads.json';
	$file_content = file_get_contents( $file_path );
	$leads        = array();
	if ( ! empty( $file_content ) ) {
		$leads = json_decode( $file_content, true );
	}

	$leads[] = array(
		'time'          => date( 'Y-m-d H:i:s' ),
		'phone_number'  => $phone_number,
		'user_location' => $user_location,
	);

	file_put_contents( $file_path, json_encode( $leads ) );

	wp_safe_redirect( core_home_url() );
}

get_component_template( 'modals/contact-manager-modal/contact-manager-modal' );
