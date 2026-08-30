<?php
/**
 * Add or edit property project template
 *
 * @var Entities\Property $property
 * @var $locations WP_Term[] of WP_Terms with location taxonomy
 */

$property = $args['property'] ?? null;
if ( null !== $property && ! $property->exists() ) {
	$property = null;
}

$translations = null !== $property ? $property->get_translations() : array();

$locations              = $args['locations'] ?? array();
$developers             = $args['developers'] ?? array();
$property_type_options  = $args['property_type_options'] ?? array();
$ownership_type_options = $args['ownership_type_options'] ?? array();

$h1       = $property ? __( 'Edit property project:', 'east-property' ) . ' ' . $property->get_title()
	: __( 'Add new property project', 'east-property' );
$tab_text = $property ? __( 'Edit property project', 'east-property' )
	: __( 'Add property project', 'east-property' );

// Everything except the title and the description is shared between languages,
// so it is mirrored across the fieldsets by syncLangValues().
$latitude             = $property ? $property->get_latitude() : '';
$longitude            = $property ? $property->get_longitude() : '';
$delivery_date        = $property ? $property->get_delivery_date( false ) : '';
$is_completed         = $property && $property->is_completed();
$delivery_date        = $delivery_date ? date( 'Y-m-d', strtotime( $delivery_date ) ) : '';
$selected_location    = $property && $property->get_location() ? $property->get_location() : null;
$selected_developer   = $property && $property->get_developer() ? $property->get_developer() : null;
$property_types       = $property ? $property->get_property_type() : array();
$ownership_type       = $property ? $property->get_ownership_type() : '';
$property_gallery_ids = $property ? implode( ',', $property->get_gallery_ids() ) : '';

