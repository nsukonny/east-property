<?php
/**
 * Al functionality for properties. Search, filters, etc.
 */

/**
 * Get properties list by filters if they is set
 *
 * @return array
 */
function get_properties(): array {
	$args = array(
		'post_type'      => 'properties',
		'posts_per_page' => - 1,
	);

	if ( ! empty( $_REQUEST['available'] ) && 'all' !== $_REQUEST['available'] ) {
		$year = sanitize_text_field( $_REQUEST['available'] );

		$args['meta_query'][] = array(
			'key'     => 'delivery_date',
			'value'   => array( $year . '0101', $year . '1231' ),
			'compare' => 'BETWEEN',
			'type'    => 'NUMERIC',
		);
	}

	if ( ! empty( $_REQUEST['property_type'] ) && 'all' !== $_REQUEST['property_type'] ) {
		$property_type = sanitize_text_field( $_REQUEST['property_type'] );

		$args['meta_query'][] = array(
			'key'     => 'property_type',
			'value'   => $property_type,
			'compare' => '=',
		);
	}

	$properties_posts = get_posts( $args );
	if ( empty( $properties_posts ) ) {
		return array();
	}

	$properties = array();
	foreach ( $properties_posts as $post ) {
		$property = new \Entities\Property( $post );

		$price_filter            = ! empty( $_REQUEST['price'] ) && 'all' !== $_REQUEST['price'] ? (int) sanitize_text_field( $_REQUEST['price'] ) : null;
		$is_skip_by_price_filter = $price_filter && $property->get_price() > $price_filter;
		if ( $is_skip_by_price_filter ) {
			continue;
		}

		$developer_filter            = ! empty( $_REQUEST['developer'] ) && 'all' !== $_REQUEST['developer'] ? (int) sanitize_text_field( $_REQUEST['developer'] ) : null;
		$is_skip_by_developer_filter = $developer_filter && $property->get_developer()['id'] !== $developer_filter;
		if ( $is_skip_by_developer_filter ) {
			continue;
		}

		$properties[] = $property;
	}

	return $properties;
}