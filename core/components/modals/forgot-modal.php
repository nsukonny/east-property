<div class="modal-wrapper forgot-modal" data-modal-id="forgot-modal">
	<div class="modal">
		<div class="modal-info">
			<div class="modal-title">
				<h3>
					<?php esc_html_e( 'Forgot password' , 'east-property' ); ?>
				</h3>
				<button class="modal-close" data-modal-close aria-label="Close">
					<img src="<?php echo THEME_URL; ?>/assets/img/close.svg" width="24" height="24" alt="Close icon">
				</button>
			</div>
			<form name="forgot-password-form" method="post"
			      action="<?php echo esc_url( core_home_url( '/account?action=reset_password' ) ); ?>">
				<fieldset>
					<label for="forgot_email">
						<?php _e( 'Email or username' , 'east-property' ); ?>
						<input type="email" id="forgot_email" name="email"
						       placeholder="<?php _e( 'Email or username' , 'east-property' ); ?>" required>
					</label>
				</fieldset>
				<div class="submit-group between">
					<button class="button link" type="button" data-modal-open="signin-modal">
						<?php esc_html_e( 'Sign in' , 'east-property' ); ?>
					</button>
					<button class="button green orange sm" type="submit">
						<?php esc_html_e( 'Send password reset link' , 'east-property' ); ?>
					</button>
				</div>
			</form>
		</div>
	</div>
</div>