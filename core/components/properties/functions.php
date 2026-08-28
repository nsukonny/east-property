<?php
/**
 * Al functionality for properties. Search, filters, etc.
 */

/**
 * Get properties list by filters if they is set
 *
 * @return array
 */
function get_properties( $limit = - 1, $skip_filters = false ): array {
	global $wpdb;

	$current_language = 'en';
	if ( function_exists( 'pll_current_language' ) ) {
		$current_language = (string) pll_current_language( 'slug' );
	}

	$current_page = pagination_get_current_page() ?? 1;
	$filters_hash = build_filters_hash(
		$_REQUEST,
		array(
			'limit'        => $limit,
			'page'         => $current_page,
			'skip_filters' => $skip_filters,
			'language'     => $current_language,
		)
	);

	$properties = ! IS_DEV ? get_transient( 'properties_' . $filters_hash ) : false;
	if ( ! empty( $properties ) ) {
		return $properties;
	}

	if ( 0 > $limit ) {
		$limit = PROPERTIES_PER_PAGE;
	}
	$offset = ( $current_page - 1 ) * $limit;

	$joins  = array();
	$where  = array( 'p.post_type = %s', 'p.post_status = %s' );
	$params = array( 'property', 'publish' );

	if ( ! empty( $_REQUEST['available'] ) && 'all' !== $_REQUEST['available'] ) {
		if ( 'available_immediately' === $_REQUEST['available'] ) {
			$date_to   = date( 'Ymd' );
			$date_from = '20000101';
		} elseif ( 'in_construction' === $_REQUEST['available'] ) {
			$date_to   = date( 'Ymd', strtotime( '+20 years' ) );
			$date_from = date( 'Ymd' );
		} else {
			$year      = sanitize_text_field( wp_unslash( $_REQUEST['available'] ) );
			$date_to   = date( 'Ymd', strtotime( $year . '1231' ) );
			$date_from = '20000101';
		}

		$joins[] = "
			INNER JOIN {$wpdb->postmeta} AS pm_delivery_date
				ON pm_delivery_date.post_id = p.ID
				AND pm_delivery_date.meta_key = 'delivery_date'
		";

		if ( ! empty( $date_from ) ) {
			$where[]  = 'pm_delivery_date.meta_value >= %s';
			$params[] = $date_from;
		}

		if ( ! empty( $date_to ) ) {
			$where[]  = 'pm_delivery_date.meta_value <= %s';
			$params[] = $date_to;
		}
	}

	if ( ! empty( $_REQUEST['property_type'] ) && 'all' !== $_REQUEST['property_type'] ) {
		$property_type = sanitize_text_field( wp_unslash( $_REQUEST['property_type'] ) );
		$joins[]       = "
			INNER JOIN {$wpdb->postmeta} AS pm_property_type
				ON pm_property_type.post_id = p.ID
				AND pm_property_type.meta_key = 'property_type'
		";
		$where[]       = 'pm_property_type.meta_value = %s';
		$params[]      = $property_type;
	}

	if ( ! empty( $_REQUEST['location'] ) && 'all' !== $_REQUEST['location'] ) {
		$location = sanitize_title( wp_unslash( $_REQUEST['location'] ) );
		$joins[]  = "
			INNER JOIN {$wpdb->term_relationships} AS tr_location
				ON tr_location.object_id = p.ID
			INNER JOIN {$wpdb->term_taxonomy} AS tt_location
				ON tt_location.term_taxonomy_id = tr_location.term_taxonomy_id
				AND tt_location.taxonomy = 'location'
			INNER JOIN {$wpdb->terms} AS t_location
				ON t_location.term_id = tt_location.term_id
		";
		$where[]  = 't_location.slug = %s';
		$params[] = $location;
	}

	//JUST properties with units
	if ( ! $skip_filters ) {
		$joins[] = "
			INNER JOIN {$wpdb->postmeta} AS pm_units_count
				ON pm_units_count.post_id = p.ID
				AND pm_units_count.meta_key = 'units_count'
		";
		$where[] = "CAST(pm_units_count.meta_value AS UNSIGNED) > 0";
	}

	$filter_min_price = ! empty( $_REQUEST['min_price'] )
		? (int) sanitize_text_field( wp_unslash( $_REQUEST['min_price'] ) )
		: null;
	$filter_max_price = ! empty( $_REQUEST['max_price'] )
		? (int) sanitize_text_field( wp_unslash( $_REQUEST['max_price'] ) )
		: null;
	if ( ! $skip_filters && ( null !== $filter_min_price || null !== $filter_max_price ) ) {
		$joins[] = "
			INNER JOIN {$wpdb->postmeta} AS pm_price
				ON pm_price.post_id = p.ID
				AND pm_price.meta_key = 'price'
		";
		if ( null !== $filter_min_price ) {
			$where[]  = 'CAST(pm_price.meta_value AS UNSIGNED) >= %d';
			$params[] = $filter_min_price;
		}
		if ( null !== $filter_max_price ) {
			$where[]  = 'CAST(pm_price.meta_value AS UNSIGNED) <= %d';
			$params[] = $filter_max_price;
		}
	}

	if ( ! empty( $_REQUEST['developer'] ) && 'all' !== $_REQUEST['developer'] ) {
		$developer_filter = (int) sanitize_text_field( wp_unslash( $_REQUEST['developer'] ) );
		if ( ! $skip_filters && $developer_filter > 0 ) {
			$joins[]  = "
				INNER JOIN {$wpdb->postmeta} AS pm_developer
					ON pm_developer.post_id = p.ID
					AND pm_developer.meta_key = 'developer'
			";
			$where[]  = 'CAST(pm_developer.meta_value AS UNSIGNED) = %d';
			$params[] = $developer_filter;
		}
	}

	//add polylang support
	if ( function_exists( 'pll_current_language' ) ) {
		$joins[] = "INNER JOIN {$wpdb->term_relationships} AS pll_language_relation
    					ON pll_language_relation.object_id = p.ID";
		$joins[] = "INNER JOIN {$wpdb->term_taxonomy} AS pll_language_taxonomy
						ON pll_language_taxonomy.term_taxonomy_id =
						   pll_language_relation.term_taxonomy_id
						AND pll_language_taxonomy.taxonomy = 'language'";
		$joins[] = "INNER JOIN {$wpdb->terms} AS pll_language
						ON pll_language.term_id = pll_language_taxonomy.term_id
						AND pll_language.slug = '{$current_language}'";
	}

	$join_sql  = implode( "\n", $joins );
	$where_sql = implode( "\nAND ", $where );
	$sql       = "
		SELECT 
			p.ID,
			COUNT(*) OVER() AS total_count
		FROM {$wpdb->posts} AS p
		{$join_sql}
		WHERE {$where_sql}
		GROUP BY p.ID
		ORDER BY p.post_title ASC
		LIMIT %d OFFSET %d
	";
	$params[]  = (int) $limit;
	$params[]  = (int) $offset;
	$query     = $wpdb->prepare( $sql, $params );

	$properties_posts = $wpdb->get_results( $query );

	if ( empty( $properties_posts ) ) {
		$properties = array(
			'items' => array(),
			'total' => 0,
		);
		set_transient( 'properties_' . $filters_hash, $properties, DAY_IN_SECONDS );

		return $properties;
	}
	$total = ! empty( $properties_posts[0]->total_count ) ? (int) $properties_posts[0]->total_count : 0;

	$properties_entities = array();
	foreach ( $properties_posts as $post ) {
		unset( $post->total_count );
		$properties_entities[] = new \Entities\Property( $post->ID );
	}

	$properties = array(
		'items'     => $properties_entities,
		'map_items' => array(),
		'total'     => $total,
	);

	set_transient( 'properties_' . $filters_hash, $properties, DAY_IN_SECONDS );

	return $properties;
}

