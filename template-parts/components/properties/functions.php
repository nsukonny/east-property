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

	$properties_posts = get_posts( $args );
	if ( empty( $properties_posts ) ) {
		return array();
	}

	$properties = array();
	foreach ( $properties_posts as $post ) {
		$properties[] = new \Entities\Property( $post );
	}

	return $properties;
}