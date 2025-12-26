<?php
/**
 * Search tabs component
 */
?>
<div class="search-tabs" data-search-tabs>
    <div class="tabs-buttons" role="tablist">
        <button type="button" class="button is-active" role="tab" id="search-tabs-tab-all"
                aria-controls="search-tabs-panel" aria-selected="true" tabindex="0" data-search-tab data-type="all">All
        </button>
        <button type="button" class="button" role="tab" id="search-tabs-tab-apartments"
                aria-controls="search-tabs-panel" aria-selected="false" tabindex="-1" data-search-tab
                data-type="apartments">Apartments
        </button>
        <button type="button" class="button" role="tab" id="search-tabs-tab-houses" aria-controls="search-tabs-panel"
                aria-selected="false" tabindex="-1" data-search-tab data-type="houses">Houses
        </button>
        <button type="button" class="button" role="tab" id="search-tabs-tab-villas" aria-controls="search-tabs-panel"
                aria-selected="false" tabindex="-1" data-search-tab data-type="villas">Villas
        </button>
        <button type="button" class="button" role="tab" id="search-tabs-tab-offices" aria-controls="search-tabs-panel"
                aria-selected="false" tabindex="-1" data-search-tab data-type="offices">Offices
        </button>
    </div>
    <form class="tabs-panel" data-search-panel>
        <div class="tabs-fields" role="tabpanel" id="search-tabs-panel" aria-labelledby="search-tabs-tab-all">
            <div class="tab-field">
                <button type="button" class="tab-selector" data-search-selector="available" aria-haspopup="listbox"
                        aria-expanded="false">
                    <span class="tab-field-label">Available</span>
                    <span class="tab-selector-value">
                        <span data-search-available-text>2026</span>
                        <img src="<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16" alt="Dropdown arrow">
                    </span>
                </button>
                <div class="tab-dropdown" role="listbox" tabindex="-1" data-search-dropdown="available"
                     data-check-icon="<?php echo THEME_URL; ?>/assets/img/check.svg" hidden></div>
            </div>
            <div class="tab-divider" aria-hidden="true"></div>
            <div class="tab-field">
                <button type="button" class="tab-selector" data-search-selector="price" aria-haspopup="listbox"
                        aria-expanded="false">
                    <span class="tab-field-label">Max price, AED</span>
                    <span class="tab-selector-value">
                        <span data-search-price-text>8,000,000</span>
                        <img src="<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16"
                             alt="Dropdown arrow">
                    </span>
                </button>
                <div class="tab-dropdown" role="listbox" tabindex="-1" data-search-dropdown="price"
                     data-check-icon="<?php echo THEME_URL; ?>/assets/img/check.svg" hidden></div>
            </div>
            <?php
            get_template_part( 'template-parts/components/ui/button', null,
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
        <input type="hidden" name="available" value="" data-search-available-value>
        <input type="hidden" name="max_price" value="" data-search-price-value>
    </form>
</div>
