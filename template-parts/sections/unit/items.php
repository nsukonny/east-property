<?php
/**
 * Unit items section
 *
 * @var \Entities\Unit $unit
 */
$unit = $args['unit'] ?? null;
if ( $unit === null || ! $unit->exists() ) {
    return;
}

$property = $unit->get_property();
if ( null === $property ) {
    return;
}

$gallery             = $unit->get_gallery();
$developer           = $unit->get_developer();
$whatsapp_share_text = 'https://wa.me/?text=' . rawurlencode( sprintf(
                '%s | %s | %s View %s',
                $unit->get_title(),
                $property ? $property->get_location() : '',
                $unit->get_price_html(),
                get_permalink( $unit->get_id() )
        ) );

$amenities  = $unit->get_amenities();
$floor_plan = $unit->get_floor_plan();
$broker     = $unit->get_broker();

$location             = $property->get_location();
$down_payment_group   = $property->get_down_payment_group();
$delivery_date        = $property->get_delivery_date();
$property_information = $property->get_key_information();
$property_amenities   = $property->get_amenities();
$building_name        = $property->get_title();
$floors               = $property->get_floors();
$latitude             = $property->get_latitude();
$longitude            = $property->get_longitude();
?>
<section class="single-items">
    <div class="container">
        <div class="single-items-wrapper">
            <?php
            get_template_part( 'components/common/breadcrumbs' );
            get_template_part( 'template-parts/sections/single/thumbs-slider', null,
                    array(
                            'gallery' => $unit->get_gallery(),
                    )
            );
            ?>
            <div class="single-items-top">
                <div class="single-items-top-left">
                    <h1><?php echo esc_html( $unit->get_price_html() ); ?></h1>
                    <h2><?php _e( 'Apartment by' ); ?>
                        <?php echo esc_attr( $property->get_title() ); ?></h2>
                    <div class="single-items-top-buttons">
                        <?php
                        get_template_part( 'components/ui/button', null,
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

                        get_template_part( 'components/ui/button', null,
                                array(
                                        'class' => 'gray sm',
                                        'text'  => __( 'Save' ),
                                        'src'   => THEME_URL . '/assets/img/bookmark.svg',
                                        'alt'   => __( 'Save' ),
                                )
                        );
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
                                    <div class="broker-img">
                                        <img src="<?php echo esc_url( $broker['avatar'] ); ?>" width="64" height="64"
                                             alt="<?php echo esc_html( $broker['name'] ); ?>">
                                    </div>
                                    <div class="broker-info">
                                        <span class="broker-name"><?php echo esc_html( $broker['name'] ); ?></span>
                                        <span class="broker-position"><?php _e( 'Property broker' ); ?></span>
                                    </div>
                                </div>
                                <div class="broker-estate">
                                    <img src="<?php echo esc_url( $broker['logo'] ); ?>" height="66"
                                         alt="Real estate">
                                </div>
                            </div>
                            <div class="broker-bottom">
                                <?php
                                get_template_part( 'components/ui/button', null,
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
            <div class="single-info">
                <div class="single-info-block">
                    <h3><?php echo esc_html( $unit->get_title() ); ?></h3>
                    <div class="texts">
                        <p>
                            <?php echo $unit->get_description_short(); ?>
                        </p>
                    </div>
                    <button class="button learn-more" data-modal-open="desc-modal">
                        <?php _e( 'Learn more' ); ?>
                        <img src="<?php echo THEME_URL; ?>/assets/img/link.svg" width="16" height="16"
                             alt="Vector link">
                    </button>
                </div>

                <?php if ( ! empty( $down_payment_group ) ) { ?>
                    <div class="single-info-block">
                        <div class="single-steps">
                            <div class="single-step">
                                <h3><?php _e( 'Down payment' ); ?></h3>
                                <span><?php echo esc_html( $down_payment_group['down_payment'] ); ?>%</span>
                                <p><?php _e( 'Sales launch' ); ?></p>
                            </div>
                            <div class="single-step-arrow">
                                <img src="<?php echo THEME_URL; ?>/assets/img/arrow-right.svg" width="24" height="24"
                                     alt="Vector arrow">
                            </div>
                            <div class="single-step">
                                <h3><?php _e( 'During construction' ); ?></h3>
                                <span><?php echo esc_html( $down_payment_group['during_construction'] ); ?>%</span>
                                <p><?php echo esc_html( $down_payment_group['installments'] ); ?><?php _e( 'installments' ); ?></p>
                            </div>
                            <div class="single-step-arrow">
                                <img src="<?php echo THEME_URL; ?>/assets/img/arrow-right.svg" width="24" height="24"
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

                <?php if ( ! empty( $floor_plan ) ) { ?>
                    <div class="single-info-block">
                        <h3><?php _e( 'Floor plan' ); ?></h3>
                        <div class="single-info-block-img" data-modal-open="plan-modal">
                            <img src="<?php echo esc_url( $floor_plan ); ?>"
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
    <?php get_template_part( 'components/ui/broker-panel' ); ?>
</section>