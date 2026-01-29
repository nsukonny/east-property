<?php
/**
 * Search results tabs section
 */

$h2            = $args['h2'] ?? '';
$cards         = $args['cards'] ?? '';
$card_template = $args['card_template'] ?? 'large-card';
?>
<section class="result-tabs" data-tabs>
    <div class="container">
        <div class="result-tabs-wrapper">
            <div class="result-tabs-buttons" role="tablist">
                <button class="result-tab-button active" type="button" id="result-tabs-list-tab" data-tab-button
                        data-tab="list" role="tab" aria-selected="true" aria-controls="result-tabs-list-panel">
                    <?php _e( 'List view' ); ?>
                </button>
                <button class="result-tab-button" type="button" id="result-tabs-map-tab" data-tab-button data-tab="map"
                        role="tab" aria-selected="false" tabindex="-1" aria-controls="result-tabs-map-panel">
                    <?php _e( 'Map view' ); ?>
                </button>
            </div>
            <div class="result-tabs-content active" id="result-tabs-list-panel" data-tab-panel data-tab="list"
                 role="tabpanel" aria-labelledby="result-tabs-list-tab">
                <div class="content-title">
                    <?php get_template_part( 'components/common/breadcrumbs' ); ?>
                    <div class="title-top">
                        <h2><?php echo esc_html( $h2 ); ?></h2>
                        <?php
                        //TODO Hidden for MVP
                        /*
                        <button class="sort">
                            Show expensive first
                            <img src="/img/arrow-down.svg" width="16" height="16" alt="Arrow down">
                        </button>
                        */
                        ?>
                    </div>
                    <p><?php _e( 'Aliquam lacinia diam quis lacus euismod' ); ?></p>
                </div>
                <div class="content-list">
                    <?php
                    if ( ! empty( $cards ) ) {
                        foreach ( $cards as $card ) {
                            get_template_part( 'template-parts/cards/' . $card_template, null,
                                    array(
                                            'title'          => $card['title'],
                                            'price'          => $card['price'],
                                            'location'       => $card['location'],
                                            'gallery'        => $card['gallery'],
                                            'labels'         => $card['labels'] ?? array(),
                                            'amenities'      => $card['amenities'] ?? array(),
                                            'property_name'  => $card['property_name'] ?? '',
                                            'property_url'   => $card['property_url'] ?? '',
                                            'developer_name' => $card['developer_name'] ?? '',
                                            'url'            => $card['url'],
                                    )
                            );
                        }
                    } else {
                        _e( 'Items not found' );
                    }
                    ?>
                </div>
            </div>
            <div class="result-tabs-content" id="result-tabs-map-panel" data-tab-panel data-tab="map" role="tabpanel"
                 aria-labelledby="result-tabs-map-tab">
                <div class="result-tabs-content-inner">
                    <div class="map full-width">
                        <iframe
                                class="map-iframe" title="Dubai map" loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                data-src="https://www.google.com/maps?q=Dubai&z=12&output=embed"
                        ></iframe>
                    </div>
                    <div class="content-scroll">
                        <?php
                        if ( ! empty( $cards ) ) {
                            foreach ( $cards as $card ) {
                                get_template_part( 'template-parts/cards/small-card', null,
                                        array(
                                                'title'     => $card['title'],
                                                'price'     => $card['price'],
                                                'location'  => $card['location'],
                                                'gallery'   => $card['gallery'],
                                                'labels'    => $card['labels'] ?? array(),
                                                'amenities' => $card['amenities'] ?? array(),
                                                'url'       => $card['url'],
                                        )
                                );
                            }
                        }
                        ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>