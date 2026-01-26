<?php
/**
 * Result filters component
 */

$search_tabs_data = get_search_tabs_data();

if ( empty( $search_tabs_data ) ) {
    return;
}

$max_price          = number_format( (int) $search_tabs_data['price_max'], 0, '', ',' );
$available_last_key = array_key_last( $search_tabs_data['filters']['available']['options'] );
$available          = $search_tabs_data['filters']['available']['options'][ $available_last_key ];
if ( ! empty( $_POST['available'] ) ) {
    foreach ( $search_tabs_data['filters']['available']['options'] as $option ) {
        if ( $option['value'] !== $_POST['available'] ) {
            continue;
        }

        $available = $option;
        break;
    }
}

$price_last_key = array_key_last( $search_tabs_data['filters']['price']['options'] );
$price          = $search_tabs_data['filters']['price']['options'][ $price_last_key ];
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
if ( ! empty( $_POST['property_type'] ) ) {
    foreach ( $search_tabs_data['filters']['property_type']['options'] as $option ) {
        if ( $option['value'] !== $_POST['property_type'] ) {
            continue;
        }
        $property_type = $option['label'];
        break;
    }
}
?>
<script>
    const searchTabsData = <?php echo wp_json_encode( $search_tabs_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>;
</script>
<div class="results-filters-wrapper">
    <div class="results-filters-items">
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
            <span class="result-dropdown">

                </span>
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
            <span class="result-dropdown"></span>
        </button>
        <button class="result-filter color" type="button" data-filter="property_type">
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
    </div>
    <?php //TODO hide for MVP
    /*
    <button class="advanced">
        <?php _e('Advanced search'); ?>
        <img src=<?php echo THEME_URL; ?>/assets/img/filters.svg" width="16" height="16" alt="Vector filters">
    </button>
    */ ?>
</div>