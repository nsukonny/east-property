<?php

add_action( 'phpmailer_init', static function ( $phpmailer ) {
	$phpmailer->XMailer = PROJECT_NAME . ' ' . __( 'Mailer' , 'east-property' );
	$phpmailer->Sender  = SUPPORT_EMAIL;
} );