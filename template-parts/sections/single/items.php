<?php
/**
 * Single Items Section Template
 *
 * @var Entities\Property $property
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$property = $args['property'] ?? '';
if ( empty( $property ) ) {
    return;
}

$labels               = $property->get_labels();
$property_amenities   = $property->get_amenities();
$latitude             = $property->get_latitude();
$longitude            = $property->get_longitude();
$property_information = $property->get_key_information();
$whatsapp_share_text  = 'https://wa.me/?text=' . rawurlencode( sprintf(
                '%s | %s | %s View %s',
                $property->get_title(),
                $property->get_location(),
                $property->get_price_html(),
                get_permalink( $property->get_id() )
        ) );
?>
<section class="single-items">
    <div class="container">
        <div class="single-items-wrapper">
            <?php get_template_part( 'components/common/breadcrumbs' ); ?>
            <div class="single-items-top">
                <div class="single-items-top-left">
                    <h1><?php echo esc_html( $property->get_title() ); ?></h1>
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
                    get_template_part( 'components/ui/button', null,
                            array(
                                    'class'  => 'gray sm',
                                    'text'   => __( 'Share' ),
                                    'src'    => THEME_URL . '/assets/img/share.svg',
                                    'link'   => $whatsapp_share_text,
                                    'target' => '_blank',
                                    'rel'    => 'noopener noreferrer',
                            )
                    );

                    get_template_part( 'components/ui/button', null,
                            array(
                                    'class' => 'gray sm',
                                    'text'  => __( 'Save' ),
                                    'src'   => THEME_URL . '/assets/img/bookmark.svg',
                            )
                    );

                    get_template_part( 'components/ui/button', null,
                            array(
                                    'class' => 'orange sm request-quote',
                                    'text'  => __( 'Request quote' ),
                                    'modal' => 'quote-modal',
                            )
                    );
                    ?>
                </div>
                <?php
                get_template_part( 'template-parts/sections/single/thumbs-slider', null,
                        array(
                                'gallery' => $property->get_gallery(),
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
                                <?php
                                $location  = $property->get_location();
                                $developer = $property->get_developer();

                                if ( ! empty( $location ) ) {
                                    ?>
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
                    $grouped_units = $property->get_units_by_beds();
                    if ( ! empty( $grouped_units ) ) {
                        ?>
                        <div class="single-info-block">
                            <h3><?php _e( 'Pricing' ); ?></h3>
                            <div class="single-dropdowns">
                                <?php
                                foreach ( $grouped_units as $units ) {
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

                    <div class="single-info-block">
                        <div class="single-steps">
                            <div class="single-step">
                                <h3>Down payment</h3>
                                <span>10%</span>
                                <p>Sales launch</p>
                            </div>
                            <div class="single-step-arrow">
                                <img src="<?php echo THEME_URL; ?>/assets/img/arrow-right.svg" width="24" height="24"
                                     alt="Vector arrow">
                            </div>
                            <div class="single-step">
                                <h3>During construction</h3>
                                <span>70%</span>
                                <p>7 installments</p>
                            </div>
                            <div class="single-step-arrow">
                                <img src="<?php echo THEME_URL; ?>/assets/img/arrow-right.svg" width="24" height="24"
                                     alt="Vector arrow">
                            </div>
                            <div class="single-step">
                                <h3>On handover</h3>
                                <span>30%</span>
                                <p>Sep 2029</p>
                            </div>
                        </div>
                    </div>
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
                            <div class="map">
                                <iframe
                                        class="map-iframe" title="Dubai map" loading="lazy"
                                        referrerpolicy="no-referrer-when-downgrade"
                                        data-src="https://www.google.com/maps?q=<?php echo esc_attr( $latitude ); ?>,<?php echo esc_attr( $longitude ); ?>&z=15&output=embed"
                                ></iframe>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <?php get_template_part( 'components/ui/contact-panel' ); ?>
</section>