<?php
/**
 * Filters in the header of the search results page.
 */

$post_type        = $args['post_type'] ?? 'property';
$search_by        = $args['search_by'] ?? array();
$search_tabs_data = $args['search_tabs_data'] ?? array();
$default_filters  = $args['default_filters'] ?? array();
$listing_type     = $args['listing_type'] ?? '';

if ( empty( $search_tabs_data ) || empty( $search_by ) ) {
	return;
}

$baths         = $search_tabs_data['filters']['baths'] ?? null;
$beds          = $search_tabs_data['filters']['beds'] ?? null;
$is_show_beds  = $search_by['beds'] ?? false;
$is_show_baths = $search_by['baths'] ?? false;

//TODO Need to rebuild this search to normal dropdowns
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
<div class="results-filters-wrapper">
	<div class="results-filters-items">
		<input type="hidden" name="action" value="get_<?php echo $post_type; ?>">
		<input type="hidden" name="listing_type" value="<?php echo esc_attr( $listing_type ); ?>">
		<?php
		wp_nonce_field( 'filterjdj3' );

		foreach ( $default_filters as $filter_key => $filter_value ) {
			?>
			<input type="hidden" name="<?php echo esc_attr( $filter_key ); ?>"
			       value="<?php echo esc_attr( $filter_value ); ?>">
			<?php
		}

		if ( $search_by['location'] ?? false ) {
			$selected_location = ! empty( $_GET['location'] ) ? sanitize_text_field( $_GET['location'] ) : 'all';
			?>
			<button class="result-filter" type="button" data-filter="location"
			        data-selected-value="<?php echo esc_attr( $selected_location ); ?>">
				<label for="location-input-search" class="result-filter-search">
					<input type="text" class="dropdown-search-input"
					       id="location-input-search"
					       placeholder="<?php esc_html_e( 'Search...' , 'east-property' ); ?>">
				</label>
				<span class="result-filter-top">
					<span class="result-title">
						<?php _e( 'Location' , 'east-property' ); ?>
					</span>
				</span>
				<span class="result-filter-bottom">
					<span class="result-value">
						<img src="<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16"
						     alt="Dropdown arrow">
					</span>
				</span>
				<span class="result-dropdown"></span>
			</button>
		<?php } ?>

		<?php
		if ( $search_by['available'] ?? false ) {
			$selected_available_year = ! empty( $_GET['available'] ) ? sanitize_text_field( $_GET['available'] ) : 'all';
			?>
			<button class="result-filter" type="button" data-filter="available"
			        data-selected-value="<?php echo esc_attr( $selected_available_year ); ?>">
				<span class="result-filter-top">
					<span class="result-title">
						<?php echo esc_html( $search_tabs_data['filters']['available']['label'] ); ?>
					</span>
				</span>
				<span class="result-filter-bottom">
					<span class="result-value">
						<img src="<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16"
						     alt="Dropdown arrow">
					</span>
				</span>
				<span class="result-dropdown"></span>
			</button>
		<?php } ?>

		<?php
		if ( $search_by['price'] ?? false ) {
			$filter_price_label = $search_tabs_data['filters']['price']['label'] ?? '';
			$filter_price_min   = $_GET['min_price'] ?? $search_tabs_data['filters']['price']['options']['min'];
			$filter_price_max   = $_GET['max_price'] ?? $search_tabs_data['filters']['price']['options']['max'];
			?>
			<div class="result-filter-wrapper" data-filter-field-type="min_max">
				<button class="result-filter" type="button" data-filter="min_max_price">
					<span class="result-filter-top">
						<span class="result-title">
							<?php echo esc_html( $filter_price_label ); ?>
						</span>
					</span>
					<span class="result-filter-bottom">
						<span class="result-value">
							<span data-result-min-max-text>
								<?php echo number_format( floor( $filter_price_min ) ) . ' - ' . number_format( floor( $filter_price_max ) ); ?>
							</span>
							<img src="<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16"
							     alt="Dropdown arrow">
						</span>
					</span>
				</button>
				<div class="min-max-dropdown fixed" data-result-dropdown="min_max_price" hidden>
					<div class="min-max-content">
						<fieldset>
							<label class="input-label">
								<?php esc_html_e( 'Min price' , 'east-property' ); ?>
								<input type="text" name="min_price" class="input field_min"
								       placeholder="<?php echo esc_attr( $filter_price_min ); ?>"
								       value="<?php echo esc_attr( $filter_price_min ); ?>"
								       min="<?php echo esc_attr( $filter_price_min ); ?>"
								       inputmode="numeric"
								>
							</label>
							<span class="separator">-</span>
							<label class="input-label">
								<?php esc_html_e( 'Max price' , 'east-property' ); ?>
								<input type="text" name="max_price" class="input field_max"
								       placeholder="<?php echo esc_attr( $filter_price_max ); ?>"
								       value="<?php echo esc_attr( $filter_price_max ); ?>"
								       max="<?php echo esc_attr( $filter_price_max ); ?>"
								       inputmode="numeric"
								>
							</label>
						</fieldset>

						<div class="min-max-actions">
							<button class="button gray sm min-max-cancel" type="button">
								<span><?php _e( 'Cancel' , 'east-property' ); ?></span>
							</button>
							<button class=" button green orange sm min-max-apply" type="button">
								<span><?php _e( 'Apply' , 'east-property' ); ?></span>
							</button>
						</div>
					</div>
				</div>
			</div>
		<?php } ?>

		<?php if ( $is_show_beds || $is_show_baths ) { ?>
			<div class="result-filter-wrapper">
				<button class="result-filter" type="button" data-filter="beds_baths">
					<span class="result-filter-top">
						<span class="result-title">
							<?php _e( 'Beds' , 'east-property' ); ?>
						</span>
					</span>
					<span class="result-filter-bottom">
						<span class="result-value">
							<span data-result-beds-baths-text><?php _e( 'Select' , 'east-property' ); ?></span>
							<img src="<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16"
							     alt="Dropdown arrow">
						</span>
					</span>
				</button>
				<div class="beds-baths-dropdown fixed" data-result-dropdown="beds_baths" hidden>
					<div class="beds-baths-content">
						<?php if ( $is_show_beds && ! empty( $beds['options'] ) ) { ?>
							<div class="beds-baths-section">
								<span class="beds-baths-label"><?php echo esc_attr( $beds['label'] ); ?></span>
								<div class="beds-baths-buttons">
									<?php
									$active_beds = '';
									foreach ( $beds['options'] as $bed ) {
										$class = ( ! empty( $bed['active'] ) && true === $bed['active'] ) ? ' active' : '';
										?>
										<button type="button"
										        class="beds-baths-btn<?php echo esc_attr( $class ); ?>"
										        data-beds="<?php echo esc_attr( $bed['value'] ); ?>"><?php echo esc_attr( $bed['label'] ); ?></button>
									<?php } ?>
								</div>
							</div>
						<?php } ?>

						<?php if ( $is_show_baths && ! empty( $baths['options'] ) ) { ?>
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
							<button class="button green orange sm beds-baths-apply" type="button">
								<span><?php _e( 'Apply' , 'east-property' ); ?></span>
							</button>
						</div>
					</div>
				</div>
				<input type="hidden" name="beds" value="" data-result-beds-value="">
				<input type="hidden" name="baths" value="" data-result-baths-value>
			</div>
		<?php } ?>

		<?php
		if ( $search_by['property_type'] ?? false ) {
			$property_type = 'All';
			if ( ! empty( $_GET['property_type'] ) ) {
				foreach ( $search_tabs_data['filters']['property_type']['options'] as $option ) {
					if ( $option['value'] !== $_GET['property_type'] ) {
						continue;
					}
					$property_type = $option['label'];
					break;
				}
			}
			?>
			<button class="result-filter" type="button" data-filter="property_type">
				<span class="result-filter-top">
					<span class="result-title">
						<?php _e( 'Property type' , 'east-property' ); ?>
					</span>
				</span>
				<span class="result-filter-bottom">
						<span class="result-value">
							<?php echo esc_html( $property_type ); ?>
							<img src="<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16"
							     alt="Dropdown arrow">
						</span>
				</span>
				<span class="result-dropdown"></span>
			</button>
		<?php } ?>

		<?php
		if ( $search_by['developer'] ?? false ) {
			$selected_developer = ! empty( $_GET['developer'] ) ? sanitize_text_field( $_GET['developer'] ) : 'all';
			?>
			<button class="result-filter color" type="button" data-filter="developer"
			        data-selected-value="<?php echo esc_attr( $selected_developer ); ?>">
				<label for="developer-input-search" class="result-filter-search">
					<input type="text" class="dropdown-search-input"
					       id="developer-input-search"
					       placeholder="<?php esc_html_e( 'Search...' , 'east-property' ); ?>">
				</label>
				<span class="result-filter-top">
					<span class="result-title">
						<?php _e( 'Developer' , 'east-property' ); ?>
					</span>
				</span>
				<span class="result-filter-bottom">
					<span class="result-value">
						<?php _e( 'All' , 'east-property' ); ?>
						<img src="<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16"
						     alt="Dropdown arrow">
					</span>
				</span>
				<span class="result-dropdown">

				</span>
			</button>
		<?php } ?>

		<?php if ( $search_by['max_area'] ?? false ) { ?>
			<button class="result-filter color" type="button" data-filter="area">
				<span class="result-filter-top">
					<span class="result-title">
						<?php _e( 'Max Area' , 'east-property' ); ?>
					</span>
				</span>
				<span class="result-filter-bottom">
					<span class="result-value">
						<?php _e( 'All' , 'east-property' ); ?>
						<img src="<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16"
						     alt="Dropdown arrow">
					</span>
				</span>
				<span class="result-dropdown"></span>
			</button>
		<?php } ?>
	</div>
	<?php
	//TODO hide for MVP
	/*
	<button class="advanced">
		<?php _e('Advanced search'); ?>
		<img src="<?php echo THEME_URL; ?>/assets/img/filters.svg" width="16" height="16" alt="Vector filters">
	</button>
	*/
	?>
</div>