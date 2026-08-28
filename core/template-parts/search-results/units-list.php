<?php
/**
 * Search results tabs section
 */

$h2            = $args['h2'] ?? '';
$units         = $args['units'] ?? '';
$description   = $args['description'] ?? __( 'Browse a wide selection of apartments in Dubai. From smart studios'
                                             . ' to spacious family residences and premium off-plan units in top'
                                             . ' locations. Compare prices, layouts, and communities in one place '
                                             . 'and find the apartment that fits your lifestyle, goals, and budget.' , 'east-property' );
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
					<p><?php echo wp_kses_post( $description ); ?></p>
				</div>
				<div class="content-list">
					<?php
					if ( ! empty( $units ) ) {
						foreach ( $units['items'] as $unit ) {
							get_template_part( 'core/components/cards/unit-card',
								null,
								array(
									'unit'     => $unit,
									'template' => $card_template,
								)
							);
						}

						get_template_part(
							'core/components/common/pagination',
							null,
							array(
								'total_items'    => $units['total'] ?? count( $units ),
								'items_per_page' => PROPERTIES_PER_PAGE,
								'current_href'   => $_REQUEST['current_href'] ?? '',
							)
						);
					} else {
						_e( 'Items not found' , 'east-property' );
					}
					?>
				</div>
			</div>
		</div>
	</div>
</section>