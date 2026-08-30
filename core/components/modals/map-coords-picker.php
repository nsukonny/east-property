<?php
/**
 * Modal: How It Works Modal
 */

global $wp_query;

if ( empty( $wp_query->query['pagename'] ) || 'account' !== $wp_query->query['pagename'] ) {
	return;
}

?>
<div class="modal-wrapper desc-modal" data-modal-id="map-coords-picker-modal">
	<div class="modal">
		<div class="modal-info">
			<div class="modal-title">
				<h3><?php esc_html_e( 'Pick property coordinates', 'east-property' ); ?></h3>
				<button class="modal-close" data-modal-close aria-label="Close">
					<img src="<?php echo THEME_URL; ?>/assets/img/close.svg" width="24" height="24" alt="Close icon">
				</button>
			</div>
			<div class="modal-desc">
				<div class="modal-desc-content" id="map-coords-picker">
					<?php
					get_template_part( 'core/components/properties/map',
						null,
						array(
							'show_sidebar' => false,
							'mode' => 'select',
							'search_by_address' => false, //TODO disabled because need google API
						)
					);
					?>
				</div>
			</div>
		</div>
	</div>
</div>