<?php
$boost_points = $args['boost_points'] ?? 0;

if ( 700 < $boost_points ) {
	?>
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