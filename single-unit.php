<?php
/**
 * Single template for Properties (CPT: properties)
 */

use Entities\Unit;

get_header( null, array( 'color' => 'white' ) );

while ( have_posts() ) {
	the_post();

	$unit = new Unit( get_the_ID() );
	if ( ! $unit->exists() ) {
		continue;
	}

	$property = $unit->get_property();
	if ( ! $property->exists() ) {
		continue;
	}

	$gallery             = $unit->get_gallery();
	$developer           = $unit->get_developer();
	$whatsapp_share_text = 'https://wa.me/?text=' . rawurlencode( sprintf(
			'%s | %s | %s View %s',
			$unit->get_title(),
			$property ? $property->get_location()->name : '',
			$unit->get_price_html(),
			get_permalink( $unit->get_id() )
		) );

	$amenities  = $unit->get_amenities();
	$floor_plan = $unit->get_floor_plan();
	$broker     = $unit->get_broker();
	$desc       = $unit->get_description_full();

	$location             = $property->get_location()->name;
	$down_payment_group   = $property->get_down_payment_group();
	$delivery_date        = $property->get_delivery_date();
	$property_information = $property->get_key_information();
	$property_amenities   = $property->get_amenities();
	$building_name        = $property->get_title();
	$floors               = $property->get_floors();
	$latitude             = $property->get_latitude();
	$longitude            = $property->get_longitude();
	$payment_plans        = $property->get_payment_plans();
	?>
	<section class="single-items">
		<div class="container">
			<div class="single-items-wrapper">
				<?php
				get_template_part( 'core/components/common/breadcrumbs' );
				?>
				<div class="single-items-top">
					<div class="single-items-top-left">
						<div class="h2"><?php echo esc_attr( __( 'Apartment by' ) . ' ' . $property->get_title() ); ?></div>
						<h1><?php echo esc_html( $unit->get_price_html() ); ?></h1>
						<div class="single-items-top-buttons">
							<?php
							get_template_part( 'core/components/ui/button', null,
								array(
									'class'  => 'gray sm',
									'text'   => __( 'Share' ),
									'src'    => THEME_URL . '/assets/img/share.svg',
									'alt'    => __( 'Share' ),
									'link'   => $whatsapp_share_text,
									'target' => '_blank',
									'rel'    => 'noopener noreferrer',
								)
							);
							//TODO add saving to wishlist

							//						get_template_part( 'core/components/ui/button', null,
							//							array(
							//								'class' => 'gray sm',
							//								'text'  => __( 'Save' ),
							//								'src'   => THEME_URL . '/assets/img/bookmark.svg',
							//								'alt'   => __( 'Save' ),
							//							)
							//						);
							?>
						</div>
						<?php if ( ! empty( $amenities ) ) { ?>
							<div class="single-items-top-items">
								<?php foreach ( $amenities as $amenity ) { ?>
									<span>
                                    <img src="<?php echo esc_url( $amenity['icon'] ); ?>" width="16" height="16"
                                         alt="Vector icon">
                                    <?php echo esc_html( $amenity['value'] ); ?>
                                </span>
								<?php } ?>
							</div>
						<?php } ?>

						<?php if ( ! empty( $location ) ) { ?>
							<p><?php echo esc_html( $location ); ?></p>
						<?php } ?>
					</div>

					<?php if ( ! empty( $broker ) ) { ?>
						<div class="single-items-top-right">
							<div class="broker">
								<div class="broker-top">
									<div class="broker-top-left">
										<?php if ( ! empty( $broker['avatar'] ) ) { ?>
											<div class="broker-img">
												<img src="<?php echo esc_url( $broker['avatar'] ); ?>" width="64"
												     height="64" alt="<?php echo esc_html( $broker['name'] ); ?>">
											</div>
										<?php } ?>
										<div class="broker-info">
											<span class="broker-name"><?php echo esc_html( $broker['name'] ); ?></span>
											<span class="broker-position"><?php _e( 'Property broker' ); ?></span>
										</div>
									</div>
									<?php if ( ! empty( $broker['logo'] ) ) { ?>
										<div class="broker-estate">
											<img src="<?php echo esc_url( $broker['logo'] ); ?>" height="66"
											     alt="Real estate">
										</div>
									<?php } ?>
								</div>
								<div class="broker-bottom">
									<?php
									get_template_part( 'core/components/ui/button', null,
										array(
											'class' => 'orange sm',
											'text'  => __( 'Contact broker' ),
											'modal' => 'broker-modal',
										)
									);
									?>
									<p>
										<?php _e( 'Get in touch with broker using email, phone ow Whatsapp. It’s freeand does not
                                                                        require any commitment from your side' ); ?>
									</p>
								</div>
							</div>
						</div>
					<?php } ?>

				</div>
				<?php
				get_template_part(
					'core/components/sliders/thumbs-slider',
					null,
					array(
						'gallery'  => $gallery,
						'template' => 'unit-thumbs-slider',
					)
				);
				?>
				<div class="single-info">
					<div class="single-info-block">
						<h3><?php echo esc_html( $unit->get_title() ); ?></h3>
						<div class="texts">
							<p>
								<?php echo $unit->get_description_short(); ?>
							</p>
						</div>
						<?php if ( ! empty( $desc ) ) { ?>
							<button class="button learn-more" data-modal-open="desc-modal">
								<?php esc_html_e( 'Learn more' ); ?>
								<img src="<?php echo THEME_URL; ?>/assets/img/link.svg" width="16" height="16"
								     alt="<?php esc_html_e( 'Learn more' ); ?>">
							</button>
						<?php } ?>
					</div>

					<?php if ( ! empty( $payment_plans[0]['items'] ) ) { ?>
						<div class="single-info-block">
							<h3><?php _e( 'Payment plan' ); ?></h3>
							<div class="single-steps">
								<?php foreach ( $payment_plans[0]['items'] as $key => $plan ) { ?>
									<div class="single-step">
										<div class="single-step-title"><?php echo esc_html( $plan['description'] ); ?></div>
										<div class="single-step-descr"><?php echo esc_html( $plan['name'] ); ?></div>
									</div>
								<?php } ?>
							</div>
						</div>
					<?php } ?>

					<?php if ( ! empty( $down_payment_group ) ) { ?>
						<div class="single-info-block">
							<div class="single-steps">
								<div class="single-step">
									<h3><?php _e( 'Down payment' ); ?></h3>
									<span><?php echo esc_html( $down_payment_group['down_payment'] ); ?>%</span>
									<p><?php _e( 'Sales launch' ); ?></p>
								</div>
								<div class="single-step-arrow">
									<img src="<?php echo THEME_URL; ?>/assets/img/arrow-right.svg" width="24"
									     height="24"
									     alt="Vector arrow">
								</div>
								<div class="single-step">
									<h3><?php _e( 'During construction' ); ?></h3>
									<span><?php echo esc_html( $down_payment_group['during_construction'] ); ?>%</span>
									<p><?php echo esc_html( $down_payment_group['installments'] ); ?><?php _e( 'installments' ); ?></p>
								</div>
								<div class="single-step-arrow">
									<img src="<?php echo THEME_URL; ?>/assets/img/arrow-right.svg" width="24"
									     height="24"
									     alt="Vector arrow">
								</div>
								<div class="single-step">
									<h3><?php _e( 'On handover' ); ?></h3>
									<span><?php echo esc_html( $down_payment_group['on_handover'] ); ?>%</span>
									<p><?php echo esc_html( $delivery_date ); ?></p>
								</div>
							</div>
						</div>
					<?php } ?>

					<?php if ( ! empty( $floor_plan[0]['layout']['sizes']['large'] ) ) { ?>
						<div class="single-info-block">
							<h3><?php _e( 'Floor plan' ); ?></h3>
							<div class="single-info-block-img" data-modal-open="plan-modal">
								<img src="<?php echo esc_url( $floor_plan[0]['layout']['sizes']['large'] ); ?>"
								     alt="<?php _e( 'Floor plan' ); ?>">
							</div>
						</div>
					<?php } ?>

					<?php if ( ! empty( $property_information ) ) { ?>
						<div class="single-info-block">
							<h3><?php _e( 'Property information' ); ?></h3>
							<div class="single-info-rows">
								<div class="single-info-row">
									<?php
									$col = 0;
									foreach ( $property_information as $info ) {
										$col ++; ?>

										<?php
										if ( 3 === $col ) {
											$i = 0;
											echo '</div><div class="single-info-row">';
										}
										?>

										<div class="single-info-col">
											<span><?php echo esc_html( $info['label'] ); ?></span>
											<p><?php echo esc_html( $info['value'] ); ?></p>
										</div>

									<?php } ?>
								</div>
								<?php if ( ! empty( $location ) && ! empty( $developer ) ) { ?>
									<div class="single-info-row">
										<div class="single-info-col">
											<span><?php _e( 'Location' ); ?></span>
											<a href="<?php echo esc_url( $developer->get_developer_url() ); ?>"
											   target="_blank"
											   rel="noopener noreferrer">
												<?php echo esc_html( $location ); ?>
												<img src="<?php echo THEME_URL; ?>/assets/img/link.svg" width="16"
												     height="16" alt="<?php echo esc_html( $location ); ?>">
											</a>
										</div>
									</div>
								<?php } ?>
							</div>
						</div>
					<?php } ?>

					<?php if ( ! empty( $building_name ) ) { ?>
						<div class="single-info-block">
							<h2 class="h3">
								<?php _e( 'Building Information' ); ?>
							</h2>
							<div class="single-info-rows">
								<div class="single-info-row">
									<div class="single-info-col">
										<span><?php _e( 'Building name' ); ?></span>
										<p><?php echo esc_html( $building_name ); ?></p>
									</div>
									<?php if ( ! empty( $floors ) ) { ?>
										<div class="single-info-col">
											<span><?php _e( 'Floors' ); ?></span>
											<p><?php echo esc_attr( $floors ); ?></p>
										</div>
									<?php } ?>
								</div>
							</div>
						</div>
					<?php } ?>

					<?php if ( 0 < count( $property_amenities ) ) { ?>
						<div class="single-info-block">
							<h3><?php _e( 'Amenities' ); ?></h3>
							<ul class="single-list">
								<?php foreach ( $property_amenities as $amenity ) { ?>
									<li><?php echo esc_html( $amenity ); ?></li>
								<?php } ?>
							</ul>
						</div>
					<?php } ?>

					<?php
					$featured_units = $property->get_random_units( 3 );
					if ( ! empty( $featured_units ) ) {
						$all_units_link = home_url( '/units' );
						get_component_template(
							'units/featured',
							array(
								'h2'            => __( 'More properties like this' ),
								'href'          => $all_units_link,
								'show_all_link' => $all_units_link,
								'link_text'     => __( 'All properties' ),
								'units'         => $featured_units,
								'card_template' => 'unit-square-card',
								'before'        => '',
								'after'         => '',
							)
						);
					}
					?>

					<?php
					$developer = $property->get_developer();
					if ( ! empty( $developer ) ) {
						$developer_thumb = $developer->get_thumb() ?: '';
						$developer_title = $developer->get_title() ?: '';
						$developer_url   = $developer->get_url() ?: '';
						?>
						<div class="single-info-block">
							<div class="developer">
								<?php if ( ! empty( $developer_thumb ) ) { ?>
									<div class="developer-image">
										<img src="<?php echo esc_url( $developer_thumb ); ?>"
										     height="50"
										     alt="<?php echo esc_attr( $developer_title ); ?>">
									</div>
								<?php } ?>
								<div class="developer-info">
									<span><?php echo esc_html( $developer_title ); ?></span>
									<?php if ( ! empty( $developer_url ) ) { ?>
										<a href="<?php echo esc_url( $developer_url ); ?>">
											<?php _e( 'View developer' ); ?>
										</a>
									<?php } ?>
								</div>
							</div>
						</div>
					<?php } ?>

					<?php if ( ! empty( $latitude ) && ! empty( $longitude ) ) { ?>
						<div class="single-info-block">
							<h3><?php _e( 'Location' ); ?></h3>
							<?php
							get_template_part( 'core/components/properties/map', null,
								array(
									'property'     => $property,
									'show_sidebar' => false,
									'mode'         => 'single',
								)
							);
							?>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>
		<?php get_template_part( 'core/components/ui/contact-panel' ); ?>
	</section>
	<?php
}
get_footer();

