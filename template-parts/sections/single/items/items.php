<?php
/**
 * Single Items Section Template
 *
 * @var \MessiaTheme\Entities\Property $property
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$property = $args['property'] ?? '';
$labels   = $property->get_labels();
?>
<section class="single-items">
    <div class="container">
        <div class="single-items-wrapper">
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
                    <div class="single-items-top-right">
                        <?php
                        get_template_part( 'template-parts/components/ui/button', null,
                                array(
                                        'class' => 'gray sm',
                                        'text'  => __( 'Share' ),
                                        'src'   => THEME_URL . '/assets/img/share.svg',
                                )
                        );

                        get_template_part( 'template-parts/components/ui/button', null,
                                array(
                                        'class' => 'gray sm',
                                        'text'  => __( 'Save' ),
                                        'src'   => THEME_URL . '/assets/img/bookmark.svg',
                                )
                        );

                        get_template_part( 'template-parts/components/ui/button', null,
                                array(
                                        'class'     => 'orange sm request-quote',
                                        'text'      => __( 'Request quote' ),
                                        'dataModal' => 'quote-modal',
                                )
                        );
                        ?>
                    </div>
                </div>
                <?php
                get_template_part( 'template-parts/sections/single/items/thumbs-slider', null,
                        array(
                                'gallery' => $property->get_gallery(),
                        )
                );
                ?>
                <div class="single-info">
                    <div class="single-info-block">
                        <h3><?php _e( 'Key information' ); ?></h3>
                        <div class="single-info-rows">
                            <?php
                            $col = 0;
                            foreach ( $property->get_key_information() as $info ) {
                                $col ++; ?>

                                <?php if ( 1 === $col ) { ?>
                                    <div class="single-info-row">
                                <?php } ?>

                                <div class="single-info-col">
                                    <span><?php echo esc_html( $info['label'] ); ?></span>
                                    <p><?php echo esc_html( $info['value'] ); ?></p>
                                </div>

                                <?php if ( 3 <= $col ) {
                                    $col = 0; ?>
                                    </div>
                                <?php } ?>

                            <?php } ?>

                            <?php
                            $location = $property->get_location();
                            if ( ! empty( $location ) ) {
                                ?>
                                <div class="single-info-row">
                                    <div class="single-info-col">
                                        <span><?php _e( 'Location' ); ?></span>
                                        <a href="<?php echo esc_url( $location['url'] ); ?>" target="_blank"
                                           rel="noopener noreferrer">
                                            <?php echo esc_html( $location['location'] ); ?>
                                            <img src="<?php echo THEME_URL; ?>/assets/img/link.svg" width="16"
                                                 height="16" alt="<?php echo esc_html( $location['location'] ); ?>">
                                        </a>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                    <?php
                    $developer = $property->get_developer();
                    if ( ! empty( $developer ) ) {
                        ?>
                        <div class="single-info-block">
                            <div class="developer">
                                <?php if ( ! empty( $developer['logo'] ) ) { ?>
                                    <div class="developer-image">
                                        <img src="<?php echo esc_url( $developer['logo'] ); ?>" width="80"
                                             height="50"
                                             alt="<?php echo esc_attr( $developer['title'] ); ?>">
                                    </div>
                                <?php } ?>
                                <div class="developer-info">
                                    <span><?php echo esc_html( $developer['title'] ); ?></span>
                                    <?php if ( ! empty( $developer['url'] ) ) { ?>
                                        <a href="<?php echo esc_url( $developer['url'] ); ?>">
                                            <?php _e( 'View developer' ); ?>
                                        </a>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>

                    <?php
                    $units = $property->get_units();
                    if ( ! empty( $units ) ) {
                        ?>
                        <div class="single-info-block">
                            <h3><?php _e( 'Pricing' ); ?></h3>
                            <div class="single-dropdowns">
                                <?php
                                foreach ( $units as $unit ) {
                                    get_template_part( 'template-parts/sections/single/dropdowns', null,
                                            array(
                                                    'unit' => $unit,
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
                                <img src="/img/arrow-right.svg" width="24" height="24" alt="Vector arrow">
                            </div>
                            <div class="single-step">
                                <h3>During construction</h3>
                                <span>70%</span>
                                <p>7 installments</p>
                            </div>
                            <div class="single-step-arrow">
                                <img src="/img/arrow-right.svg" width="24" height="24" alt="Vector arrow">
                            </div>
                            <div class="single-step">
                                <h3>On handover</h3>
                                <span>30%</span>
                                <p>Sep 2029</p>
                            </div>
                        </div>
                    </div>
                    <div class="single-info-block">
                        <h3>Amenities</h3>
                        <ul class="single-list">
                            <li>Kids’ Clubs</li>
                            <li>Restorative Wellness Pod</li>
                            <li>Fitness, Spa, and Pool</li>
                            <li>Serene Co-working Lounge</li>
                            <li>Dedicated Pet Spa and Grooming Studio</li>
                            <li>A Signature Communal Hub</li>
                        </ul>
                    </div>
                    <div class="single-info-block">
                        <h3>Description</h3>
                        <div class="texts">
                            <p>
                                The Row Saadiyat by Aldar Properties combines modern premium living with the rich
                                cultural
                                heritage of Abu Dhabi's prestigious Cultural District. This boutique development
                                offers
                                an
                                exclusive selection of 1 to 3-bedroom apartments, perfectly positioned to provide
                                residents
                                with front-row access to Saadiyat Island’s lively waterfront lifestyle. With iconic
                                landmarks like the Zayed National Museum nearby and proximity to top-tier cultural
                                institutions, The Row Saadiyat delivers a lifestyle that blends sophistication,
                                convenience,
                                and cultural richness.
                            </p>
                            <p>
                                Comprising seven mid-rise complexes rising 8 to 10 levels above landscaped green
                                podiums,
                                the first phase introduces 315 meticulously designed units across three buildings.
                                With
                                prices starting from AED 3.7 million, these residences offer exceptional value in
                                one of
                                Abu Dhabi's most distinguished locations.
                            </p>
                        </div>
                        <button class="button learn-more" data-modal-open="desc-modal">
                            Learn more
                            <img src="/img/link.svg" width="16" height="16" alt="Vector link">
                        </button>
                    </div>
                    <div class="single-info-block">
                        <h3>Location</h3>
                        <div class="map">
                            <iframe
                                    class="map-iframe" title="Dubai map" loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"
                                    data-src="https://www.google.com/maps?q=Dubai&z=12&output=embed"
                            ></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @@include('components/ui/contact-panel/contact-panel.html')
</section>