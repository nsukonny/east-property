<?php

/**
 * Request for reset password for email
 *
 * @return void
 */
function ajax_reset_password(): void {
	check_ajax_referer( 'get_filtered_properties' );

	$email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
	if ( empty( $email ) ) {
		wp_send_json_error( array( 'message' => __( 'Email is required.' , 'east-property' ) ) );
	}

	$user = get_user_by( 'email', $email );
	if ( ! $user ) {
		wp_send_json_error( array( 'message' => __( 'No user found with this email.' , 'east-property' ) ) );
	}

	$reset_key = get_password_reset_key( $user );
	if ( is_wp_error( $reset_key ) ) {
		wp_send_json_error( array( 'message' => __( 'Error generating reset key.' , 'east-property' ) ) );
	}

	$reset_link = core_home_url( '/reset-password/?key=' . $reset_key . '&login=' . $user->user_login );

	$html_text  = __( 'Hello,' , 'east-property' );
	$html_text .= '<br><br>';
	$html_text .= __(
		'We received a request to reset the password for your EastProperty account.
	To create a new password, please click the link below:'
	, 'east-property' );
	$html_text .= '<br><br>';
	$html_text .= '<a href="' . $reset_link . '">' . __( 'Reset password' , 'east-property' ) . '</a>';
	$html_text .= '<br><br>';
	$html_text .= __( 'or copy and paste the following link into your browser:' , 'east-property' );
	$html_text .= '<br><b>' . preg_replace( '~^https?://~i', '', $reset_link ) . '</b><br>';

	$html_text .= '<br><br>';

	$html_text .= __( 'If you did not request a password reset, you can safely ignore this email. Your password will remain unchanged.' , 'east-property' );

	get_template_part(
		'core/components/email/send',
		null,
		array(
			'email'   => $user->user_email,
			'subject' => __( 'Password Reset Request' , 'east-property' ),
			'content' => $html_text,
		)
	);

	wp_send_json_success( array( 'message' => __( 'Password reset request sent to your email address. Check your inbox.' , 'east-property' ) ) );
}

add_action( 'wp_ajax_reset_password', 'ajax_reset_password' );
add_action( 'wp_ajax_nopriv_reset_password', 'ajax_reset_password' );
