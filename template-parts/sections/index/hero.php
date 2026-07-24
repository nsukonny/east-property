<?php
/**
 * Hero section template
 */

$hero = get_field( 'hero_section', 'option' );
$h1   = ! empty( $hero['title'] ) ? $hero['title'] : get_bloginfo( 'name' );

$available_properties_count = get_count_of_units_by_date( '2000-01-01', date( 'Y-m-d' ) );
$in_construction_properties_count = get_count_of_units_by_date( date( 'Y-m-d' ), '2070-01-01' )
?>
<section class="hero">
	<div class="container">
		<div class="hero-wrapper">
			<div class="hero-left">
				<h1><?php echo $h1; ?></h1>
				<?php
				get_template_part(
					'core/components/filters/search-tabs',
					null,
					array(
						'form_action' => home_url( '/units/' ),
						'show_tabs'   => true,
					)
				); ?>
				<div class="hero-items">
					<div class="hero-item">
                        <span>
                            <?php echo esc_attr( $available_properties_count ); ?>+
                        </span>
						<p>
							<?php _e( 'Available immediately' ); ?>
						</p>
					</div>
					<div class="hero-item">
                        <span>
                           <?php echo esc_attr( $in_construction_properties_count ); ?>+
                        </span>
						<p>
							<?php _e( 'In construction' ); ?>
						</p>
					</div>
				</div>
			</div>
			<div class="hero-right">
				<div class="hero-img">
					<img src="<?php echo THEME_URL; ?>/assets/img/hero-image.png" width="655" height="418" alt="">
				</div>
			</div>
		</div>
	</div>
</section>