<?php
/**
 * Al functionality for units. Search, filters, etc.
 */

use Entities\Unit;

/**
 * Get available listing type choices.
 *
 * Single source of truth for the listing_type meta. Keep the keys in sync with
 * the choices of the ACF field field_694ea5354ae1e (acf-json Units group).
 *
 * @return array
 */
function core_get_listing_type_choices(): array {
	return array(
		'off-plan'  => __( 'Off-plan', 'east-property' ),
		'secondary' => __( 'Secondary', 'east-property' ),
		'distress'  => __( 'Distress', 'east-property' ),
	);
}

/**
 * Check the given listing type is known, fall back to the default one.
 *
 * @param string $listing_type Listing type slug.
 *
 * @return string
 */
function core_sanitize_listing_type( string $listing_type ): string {
	return isset( core_get_listing_type_choices()[ $listing_type ] ) ? $listing_type : 'off-plan';
}

/**
 * Get list of units by filters if they is set
 *
 * @param int $limit
 *
 * @return array
 */
function get_units( $listing_type = '', $limit = 25 ): array {
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
			'language'     => $current_language,
			'listing_type' => $listing_type,
		)
	);
	$units        = ! IS_DEV ? get_transient( 'units_' . $filters_hash ) : false;
	if ( false !== $units ) {
		return $units;
	}

	if ( 0 > $limit ) {
		$limit = PROPERTIES_PER_PAGE;
	}
	$offset = ( $current_page - 1 ) * $limit;

	$joins  = array( "LEFT JOIN {$wpdb->postmeta} AS pm_property ON pm_property.post_id = u.ID AND pm_property.meta_key = 'property'" );
	$where  = array( 'u.post_type = %s', 'u.post_status = %s' );
	$params = array( 'unit', 'publish' );

	if ( ! empty( $_REQUEST['listing_type'] ) ) {
		$listing_type = sanitize_text_field( wp_unslash( $_REQUEST['listing_type'] ) );
	}

	if ( ! empty( $listing_type ) && 'all' !== $listing_type ) {
		$joins[]  = "
			INNER JOIN {$wpdb->postmeta} AS pm_listing_type
				ON pm_listing_type.post_id = u.ID
				AND pm_listing_type.meta_key = 'listing_type'
		";
		$where[]  = 'pm_listing_type.meta_value = %s';
		$params[] = sanitize_text_field( wp_unslash( $listing_type ) );
	}

	if ( ! empty( $_REQUEST['area'] ) && ( 'all' !== $_REQUEST['area'] ) ) {
		$joins[]  = "
			INNER JOIN {$wpdb->postmeta} AS pm_area
				ON pm_area.post_id = u.ID
				AND pm_area.meta_key = 'area_size'
		";
		$where[]  = 'pm_delivery_date.meta_value <= %d';
		$params[] = (int) sanitize_text_field( $_REQUEST['area'] );
	}

	if ( ! empty( $_REQUEST['beds'] ) ) {
		$beds    = explode( ',', sanitize_text_field( $_REQUEST['beds'] ) );
		$joins[] = "
			INNER JOIN {$wpdb->postmeta} AS pm_beds
				ON pm_beds.post_id = u.ID
				AND pm_beds.meta_key = 'bedrooms'
		";
		$where[] = 'pm_beds.meta_value IN (' . implode( ',', array_fill( 0, count( $beds ), '%d' ) ) . ')';
		$params  = array_merge( $params, array_map( 'intval', $beds ) );
	}

	if ( ! empty( $_REQUEST['available'] ) && 'all' !== $_REQUEST['available'] ) {
		if ( 'off-plan' === $_REQUEST['listing_type'] ) {
			$year    = sanitize_text_field( wp_unslash( $_REQUEST['available'] ) );
			$date_to = date( 'Ymd', strtotime( $year . '1231' ) );
		} else {
			$year      = sanitize_text_field( wp_unslash( $_REQUEST['available'] ) );
			$date_from = date( 'Ymd', strtotime( $year . '0101' ) );
		}

		$joins[] = "
			INNER JOIN {$wpdb->postmeta} AS pm_delivery_date
				ON pm_delivery_date.post_id = CAST(pm_property.meta_value AS UNSIGNED)
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
		$joins[]  = "
			INNER JOIN {$wpdb->postmeta} AS pm_property_type
				ON pm_property_type.post_id = CAST(pm_property.meta_value AS UNSIGNED)
				AND pm_property_type.meta_key = 'property_type'
		";
		$where[]  = 'pm_property_type.meta_value = %s';
		$params[] = sanitize_text_field( wp_unslash( $_REQUEST['property_type'] ) );
	}

	if ( ! empty( $_REQUEST['location'] ) && 'all' !== $_REQUEST['location'] ) {
		$joins[]  = "
			INNER JOIN {$wpdb->term_relationships} AS tr_location
				ON tr_location.object_id = u.ID
			INNER JOIN {$wpdb->term_taxonomy} AS tt_location
				ON tt_location.term_taxonomy_id = tr_location.term_taxonomy_id
				AND tt_location.taxonomy = 'location'
			INNER JOIN {$wpdb->terms} AS t_location
				ON t_location.term_id = tt_location.term_id
		";
		$where[]  = 't_location.slug = %s';
		$params[] = sanitize_title( wp_unslash( $_REQUEST['location'] ) );
	}

	$filter_min_price = ! empty( $_REQUEST['min_price'] )
		? (int) sanitize_text_field( wp_unslash( $_REQUEST['min_price'] ) )
		: null;
	$filter_max_price = ! empty( $_REQUEST['max_price'] )
		? (int) sanitize_text_field( wp_unslash( $_REQUEST['max_price'] ) )
		: null;
	if ( null !== $filter_min_price || null !== $filter_max_price ) {
		$joins[] = "
			INNER JOIN {$wpdb->postmeta} AS pm_price
				ON pm_price.post_id = u.ID
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
		if ( $developer_filter > 0 ) {
			$joins[]  = "
				INNER JOIN {$wpdb->postmeta} AS pm_developer
					ON pm_developer.post_id = CAST(pm_property.meta_value AS UNSIGNED)
					AND pm_developer.meta_key = 'developer_rel'
			";
			$where[]  = 'CAST(pm_developer.meta_value AS UNSIGNED) = %d';
			$params[] = $developer_filter;
		}
	}

	$joins[] = "
			LEFT JOIN {$wpdb->postmeta} AS pm_boost_score
				ON pm_boost_score.post_id = u.ID
				AND pm_boost_score.meta_key = 'boost_score'
		";

	//add polylang support
	if ( function_exists( 'pll_current_language' ) ) {
		$lang    = pll_current_language();
		$joins[] = "INNER JOIN {$wpdb->term_relationships} AS pll_language_relation
    					ON pll_language_relation.object_id = u.ID";
		$joins[] = "INNER JOIN {$wpdb->term_taxonomy} AS pll_language_taxonomy
						ON pll_language_taxonomy.term_taxonomy_id =
						   pll_language_relation.term_taxonomy_id
						AND pll_language_taxonomy.taxonomy = 'language'";
		$joins[] = "INNER JOIN {$wpdb->terms} AS pll_language
						ON pll_language.term_id = pll_language_taxonomy.term_id
						AND pll_language.slug = '{$lang}'";
	}

	$join_sql  = implode( "\n", $joins );
	$where_sql = implode( "\nAND ", $where );
	$sql       = "
		SELECT 
			u.ID,
			COUNT(*) OVER() AS total_count
		FROM {$wpdb->posts} AS u
		{$join_sql}
		WHERE {$where_sql}
		ORDER BY pm_boost_score.meta_value DESC
		LIMIT %d OFFSET %d
	";
	$params[]  = (int) $limit;
	$params[]  = (int) $offset;

	$query       = $wpdb->prepare( $sql, $params );
	$units_posts = $wpdb->get_results( $query );

	if ( empty( $units_posts ) ) {
		$units = array(
			'items' => array(),
			'total' => 0,
		);
		set_transient( 'units_' . $filters_hash, $units, DAY_IN_SECONDS );

		return $units;
	}
	$total = ! empty( $units_posts[0]->total_count ) ? (int) $units_posts[0]->total_count : 0;

	$units_entities = array();
	foreach ( $units_posts as $post ) {
		unset( $post->total_count );
		$units_entities[] = new Unit( $post->ID );
	}

	$units = array(
		'items' => $units_entities,
		'total' => $total,
	);

	set_transient( 'units_' . $filters_hash, $units, DAY_IN_SECONDS );

	return $units;
}

