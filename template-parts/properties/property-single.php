<?php
/**
 * Template for single property
 */

$title = $args['title'] ?? null;

if ( empty( $title ) ) {
	return;
}

$labels               = $args['labels'] ?? array();
$whatsapp_share_text  = $args['whatsapp_share_text'] ?? '';
$property_information = $args['property_information'] ?? array();
$units                = $args['units'] ?? array();
$all_units_link       = $args['all_units_link'] ?? '';
$location             = $args['location'] ?? '';
$developer            = $args['developer'] ?? '';
$gallery              = $args['gallery'] ?? array();
$quote_button_args    = $args['quote_button_args'] ?? array();
$units_by_beds        = $args['units_by_beds'] ?? array();
$amenities            = $args['amenities'] ?? array();
$latitude             = $args['latitude'] ?? '';
$longitude            = $args['longitude'] ?? '';
$payment_plans        = $args['payment_plans'] ?? array();
$button_text          = $args['button_text'] ?? '';
$button_url           = $args['button_url'] ?? '';
?>
<section class="single-items">
	<div class="container">
		<div class="single-items-wrapper">
			<?php get_template_part( 'core/components/common/breadcrumbs' ); ?>
			<div class="single-items-top">
				<div class="single-items-top-left">
					<h1><?php echo esc_html( $title ); ?></h1>
					<?php if ( ! empty( $labels ) ) { ?>
						<div class="single-items-top-labels">
							<?php foreach ( $labels as $label ) { ?>
								<div class="label <?php echo esc_attr( strtolower( $label['color'] ) ); ?>">
									<span><?php echo esc_html( mb_strtoupper( $label['name'] ) ); ?></span>
								</div>
							<?php } ?>
						</div>
					<?php } ?>
				</div>
				<div class="single-items-top-right">
					<?php
					get_template_part(
						'core/components/ui/button',
						null,
						array(
							'class'  => 'gray sm',
							'text'   => __( 'Share' ),
							'src'    => THEME_URL . '/assets/img/share.svg',
							'link'   => $whatsapp_share_text,
							'target' => '_blank',
							'rel'    => 'noopener noreferrer',
						)
					);

					get_template_part(
						'core/components/ui/button',
						null,
						$quote_button_args
					);
					?>
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
						<?php
						if ( ! empty( $developer ) ) {
							$developer_thumb = $developer->get_thumb() ?: '';
							$developer_title = $developer->get_title() ?: '';
							$developer_url   = $developer->get_url() ?: '';
							?>
							<div class="single-info-block">
								<div class="developer">
									<?php if ( ! empty( $developer_thumb ) ) { ?>
										<div class="developer-image">
											<img src="<?php echo esc_url( $developer_thumb ); ?>" width="80"
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
					<?php } ?>

					<?php
					$tree_latest_units = 3 < count( $units ) ? array_slice( $units, 0, 3 ) : $units;
					get_component_template(
						'units/featured',
						array(
							'h2'            => count( $units ) . ' ' . __( 'properties available in this project' ),
							'href'          => $all_units_link,
							'show_all_link' => $all_units_link,
							'link_text'     => __( 'All properties' ),
							'units'         => $tree_latest_units,
							'card_template' => 'unit-square-card',
							'before'        => '',
							'after'         => '',
							'button_text'   => $button_text,
							'button_url'    => $button_url,
						)
					);
					?>

					<?php
					if ( ! empty( $units_by_beds ) ) {
						get_template_part(
							'core/components/units/units-by-beds',
							null,
							array(
								'units_by_beds' => $units_by_beds,
							)
						);
					}
					?>

					<?php if ( ! empty( $payment_plans ) ) { ?>
						<div class="single-info-block">
							<h3><?php _e( 'Payment plan' ); ?></h3>
							<div class="single-steps">
								<?php foreach ( $payment_plans as $key => $plan ) { ?>
									<div class="single-step">
										<div class="single-step-title"><?php echo esc_html( $plan['description'] ); ?></div>
										<div class="single-step-descr"><?php echo esc_html( $plan['name'] ); ?></div>
									</div>
								<?php } ?>
							</div>
						</div>
					<?php } ?>

					<?php if ( 0 < count( $amenities ) ) { ?>
						<div class="single-info-block">
							<h3><?php _e( 'Amenities' ); ?></h3>
							<ul class="single-list">
								<?php foreach ( $amenities as $amenity ) { ?>
									<li><?php echo esc_html( $amenity ); ?></li>
								<?php } ?>
							</ul>
						</div>
					<?php } ?>

					<div class="single-info-block">
						<h3><?php _e( 'Description' ); ?></h3>
						<div class="texts">
							<?php the_content(); ?>
						</div>
						<button class="button learn-more" data-modal-open="desc-modal">
							<?php _e( 'Learn more' ); ?>
							<img src="<?php echo THEME_URL; ?>/assets/img/link.svg" width="16" height="16"
							     alt="Vector link">
						</button>
					</div>

					<?php if ( ! empty( $latitude ) && ! empty( $longitude ) ) { ?>
						<div class="single-info-block">
							<h3><?php _e( 'Location' ); ?></h3>
							<?php
							get_template_part(
								'core/components/properties/map',
								null,
								array(
									'property'     => new \Entities\Property( get_the_ID() ),
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