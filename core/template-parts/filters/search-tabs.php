<?php
/**
 * Search tabs template
 */

$search_tabs_data = $args['search_tabs_data'] ?? array();
$form_action      = $args['form_action'] ?? core_home_url( '/off-plan/' );
$is_show_tabs     = $args['show_tabs'] ?? true;

if ( empty( $search_tabs_data ) ) {
	return;
}

$max_price          = number_format( $search_tabs_data['price_max'] ?? 100000000000, 0, '', ',' );
$available_last_key = array_key_last( $search_tabs_data['filters']['available']['options'] );
$available          = $search_tabs_data['filters']['available']['options'][ $available_last_key ];

$price_last_key = array_key_last( $search_tabs_data['filters']['price']['options'] );
$price          = $search_tabs_data['filters']['price']['options'][ $price_last_key ];

$baths = $search_tabs_data['filters']['baths'] ?? array();
$beds  = $search_tabs_data['filters']['beds'];
?>
<script>
	const searchTabsData =
		<?php
		echo wp_json_encode(
			$search_tabs_data,
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		);
		?>
	;
</script>
<div class="search-tabs" data-search-tabs>
	<?php if ( $is_show_tabs && ! empty( $search_tabs_data['categories'] ) ) { ?>
		<div class="tabs-buttons" role="tablist">
			<?php foreach ( $search_tabs_data['categories'] as $category_key => $category ) { ?>
				<button type="button" class="button
				<?php
				if ( 0 === $category_key ) {
					?>
				is-active<?php } ?>" role="tab"
				        id="search-tabs-tab-<?php echo esc_attr( $category['slug'] ); ?>"
				        aria-controls="search-tabs-panel" aria-selected="true" tabindex="0" data-search-tab
				        data-type="<?php echo esc_attr( $category['slug'] ); ?>">
					<?php echo esc_html( $category['label'] ); ?>
				</button>
			<?php } ?>
		</div>
	<?php } ?>
	<form action="<?php echo esc_url( $form_action ); ?>" class="tabs-panel" method="get"
	      data-search-panel>
		<div class="tabs-fields" role="tabpanel" id="search-tabs-panel" aria-labelledby="search-tabs-tab-all">

			<?php if ( ! empty( $baths['options'] ) || ! empty( $beds['options'] ) ) { ?>
				<div class="tab-field">
					<button type="button" class="tab-selector" data-search-selector="beds_baths" aria-haspopup="true"
					        aria-expanded="false">
						<span class="tab-field-label"><?php _e( 'Bedrooms' , 'east-property' ); ?></span>
						<span class="tab-selector-value">
						<span data-search-beds-baths-text><?php _e( '' , 'east-property' ); ?></span>
						<img src="<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16"
						     alt="Dropdown arrow">
					</span>
					</button>
					<div class="beds-baths-dropdown" data-search-dropdown="beds_baths" hidden>
						<div class="beds-baths-content">
							<?php if ( ! empty( $beds['options'] ) ) { ?>
								<div class="beds-baths-section">
									<span class="beds-baths-label"><?php echo esc_attr( $beds['label'] ); ?></span>
									<div class="beds-baths-buttons">
										<?php
										foreach ( $beds['options'] as $bed ) {
											$class = ! empty( $bed['active'] ) && true === $bed['active'] ? ' active' : '';
											?>
											<button type="button"
											        class="beds-baths-btn<?php echo esc_attr( $class ); ?>"
											        data-beds="<?php echo esc_attr( $bed['value'] ); ?>"><?php echo esc_attr( $bed['label'] ); ?></button>
										<?php } ?>
									</div>
								</div>
							<?php } ?>

							<?php if ( ! empty( $baths['options'] ) ) { ?>
								<div class="beds-baths-section">
									<span class="beds-baths-label"><?php echo esc_attr( $baths['label'] ); ?></span>
									<div class="beds-baths-buttons">
										<?php
										foreach ( $baths['options'] as $bath ) {
											$class = ! empty( $bath['active'] ) && true === $bath['active'] ? ' active' : '';
											?>
											<button type="button"
											        class="beds-baths-btn<?php echo esc_attr( $class ); ?>"
											        data-baths="<?php echo esc_attr( $bath['value'] ); ?>"><?php echo esc_attr( $bath['label'] ); ?></button>
										<?php } ?>
									</div>
								</div>
							<?php } ?>

							<div class="beds-baths-actions">
								<button class="button gray sm beds-baths-cancel" type="button">
									<span><?php _e( 'Cancel' , 'east-property' ); ?></span>
								</button>
								<button class="button orange green sm beds-baths-apply" type="button">
									<span><?php _e( 'Apply' , 'east-property' ); ?></span>
								</button>
							</div>
						</div>
					</div>
				</div>
			<?php } ?>

			<div class="tab-divider" aria-hidden="true"></div>

			<div class="tab-field">
				<button type="button" class="tab-selector" data-search-selector="location" aria-haspopup="listbox"
				        aria-expanded="false">
					<label for="location-input-search" class="result-filter-search">
						<input type="text" class="dropdown-search-input"
						       id="location-input-search"
						       placeholder="<?php esc_html_e( 'Search...' , 'east-property' ); ?>">
					</label>
					<span class="tab-field-label"><?php esc_html_e( 'District' , 'east-property' ); ?></span>
					<span class="tab-selector-value">
						<span data-search-location-text><?php _e( 'Any Locations' , 'east-property' ); ?></span>
						<img src="<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16"
						     alt="<?php esc_html_e( 'Dropdown arrow' , 'east-property' ); ?>">
					</span>
				</button>
				<div class="tab-dropdown" role="listbox" tabindex="-1" data-search-dropdown="location"
				     data-check-icon="<?php echo THEME_URL; ?>/assets/img/check.svg" hidden></div>
			</div>

			<div class="tab-divider" aria-hidden="true"></div>

			<div class="tab-field">
				<button type="button" class="tab-selector" data-search-selector="developer" aria-haspopup="listbox"
				        aria-expanded="false">
					<label for="developer-input-search" class="result-filter-search">
						<input type="text" class="dropdown-search-input"
						       id="developer-input-search"
						       placeholder="<?php esc_html_e( 'Search...' , 'east-property' ); ?>">
					</label>
					<span class="tab-field-label"><?php esc_html_e( 'Developer' , 'east-property' ); ?></span>
					<span class="tab-selector-value">
						<span data-search-developer-text><?php _e( 'Any Developers' , 'east-property' ); ?></span>
						<img src="<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16"
						     alt="<?php esc_html_e( 'Dropdown arrow' , 'east-property' ); ?>">
					</span>
				</button>
				<div class="tab-dropdown" role="listbox" tabindex="-1" data-search-dropdown="developer"
				     data-check-icon="<?php echo THEME_URL; ?>/assets/img/check.svg" hidden></div>
			</div>

			<button class="button green orange xl submit">
				<svg width="16px" height="16px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path fill-rule="evenodd" clip-rule="evenodd"
					      d="M17.0392 15.6244C18.2714 14.084 19.0082 12.1301 19.0082 10.0041C19.0082 5.03127 14.9769 1 10.0041 1C5.03127 1 1 5.03127 1 10.0041C1 14.9769 5.03127 19.0082 10.0041 19.0082C12.1301 19.0082 14.084 18.2714 15.6244 17.0392L21.2921 22.707C21.6828 23.0977 22.3163 23.0977 22.707 22.707C23.0977 22.3163 23.0977 21.6828 22.707 21.2921L17.0392 15.6244ZM10.0041 17.0173C6.1308 17.0173 2.99087 13.8774 2.99087 10.0041C2.99087 6.1308 6.1308 2.99087 10.0041 2.99087C13.8774 2.99087 17.0173 6.1308 17.0173 10.0041C17.0173 13.8774 13.8774 17.0173 10.0041 17.0173Z"
					      fill="#0F0F0F"/>
				</svg>
				<?php esc_html_e( 'Search' , 'east-property' ); ?>
			</button>
		</div>

		<input type="hidden" name="available" value="all" data-search-type>
		<input type="hidden" name="property_type" value="all" data-search-type>
		<input type="hidden" name="location" value="all" data-search-location-value>
		<input type="hidden" name="developer" value="all" data-search-developer-value>
		<input type="hidden" name="beds" value="" data-search-beds-value>
		<input type="hidden" name="baths" value="" data-search-baths-value>
	</form>
</div>
