<?php

/**
 * Account login/register template.
 */
$user = $args['user'] ?? null;
$key  = $args['key'] ?? null;

if ( ! $user || ! $key ) {
	return;
}
?>
<section class="profile-tabs" data-tabs>
	<div class="container">
		<div class="profile-tabs-wrapper">
			<div class="profile-tabs-content active" id="profile-tabs-login-panel" data-tab-panel data-tab="login"
			     role="tabpanel" aria-labelledby="profile-tabs-login-tab">
				<div class="content-title">
					<div class="title-top">
						<h2><?php esc_html_e( 'Enter new password' , 'east-property' ); ?></h2>
					</div>
				</div>

				<div class="content-list">
					<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"
					      class="login-form">
						<input type="hidden" name="action" value="reset_user_password">
						<input type="hidden" name="user_id" value="<?php echo esc_attr( $user->ID ); ?>">
						<input type="hidden" name="key" value="<?php echo esc_attr( $key ); ?>">
						<?php wp_nonce_field( 'upd_account_passwd' ); ?>
						<fieldset>
							<label for="password">
								<span class="required">*</span>
								<?php esc_html_e( 'Password' , 'east-property' ); ?>
								<input type="password" id="password" name="password" required>
							</label>
							<label for="repeat_password">
								<span class="required">*</span>
								<?php esc_html_e( 'Repeat password' , 'east-property' ); ?>
								<input type="password" id="repeat_password" name="repeat_password" required>
							</label>
						</fieldset>
						<div class="submit-group between">
							<button class="button green orange sm" type="submit">
								<?php esc_html_e( 'Update password' , 'east-property' ); ?>
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</section>

