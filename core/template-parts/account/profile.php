<?php
/**
 * Account profile template.
 */

use Entities\Estate_User;

global $current_user;

$agencies_posts  = $args['agencies_posts'] ?? array();
$user_units      = $args['user_units'] ?? array();
$favourites      = $args['favourites'] ?? array();
$user_properties = $args['user_properties'] ?? array();

$user_entity = new Estate_User( $current_user );

$user_agency      = get_field( 'agency', 'user_' . $current_user->ID );
$user_agency_id   = $user_agency ? $user_agency->ID : null;
$user_avatar_url  = $user_entity->get_avatar();
$user_avatar_data = $user_entity->get_avatar_data();
$user_avatar_id   = $user_avatar_data['ID'] ?? '';

$broker       = new Estate_User( $current_user );
$boost_points = $broker->get_boost_points();

$active_tab     = core_get_account_active_tab();
$logout_url     = wp_logout_url( core_get_account_page_url() );
$posts_per_page = PROPERTIES_PER_PAGE ?? 20;

$languages = get_all_languages();

$add_property_btn_text = IS_DISTRESS ? __( '+ Add new distress', 'east-property' ) : __( '+ Add new property',
	'east-property' );
$my_properties_text    = IS_DISTRESS ? __( 'My distress', 'east-property' ) : __( 'My properties', 'east-property' );
?>
<section class="profile-tabs" data-tabs>
	<div class="container">
		<div class="profile-tabs-wrapper">
			<div class="profile-tabs-buttons" role="tablist">
				<button class="result-tab-button <?php echo 'units' === $active_tab ? 'active' : ''; ?>" type="button"
				        id="profile-tabs-units-tab" data-tab-button data-tab="units" role="tab"
				        aria-selected="<?php echo 'units' === $active_tab ? 'true' : 'false'; ?>"
				        aria-controls="profile-tabs-units-panel">
					<?php echo esc_attr( $my_properties_text ); ?>
				</button>
				<button class="result-tab-button <?php echo 'projects' === $active_tab ? 'active' : ''; ?>"
				        type="button"
				        id="profile-tabs-projects-tab" data-tab-button data-tab="projects" role="tab"
				        aria-selected="<?php echo 'projects' === $active_tab ? 'true' : 'false'; ?>"
				        aria-controls="profile-tabs-projects-panel">
					<?php esc_html_e( 'My projects', 'east-property' ); ?>
				</button>
				<button class="result-tab-button <?php echo 'favourites' === $active_tab ? 'active' : ''; ?>"
				        type="button"
				        id="profile-tabs-favourites-tab" data-tab-button data-tab="favourites" role="tab"
				        aria-selected="<?php echo 'favourites' === $active_tab ? 'true' : 'false'; ?>"
				        aria-controls="profile-tabs-units-panel">
					<?php esc_html_e( 'Saved properties', 'east-property' ); ?>
				</button>
				<button class="result-tab-button <?php echo 'account' === $active_tab ? 'active' : ''; ?>" type="button"
				        id="profile-tabs-account-tab" data-tab-button data-tab="account" role="tab"
				        aria-selected="<?php echo 'account' === $active_tab ? 'true' : 'false'; ?>"
				        aria-controls="profile-tabs-account-panel">
					<?php esc_html_e( 'Account details', 'east-property' ); ?>
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
							<?php
							echo esc_html(
								sprintf(
									IS_DISTRESS ? __( 'My distress (%d)', 'east-property' ) : __( 'My properties (%d)',
										'east-property' ),
									(int) $user_units['total']
								)
							);
							?>
						</h2>
						<?php if ( $broker->is_verified() ) { ?>
							<a class="button green orange sm"
							   href="<?php echo esc_url( core_home_url( '/account?action=add_unit' ) ); ?>">
								<?php echo esc_attr( $add_property_btn_text ); ?>
							</a>
						<?php } ?>
					</div>
				</div>
				<div class="content-list">
					<?php
					if ( ! $broker->is_verified() ) {
						get_component_template( 'account/email-verification' );
					} elseif ( ! empty( $user_units['items'] ) ) {
						?>
						<div class="properties-section-items">
							<?php if ( 700 < $boost_points ) { ?>
								<div class="boost-promo">
									<div class="boost-promo-img">
										<img src="<?php echo esc_url( THEME_URL ); ?>/assets/img/gold_star.png"
										     alt="<?php esc_html_e( 'Gold Star', 'east-property' ); ?>">
									</div>
									<div class="boost-promo-text">
										<div class="boost-promo-title"><?php esc_html_e( 'Boost your property to get it sold faster',
												'east-property' ); ?></div>
										<div class="boost-promo-desription"><?php esc_html_e( 'Get More Eyes, Get More Offers. Fast-Track Your Sale. Priority Listing: Sell 2x Faster.',
												'east-property' ); ?></div>
										<button class="button orange green sm" data-modal-open="boost-info-modal">
											<?php esc_html_e( 'How it works', 'east-property' ); ?>
										</button>
									</div>
									<div class="boost-promo-labels">
										<div class="boost-promo-label">
											<div class="label red">
												<img src="<?php echo esc_url( THEME_URL ); ?>/assets/img/star_white.svg"
												     alt="<?php esc_html_e( 'Star', 'east-property' ); ?>">
												<?php esc_html_e( 'Promoted', 'east-property' ); ?>
											</div>
										</div>
									</div>
								</div>
							<?php } ?>

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
						<p><?php esc_html_e( 'You have not added any units yet.', 'east-property' ); ?></p>
					<?php } ?>
				</div>
			</div>

			<div class="profile-tabs-content <?php echo 'projects' === $active_tab ? 'active' : ''; ?>"
			     id="profile-tabs-projects-panel" data-tab-panel data-tab="projects" role="tabpanel"
			     aria-labelledby="profile-tabs-projects-tab">
				<div class="content-title">
					<div class="title-top">
						<h2>
							<?php
							echo esc_html(
								sprintf(
									__( 'My projects (%d)', 'east-property' ),
									(int) $user_properties['total']
								)
							);
							?>
						</h2>
						<?php
						if ( $broker->is_verified() ) {
							get_template_part(
								'core/components/ui/button',
								null,
								array(
									'class' => 'green orange sm',
									'text'  => __( '+ Add new project', 'east-property' ),
									'link'  => core_home_url( '/account?action=add_property' ),
								)
							);
						}
						?>
					</div>
				</div>
				<div class="content-list">
					<?php
					if ( ! $broker->is_verified() ) {
						get_component_template( 'account/email-verification' );
					} elseif ( ! empty( $user_properties['items'] ) ) {
						?>
						<div class="properties-section-items">
							<div class="content-list">
								<?php
								$limit = $posts_per_page;
								foreach ( $user_properties['items'] as $property ) {
									get_template_part(
										'core/components/cards/property-card',
										null,
										array(
											'property' => $property,
											'template' => 'large-card',
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
										'total_items'    => $user_properties['total'] ?? count( $user_properties ),
										'items_per_page' => $posts_per_page,
									)
								);
								?>
							</div>
						</div>
					<?php } else { ?>
						<p><?php esc_html_e( 'You have not added any units yet.', 'east-property' ); ?></p>
					<?php } ?>
				</div>
			</div>

			<div class="profile-tabs-content <?php echo 'favourites' === $active_tab ? 'active' : ''; ?>"
			     id="profile-tabs-favourites-panel" data-tab-panel data-tab="favourites" role="tabpanel"
			     aria-labelledby="profile-tabs-favourites-tab">
				<div class="content-title">
					<div class="title-top">
						<h2>
							<?php esc_html_e( 'Saved properties', 'east-property' ); ?>
							(<?php echo esc_attr( $favourites['total'] ); ?>)
						</h2>
					</div>
				</div>
				<div class="content-list">
					<?php
					if ( ! $broker->is_verified() ) {
						get_component_template( 'account/email-verification' );
					} elseif ( ! empty( $favourites['items'] ) ) {
						?>
						<div class="properties-section-items">
							<?php
							$limit = $posts_per_page;
							foreach ( $favourites['items'] as $favorite_unit ) {
								get_template_part(
									'core/components/cards/unit-card',
									null,
									array(
										'unit'     => $favorite_unit,
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
									'total_items'    => $favourites['total'] ?? count( $favourites ),
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

									<div class="input-group">
										<div class="input-wrapper">
											<div class="label-text">
												<span><?php esc_html_e( 'Agency', 'east-property' ); ?></span>
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
															<?php echo ! empty( $user_agency ) ? esc_html( $user_agency->post_title ) : esc_attr__( 'select',
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
											       value="<?php echo esc_attr( $user_agency_id ); ?>"
											       data-required>
										</div>
									</div>
									<br>
									<?php foreach ( $languages as $language_slug => $language ) { ?>
										<div class="input-group">
											<label for="phone_<?php echo esc_attr( $language_slug ); ?>">
												<?php esc_html_e( 'Phone number', 'east-property' ); ?>
												(<?php echo esc_attr( $language['name'] ); ?>)
												<input type="text" id="phone_<?php echo esc_attr( $language_slug ); ?>"
												       name="phone[<?php echo esc_attr( $language_slug ); ?>]"
												       value="<?php echo esc_attr( $user_entity->get_phone( $language_slug ) ); ?>"
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
												       value="<?php echo esc_attr( $user_entity->get_whatsapp( $language_slug,
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

