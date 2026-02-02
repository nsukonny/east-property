<?php
/**
 * Al functionality for units. Search, filters, etc.
 */

/**
 * Get list of units by filters if they is set
 *
 * @return array
 */
function get_units(): array {
	$args = array(
		'post_type'      => 'unit',
		'posts_per_page' => - 1,
	);

	if ( ! empty( $_REQUEST['area'] ) && 'all' !== $_REQUEST['area'] ) {
		$args['meta_query'] = array( //TODO Recheck
			array(
				'key'     => 'area_size',
				'value'   => (int) sanitize_text_field( $_REQUEST['area'] ),
				'compare' => '<=',
				'type'    => 'NUMERIC',
			),
		);
	}

	$units_posts = get_posts( $args );
	if ( empty( $units_posts ) ) {
		return array();
	}

	$units = array();
	foreach ( $units_posts as $unit_post ) {

		$meta = get_post_meta( $unit_post->ID );
		$unit = new \Entities\Unit( $unit_post );

		$price_filter            = ! empty( $_REQUEST['price'] ) && 'all' !== $_REQUEST['price'] ? (int) sanitize_text_field( $_REQUEST['price'] ) : null;
		$is_skip_by_price_filter = $price_filter && $unit->get_price() > $price_filter;
		if ( $is_skip_by_price_filter ) {
			continue;
		}

		$developer_filter = ! empty( $_REQUEST['developer'] ) && 'all' !== $_REQUEST['developer'] ? (int) sanitize_text_field( $_REQUEST['developer'] ) : null;
		if ( null !== $developer_filter ) {
			$developer = $unit->get_developer();

			if ( null !== $developer && $developer->get_id() !== $developer_filter ) {
				continue;
			}
		}

		$property = $unit->get_property();

		if ( ! empty( $_REQUEST['available'] ) && 'all' !== $_REQUEST['available'] && null !== $property ) {
			$year = sanitize_text_field( $_REQUEST['available'] );

			$available_date = $property->get_delivery_date( false );
			if ( strtotime( $available_date ) < strtotime( $year . '-01-01' ) || strtotime( $available_date ) > strtotime( $year . '-12-31' ) ) {
				continue;
			}
		}

		if ( ! empty( $_REQUEST['property_type'] ) && 'all' !== $_REQUEST['property_type'] && null !== $property ) {
			$property_type_filter = sanitize_text_field( $_REQUEST['property_type'] );
			$property_type        = $property->get_property_type();
			if ( empty( $property_type['value'] ) || $property_type_filter !== $property_type['value'] ) {
				continue;
			}
		}

		$filter_by_beds = ! empty( $_REQUEST['beds'] ) ? explode( ',', sanitize_text_field( $_REQUEST['beds'] ) ) : null;
		if ( ! empty( $filter_by_beds ) ) {
			foreach ( $filter_by_beds as &$filter_by_bed ) {
				if ( 'studio' === $filter_by_bed ) {
					$filter_by_bed = '0';
				}
			}
		}

		if ( ! empty( $filter_by_beds ) && ! in_array( (string) $unit->get_beds(), $filter_by_beds, true ) ) {
			continue;
		}

		$filter_by_baths = ! empty( $_REQUEST['baths'] ) ? explode( ',', sanitize_text_field( $_REQUEST['baths'] ) ) : null;
		if ( ! empty( $filter_by_baths ) && ! in_array( (string) $unit->get_baths(), $filter_by_baths, true ) ) {
			continue;
		}

		$filter_by_areas = ! empty( $_REQUEST['area'] ) ? (int) sanitize_text_field( $_REQUEST['baths'] ) : null;
		if ( ! empty( $filter_by_baths ) && ! in_array( (string) $unit->get_baths(), $filter_by_baths, true ) ) {
			continue;
		}

		$units[] = $unit;
	}

	return $units;
}