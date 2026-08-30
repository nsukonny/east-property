<?php
/**
 * Account login/register template.
 */

$agencies_posts = $args['agencies_posts'] ?? array();
$user_roles     = $args['user_roles'] ?? array();

$active_tab = core_get_account_active_tab();
$redirect_to = core_home_url( '/account?tab=account' );
?>
<section class="profile-tabs" data-tabs>
	<div class="container">
		<div class="profile-tabs-wrapper">
			<div class="profile-tabs-buttons" role="tablist">
				<button class="result-tab-button <?php echo 'login' === $active_tab ? 'active' : ''; ?>" type="button"
				        id="profile-tabs-login-tab" data-tab-button data-tab="login" role="tab"
				        aria-selected="<?php echo 'login' === $active_tab ? 'true' : 'false'; ?>"
				        aria-controls="profile-tabs-login-panel">
					<?php esc_html_e( 'Sign in', 'east-property' ); ?>
				</button>
				<button class="result-tab-button <?php echo 'register' === $active_tab ? 'active' : ''; ?>"
				        type="button" id="profile-tabs-register-tab" data-tab-button data-tab="register" role="tab"
				        aria-selected="<?php echo 'register' === $active_tab ? 'true' : 'false'; ?>"
				        aria-controls="profile-tabs-register-panel">
					<?php esc_html_e( 'Create account', 'east-property' ); ?>
				</button>
			</div>

			<div class="profile-tabs-content <?php echo 'login' === $active_tab ? 'active' : ''; ?>"
			     id="profile-tabs-login-panel" data-tab-panel data-tab="login" role="tabpanel"
			     aria-labelledby="profile-tabs-login-tab">
				<div class="content-title">
					<div class="title-top">
						<h2><?php esc_html_e( 'Sign in to start selling', 'east-property' ); ?></h2>
					</div>
				</div>
				<div class="content-list">
					<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"
					      class="login-form">
						<input type="hidden" name="action" value="core_account_login">
						<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>">
						<?php wp_nonce_field( 'core_account_login', 'core_account_login_nonce' ); ?>
						<fieldset>
							<label for="distress-account-log">
								<?php esc_html_e( 'Email or username', 'east-property' ); ?>
								<input type="text" id="distress-account-log" name="log" required>
							</label>
							<label for="distress-account-pwd">
								<?php esc_html_e( 'Password', 'east-property' ); ?>
								<input type="password" id="distress-account-pwd" name="pwd" required>
							</label>
						</fieldset>
						<div class="submit-group between">
							<?php
							get_template_part(
								'core/components/ui/button',
								null,
								array(
									'class' => 'green orange sm',
									'text'  => __( 'Sign in', 'east-property' ),
									'type'  => 'submit',
								)
							);

							get_template_part(
								'core/components/ui/button',
								null,
								array(
									'class' => 'link',
									'text'  => __( 'Forgot password?', 'east-property' ),
									'modal' => 'forgot-modal',
								)
							);
							?>
						</div>
					</form>
				</div>
			</div>

			<div class="profile-tabs-content <?php echo 'register' === $active_tab ? 'active' : ''; ?>"
			     id="profile-tabs-register-panel" data-tab-panel data-tab="register" role="tabpanel"
			     aria-labelledby="profile-tabs-register-tab">
				<div class="content-title">
					<div class="title-top">
						<h2><?php esc_html_e( 'Create a new account', 'east-property' ); ?></h2>
					</div>
				</div>
				<div class="content-list submit-unit">
					<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST"
					      enctype="multipart/form-data" class="login-form registration-form">
						<input type="hidden" name="action" value="core_account_register">
						<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>">
						<?php wp_nonce_field( 'core_account_register', 'core_account_register_nonce' ); ?>
						<div class="submit-unit-inner">
							<div class="submit-unit-left">
								<fieldset>
									<div class="input-group">
										<label for="distress-register-first-name">
											<span class="required">*</span>
											<?php esc_html_e( 'First name', 'east-property' ); ?>
											<input type="text" id="distress-register-first-name" name="first_name"
											       placeholder="<?php esc_html_e( 'First name', 'east-property' ); ?>"
											       required
											>
										</label>
									</div>

									<div class="input-group">
										<label for="user_email">
											<span class="required">*</span>
											<?php esc_html_e( 'Email address', 'east-property' ); ?>
											<input type="text" id="user_email" name="user_email"
											       placeholder="<?php esc_html_e( 'admin@example.com',
													   'east-property' ); ?>"
											       required
											>
										</label>
									</div>

									<div class="input-group">
										<label for="user_password">
											<span class="required">*</span>
											<?php esc_html_e( 'Password', 'east-property' ); ?>
											<input type="password" id="user_password" name="user_password">
										</label>
									</div>

									<div class="input-group">
										<label for="repeat_password">
											<span class="required">*</span>
											<?php esc_html_e( 'Repeat Password', 'east-property' ); ?>
											<input type="password" id="repeat_password" name="repeat_password">
										</label>
									</div>

									<div class="input-group">
										<div class="input-wrapper">
											<div class="label-text">
												<span class="required">*</span>
												<span><?php esc_html_e( 'Choose your role', 'east-property' ); ?></span>
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

									<div class="input-group" data-for-role="broker">
										<label for="phone_number_inp">
											<?php esc_html_e( 'Phone number', 'east-property' ); ?>
											<input type="text" id="phone_number_inp" name="phone_number"
											       placeholder="971509670043"
											>
										</label>
									</div>

									<div class="input-group" data-for-role="broker">
										<div class="input-wrapper">
											<div class="label-text">
												<span><?php esc_html_e( 'Choose your agency',
														'east-property' ); ?></span>
												<span class="agency_info">
													<?php
													esc_html_e( 'If you can’t find your agency in the list, please contact us at ',
														'east-property' );
													echo '<a href="mailto:' . SUPPORT_EMAIL . '">' . SUPPORT_EMAIL . '</a>. ';
													esc_html_e( 'Your request will be reviewed within 24 hours.',
														'east-property' );
													?>
												</span>
											</div>
											<div class="dropdown">
												<button class="dropdown-button" type="button">
														<span class="dropdown-title">
															<?php echo ! empty( $user_agency ) ? esc_html( $user_agency->post_title ) : esc_attr__( 'Select',
																'east-property' ); ?>
														</span>
													<span class="dropdown-arrow">
															<img src="<?php echo THEME_URL; ?>/assets/img/arrow-down.svg"
															     width="16" height="16"
															     alt="vector arrow">
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
											<input type="hidden" name="agency_id"
											       value="0">
										</div>
									</div>
								</fieldset>
								<div class="submit-group">
									<label class="custom-checkbox accept-policy">
										<input type="checkbox" name="accepted_with_policy" value="1">
										<span>
											<?php esc_html_e( 'I have read and agree to the', 'east-property' ); ?>
											<a href="/terms-of-use" class="button link" target="_blank">
												<?php esc_html_e( 'Terms of Use', 'east-property' ); ?>
											</a>
											<?php esc_html_e( 'and', 'east-property' ); ?>
											<a href="/privacy-policy" class="button link" target="_blank">
												<?php esc_html_e( 'Privacy Policy', 'east-property' ); ?>
											</a>
										</span>
									</label>
								</div>
								<div class="submit-unit-bottom">
									<button class="button green orange xl" type="submit" disabled="disabled">
										<?php esc_html_e( 'Create account', 'east-property' ); ?>
									</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</section>

