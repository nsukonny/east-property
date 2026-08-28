<?php
/**
 * Search results tabs section
 */

$h2            = $args['h2'] ?? '';
$properties    = $args['properties'] ?? '';
$card_template = $args['card_template'] ?? 'large-card';
?>
<section class="result-tabs" data-tabs>
    <div class="container">
        <div class="result-tabs-wrapper">
            <div class="result-tabs-buttons" role="tablist">
                <button class="result-tab-button active" type="button" id="result-tabs-list-tab" data-tab-button
                        data-tab="list" role="tab" aria-selected="true" aria-controls="result-tabs-list-panel">
					<?php _e( 'List view' , 'east-property' ); ?>
                </button>
                <button class="result-tab-button" type="button" id="result-tabs-map-tab" data-tab-button data-tab="map"
                        role="tab" aria-selected="false" tabindex="-1" aria-controls="result-tabs-map-panel">
					<?php _e( 'Map view' , 'east-property' ); ?>
                </button>
            </div>
            <div class="result-tabs-content active" id="result-tabs-list-panel" data-tab-panel data-tab="list"
                 role="tabpanel" aria-labelledby="result-tabs-list-tab">
                <div class="content-title">
					<?php get_template_part( 'core/components/common/breadcrumbs' ); ?>
                    <div class="title-top">
                        <h2><?php echo esc_html( $h2 ); ?></h2>
						<?php
						//TODO Hidden for MVP
						/*
						<button class="sort">
							Show expensive first
							<img src="<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16" alt="Arrow down">
						</button>
						*/
						?>
                    </div>
                    <p><?php _e( 'Aliquam lacinia diam quis lacus euismod' , 'east-property' ); ?></p>
                </div>
                <div class="content-list">
					<?php
					if ( ! empty( $properties ) ) {
						foreach ( $properties['items'] as $property ) {
							get_template_part( 'core/components/cards/property-card', null,
								array(
									'property' => $property,
									'template' => 'large-card',
								)
							);
						}

						get_template_part(
							'core/components/common/pagination',
							null,
							array(
								'total_items'    => $properties['total'] ?? count( $properties ),
								'items_per_page' => PROPERTIES_PER_PAGE,
								'current_href'   => $_REQUEST['current_href'],
							)
						);
					} else {
						_e( 'Items not found' , 'east-property' );
					}
					?>
                </div>
            </div>
            <div class="result-tabs-content" id="result-tabs-map-panel" data-tab-panel data-tab="map" role="tabpanel"
                 aria-labelledby="result-tabs-map-tab">
                <div class="result-tabs-content-inner">
					<?php
					get_template_part( 'core/components/properties/map', null,
						array(
							'properties'   => $properties,
							'show_sidebar' => true,
							'class'        => 'full-width',
						)
					);
					?>
                </div>
            </div>
        </div>
    </div>
</section>