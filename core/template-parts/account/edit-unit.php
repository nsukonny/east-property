<?php
/**
 * Add or edit unit template
 *
 * @var Entities\Unit $unit
 */

$unit = $args['unit'] ?? null;
if ( null !== $unit && ! $unit->exists() ) {
	$unit = null;
}

$translations = null !== $unit ? $unit->get_translations() : array();

$properties        = $args['properties'] ?? array();
$unit_type_choices = $args['unit_type_choices'] ?? array();
$beds_options      = function_exists( 'get_filter_beds_options' ) ? get_filter_beds_options() : array();
$baths_options     = function_exists( 'get_filter_baths_options' ) ? get_filter_baths_options() : array();

$h1       = $unit ? __( 'Edit property:', 'east-property' ) . ' ' . $unit->get_title() : __( 'Add new property',
	'east-property' );
$tab_text = $unit ? __( 'Edit property', 'east-property' ) : __( 'Add property', 'east-property' );

$unit_property_id = $unit ? $unit->get_property()?->get_id() : '';
$beds             = $unit ? $unit->get_beds() : '';
$baths            = $unit ? $unit->get_baths() : '';
$area             = $unit ? $unit->get_area() : '';
$price            = $unit ? $unit->get_price() : '';
$type_of_sale     = $unit ? $unit->get_field( 'type_of_sale' ) : '';
$unit_type        = $unit ? $unit->get_unit_type() : '';
if ( empty( $unit_type ) ) {
	$unit_type = $unit_type_choices ? array_key_first( $unit_type_choices ) : '';
}
$listing_type   = core_sanitize_listing_type( $unit ? $unit->get_listing_type() : 'off-plan' );
$is_distress    = 'distress' === $listing_type;
$original_price = $unit && $is_distress ? $unit->get_original_price() : '';

$selected_property = $properties[0] ?? null;
if ( ! empty( $unit_property_id ) ) {
	foreach ( $properties as $property ) {
		if ( $property->get_id() === $unit_property_id ) {
			$selected_property = $property;
			break;
		}
	}
}

