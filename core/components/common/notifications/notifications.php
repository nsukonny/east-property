<?php
/**
 * Display errors, success or info messages if they exists
 */

if ( session_status() !== PHP_SESSION_ACTIVE ) {
	session_start();
}

$notifications = array();
if ( ! empty( $_SESSION['core_notifications'] ) && is_array( $_SESSION['core_notifications'] ) ) {
	$notifications = $_SESSION['core_notifications'];
	unset( $_SESSION['core_notifications'] );
}

if ( empty( $notifications ) ) {
	return;
}

$codes = array(
	'success'       => array(
		'property_added'   => __( 'Your property has been submitted successfully and saved as draft. Wait approve.' , 'east-property' ),
		'profile_created'  => __( 'Your profile has been created successfully.' , 'east-property' ),
		'profile_updated'  => __( 'Your profile has been updated.' , 'east-property' ),
		'unit_deleted'     => __( 'Your property has been deleted.' , 'east-property' ),
		'password_updated' => __( 'Your password has been updated. Now you can login.' , 'east-property' ),
		'email_verified'   => __( 'Your email has been verified successfully.' , 'east-property' ),
	),
	'error'         => array(
		'security_error'             => __( 'Security error. Please try again.' , 'east-property' ),
		'validation_error'           => __( 'Validation error. Please check your input and try again.' , 'east-property' ),
		'credentials_empty'          => __( 'Please enter login and password.' , 'east-property' ),
		'required_fields'            => __( 'Please fill in all required fields.' , 'east-property' ),
		'email_exists'               => __( 'This email is already registered. Please use another email.' , 'east-property' ),
		'error_on_update'            => __( 'An error occurred while updating your profile. Please try again later.' , 'east-property' ),
		'error_on_avatar_upload'     => __( 'An error occurred while uploading your avatar. Please try again later.' , 'east-property' ),
		'expired_reset_key'          => __( 'Your password reset link has expired. Please request a new one.' , 'east-property' ),
		'password_length'            => __( 'Your password must be at least 8 characters long.' , 'east-property' ),
		'password_mismatch'          => __( 'Your password and confirmation password do not match.' , 'east-property' ),
		'invalid_verification_token' => __( 'Invalid or expired verification token.' , 'east-property' ),
	),
	'notices'       => array(),
	'default_error' => __( 'Unknown error occurred. Please try again later.' , 'east-property' ),
);
?>
	<div class="notifications">
		<?php foreach ( $notifications as $notification ) { ?>
			<div class="notification <?php echo esc_attr( $notification['status'] ); ?>">
				<?php
				if ( ! empty( $notification['why'] ) ) {
					echo esc_html( $notification['why'] );
				} elseif ( ! empty( $codes[ $notification['status'] ][ $notification['code'] ] ) ) {
					echo esc_html( $codes[ $notification['status'] ][ $notification['code'] ] );
				} else {
					echo esc_html( $codes['default_error'] );
				}
				?>
				<button type="button" class="notification-close"
				        aria-label="<?php esc_attr_e( 'Close notification' , 'east-property' ); ?>"></button>
			</div>
		<?php } ?>
	</div>
<?php
