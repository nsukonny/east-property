<?php
/**
 * Default template for property single page
 */

$title = $args['title'] ?? null;

if ( empty( $title ) ) {
	return;
}

$labels       = $args['labels'] ?? array();
$property_url = $args['property_url'] ?: '#';


$whatsapp_share_text  = $args['whatsapp_share_text'] ?? '';
$property_information = $args['property_information'] ?? array();
$units                = $args['units'] ?? array();
$all_units_link       = $args['all_units_link'] ?? '';
$location             = $args['location'] ?? '';
$location_url         = $args['location_url'] ?? '';
$developer            = $args['developer'] ?? '';
$gallery              = $args['gallery'] ?? array();
$quote_button_args    = $args['quote_button_args'] ?? array();

if ( ! empty( $location ) && ! empty( $location_url ) ) {
	$property_information[] = array(
		'label' => __( 'Location' , 'east-property' ),
		'value' => '<a href="' . esc_url( $location_url ) . '">' . esc_html( $location ) . '</a>',
	);
}
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
							'class' => 'button orange sm',
							'text'  => __( 'View object page' , 'east-property' ),
							'link'  => $property_url,
						)
					);
					?>
                </div>
            </div>
            <div class="single-info">
                <div class="single-info-block">
                    <h2 class="h3">
						<?php esc_html_e( 'Property information' , 'east-property' ); ?>
                    </h2>
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
                                    <p><?php echo wp_kses_post( $info['value'] ); ?></p>
                                </div>

							<?php } ?>
                        </div>
                    </div>
                </div>

				<?php if ( ! empty( $units ) ) { ?>
					<?php foreach ( $units as $unit ) { ?>
                        <div class="single-info-block">
							<?php
							get_template_part( 'core/components/cards/unit-card', null,
								array(
									'unit'     => $unit,
									'template' => 'unit-card',
								)
							);
							?>
                        </div>
					<?php } ?>
				<?php } ?>
            </div>
        </div>
    </div>
	<?php get_template_part( 'core/components/ui/contact-panel' ); ?>
</section>