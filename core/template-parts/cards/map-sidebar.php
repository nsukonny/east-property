<?php
/**
 * Map sidebar template for display properties on the map
 */

$title           = $args['title'] ?? '';
$location        = $args['location'] ?? '';
$gallery         = $args['gallery'] ?? '';
$units_available = $args['units_available'] ?? '';
$price_from      = $args['price_from'] ?? '';
$url             = $args['url'] ?? '';
$units           = $args['units'] ?? '';
$delivery_date   = $args['delivery_date'] ?? '';
$developer_name  = $args['developer_name'] ?? '';
?>
<div class="building-card">
	<div class="building-card-header">

		<?php if ( ! empty( $gallery ) ) { ?>
			<div class="swiper single-swiper building-card-slider">
				<div class="swiper-wrapper">
					<?php foreach ( $gallery as $gallery_image ) { ?>
						<div class="swiper-slide">
							<img src="<?php echo esc_url( $gallery_image ); ?>" alt="<?php echo esc_attr( $title ); ?>">
						</div>
					<?php } ?>
				</div>
				<button class="swiper-prev sm">
					<img src="<?php echo THEME_URL ?>/assets/img/swiper-arr.svg" width="16"
					     height="16" alt="Prev">
				</button>
				<button class="swiper-next sm">
					<img src="<?php echo THEME_URL ?>/assets/img/swiper-arr.svg" width="16"
					     height="16" alt="Next">
				</button>
			</div>
		<?php } ?>

		<div class="building-card-info">
			<div class="building-card-desc">
				<h3><?php echo esc_html( $title ); ?></h3>
				<p><?php echo esc_html( $location ); ?></p>
			</div>
			<div class="building-card-meta">
				<span class="building-card-badge"><?php echo esc_html( $units_available ) . ' '; ?><?php _e( 'apartments for sale' , 'east-property' ); ?></span>
			</div>
			<div class="building-card-price"><?php echo esc_html( $price_from ); ?></div>
		</div>
	</div>
	<div class="building-card-bottom">
		<?php if ( ! empty( $units ) ) { ?>
			<div class="building-card-units">
				<?php foreach ( $units as $unit ) { ?>
					<a href="<?php echo esc_attr( $unit['url'] ); ?>" class="unit" target="_blank">

						<?php if ( ! empty( $delivery_date ) ) { ?>
							<span class="unit-date"><?php echo esc_html( $delivery_date ); ?></span>
						<?php } ?>

						<div class="unit-inner">
							<?php if ( ! empty( $unit['image'] ) ) { ?>
								<div class="unit-left">
									<img src="<?php echo esc_url( $unit['image'] ); ?>"
									     alt="<?php _e( 'Unit image' , 'east-property' ); ?>">
								</div>
							<?php } ?>

							<div class="unit-right">
								<div class="unit-title">
									<span><?php echo esc_html( $unit['price'] ); ?></span>
									<p><?php echo esc_html( $developer_name ); ?></p>
								</div>
								<div class="unit-items">
									<?php if ( ! empty( $unit['beds'] ) ) { ?>
										<span>
                                            <img src="<?php echo THEME_URL ?>/assets/img/bed.svg" width="16" height="16"
                                                 alt="<?php _e( 'Beds' , 'east-property' ); ?>"/>
                                            <?php echo esc_html( $unit['beds'] ); ?> <?php _e( 'Beds' , 'east-property' ); ?>
                                        </span>
									<?php } ?>
									<?php if ( ! empty( $unit['area'] ) ) { ?>
										<span>
                                            <img src="<?php echo THEME_URL ?>/assets/img/meters.svg" width="16"
                                                 height="16"
                                                 alt="<?php _e( 'Area' , 'east-property' ); ?>"/>
                                                <?php echo esc_html( $unit['area'] ); ?> <?php _e( 'sqft' , 'east-property' ); ?>
                                        </span>
									<?php } ?>
								</div>
							</div>
						</div>
					</a>
				<?php } ?>

			</div>
		<?php } ?>
	</div>
</div>
