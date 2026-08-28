<?php

$redirect_to = core_home_url( '/account' );
?>
<div class="modal-wrapper boost-modal" data-modal-id="boost-modal">
	<div class="modal">
		<div class="modal-info">
			<div class="modal-title">
				<h3>
					<?php _e( 'Boost your property' , 'east-property' ); ?>
				</h3>
				<button class="modal-close" data-modal-close aria-label="Close">
					<img src="<?php echo THEME_URL; ?>/assets/img/close.svg" width="24" height="24" alt="Close icon">
				</button>
			</div>

			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" name="boost_form">
				<input type="hidden" name="action" value="boost_property">
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>">
				<?php wp_nonce_field( 'boost_property_nonce' ); ?>
				<p>
					<?php esc_html_e( 'Choose how many days you want to boost this listing. During the boost period,' , 'east-property' ); ?>
					<?php esc_html_e( ' your listing will receive higher priority and appear more often to potential buyers.' , 'east-property' ); ?>
				</p>
				<fieldset>
					<div class="input-group">
						<?php
						$boost_plans_items = array();
						foreach ( BOOST_PLANS as $plan ) {
							$boost_plans_items[ $plan['days'] ] = $plan['title'];
						}
						$first_key = array_keys( $boost_plans_items )[0] ?? null;

						get_component_template(
							'ui/dropdown',
							array(
								'input_name' => 'boost_plan',
								'title' => '',
								'link_text' => '',
								'link_url' => '',
								'selected_title' => $boost_plans_items[ $first_key ] ?? __( 'Select' , 'east-property' ),
								'selected_key' => $first_key,
								'items' => $boost_plans_items,
								'search_enabled' => false,
							)
						);
						?>
					</div>
				</fieldset>
				<div class="submit-group between">
					<button class="button green orange sm" type="submit">
						<svg width="15" height="15" viewBox="0 0 15 15" fill="none"
						     xmlns="http://www.w3.org/2000/svg">
							<path d="M8.70935 1.51092L9.94121 3.99501C10.1092 4.34081 10.5571 4.67249 10.9351 4.736L13.1679 5.11003C14.5957 5.34997 14.9317 6.39442 13.9028 7.42475L12.167 9.1749C11.873 9.4713 11.712 10.0429 11.803 10.4522L12.2999 12.6187C12.6919 14.3336 11.789 14.997 10.2842 14.1007L8.1914 12.8516C7.81344 12.6258 7.19051 12.6258 6.80556 12.8516L4.71279 14.1007C3.21495 14.997 2.30505 14.3266 2.69701 12.6187L3.19395 10.4522C3.28494 10.0429 3.12396 9.4713 2.83 9.1749L1.09419 7.42475C0.0723 6.39442 0.401264 5.34997 1.82911 5.11003L4.06186 4.736C4.43282 4.67249 4.88077 4.34081 5.04875 3.99501L6.28061 1.51092C6.95254 0.163025 8.04442 0.163025 8.70935 1.51092Z"
							      stroke="#181A20" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
						<?php esc_html_e( 'Boost' , 'east-property' ); ?>
					</button>
				</div>
			</form>
		</div>
	</div>
</div>