/**
 * Content for sidebar on the map.
 */
function ajax_get_map_property(): void {
	//check_ajax_referer( 'get_filtered_properties' ); //TODO Check, maybe javascript was cached it

	$property_id = sanitize_text_field( $_REQUEST['property_id'] ) ?? null;
	if ( null === $property_id ) {
		wp_send_json_error( array( 'message' => 'Property ID is required' ) );
	}

	$property = new \Entities\Property( $property_id );

	$units          = array();
	$property_units = $property->get_units();
	if ( ! empty( $property_units ) ) {
		foreach ( $property_units as $unit ) {
			$units[] = array(
				'id'    => $unit->get_id(),
				'price' => $unit->get_price_html(),
				'image' => $unit->get_gallery()[0]['sizes']['medium'] ?? '',
				'beds'  => $unit->get_beds(),
				'area'  => $unit->get_area(),
				'url'   => $unit->get_url(),
			);
		}
	}

	$prop_images = array();
	$gallery     = $property->get_gallery();
	foreach ( $gallery as $image ) {
		$prop_images[] = ( ! empty( $image['sizes']['large'] ) ) ? $image['sizes']['large'] : $image['url'];
	}

	ob_start();

	get_component_template(
		'cards/map-sidebar',
		array(
			'title'           => $property->get_title(),
			'location'        => $property->get_location()->name ?? '',
			'gallery'         => $prop_images,
			'units_available' => count( $units ),
			'price_from'      => $property->get_price_html(),
			'units'           => $units,
			'developer_name'  => $property->get_developer()?->get_title() ?? '',
			'delivery_date'   => $property->get_delivery_date(),
		)
	);

	$map_property_html = ob_get_clean();

	wp_send_json_success(
		array(
			'map_property_html' => $map_property_html,
		)
	);
}

