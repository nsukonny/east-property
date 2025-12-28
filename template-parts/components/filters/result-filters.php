<?php
/**
 * Result filters component
 */
?>
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
                        2026
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
                        8,000,000
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
                        <?php _e( 'All' ); ?>
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
                        <?php _e( 'Area' ); ?>
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