$errors           = $unit ? $unit->get_approve_errors() : '';
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
				<input type="hidden" name="action" value="create_unit">
				<?php wp_nonce_field( 'account_create_unit_nonce' ); ?>

				<?php
				if ( ! empty( $translations ) ) {
					foreach ( $translations as $lang => $translation ) {
						?>
						<input type="hidden" name="<?php echo esc_attr( $lang ); ?>[unit_id]"
						       value="<?php echo esc_attr( $translation->get_id() ); ?>">
						<?php
					}
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

							// Resolved inside the loop so each locale gets its own labels.
							$listing_type_choices = core_get_listing_type_choices();

							$unit_title  = isset( $translations[ $slug ] ) ? $translations[ $slug ]->get_title() : '';
							$description = isset( $translations[ $slug ] ) ? $translations[ $slug ]->get_description_full() : '';
							?>
							<fieldset data-show-on-lang="<?php echo esc_attr( $slug ); ?>">
								<?php if ( ! empty( $errors ) ) { ?>
									<div class="input-group input-group-errors">
										<?php echo wp_kses_post( $errors ); ?>
									</div>
								<?php } ?>

								<div class="input-group">
									<label for="s-text_<?php echo esc_attr( $language['slug'] ); ?>">
										<span class="required">*</span>
										<?php esc_html_e( 'Title', 'east-property' ); ?>
										<input type="text" id="s-text_<?php echo esc_attr( $language['slug'] ); ?>"
										       name="<?php echo esc_attr( $language['slug'] . '[unit_title]' ); ?>"
										       value="<?php echo esc_html( $unit_title ); ?>"
										       placeholder="<?php esc_html_e( 'City Walk 4BR Apartment on 2 floor',
												   'east-property' ); ?>"
										       required>
									</label>
								</div>
								<div class="inputs-group">
									<div class="input-group half-width">
										<?php
										get_component_template(
											'ui/dropdown',
											array(
												'input_name'     => $language['slug'] . '[listing_type]',
												'title'          => __( 'Listing Type', 'east-property' ),
												'search_enabled' => false,
												'selected_title' => $listing_type_choices[ $listing_type ],
												'selected_key'   => $listing_type,
												'items'          => $listing_type_choices,
												'lang_sync'      => 'listing_type',
											),
										);
										?>
									</div>
								</div>
								<div class="inputs-wrapper">
									<div class="inputs-box">
										<div class="inputs-group">
											<div class="input-group">
												<label for="s-price_<?php echo esc_attr( $language['slug'] ); ?>">
													<span class="required">*</span>
													<?php esc_html_e( 'Price, AED', 'east-property' ); ?>
													<input type="number"
													       id="s-price_<?php echo esc_attr( $language['slug'] ); ?>"
													       name="<?php echo esc_attr( $language['slug'] . '[price]' ); ?>"
													       min="0" required
													       value="<?php echo esc_attr( $price ); ?>"
													       placeholder="440000"
													       data-lang-sync="price"
													/>
												</label>
											</div>
											<div class="input-group half-width">
												<label for="s-sqrt_<?php echo esc_attr( $language['slug'] ); ?>">
													<span class="required">*</span>
													<?php esc_html_e( 'Area (sqft)', 'east-property' ); ?>
													<input type="number"
													       id="s-sqrt_<?php echo esc_attr( $language['slug'] ); ?>"
													       name="<?php echo esc_attr( $language['slug'] . '[area]' ); ?>"
													       min="200"
													       step="0.01"
													       placeholder="210"
													       value="<?php echo esc_attr( $area ); ?>"
													       data-lang-sync="area"
													       required>
												</label>
											</div>
										</div>
										<span class="sqrt-value"
										      data-sqrt-value><?php esc_html_e( 'Price per square foot:',
												'east-property' ); ?>
										<span></span>
									</span>
									</div>
								</div>
								<div class="inputs-wrapper<?php echo $is_distress ? '' : ' hidden'; ?>"
								     data-distress-fields>
									<div class="inputs-box">
										<div class="inputs-group">
											<div class="input-group">
												<label for="s-original-price_<?php echo esc_attr( $language['slug'] ); ?>">
													<span class="required">*</span>
													<?php esc_html_e( 'Original price, AED', 'east-property' ); ?>
													<input type="number"
													       id="s-original-price_<?php echo esc_attr( $language['slug'] ); ?>"
													       name="<?php echo esc_attr( $language['slug'] . '[original_price]' ); ?>"
													       min="0"
													       value="<?php echo esc_attr( $original_price ); ?>"
													       placeholder="520000"
													       data-lang-sync="original_price"
													/>
												</label>
											</div>
										</div>
										<span class="sqrt-value"
										      data-discount-value><?php esc_html_e( 'Discount:',
												'east-property' ); ?>
										<span></span>
									</span>
									</div>
								</div>
								<div class="inputs-group">
									<div class="input-group" data-project-images-switcher="true">
										<?php
										$properties_items = array();
										foreach ( $properties as $property ) {
											$properties_items[ $property->get_id() ] = $property->get_title();
										}

										get_component_template(
											'ui/dropdown',
											array(
												'input_name'     => $language['slug'] . '[property_id]',
												'title'          => __( 'Project', 'east-property' ),
												'link_text'      => __( '+ Add new project', 'east-property' ),
												'link_url'       => esc_url( core_home_url( '/account?action=add_property' ) ),
												'selected_title' => $selected_property->get_title() ?? __( 'Select',
														'east-property' ),
												'selected_key'   => $selected_property->get_id() ?? '',
												'items'          => $properties_items,
												'lang_sync'      => 'property_id',
											)
										);
										?>
									</div>

									<?php if ( ! empty( $unit_type_choices ) ) { ?>
										<div class="input-group">
											<?php
											get_component_template(
												'ui/dropdown',
												array(
													'input_name'     => $language['slug'] . '[unit_type]',
													'title'          => __( 'Property type', 'east-property' ),
													'selected_title' => $unit_type_choices[ $unit_type ] ?? __( 'Select',
															'east-property' ),
													'selected_key'   => $unit_type ?? '',
													'items'          => $unit_type_choices,
													'lang_sync'      => 'unit_type',
												)
											);
											?>
										</div>
									<?php } ?>
								</div>
								<div class="inputs-group">
									<div class="submit-buttons-wrapper">
										<span class="dropdown-label">
											<span class="required">*</span>
											<?php esc_html_e( 'Bedrooms', 'east-property' ); ?>
										</span>
										<div class="submit-buttons" data-beds-group>
											<?php
											foreach ( $beds_options['options'] as $option ) {
												$active_class = (int) $option['value'] === $beds ? 'active' : '';
												?>
												<button type="button"
												        class="beds-baths-btn <?php echo esc_attr( $active_class ); ?>"
												        data-beds="<?php echo esc_attr( $option['value'] ); ?>">
													<?php echo esc_html( $option['label'] ); ?>
												</button>
											<?php } ?>
										</div>
										<input type="hidden"
										       name="<?php echo esc_attr( $language['slug'] . '[bedrooms]' ); ?>"
										       value="<?php echo esc_attr( $beds ); ?>"
										       data-lang-sync="beds"
										       data-required>
									</div>
									<div class="submit-buttons-wrapper">
									<span class="dropdown-label">
										<span class="required">*</span>
										<?php esc_html_e( 'Bathrooms', 'east-property' ); ?>
									</span>
										<div class="submit-buttons" data-baths-group>
											<?php
											foreach ( $baths_options['options'] as $option ) {
												$active_class = (int) $option['value'] === $baths ? 'active' : '';
												?>
												<button type="button"
												        class="beds-baths-btn <?php echo esc_attr( $active_class ); ?>"
												        data-beds="<?php echo esc_attr( $option['value'] ); ?>">
													<?php echo esc_html( $option['label'] ); ?>
												</button>
											<?php } ?>
										</div>
										<input type="hidden"
										       name="<?php echo esc_attr( $language['slug'] . '[bathrooms]' ); ?>"
										       value="<?php echo esc_attr( $baths ); ?>"
										       data-lang-sync="baths"
										       data-required>
									</div>
								</div>
								<div class="input-group">
									<label for="s-desc">
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
								if ( ! empty( $unit ) ) {
									$delete_link = core_home_url( '/account?action=delete_unit&unit_id=' . esc_attr( $unit->get_id() ) );
									?>
									<div class="delete_wrapper">
										<a href="#" class="button delete_approve_call">
											<?php esc_html_e( 'Delete Property', 'east-property' ); ?>
										</a>
										<div class="delete_approve">
										<span><?php esc_html_e( 'Are you sure to delete this property?',
												'east-property' ); ?></span>
											<a href="#" class="cancel">
												<?php esc_html_e( 'No', 'east-property' ); ?>
											</a>
											<a href="<?php echo esc_url( $delete_link ); ?>"
											   class="delete_confirmation">
												<?php esc_html_e( 'Yes, Delete', 'east-property' ); ?>
											</a>
										</div>
									</div>
								<?php } else { ?>
									<span>
									<?php esc_html_e( 'New units are saved as drafts for moderation',
										'east-property' ); ?>
								</span>
								<?php } ?>

								<button class="button green orange xl" type="submit">
									<img src="<?php echo esc_url( THEME_URL ); ?>/assets/img/rect.svg'" width="16"
									     height="16" alt="">
									<?php if ( $unit ) { ?>
										<?php esc_html_e( 'Update Unit', 'east-property' ); ?>
									<?php } else { ?>
										<?php esc_html_e( 'Submit Unit', 'east-property' ); ?>
									<?php } ?>
								</button>
							</div>
							<?php
						}
						restore_previous_locale();
						?>
					</div>
					<script>
						let defaultUserFiles = [];
						<?php
						$property_gallery_ids = $selected_property->get_gallery_ids();
						$gallery_images = $unit ? $unit->get_gallery() : array();
						$user_thumbnail_ids = '';
						if ( ! empty( $gallery_images ) ) {
						foreach ( $gallery_images as $gallery_image ) {
						if ( in_array( $gallery_image['ID'], $property_gallery_ids, true ) ) {
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
							       value="<?php echo esc_html( $user_thumbnail_ids ); ?>"
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

							<div class="uploader-presets">
								<div class="uploader-header">
									<span class="required">*</span>
									<h2>
										<?php esc_html_e( 'Select property pictures to include in unit page',
											'east-property' ); ?>
									</h2>
									<button type="button" class="select-all" id="select-all-images">
										<?php esc_html_e( 'Select all', 'east-property' ); ?>
									</button>
								</div>
								<div class="uploader-grid" id="presets-grid">
									<?php
									$property_images = $selected_property->get_gallery();
									$unit_images_ids = $unit ? $unit->get_gallery_ids() : array();
									if ( ! empty( $property_images ) ) {
										foreach ( $property_images as $property_image ) {
											if ( empty( $property_image ) || 0 === $property_image['ID'] ) {
												continue;
											}

											$selected_class = in_array(
												$property_image['ID'],
												$unit_images_ids,
												true
											) ? 'is-selected' : '';
											?>
											<div class="uploader-item <?php echo esc_attr( $selected_class ); ?>"
											     data-id="<?php echo esc_attr( $property_image['ID'] ); ?>"
											     data-url="<?php echo esc_url( $property_image['sizes']['large'] ); ?>">
												<img src="<?php echo esc_url( $property_image['sizes']['thumbnail'] ); ?>"
												     alt="<?php esc_html_e( 'Property image', 'east-property' ); ?>">
											</div>
											<?php
										}
									}
									?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
</section>

