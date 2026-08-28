<?php
$gallery = $args['gallery'] ?? null;
if ( empty( $gallery ) ) {
	return;
}
?>
<div class="modal-wrapper gallery-modal dark " data-modal-id="gallery-modal">
	<div class="modal">
		<div class="modal-top">
			<div class="gallery-modal-counter"></div>
			<button class="modal-close" data-modal-close aria-label="Close">
				<img src="<?php echo esc_attr( THEME_URL ); ?>/assets/img/close-white.svg" width="24" height="24"
					alt="Close icon">
			</button>
		</div>
		<div class="swiper gallery-swiper">
			<div class="swiper-wrapper">
				<?php foreach ( $gallery as $image ) { ?>
					<div class="swiper-slide">
						<img src="<?php echo esc_url( $image['sizes']['large'] ); ?>"
							alt="<?php echo esc_attr( $image['title'] ); ?>">
					</div>
				<?php } ?>
			</div>
		</div>
		<?php if ( 1 < count( $gallery ) ) { ?>
			<div class="swiper-actions">
				<button class="swiper-prev gallery-arrow-prev">
					<img src="<?php echo esc_attr( THEME_URL ); ?>/assets/img/swiper-arr.svg" width="16" height="16"
						alt="Prev">
				</button>
				<button class="swiper-next gallery-arrow-next">
					<img src="<?php echo esc_attr( THEME_URL ); ?>/assets/img/swiper-arr.svg" width="16" height="16"
						alt="Next">
				</button>
			</div>
		<?php } ?>
	</div>
</div>

