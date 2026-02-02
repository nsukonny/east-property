<?php
/**
 * Result filters component
 */

$post_type = $args['post_type'] ?? 'properties';

$search_tabs_data = get_search_tabs_data( $post_type );

if ( empty( $search_tabs_data ) ) {
    return;
}

$location = $search_tabs_data['filters']['location']['options'][0] ?? null;
if ( isset( $_REQUEST['location'] ) && 0 < count( $search_tabs_data['filters']['location']['options'] ) ) {
    $selected_location = $_REQUEST['location'];
    foreach ( $search_tabs_data['filters']['location']['options'] as $location_value ) {
        if ( $location_value['value'] === $selected_location ) {
            $location = $location_value;
        }
    }
}

$max_price           = number_format( (int) $search_tabs_data['price_max'], 0, '', ',' );
$available_first_key = array_key_first( $search_tabs_data['filters']['available']['options'] );
$available           = $search_tabs_data['filters']['available']['options'][ $available_first_key ];
if ( ! empty( $_POST['available'] ) ) {
    foreach ( $search_tabs_data['filters']['available']['options'] as $option ) {
        if ( $option['value'] !== $_POST['available'] ) {
            continue;
        }

        $available = $option;
        break;
    }
}

$price_first_key = array_key_first( $search_tabs_data['filters']['price']['options'] );
$price           = $search_tabs_data['filters']['price']['options'][ $price_first_key ];
if ( ! empty( $_POST['max_price'] ) ) {
    foreach ( $search_tabs_data['filters']['price']['options'] as $option ) {
        if ( $option['value'] !== $_POST['max_price'] ) {
            continue;
        }

        $price = $option;
        break;
    }
}

$property_type = 'All';
if ( ! empty( $_REQUEST['property_type'] ) ) {
    foreach ( $search_tabs_data['filters']['property_type']['options'] as $option ) {
        if ( $option['value'] !== $_REQUEST['property_type'] ) {
            continue;
        }
        $property_type = $option['label'];
        break;
    }
}

$baths = $search_tabs_data['filters']['baths'];
$beds  = $search_tabs_data['filters']['beds'];
?>
<script>
    const searchTabsData = <?php echo wp_json_encode( $search_tabs_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>;
</script>
<div class="results-filters-wrapper">
    <div class="results-filters-items">
        <input type="hidden" name="action" value="get_<?php echo $post_type; ?>">

        <button class="result-filter" type="button" data-filter="location">
                <span class="result-filter-top">
                    <span class="result-title">
                        <?php _e( 'Location' ); ?>
                    </span>
                </span>
            <span class="result-filter-bottom">
                    <span class="result-value">
                        <?php echo esc_attr( $location['label'] ); ?>
                        <img src=<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16"
                             alt="Dropdown arrow">
                    </span>
                </span>
            <span class="result-dropdown"></span>
        </button>

        <button class="result-filter" type="button" data-filter="available">
                <span class="result-filter-top">
                    <span class="result-title">
                        <?php _e( 'Available' ); ?>
                    </span>
                </span>
            <span class="result-filter-bottom">
                    <span class="result-value">
                        <?php echo esc_attr( $available['label'] ); ?>
                        <img src=<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16"
                             alt="Dropdown arrow">
                    </span>
                </span>
            <span class="result-dropdown"></span>
        </button>

        <button class="result-filter" type="button" data-filter="price">
                <span class="result-filter-top">
                    <span class="result-title">
                        <?php _e( 'Max Price' ); ?>
                    </span>
                </span>
            <span class="result-filter-bottom">
                    <span class="result-value">
                        <?php echo esc_attr( $price['label'] ); ?>
                        <img src=<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16"
                             alt="Dropdown arrow">
                    </span>
                </span>
            <span class="result-dropdown">
                <?php echo esc_html( $max_price ); ?>
            </span>
        </button>

        <?php if ( ! empty( $baths['options'] ) || ! empty( $beds['options'] ) ) { ?>
            <div class="result-filter-wrapper">
                <button class="result-filter" type="button" data-filter="beds_baths">
                    <span class="result-filter-top">
                        <span class="result-title">
                            <?php _e( 'Beds and baths' ); ?>
                        </span>
                    </span>
                    <span class="result-filter-bottom">
                        <span class="result-value">
                            <span data-result-beds-baths-text><?php _e( 'Select' ); ?></span>
                            <img src="<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16"
                                 alt="Dropdown arrow">
                        </span>
                    </span>
                </button>
                <div class="beds-baths-dropdown fixed" data-result-dropdown="beds_baths" hidden>
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
                <input type="hidden" name="beds" value="" data-result-beds-value>
                <input type="hidden" name="baths" value="" data-result-baths-value>
            </div>
        <?php } ?>

        <button class="result-filter" type="button" data-filter="property_type">
                <span class="result-filter-top">
                    <span class="result-title">
                        <?php _e( 'Property type' ); ?>
                    </span>
                </span>
            <span class="result-filter-bottom">
                        <span class="result-value">
                            <?php echo esc_html( $property_type ); ?>
                            <img src=<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16"
                                 alt="Dropdown arrow">
                        </span>
                </span>
            <span class="result-dropdown"></span>
        </button>

        <button class="result-filter color" type="button" data-filter="developer">
                <span class="result-filter-top">
                    <span class="result-title">
                        <?php _e( 'Developer' ); ?>
                    </span>
                </span>
            <span class="result-filter-bottom">
                    <span class="result-value">
                        <?php _e( 'All' ); ?>
                        <img src=<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16"
                             alt="Dropdown arrow">
                    </span>
                </span>
            <span class="result-dropdown">

                </span>
        </button>

        <?php if ( ! empty( $search_tabs_data['filters']['area'] ) ) { ?>
            <button class="result-filter color" type="button" data-filter="area">
                <span class="result-filter-top">
                    <span class="result-title">
                        <?php _e( 'Max Area' ); ?>
                    </span>
                </span>
                <span class="result-filter-bottom">
                    <span class="result-value">
                        <?php _e( 'All' ); ?>
                        <img src=<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16"
                             alt="Dropdown arrow">
                    </span>
                </span>
                <span class="result-dropdown"></span>
            </button>
        <?php } ?>
    </div>
    <?php //TODO hide for MVP
    /*
    <button class="advanced">
        <?php _e('Advanced search'); ?>
        <img src=<?php echo THEME_URL; ?>/assets/img/filters.svg" width="16" height="16" alt="Vector filters">
    </button>
    */ ?>
</div>