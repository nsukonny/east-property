<?php

$units_by_beds = $args['units_by_beds'] ?? array();

if ( empty( $units_by_beds ) ) {
	return;
}
?>
<div class="single-info-block">
	<h3><?php _e( 'Pricing' , 'east-property' ); ?></h3>
	<div class="accordion-wrapper">
		<?php foreach ( $units_by_beds as $units ) {
			$beds = $units['beds'] ?? 1;

			$area = $units['min_area'] . ' - ' . $units['max_area'];
			if ( $units['min_area'] === $units['max_area'] || ( empty( $units['min_area'] ) || empty( $units['max_area'] ) ) ) {
				$area = $units['min_area'];
			}

			$price = sprintf( '%s %s', __( 'AED' , 'east-property' ), number_format( (float) $units['price'], 0, '.', ',' ) );

			$content_rows = array();

			if ( ! empty( $units['units'] ) ) {
				foreach ( $units['units'] as $unit ) {
					$unit_beds        = $unit->get_beds();
					$unit_area        = $unit->get_area();
					$unit_floor_plans = $unit->get_floor_plan();

					$content_cols = array();
					if ( ! empty( $unit_beds ) ) {
						$content_cols[] = array(
							'img'  => THEME_URL . '/assets/img/bed.svg',
							'text' => $unit_beds . ' ' . __( 'Bed' , 'east-property' ),
						);
					}

					if ( ! empty( $unit_area ) ) {
						$content_cols[] = array(
							'img'  => THEME_URL . '/assets/img/meters.svg',
							'text' => $unit_area . ' ' . __( 'sqft' , 'east-property' ),
						);
					}

					if ( ! empty( $unit_floor_plans ) ) {
						foreach ( $unit_floor_plans as $unit_floor_plan ) {
							if ( empty( $unit_floor_plan['layout'] ) || empty( $unit_floor_plan['layout']['sizes'] ) ) {
								continue;
							}

							$content_cols[] = array(
								'image_col' => true,
								'img'       => $unit_floor_plan['layout']['sizes']['thumbnail'],
								'alt'       => sprintf( __( 'Floor plan for %s bed unit' , 'east-property' ), $unit_beds ),
								'modal'     => array(
									'id'    => 'plan-modal',
									'title' => sprintf( __( 'Floor plan for %s bed unit' , 'east-property' ), $unit_beds ),
									'image' => $unit_floor_plan['layout']['sizes']['large'],
								),
							);
						}
					}

					$content_rows[] = $content_cols;
				}
			}

			get_component_template(
				'ui/accordion/accordion',
				array(
					'title_cols'   => array(
						array(
							'img'  => THEME_URL . '/assets/img/bed.svg',
							'text' => $beds . ' ' . __( 'Beds' , 'east-property' ),
						),
						array(
							'img'  => THEME_URL . '/assets/img/meters.svg',
							'text' => $area . ' ' . __( 'sqft' , 'east-property' ),
						),
						array(
							'text' => __( 'from' , 'east-property' ) . ' <em>' . $price . '</em>',
						),
					),
					'content_rows' => $content_rows,
				)
			);
		}
		?>
	</div>
</div>
