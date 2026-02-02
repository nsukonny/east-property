<?php
/**
 * Developer list of properties
 */
$developer = $args['developer'] ?? null;

if ( ! $developer ) {
    return;
}
?>
<section class="result-tabs" data-tabs>
    <div class="container">
        <div class="result-tabs-wrapper">
            <div class="result-tabs-buttons" role="tablist">
                <button class="result-tab-button active" type="button" id="result-tabs-list-tab" data-tab-button
                        data-tab="list" role="tab" aria-selected="true" aria-controls="result-tabs-list-panel">List view
                </button>
                <button class="result-tab-button" type="button" id="result-tabs-map-tab" data-tab-button data-tab="map"
                        role="tab" aria-selected="false" tabindex="-1" aria-controls="result-tabs-map-panel">Map view
                </button>
            </div>
            <div class="result-tabs-content active" id="result-tabs-list-panel" data-tab-panel data-tab="list"
                 role="tabpanel" aria-labelledby="result-tabs-list-tab">
                <div class="content-title">
                    <div class="title-top">
                        <h2>Modon Properties: 11 properties</h2>
                        <button class="sort">
                            Show expensive first
                            <img src="/img/arrow-down.svg" width="16" height="16" alt="Arrow down">
                        </button>
                    </div>
                    <p>Metropolitan Capital Real Estate is one of the leading real estate agencies in the UAE capital,
                        which was established in 2012 as a part of Metropolitan Group. We provide local and foreign
                        customers with the highest possible level of service and assistance. We have access to the best
                        real estate in the emirate, and operate both within off-plan properties (under construction)
                        and the resale property market of Abu Dhabi.
                    </p>
                </div>
                <div class="content-list">
                    @@include('components/cards/large-card/large-card.html')
                    @@include('components/cards/large-card/large-card.html')
                    @@include('components/cards/large-card/large-card.html')
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
                        @@include('components/cards/small-card/small-card.html')
                        @@include('components/cards/small-card/small-card.html')
                        @@include('components/cards/small-card/small-card.html')
                        @@include('components/cards/small-card/small-card.html')
                        @@include('components/cards/small-card/small-card.html')
                        @@include('components/cards/small-card/small-card.html')
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>