$languages        = get_all_languages();
$current_language = pll_current_language();
?>
<section class="submit-unit">
	<div class="container">
		<div class="submit-unit-wrapper">
			<?php
			get_template_part(
				'core/components/common/breadcrumbs',
				null,
				array(
					'force_links' => array(
						array(
							'title' => __( 'My account', 'east-property' ),
							'url'   => core_get_account_page_url(),
						),
					),
				)
			);
			?>
			<h1><?php echo esc_html( $h1 ); ?></h1>
			<form class="submit-unit-form" autocomplete="off"
			      action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST"
			      enctype="multipart/form-data">
				<input type="hidden" name="action" value="core_account_create_property">
				<?php wp_nonce_field( 'account_create_property_nonce' ); ?>

				<?php
				foreach ( $translations as $lang => $translation ) {
					?>
					<input type="hidden" name="<?php echo esc_attr( $lang ); ?>[property_id]"
					       value="<?php echo esc_attr( $translation->get_id() ); ?>">
					<?php
				}
				?>

				<div class="submit-unit-inner">
					<div class="submit-unit-left">
						<?php if ( 1 < count( $languages ) ) { ?>
							<div class="lang-switcher">
								<?php foreach ( $languages as $slug => $language ) {
									$current_class = ( $current_language === $slug ) ? 'current' : '';
									?>
									<a data-switch-to-lang="<?php echo esc_attr( $language['slug'] ); ?>" href="#"
									   class="button sm <?php echo esc_attr( $current_class ); ?>"
									>
										<img src="<?php echo esc_url( THEME_URL . '/assets/img/lang/' . $language['slug'] . '.png' ) ?>"
										     alt="<?php echo esc_html( $language['name'] ); ?>"/>
										<span><?php echo esc_html( $language['name'] ); ?></span>
									</a>
								<?php } ?>
							</div>
						<?php } ?>

						<?php
						foreach ( $languages as $slug => $language ) {
							switch_to_lang( $language['locale'] );

							$property_title = isset( $translations[ $slug ] ) ? $translations[ $slug ]->get_title() : '';
							$description    = isset( $translations[ $slug ] ) ? $translations[ $slug ]->get_description_full() : '';
							?>
							<fieldset data-show-on-lang="<?php echo esc_attr( $slug ); ?>">
								<div class="input-group">
									<label for="s-text_<?php echo esc_attr( $language['slug'] ); ?>">
										<span class="required">*</span>
										<?php esc_html_e( 'Title', 'east-property' ); ?>
										<input type="text"
										       id="s-text_<?php echo esc_attr( $language['slug'] ); ?>"
										       name="<?php echo esc_attr( $language['slug'] . '[title]' ); ?>"
										       value="<?php echo esc_attr( $property_title ); ?>"
										       placeholder="<?php esc_html_e( 'azizi “beach oasis”', 'east-property' ); ?>"
										       required>
									</label>
								</div>

								<div class="input-group">
									<?php
									$developer_items = array();
									foreach ( $developers as $developer ) {
										$developer_items[ $developer->ID ] = $developer->post_title;
									}

									get_component_template(
										'ui/dropdown',
										array(
											'input_name'     => $language['slug'] . '[developer_id]',
											'title'          => __( 'Developer', 'east-property' ),
											'selected_title' => $selected_developer ? $selected_developer->get_title()
												: __( 'Select', 'east-property' ),
											'selected_key'   => $selected_developer ? $selected_developer->get_id() : 0,
											'items'          => $developer_items,
											'lang_sync'      => 'developer_id',
										)
									);
									?>
								</div>

								<div class="inputs-wrapper">
									<div class="inputs-box">
										<div class="inputs-group">
											<a href="#" class="link orange xl"
											   data-modal-open="map-coords-picker-modal">
												<?php require THEME_PATH . '/assets/img/map.svg'; ?>
												<?php esc_html_e( 'Pick on map', 'east-property' ); ?>
											</a>
										</div>
										<div class="inputs-group">
											<div class="input-group half-width">
												<label for="s-latitude_<?php echo esc_attr( $language['slug'] ); ?>">
													<?php esc_html_e( 'Latitude', 'east-property' ); ?>
													<input type="number"
													       id="s-latitude_<?php echo esc_attr( $language['slug'] ); ?>"
													       name="<?php echo esc_attr( $language['slug'] . '[latitude]' ); ?>"
													       min="-90" step="any" max="90"
													       value="<?php echo esc_attr( $latitude ); ?>"
													       placeholder="25.123240725545"
													       data-lang-sync="latitude"
													/>
												</label>
											</div>
											<div class="input-group half-width">
												<label for="s-longitude_<?php echo esc_attr( $language['slug'] ); ?>">
													<?php esc_html_e( 'Longitude', 'east-property' ); ?>
													<input type="number"
													       id="s-longitude_<?php echo esc_attr( $language['slug'] ); ?>"
													       name="<?php echo esc_attr( $language['slug'] . '[longitude]' ); ?>"
													       step="any" min="-180" max="180"
													       value="<?php echo esc_attr( $longitude ); ?>"
													       placeholder="55.11228769811"
													       data-lang-sync="longitude"
													/>
												</label>
											</div>
										</div>
									</div>
								</div>

								<div class="inputs-group">
									<div class="input-group">
										<?php
										$location_items = array();
										foreach ( $locations as $location ) {
											$location_items[ $location->term_id ] = $location->name;
										}

										get_component_template(
											'ui/dropdown',
											array(
												'input_name'     => $language['slug'] . '[location_id]',
												'title'          => __( 'Location', 'east-property' ),
												'selected_title' => $selected_location->name ?? __( 'Select',
														'east-property' ),
												'selected_key'   => $selected_location->term_id ?? null,
												'items'          => $location_items,
												'lang_sync'      => 'location_id',
											)
										);
										?>
									</div>

									<div class="input-group">
										<?php
										get_component_template(
											'ui/dropdown',
											array(
												'input_name'     => $language['slug'] . '[ownership_type]',
												'title'          => __( 'Ownership type', 'east-property' ),
												'selected_title' => $ownership_type
													? ( $ownership_type_options[ $ownership_type ] ?? $ownership_type )
													: __( 'Select', 'east-property' ),
												'selected_key'   => $ownership_type,
												'items'          => $ownership_type_options,
												'search_enabled' => false,
												'lang_sync'      => 'ownership_type',
											)
										);
										?>
									</div>
								</div>

								<div class="inputs-wrapper">
									<div class="inputs-box">
										<div class="inputs-group centered">
											<div class="input-group half-width">
												<label for="s-delivery-date_<?php echo esc_attr( $language['slug'] ); ?>">
													<?php esc_html_e( 'Handover Date', 'east-property' ); ?>
													<input type="date"
													       id="s-delivery-date_<?php echo esc_attr( $language['slug'] ); ?>"
													       name="<?php echo esc_attr( $language['slug'] . '[delivery_date]' ); ?>"
													       value="<?php echo esc_attr( $delivery_date ); ?>"
													       placeholder="21.07.2029"
													       data-lang-sync="delivery_date"
														<?php disabled( true, $is_completed ); ?>
													/>
												</label>
											</div>
											<div class="input-group half-width">
												<label class="custom-checkbox">
													<input type="checkbox"
													       name="<?php echo esc_attr( $language['slug'] . '[is_completed]' ); ?>"
													       value="1"
													       data-lang-sync="is_completed"
														<?php checked( true, $is_completed ); ?>
													>
													<span><?php esc_html_e( 'Project is already completed / Ready',
															'east-property' ); ?></span>
												</label>
											</div>
										</div>
									</div>
								</div>

								<div class="inputs-group">
									<div class="checkbox-buttons-wrapper" data-checkbox-buttons data-multiply="true"
									     data-lang-sync="property_types">
									<span class="dropdown-label">
										<span class="required">*</span>
										<?php esc_html_e( 'Contain Property Types ', 'east-property' ); ?>
									</span>
										<div class="checkbox-buttons">
											<?php
											foreach ( $property_type_options as $option_key => $option ) {
												$active_class = in_array( $option_key, $property_types,
													true ) ? 'active' : '';
												?>
												<button type="button"
												        class="checkbox-btn <?php echo esc_attr( $active_class ); ?>"
												        data-value="<?php echo esc_attr( $option_key ); ?>">
													<?php echo esc_html( $option ); ?>
												</button>
											<?php } ?>
										</div>
										<input type="hidden"
										       name="<?php echo esc_attr( $language['slug'] . '[property_types]' ); ?>"
										       value="<?php echo esc_attr( implode( ',', $property_types ) ); ?>"
										       data-required>
									</div>
								</div>

								<div class="input-group">
									<label for="s-desc_<?php echo esc_attr( $language['slug'] ); ?>">
										<span class="required">*</span>
										<?php
										esc_html_e( 'Description', 'east-property' );

										wp_editor(
											$description,
											's-desc_' . $language['slug'],
											array(
												'textarea_name' => $language['slug'] . '[description]',
												'textarea_rows' => 12,
												'media_buttons' => false,
												'teeny'         => false,
												'quicktags'     => false,
												'tinymce'       => array(
													'toolbar1' => 'formatselect,bold,italic,bullist,numlist,blockquote,undo,redo',
													'toolbar2' => '',
												),
											)
										);
										?>
									</label>
								</div>
							</fieldset>

							<div class="submit-unit-bottom" data-show-on-lang="<?php echo esc_attr( $slug ); ?>">
								<?php
								get_template_part(
									'core/components/ui/button',
									null,
									array(
										'class' => 'green orange xl',
										'text'  => $property ? __( 'Update project', 'east-property' )
											: __( 'Save', 'east-property' ),
										'src'   => THEME_URL . '/assets/img/rect.svg',
										'type'  => 'submit',
									)
								);
								?>
							</div>
							<?php
						}
						restore_previous_locale();
						?>
					</div>

					<script>
						let defaultUserFiles = [];
						<?php
						$gallery_images = $property ? $property->get_gallery() : array();
						$user_thumbnail_ids = '';
						if ( ! empty( $gallery_images ) ) {
						foreach ( $gallery_images as $gallery_image ) {
						if ( empty( $gallery_image['ID'] ) ) {
							continue;
						}

						$user_thumbnail_ids .= $gallery_image['ID'] . ',';
						?>
						defaultUserFiles.push({
							id: <?php echo esc_attr( $gallery_image['ID'] ); ?>,
							url: '<?php echo esc_url( $gallery_image['sizes']['thumbnail'] ); ?>',
							isSelected: true,
							canDelete: true,
							attachmentId: <?php echo esc_attr( $gallery_image['ID'] ); ?>,
							isOldImage: true,
						});
						<?php
						}
						}
						?>
					</script>

					<div class="submit-unit-right">
						<div class="uploader">
							<input type="hidden" name="user_thumbnails_ids"
							       value="<?php echo esc_attr( $property_gallery_ids ); ?>"
							       id="user-thumbnail-ids">
							<div class="uploader-user" id="uploader-user">
								<div class="uploader-header" id="user-header">
									<h2><?php esc_html_e( 'Pictures', 'east-property' ); ?></h2>
									<button type="button" class="browse-link" id="trigger-browse">
										<?php esc_html_e( 'Browse files', 'east-property' ); ?>
									</button>
								</div>
								<div class="uploader-dropzone" id="uploader-dropzone">
									<input type="file" class="filepond" name="filepond" multiple
									       data-max-file-size="10MB" data-max-files="50">
								</div>
								<div class="uploader-grid" id="user-grid">
									<!-- Сюда будут рендерится загруженные фото юзера. Не удалять -->
								</div>
							</div>
						</div>

						<div class="uploader-presets hidden">
							<div class="uploader-grid" id="presets-grid">

							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
</section>
