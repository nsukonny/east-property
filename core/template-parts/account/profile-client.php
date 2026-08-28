<?php
/**
 * Account profile template.
 */


use Entities\Estate_User;

$user_units = $args['user_units'] ?? array();

global $current_user;

$client      = new Estate_User( $current_user );
$user_avatar = get_field( 'avatar', 'user_' . $current_user->ID );

$user_avatar_id  = $user_avatar ? $user_avatar['ID'] : '';
$user_avatar_url = $user_avatar ? $user_avatar['url'] : THEME_URL . '/assets/img/no-avatar-300.jpg';

$active_tab     = core_get_account_active_tab();
$logout_url     = wp_logout_url( core_get_account_page_url() );
$posts_per_page = PROPERTIES_PER_PAGE ?? 20;
$languages      = get_all_languages();
?>
<section class="profile-tabs" data-tabs>
	<div class="container">
		<div class="profile-tabs-wrapper">
			<div class="profile-tabs-buttons" role="tablist">
				<button class="result-tab-button <?php echo 'units' === $active_tab ? 'active' : ''; ?>" type="button"
				        id="profile-tabs-units-tab" data-tab-button data-tab="units" role="tab"
				        aria-selected="<?php echo 'units' === $active_tab ? 'true' : 'false'; ?>"
				        aria-controls="profile-tabs-units-panel">
					<?php esc_html_e( 'Saved properties', 'east-property' ); ?>
				</button>
				<button class="result-tab-button <?php echo 'account' === $active_tab ? 'active' : ''; ?>" type="button"
				        id="profile-tabs-account-tab" data-tab-button data-tab="account" role="tab"
				        aria-selected="<?php echo 'account' === $active_tab ? 'true' : 'false'; ?>"
				        aria-controls="profile-tabs-account-panel">
					<?php esc_html_e( 'My Account', 'east-property' ); ?>
				</button>
				<a class="button link logout" href="<?php echo esc_url( $logout_url ); ?>">
					<?php esc_html_e( 'Logout', 'east-property' ); ?>
				</a>
			</div>

			<div class="profile-tabs-content <?php echo 'units' === $active_tab ? 'active' : ''; ?>"
			     id="profile-tabs-units-panel" data-tab-panel data-tab="units" role="tabpanel"
			     aria-labelledby="profile-tabs-units-tab">
				<div class="content-title">
					<div class="title-top">
						<h2>
							<?php esc_html_e( 'Saved properties', 'east-property' ); ?>
							(<?php echo esc_attr( $user_units['total'] ); ?>)
						</h2>
					</div>
				</div>
				<div class="content-list">
					<?php
					if ( ! $client->is_verified() ) {
						get_component_template( 'account/email-verification' );
					} elseif ( ! empty( $user_units['items'] ) ) {
						?>
						<div class="properties-section-items">
							<?php
							$limit = $posts_per_page;
							foreach ( $user_units['items'] as $unit ) {
								get_template_part(
									'core/components/cards/unit-card',
									null,
									array(
										'unit'     => $unit,
										'template' => 'unit-card',
									)
								);

								-- $limit;
								if ( $limit <= 0 ) {
									break;
								}
							}

							get_template_part(
								'core/components/common/pagination',
								null,
								array(
									'total_items'    => $user_units['total'] ?? count( $user_units ),
									'items_per_page' => $posts_per_page,
								)
							);
							?>
						</div>
					<?php } else { ?>
						<p><?php esc_html_e( 'You have not added any favorite units.', 'east-property' ); ?></p>
					<?php } ?>
				</div>
			</div>

			<div class="profile-tabs-content <?php echo 'account' === $active_tab ? 'active' : ''; ?>"
			     id="profile-tabs-account-panel" data-tab-panel data-tab="account" role="tabpanel"
			     aria-labelledby="profile-tabs-account-tab">
				<div class="content-title">
					<div class="title-top">
						<h2><?php esc_html_e( 'Account details', 'east-property' ); ?></h2>
					</div>
				</div>
				<div class="content-list submit-unit">
					<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST"
					      enctype="multipart/form-data">
						<input type="hidden" name="action" value="account_update_profile">
						<?php wp_nonce_field( 'update_profile' ); ?>

						<div class="submit-unit-inner">

							<div class="submit-unit-left">
								<fieldset>
									<div class="input-group">
										<label for="display_name">
											<?php esc_html_e( 'Display name', 'east-property' ); ?>
											<input type="text" id="display_name" name="display_name"
											       value="<?php echo esc_attr( $current_user->display_name ); ?>"
											       required>
										</label>
									</div>

									<div class="input-group">
										<label for="email">
											<?php esc_html_e( 'Email address', 'east-property' ); ?>
											<input type="text" id="email" name="email"
											       value="<?php echo esc_attr( $current_user->user_email ); ?>"
											       readonly="readonly">
										</label>
									</div>

									<br>
									<?php foreach ( $languages as $language_slug => $language ) { ?>
										<div class="input-group">
											<label for="phone_<?php echo esc_attr( $language_slug ); ?>">
												<?php esc_html_e( 'Phone number', 'east-property' ); ?>
												(<?php echo esc_attr( $language['name'] ); ?>)
												<input type="text" id="phone_<?php echo esc_attr( $language_slug ); ?>"
												       name="phone[<?php echo esc_attr( $language_slug ); ?>]"
												       value="<?php echo esc_attr( $client->get_phone( $language_slug ) ); ?>"
												       placeholder="971509670043">
											</label>
										</div>
									<?php } ?>
									<br>
									<?php foreach ( $languages as $language_slug => $language ) { ?>
										<div class="input-group">
											<label for="whatsapp_<?php echo esc_attr( $language_slug ); ?>">
												<?php esc_html_e( 'WhatsApp number', 'east-property' ); ?>
												(<?php echo esc_attr( $language['name'] ); ?>)
												<input type="text"
												       id="whatsapp_<?php echo esc_attr( $language_slug ); ?>"
												       name="whatsapp[<?php echo esc_attr( $language_slug ); ?>]"
												       value="<?php echo esc_attr( $client->get_whatsapp( $language_slug,
														   '',
														   false ) ); ?>"
												       placeholder="971509670043">
											</label>
										</div>
									<?php } ?>
								</fieldset>
								<div class="submit-unit-bottom">
									<button class="button green orange xl" type="submit">
										<?php esc_html_e( 'Save changes', 'east-property' ); ?>
									</button>
								</div>
							</div>

							<div class="submit-unit-right">
								<div class="input-group">
									<label for="avatar">
										<?php esc_html_e( 'Avatar', 'east-property' ); ?>
									</label>
								</div>
								<div class="avatar-uploader">
									<input type="hidden" name="current_avatar_id" id="current_avatar_id"
									       value="<?php echo esc_attr( $user_avatar_id ); ?>">
									<input type="file" id="avatar" name="avatar" accept="image/*" class="avatar-input">
									<div class="avatar-uploader-wrapper">
										<div class="avatar-preview">
											<img src="<?php echo esc_url( $user_avatar_url ); ?>"
											     alt="<?php echo esc_attr( $current_user->display_name ); ?>">
										</div>
									</div>
								</div>
							</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</section>

