<?php
/**
 * Search tabs template
 */

$search_tabs_data = $args['search_tabs_data'] ?? array();
$form_action      = $args['form_action'] ?? home_url( '/units/' );

if ( empty( $search_tabs_data ) ) {
    return;
}

$max_price          = number_format( (int) $search_tabs_data['price_max'], 0, '', ',' );
$available_last_key = array_key_last( $search_tabs_data['filters']['available']['options'] );
$available          = $search_tabs_data['filters']['available']['options'][ $available_last_key ];

$price_last_key = array_key_last( $search_tabs_data['filters']['price']['options'] );
$price          = $search_tabs_data['filters']['price']['options'][ $price_last_key ];

$baths = $search_tabs_data['filters']['baths'];
$beds  = $search_tabs_data['filters']['beds'];
?>
<script>
    const searchTabsData = <?php echo wp_json_encode( $search_tabs_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>;
</script>
<div class="search-tabs" data-search-tabs>
    <div class="tabs-buttons" role="tablist">
        <?php foreach ( $search_tabs_data['categories'] as $category_key => $category ) { ?>
            <button type="button" class="button <?php if ( 0 === $category_key ) { ?>is-active<?php } ?>" role="tab"
                    id="search-tabs-tab-<?php echo esc_attr( $category['slug'] ); ?>"
                    aria-controls="search-tabs-panel" aria-selected="true" tabindex="0" data-search-tab
                    data-type="<?php echo esc_attr( $category['slug'] ); ?>">
                <?php echo esc_html( $category['label'] ); ?>
            </button>
        <?php } ?>
    </div>
    <form action="<?php echo esc_url( $form_action ); ?>" class="tabs-panel" method="post"
          data-search-panel>
        <div class="tabs-fields" role="tabpanel" id="search-tabs-panel" aria-labelledby="search-tabs-tab-all">
            <div class="tab-field">
                <button type="button" class="tab-selector" data-search-selector="available" aria-haspopup="listbox"
                        aria-expanded="false">
                    <span class="tab-field-label"><?php echo esc_attr( $search_tabs_data['filters']['available']['label'] ); ?></span>
                    <span class="tab-selector-value">
                        <span data-search-available-text><?php echo esc_attr( $available['label'] ); ?></span>
                        <img src="<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16"
                             alt="Dropdown arrow">
                    </span>
                </button>
                <div class="tab-dropdown" role="listbox" tabindex="-1" data-search-dropdown="available"
                     data-check-icon="<?php echo THEME_URL; ?>/assets/img/check.svg" hidden></div>
            </div>

            <?php if ( ! empty( $baths['options'] ) || ! empty( $beds['options'] ) ) { ?>
                <div class="tab-divider" aria-hidden="true"></div>
                <div class="tab-field">
                    <button type="button" class="tab-selector" data-search-selector="beds_baths" aria-haspopup="true"
                            aria-expanded="false">
                        <span class="tab-field-label"><?php _e( 'Beds and baths' ); ?></span>
                        <span class="tab-selector-value">
                        <span data-search-beds-baths-text><?php _e( '2,3 beds, 2 baths' ); ?></span>
                        <img src="<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16"
                             alt="Dropdown arrow">
                    </span>
                    </button>
                    <div class="beds-baths-dropdown" data-search-dropdown="beds_baths" hidden>
                        <div class="beds-baths-content">
                            <?php if ( ! empty( $beds['options'] ) ) { ?>
                                <div class="beds-baths-section">
                                    <span class="beds-baths-label"><?php echo esc_attr( $beds['label'] ); ?></span>
                                    <div class="beds-baths-buttons">
                                        <?php foreach ( $beds['options'] as $bed ) {
                                            $class = ! empty( $bed['active'] ) && true === $bed['active'] ? ' active' : '';
                                            ?>
                                            <button type="button"
                                                    class="beds-baths-btn<?php echo esc_attr( $class ); ?>"
                                                    data-beds="<?php echo esc_attr( $bed['value'] ); ?>"><?php echo esc_attr( $bed['label'] ); ?></button>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } ?>

                            <?php if ( ! empty( $baths['options'] ) ) { ?>
                                <div class="beds-baths-section">
                                    <span class="beds-baths-label"><?php echo esc_attr( $baths['label'] ); ?></span>
                                    <div class="beds-baths-buttons">
                                        <?php foreach ( $baths['options'] as $bath ) {
                                            $class = ! empty( $bath['active'] ) && true === $bath['active'] ? ' active' : '';
                                            ?>
                                            <button type="button"
                                                    class="beds-baths-btn<?php echo esc_attr( $class ); ?>"
                                                    data-baths="<?php echo esc_attr( $bath['value'] ); ?>"><?php echo esc_attr( $bath['label'] ); ?></button>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } ?>

                            <div class="beds-baths-actions">
                                <button class="button gray sm beds-baths-cancel" type="button">
                                    <span><?php _e( 'Cancel' ); ?></span>
                                </button>
                                <button class="button orange sm beds-baths-apply" type="button">
                                    <span><?php _e( 'Apply' ); ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <div class="tab-divider" aria-hidden="true"></div>
            <div class="tab-field">
                <button type="button" class="tab-selector" data-search-selector="price" aria-haspopup="listbox"
                        aria-expanded="false">
                    <span class="tab-field-label"><?php echo esc_attr( $search_tabs_data['filters']['price']['label'] ); ?></span>
                    <span class="tab-selector-value">
                        <span data-search-price-text><?php echo esc_attr( $price['label'] ); ?></span>
                        <img src="<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16"
                             alt="Dropdown arrow">
                    </span>
                </button>
                <div class="tab-dropdown" role="listbox" tabindex="-1" data-search-dropdown="price"
                     data-check-icon="<?php echo THEME_URL; ?>/assets/img/check.svg" hidden></div>
            </div>
            <?php
            get_template_part( 'components/ui/button', null,
                    array(
                            'class' => 'orange xl submit',
                            'text'  => __( 'Search' ),
                            'src'   => THEME_URL . '/assets/img/search.svg',
                            'alt'   => __( 'Search' ),
                    )
            );
            ?>
        </div>
        <input type="hidden" name="category" value="all" data-search-type>
        <input type="hidden" name="property_type" value="all" data-search-type>
        <input type="hidden" name="available" value="<?php echo esc_attr( $available['value'] ); ?>"
               data-search-available-value>
        <input type="hidden" name="max_price" value="<?php echo esc_attr( $price['value'] ); ?>"
               data-search-price-value>
        <input type="hidden" name="beds" value="" data-search-beds-value>
        <input type="hidden" name="baths" value="" data-search-baths-value>
    </form>
</div>
