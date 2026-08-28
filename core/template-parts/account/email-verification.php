<?php
/**
 * Form for ask verification before use this functionality
 */

global $current_user;

if ( ! $current_user || ! is_user_logged_in() ) {
	return;
}
?>
<div class="account-email-verification">
	<div class="account-email-verification-left">
		<img src="<?php echo esc_url( THEME_URL . '/assets/img/verification_email.png' ); ?>"
		     alt="<?php esc_html_e( 'Email', 'east-property' ); ?>">
	</div>
	<div class="account-email-verification-right">
		<div class="account-email-verification-header">
			<h2 class="account-email-verification-title">
				<?php esc_html_e( 'Email verification', 'east-property' ); ?>
			</h2>
		</div>
		<div class="account-email-verification-body">
			<p>
				<?php esc_html_e( 'To use this functionality, you need to verify your email address',
					'east-property' ); ?>
				(<b><?php echo esc_html( $current_user->user_email ); ?></b>)
			</p>
			<p>
				<?php esc_html_e( 'Please check your email inbox for the verification email and click on the verification link.',
					'east-property' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'If you did not receive the email, please check your spam folder or click the button below to resend the verification email.',
					'east-property' ); ?>
			</p>
		</div>
		<div class="account-email-verification-footer">
			<button class="button green orange sm resend-verification-email" type="button">
				<?php esc_html_e( 'Resend verification email', 'east-property' ); ?>
			</button>
		</div>
	</div>
</div>