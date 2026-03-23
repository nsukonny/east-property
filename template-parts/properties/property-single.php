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
                    get_template_part( 'core/components/ui/button', null,
                            array(
                                    'class'  => 'gray sm',
                                    'text'   => __( 'Share' ),
                                    'src'    => THEME_URL . '/assets/img/share.svg',
                                    'link'   => $whatsapp_share_text,
                                    'target' => '_blank',
                                    'rel'    => 'noopener noreferrer',
                            )
                    );

                    //                    get_template_part( 'core/components/ui/button', null,
                    //                            array(
                    //                                    'class' => 'gray sm',
                    //                                    'text'  => __( 'Save' ),
                    //                                    'src'   => THEME_URL . '/assets/img/bookmark.svg',
                    //                            )
                    //                    );

                    get_template_part( 'core/components/ui/button', null,
                            $quote_button_args
                    );
                    ?>
                </div>
                <?php
                get_template_part( 'template-parts/sections/single/thumbs-slider', null,
                        array(
                                'gallery' => $gallery,
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

                    <?php if ( ! empty( $units_by_beds ) ) { ?>
                        <div class="single-info-block">
                            <h3><?php _e( 'Pricing' ); ?></h3>
                            <div class="single-dropdowns">
                                <?php
                                foreach ( $units_by_beds as $units ) {
                                    get_template_part( 'template-parts/sections/single/dropdowns', null,
                                            array(
                                                    'beds'      => $units['beds'],
                                                    'min_baths' => $units['min_baths'],
                                                    'max_baths' => $units['max_baths'],
                                                    'min_area'  => $units['min_area'],
                                                    'max_area'  => $units['max_area'],
                                                    'price'     => $units['price'],
                                                    'units'     => $units['units'] ?? array(),
                                            )
                                    );
                                }
                                ?>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ( ! empty( $payment_plans ) ) { ?>
                        <div class="single-info-block">
                            <div class="single-steps">
                                <?php foreach ( $payment_plans as $key => $plan ) { ?>
                                    <?php if ( 0 !== $key ) { ?>
                                        <div class="single-step-arrow">
                                            <img src="<?php echo THEME_URL; ?>/assets/img/arrow-right.svg" width="24"
                                                 height="24"
                                                 alt="Vector arrow">
                                        </div>
                                    <?php } ?>
                                    <div class="single-step">
                                        <h3><?php echo esc_html( $plan['description'] ); ?></h3>
                                        <span><?php echo esc_html( $plan['name'] ); ?></span>
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
                            get_template_part( 'core/components/properties/map', null,
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