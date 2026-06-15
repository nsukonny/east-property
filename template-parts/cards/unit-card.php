<?php
/**
 * Card for unit
 *
 * @var array $args
 */

$unit_id        = $args['unit_id'] ?? null;
$title          = $args['title'] ?? '';
$price          = $args['price'] ?? '';
$location       = $args['location'] ?? '';
$gallery        = $args['gallery'] ?? '';
$labels         = $args['labels'] ?? '';
$amenities      = $args['amenities'] ?? '';
$url            = $args['url'] ?? '#';
$property_name  = $args['property_name'] ?? '';
$property_url   = $args['property_url'] ?? '#';
$developer_name = $args['developer_name'] ?? '';
$edit_link      = $args['edit_link'] ?? '';
$is_can_boost   = $args['is_can_boost'] ?? false;

if ( empty( $title ) || empty( $price ) || empty( $gallery ) ) {
	return;
}

if ( ! empty( $amenities ) ) {
	foreach ( $amenities as $key => $amenity ) {
		if ( '0 ' . __( 'Beds' ) === $amenity['value'] ) {
			$amenities[ $key ]['value'] = __( 'Studio' );
		}
	}
}
?>
<div class="unit-card">
	<div class="unit-card-inner">
		<div class="unit-card-left">
			<?php if ( ! empty( $gallery ) ) { ?>
				<div class="swiper single-swiper">
					<div class="swiper-wrapper">
						<?php foreach ( $gallery as $image ) { ?>
							<div class="swiper-slide">
								<img src="<?php echo esc_url( $image['sizes']['unit-card'] ); ?>"
								     alt="Image">
							</div>
						<?php } ?>
					</div>
					<?php if ( 1 < count( $gallery ) ) { ?>
						<div class="swiper-buttons">
							<button class="swiper-prev sm">
								<img src="<?php echo THEME_URL ?>/assets/img/swiper-arr.svg"
								     width="16" height="16" alt="<?php _e( 'Prev' ); ?>">
							</button>
							<button class="swiper-next sm">
								<img src="<?php echo THEME_URL ?>/assets/img/swiper-arr.svg"
								     width="16" height="16" alt="<?php _e( 'Next' ); ?>">
							</button>
						</div>
					<?php } ?>
				</div>
			<?php } ?>
		</div>

		<div class="unit-card-right">
			<div class="unit-card-top">
				<div class="unit-card-top-left">
					<div class="unit-card-top-title">
						<span class="unit-card-price"><?php echo esc_html( $price ); ?></span>
						<?php if ( ! empty( $developer_name ) ) { ?>
							<span class="unit-card-desc"><?php _e( 'Apartment by' ); ?><?php echo esc_html( $developer_name ); ?></span>
						<?php } ?>

						<?php if ( ! empty( $property_name ) ) { ?>
							<a href="<?php echo esc_url( $property_url ); ?>"><?php echo esc_html( $property_name ); ?></a>
						<?php } ?>
					</div>
				</div>
				<?php if ( ! empty( $labels ) ) { ?>
					<div class="unit-card-top-right">
						<div class="unit-card-labels">
							<?php foreach ( $labels as $label ) { ?>
								<div class="label <?php echo esc_html( $label['color'] ); ?>">
									<?php if ( ! empty( $label['icon'] ) ) { ?>
										<img src="<?php echo esc_url( $label['icon'] ); ?>"
										     alt="<?php esc_html_e( 'Star' ); ?>">
										<?php
									}

									echo esc_html( $label['name'] );
									?>
								</div>
							<?php } ?>
						</div>
					</div>
				<?php } ?>
			</div>

			<div class="unit-card-bottom">
				<div class="unit-card-info">
					<?php if ( ! empty( $amenities ) ) { ?>
						<div class="large-card-info-top">
							<div class="large-card-info-items">
								<?php foreach ( $amenities as $amenity ) { ?>
									<span>
                                        <img src="<?php echo esc_url( $amenity['icon'] ); ?>" width="16" height="16"
                                             alt="<?php _e( 'Vector icon' ); ?>">
                                        <?php echo esc_html( $amenity['value'] ); ?>
                                    </span>
								<?php } ?>
							</div>
						</div>
					<?php } ?>

					<div class="unit-card-info-bottom">
						<p><?php echo esc_html( $title ); ?></p>
						<div class="unit-card-info-buttons">
							<?php if ( ! empty( $url ) ) { ?>
								<a href="<?php echo esc_url( $url ); ?>" class="button gray sm"
								   target="_blank">
									<?php esc_html_e( 'View details' ); ?>
								</a>
							<?php } ?>
							<?php
							if ( ! empty( $edit_link ) || true === $is_can_boost ) {
								if ( ! empty( $edit_link ) ) {
									?>
									<a href="<?php echo esc_url( $edit_link ); ?>"
									   class="button gray sm edit-property-link">
										<img src="<?php echo esc_url( THEME_URL . '/assets/img/edit.svg' ); ?>"
										     alt="<?php esc_html_e( 'Edit' ); ?>">
										<?php _e( 'Edit property' ); ?>
									</a>
									<?php
								}

								if ( ! empty( $is_can_boost ) ) {
									?>
									<button class="button orange sm" data-modal-open="boost-modal"
									        data-unit_id="<?php echo esc_attr( $unit_id ); ?>">
										<img src="<?php echo esc_url( THEME_URL . '/assets/img/star.svg' ); ?>"
										     alt="<?php esc_html_e( 'Star' ); ?>">
										<?php esc_html_e( 'Boost property' ); ?>
									</button>
									<?php
								}
							} else {
								?>
								<button class="button orange sm" data-modal-open="broker-modal">
									<?php esc_html_e( 'Contact broker' ); ?>
								</button>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>