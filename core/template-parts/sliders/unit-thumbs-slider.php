<?php

/**
 * source from sections/unit/items/thumbs-slider.html
 */
$gallery = $args['gallery'] ?? array();
if ( empty( $gallery ) ) {
	return;
}

$main_image              = $gallery[0];
$next_four_images        = 1 < count( $gallery ) ? array_slice( $gallery, 1, 4 ) : array();
$last_gallery_key        = array_key_last( $next_four_images );
$additional_images_count = count( $gallery ) - 5;
?>
	<div class="unit-gallery">
		<div class="unit-gallery-desktop">
			<div class="unit-gallery-main">
				<?php if ( ! empty( $main_image ) ) { ?>
					<div class="unit-gallery-item big" data-gallery-index="0" data-modal-open="gallery-modal">
						<img src="<?php echo esc_url( $main_image['url'] ); ?>"
							alt="<?php echo esc_attr( $main_image['title'] ); ?>">
					</div>
				<?php } ?>
			</div>
			<?php if ( ! empty( $next_four_images ) ) { ?>
				<div class="unit-gallery-side">
					<?php foreach ( $next_four_images as $image_key => $image ) { ?>
						<div class="unit-gallery-item" data-gallery-index="1" data-modal-open="gallery-modal">
							<img src="<?php echo esc_url( $image['sizes']['large'] ); ?>"
								alt="<?php echo esc_attr( $image['title'] ); ?>">
							<?php if ( $image_key === $last_gallery_key && 0 < $additional_images_count ) { ?>
								<div class="unit-gallery-overlay">
									+<?php echo esc_attr( $additional_images_count ); ?></div>
							<?php } ?>
						</div>
					<?php } ?>
				</div>
			<?php } ?>
		</div>

		<div class="unit-gallery-mobile">
			<div class="swiper main-swiper">
				<div class="swiper-wrapper">
					<?php foreach ( $gallery as $image_key => $image ) { ?>
						<div class="swiper-slide" data-gallery-index="<?php echo esc_attr( $image_key ); ?>"
							data-modal-open="gallery-modal">
							<img class="swiper-slide-img"
								src="<?php echo esc_url( $image['sizes']['medium_large'] ); ?>"
								alt="<?php echo esc_attr( $main_image['title'] ); ?>">
						</div>
					<?php } ?>
				</div>
				<?php if ( 1 < count( $gallery ) ) { ?>
					<button class="swiper-prev">
						<img class="swiper-slide-img"
							src="<?php echo esc_attr( THEME_URL ); ?>/assets/img/swiper-arr.svg"
							width="16" height="16" alt="<?php esc_html_e( 'Prev' , 'east-property' ); ?>">
					</button>
					<button class="swiper-next">
						<img class="swiper-slide-img"
							src="<?php echo esc_attr( THEME_URL ); ?>/assets/img/swiper-arr.svg"
							width="16" height="16" alt="<?php esc_html_e( 'Next' , 'east-property' ); ?>">
					</button>
				<?php } ?>
			</div>

			<div class="swiper thumbs-swiper-container">
				<div class="swiper-wrapper">
					<?php foreach ( $gallery as $image_key => $image ) { ?>
						<div class="swiper-slide">
							<img class="swiper-slide-img" src="<?php echo esc_url( $image['sizes']['thumbnail'] ); ?>"
								alt="<?php echo esc_attr( $main_image['title'] ); ?>">
						</div>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
<?php
get_template_part( 'core/template-parts/modals/gallery-modal', null, array( 'gallery' => $gallery ) );