add_action( 'wp_ajax_get_map_property', 'ajax_get_map_property' );
add_action( 'wp_ajax_nopriv_get_map_property', 'ajax_get_map_property' );

/**
 * Get array of properties with units more than 0 ordered by count
 *
 * @return array
 */
function get_properties_by_count_of_units(): array {
	global $wpdb;

	$results = get_transient( 'properties_by_count_of_units' );
	if ( false === $results ) {
		$query = "
		SELECT p.ID, COUNT(u.ID) as units_count
		FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON pm.meta_value = p.ID AND pm.meta_key = 'property'
			LEFT JOIN {$wpdb->posts} u ON u.ID = pm.post_id AND u.post_type = 'unit' AND u.post_status = 'publish'
		WHERE p.post_type = 'property' AND p.post_status = 'publish'
		GROUP BY p.ID
		HAVING units_count > 0
		ORDER BY units_count DESC
	";

		$results = $wpdb->get_results( $query, ARRAY_A );
		set_transient( 'properties_by_count_of_units', $results, HOUR_IN_SECONDS );
	}

	if ( empty( $results ) ) {
		return array();
	}

	$properties = array();
	foreach ( $results as $result ) {
		$property = new \Entities\Property( $result['ID'] );
		$property->set_units_count( (int) $result['units_count'] );
		$properties[] = $property;
	}

	return $properties;
}

/**
 * Update important data for properties every 15 minutes
 *
 * @return void
 */
function auto_update_properties() {
	global $wpdb;

	//get 30 properties ordered by last_updated meta timestamp and call update_data()
	$posts = get_posts(
		array(
			'posts_per_page' => 30,
			'post_type'      => 'property',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'meta_key'       => 'last_updated',
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
			'meta_query'     => array(
				array(
					'key'     => 'last_updated',
					'compare' => '<',
					'value'   => time(),
					'type'    => 'NUMERIC',
				),
			),
		)
	);

	if ( empty( $posts ) ) {
		return;
	}

	foreach ( $posts as $post_id ) {
		$property = new \Entities\Property( $post_id );
		$property->update_data();
	}
}

/**
 * Call WP Cron
 */
add_action(
	'init',
	static function () {
		if ( ! wp_next_scheduled( 'auto_update_properties' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'auto_update_properties' );
		}

		auto_update_properties();
	}
);
