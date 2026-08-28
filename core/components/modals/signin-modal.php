<?php

$redirect_to = core_home_url( '/account' );
?>
<div class="modal-wrapper signin-modal" data-modal-id="signin-modal">
	<div class="modal">
		<div class="modal-info">
			<div class="modal-title">
				<h3>
					<?php _e( 'Sign in' , 'east-property' ); ?>
				</h3>
				<button class="modal-close" data-modal-close aria-label="Close">
					<img src="<?php echo THEME_URL; ?>/assets/img/close.svg" width="24" height="24" alt="Close icon">
				</button>
			</div>

			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="core_account_login">
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>">
				<?php wp_nonce_field( 'core_account_login', 'core_account_login_nonce' ); ?>
				<div class="submit-group not-have-account">
					<div class="submit-group-right">
						<span>
							<?php esc_html_e( 'Don’t have an account?' , 'east-property' ); ?>
						</span>
						<button class="button link" type="button" data-modal-open="create-modal">
							<?php esc_html_e( 'Create account' , 'east-property' ); ?>
						</button>
					</div>
				</div>
				<fieldset>
					<label for="distress-account-log">
						<?php esc_html_e( 'Email or username' , 'east-property' ); ?>
						<input type="text" id="distress-account-log" name="log" required>
					</label>
					<label for="distress-account-pwd">
						<?php esc_html_e( 'Password' , 'east-property' ); ?>
						<input type="password" id="distress-account-pwd" name="pwd" required>
					</label>
				</fieldset>
				<div class="submit-group between">
					<button class="button green orange sm" type="submit">
						<?php esc_html_e( 'Sign in' , 'east-property' ); ?>
					</button>
					<button class="button link" data-modal-open="forgot-modal">
						<?php esc_html_e( 'Forgot password?' , 'east-property' ); ?>
					</button>
				</div>
			</form>
		</div>
	</div>
</div>