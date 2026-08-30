<?php
/**
 * Card for unit
 *
 * @var array $args
 */

$unit_id        = $args['unit_id'] ?? null;
$title          = $args['title'] ?? '';
$price          = $args['price'] ?? '';
$original_price = $args['original_price'] ?? '';
$discount       = $args['discount'] ?? '';
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
$is_favorite    = $args['is_favorite'] ?? false;
$broker         = $args['broker'] ?? '';

if ( empty( $title ) || empty( $price ) || empty( $gallery ) ) {
	return;
}

if ( ! empty( $amenities ) ) {
	foreach ( $amenities as $key => $amenity ) {
		if ( '0 ' . __( 'Beds' , 'east-property' ) === $amenity['value'] ) {
			$amenities[ $key ]['value'] = __( 'Studio' , 'east-property' );
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
								     width="16" height="16" alt="<?php _e( 'Prev' , 'east-property' ); ?>">
							</button>
							<button class="swiper-next sm">
								<img src="<?php echo THEME_URL ?>/assets/img/swiper-arr.svg"
								     width="16" height="16" alt="<?php _e( 'Next' , 'east-property' ); ?>">
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

						<?php if ( ! empty( $original_price ) ) { ?>
							<div class="unit-card-discount">
								<span class="unit-card-original-price"><?php echo esc_html( $original_price ); ?></span>
								<?php if ( ! empty( $discount ) ) { ?>
									<span class="unit-card-discount-badge">
										<?php
										/* translators: %d is the discount percent off the original price. */
										printf( esc_html__( '-%d%%' , 'east-property' ), (int) $discount );
										?>
									</span>
								<?php } ?>
							</div>
						<?php } ?>

						<?php if ( ! empty( $developer_name ) ) { ?>
							<span class="unit-card-desc"><?php _e( 'Apartment by ' , 'east-property' ); ?><?php echo esc_html( $developer_name ); ?></span>
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
										     alt="<?php esc_html_e( 'Star' , 'east-property' ); ?>">
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
                                             alt="<?php _e( 'Vector icon' , 'east-property' ); ?>">
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
								<a href="<?php echo esc_url( $url ); ?>" class="button gray sm view_details"
								   target="_blank">
									<?php esc_html_e( 'View details' , 'east-property' ); ?>
								</a>
								<?php
							}

							if ( ! empty( $edit_link ) || true === $is_can_boost ) {
								if ( ! empty( $edit_link ) ) {
									?>
									<a href="<?php echo esc_url( $edit_link ); ?>"
									   class="button gray sm edit-property-link">
										<img src="<?php echo esc_url( THEME_URL . '/assets/img/edit.svg' ); ?>"
										     alt="<?php esc_html_e( 'Edit' , 'east-property' ); ?>">
										<?php _e( 'Edit property' , 'east-property' ); ?>
									</a>
									<?php
								}

								if ( ! empty( $is_can_boost ) ) {
									?>
									<button class="button orange sm" data-modal-open="boost-modal"
									        data-unit_id="<?php echo esc_attr( $unit_id ); ?>">
										<img src="<?php echo esc_url( THEME_URL . '/assets/img/star.svg' ); ?>"
										     alt="<?php esc_html_e( 'Star' , 'east-property' ); ?>">
										<?php esc_html_e( 'Boost property' , 'east-property' ); ?>
									</button>
									<?php
								}
							} else {
								?>
								<button class="button sm toggle-favorite <?php echo $is_favorite ? 'orange green' : 'gray'; ?>"
								        data-unit-id="<?php echo $unit_id; ?>">
									<?php require THEME_PATH . '/assets/img/bookmark.svg'; ?>
								</button>

								<?php if ( ! empty( $broker ) ) {
									$whatsapp_text  = __( 'Hello, I am interested in property -' , 'east-property' ) . ' ' . $url;
									$whats_app_link = $broker->get_whatsapp( $whatsapp_text ) ?: WHATS_APP_LINK;
									?>
									<button class="button orange sm"
									        data-modal-open="broker-modal"
									        data-broker-phone="tel:<?php echo $broker->get_phone(); ?>"
									        data-broker-whatsapp="<?php echo esc_url( $whats_app_link ) ?>"
									>
										<?php echo esc_html( 'Contact broker' ); ?>
									</button>
								<?php } ?>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>