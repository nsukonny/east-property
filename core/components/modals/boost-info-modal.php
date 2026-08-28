<?php
/**
 * Modal: How It Works Modal
 */

?>
<div class="modal-wrapper desc-modal" data-modal-id="boost-info-modal">
	<div class="modal">
		<div class="modal-info">
			<div class="modal-title">
				<h3><?php esc_html_e( 'Boost your property' , 'east-property' ); ?></h3>
				<button class="modal-close" data-modal-close aria-label="Close">
					<img src="<?php echo THEME_URL; ?>/assets/img/close.svg" width="24" height="24" alt="Close icon">
				</button>
			</div>
			<div class="modal-desc">
				<div class="modal-desc-content">
					<p>
						<?php
						esc_html_e( 'The Gold Star Boost Program is an exclusive visibility tool created for 
						sellers and agents who demand the "top spot." By leveraging your earned Star Points, you can 
						instantly transform any standard listing into a featured powerhouse, ensuring your property is 
						the first thing potential buyers see when they start their search.' , 'east-property' );
						?>
					</p>

					<h4><?php esc_html_e( 'How much does it cost?' , 'east-property' ); ?></h4>
					<p>
						<?php
						esc_html_e( 'Boost costs are based on your selected timeframe. For example, you can 
					secure 24 hours of top-tier visibility for 250 points.' , 'east-property' );
						?>
					</p>

					<h4>
						<?php esc_html_e( 'How long is the boost active?' , 'east-property' ); ?>
					</h4>
					<p>
						<?php
						esc_html_e( 'Your property will hold the "Top Spot" for the full duration you select, 
						starting the exact moment you hit activate.' , 'east-property' );
						?>
					</p>

					<h4><?php esc_html_e( 'What happens after the boost ends?' , 'east-property' ); ?></h4>
					<p>
						<?php
						esc_html_e( 'Your listing will return to its standard position in the search results. 
						To maintain your lead, you can redeploy another boost at any time to stay front and center.' , 'east-property' );
						?>
					</p>
				</div>
			</div>
		</div>
	</div>
</div>