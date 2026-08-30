<?php
/**
 * Create new user form modal
 */

if ( is_user_logged_in() ) {
	return;
}

$agencies_posts = get_posts(
	array(
		'post_type'      => 'agency',
		'posts_per_page' => 10,
	)
);

$user_roles = get_existing_user_roles();

$redirect_to = core_home_url( '/account' );
?>
<div class="modal-wrapper create-modal" data-modal-id="create-modal">
	<div class="modal">
		<div class="modal-info">
			<div class="modal-title">
				<h3>
					<?php esc_html_e( 'Create account' , 'east-property' ); ?>
				</h3>
				<button class="modal-close" data-modal-close aria-label="Close">
					<img src="<?php echo THEME_URL; ?>/assets/img/close.svg" width="24" height="24" alt="Close icon">
				</button>
			</div>
			<div class="create-modal-inner">
				<p>
					<?php esc_html_e( 'What account gives you:' , 'east-property' ); ?>
				</p>
				<ul>
					<li>
						<img src="<?php echo THEME_URL; ?>/assets/img/bookmark.svg" width="24" height="24"
						     alt="vector icon">
						<?php esc_html_e( 'Bookmark your favorites' , 'east-property' ); ?>
					</li>
					<li>
						<img src="<?php echo THEME_URL; ?>/assets/img/bell.svg" width="24" height="24"
						     alt="vector icon">
						<?php esc_html_e( 'Get updates about new listings' , 'east-property' ); ?>
					</li>
				</ul>
			</div>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST"
			      class="registration-form">
				<input type="hidden" name="action" value="core_account_register">
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>">
				<?php wp_nonce_field( 'core_account_register', 'core_account_register_nonce' ); ?>
				<fieldset>
					<label for="distress-register-first-name">
						<span class="required">*</span>
						<?php esc_html_e( 'First name' , 'east-property' ); ?>
						<input type="text" id="distress-register-first-name" name="first_name">
					</label>

					<label for="distress-register-email">
						<span class="required">*</span>
						<?php esc_html_e( 'Email' , 'east-property' ); ?>
						<input type="email" id="distress-register-email" name="user_email" required>
					</label>

					<label for="distress-register-password">
						<span class="required">*</span>
						<?php esc_html_e( 'Password' , 'east-property' ); ?>
						<input type="password" id="distress-register-password" name="user_password" required>
					</label>

					<label for="distress-repeat-password">
						<span class="required">*</span>
						<?php esc_html_e( 'Repeat Password' , 'east-property' ); ?>
						<input type="password" id="distress-repeat-password" name="repeat_password" required>
					</label>

					<div class="input-group">
						<div class="input-wrapper">
							<div class="label-text">
								<span class="required">*</span>
								<span><?php esc_html_e( 'Choose your role' , 'east-property' ); ?></span>
							</div>

							<div class="dropdown">
								<button class="dropdown-button" type="button">
														<span class="dropdown-title">
															<?php echo array_first( $user_roles ); ?>
														</span>
									<span class="dropdown-arrow">
															<img src="<?php echo THEME_URL; ?>/assets/img/arrow-down.svg"
															     width="16" height="16" alt="vector arrow">
														</span>
								</button>
								<div class="dropdown-content">
									<div class="dropdown-inner">
										<?php foreach ( $user_roles as $role_key => $role ) { ?>
											<button type="button" class="dropdown-option"
											        data-value="<?php echo esc_attr( $role_key ); ?>">
												<?php echo esc_html( $role ); ?>
											</button>
										<?php } ?>
									</div>
								</div>
							</div>
							<input type="hidden" name="user_role"
							       value="<?php echo esc_attr( array_key_first( $user_roles ) ); ?>"
							       data-required>
						</div>
					</div>

					<label for="distress-register-phone" data-for-role="broker">
						<?php esc_html_e( 'Phone number' , 'east-property' ); ?>
						<input type="text" id="distress-register-phone" name="phone_number">
					</label>

					<div class="input-group" data-for-role="broker">
						<div class="input-wrapper">
							<div class="label-text">
								<span><?php esc_html_e( 'Agency' , 'east-property' ); ?></span>
								<span class="agency_info">
									<?php
									esc_html_e( 'If you can’t find your agency in the list, please contact us at ' , 'east-property' );
									echo '<a href="mailto:' . SUPPORT_EMAIL . '">' . SUPPORT_EMAIL . '</a>. ';
									esc_html_e( 'Your request will be reviewed within 24 hours.' , 'east-property' ); ?>
								</span>
							</div>
							<div class="dropdown">
								<button class="dropdown-button" type="button">
									<span class="dropdown-title">
										<?php echo ! empty( $user_agency ) ? esc_html( $user_agency->post_title ) : esc_attr__( 'Select' , 'east-property' ); ?>
									</span>
									<span class="dropdown-arrow">
										<img src="<?php echo THEME_URL; ?>/assets/img/arrow-down.svg"
										     width="16" height="16"
										     alt="<?php esc_html_e( 'vector arrow' , 'east-property' ); ?>">
									</span>
								</button>
								<div class="dropdown-content">
									<div class="dropdown-inner">
										<?php foreach ( $agencies_posts as $agency ) { ?>
											<button type="button" class="dropdown-option"
											        data-value="<?php echo esc_attr( $agency->ID ); ?>">
												<?php echo esc_html( $agency->post_title ); ?>
											</button>
										<?php } ?>
									</div>
								</div>
							</div>
							<input type="hidden" name="agency_id" data-required>
						</div>
					</div>
				</fieldset>
				<div class="submit-group">
					<label class="custom-checkbox accept-policy">
						<input type="checkbox" name="accepted_with_policy" value="1">
						<span>
							<?php esc_html_e( 'I have read and agree to the' , 'east-property' ); ?>
							<a href="/terms-of-use" class="button link" target="_blank">
								<?php esc_html_e( 'Terms of Use' , 'east-property' ); ?>
							</a>
							<?php esc_html_e( 'and' , 'east-property' ); ?>
							<a href="/privacy-policy" class="button link" target="_blank">
								<?php esc_html_e( 'Privacy Policy' , 'east-property' ); ?>
							</a>
						</span>
					</label>
				</div>
				<div class="submit-group between">
					<button class="button green orange sm" type="submit" disabled="disabled">
						<?php esc_html_e( 'Create account' , 'east-property' ); ?>
					</button>
					<div class="submit-group-right">
						<span><?php esc_html_e( 'Have an account?' , 'east-property' ); ?></span>
						<button class="button link" type="button" data-modal-open="signin-modal">
							<?php esc_html_e( 'Sign in' , 'east-property' ); ?>
						</button>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>