/**
 * Every 1 hour cron for remove all expired boost units
 *
 * @return void
 */
function remove_expired_boost_units(): void {
	$boosted_units = get_posts(
		array(
			'post_type'      => 'unit',
			'posts_per_page' => - 1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => 'boost_score',
					'compare' => '>',
					'value'   => 0,
				),
			),
		)
	);

	if ( ! empty( $boosted_units ) ) {
		foreach ( $boosted_units as $boosted_unit ) {
			$unit = new Unit( $boosted_unit );
			$unit?->boost_expires();
		}
	}
}

/**
 * Call WP Cron
 */
add_action(
	'init',
	static function () {
		if ( ! wp_next_scheduled( 'remove_expired_boost_units' ) ) {
			wp_schedule_event( time(), 'hourly', 'remove_expired_boost_units' );
		}
	}
);

/**
 * Get count of units with handover date less than current date
 *
 * @param string $date_from
 * @param string $date_to
 *
 * @return int
 */
function get_count_of_units_by_date( $date_from = '2000-01-01', $date_to = '2050-01-01' ): int {
	global $wpdb;

	$sql = "
		SELECT COUNT(DISTINCT u.ID) AS total_count
		FROM {$wpdb->posts} AS u
		    LEFT JOIN {$wpdb->postmeta} AS pm_property 
		        ON pm_property.post_id = u.ID AND pm_property.meta_key = 'property'
		INNER JOIN {$wpdb->postmeta} AS pm_delivery_date
				ON pm_delivery_date.post_id = CAST(pm_property.meta_value AS UNSIGNED)
				AND pm_delivery_date.meta_key = 'delivery_date'
		WHERE u.post_type = %s
			  AND u.post_status = %s
			  AND pm_delivery_date.meta_value >= %s
			  AND pm_delivery_date.meta_value <= %s
	";

	$params = array(
		'unit',
		'publish',
		$date_from,
		$date_to,
	);

	$query       = $wpdb->prepare( $sql, $params );
	$units_count = $wpdb->get_results( $query );

	return $units_count[0]->total_count ? (int) $units_count[0]->total_count : 0;
}
