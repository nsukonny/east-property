<?php
/**
 * Al functionality for units. Search, filters, etc.
 */

use Entities\Estate_User;
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
function get_units( $listing_type = '', int $limit = 25, $args = array() ): array {
	$current_language = core_get_current_language();
	$current_page     = pagination_get_current_page() ?? 1;

	$request_params = implode(
		'',
		array_map(
			fn( $key ) => $_REQUEST[ $key ] ?? '',
			array(
				'location',
				'available',
				'developer',
				'area',
				'beds',
				'property_type',
				'min_price',
				'max_price',
				'sort',
			)
		)
	);

	if ( ! empty( $_REQUEST['listing_type'] ) ) {
		$listing_type = sanitize_text_field( wp_unslash( $_REQUEST['listing_type'] ) );
	}
	$request_params .= $listing_type ?? '';
	$cache_key      = md5( 'units_' . $current_language . '_' . (string) $limit . (string) $current_page . $request_params );

	static $memo = array();
	if ( isset( $memo[ $cache_key ] ) ) {
		return $memo[ $cache_key ];
	}

	$units = wp_cache_get( $cache_key, 'units' );
	if ( false !== $units ) {
		$units['items']     = core_mark_favorite_units( $units['items'] ?? array() );
		$memo[ $cache_key ] = $units;

		return $units;
	}

	$units = core_query_units( $listing_type, $limit, $current_page, $current_language );

	$property_ids = wp_list_pluck( $units['items'], 'property_id' );
	$unit_ids     = wp_list_pluck( $units['items'], 'ID' );
	_prime_post_caches( array_unique( array_merge( $property_ids, $unit_ids ) ), false, false );

	foreach ( $units['items'] as $item_key => $item ) {
		$units['items'][ $item_key ]['property_url'] = get_permalink( $item['property_id'] );
		$units['items'][ $item_key ]['url']          = get_permalink( $item['ID'] );
	}

	if ( ! empty( $args['galleries'] ) ) {
		$galleries = Unit::get_galleries( array_column( $units['items'], 'ID' ) );

		foreach ( $units['items'] as $key => $item ) {
			$units['items'][ $key ]['gallery'] = $galleries[ $item['ID'] ] ?? array();
		}
	}

	wp_cache_set( $cache_key, $units, 'units', DAY_IN_SECONDS );

	$units['items']     = core_mark_favorite_units( $units['items'] );
	$memo[ $cache_key ] = $units;

	return $units;
}

/**
 * Mark the rows the current user keeps in favorites
 *
 * Stays outside the cached payload: the listing is shared by every visitor,
 * while the flag belongs to one of them.
 *
 * @return array
 */
function core_mark_favorite_units( array $items ): array {
	$favorites = is_user_logged_in()
		? get_user_meta( get_current_user_id(), 'favorite_units', true )
		: array();
	$favorites = is_array( $favorites ) ? array_map( 'intval', $favorites ) : array();

	foreach ( $items as $key => $item ) {
		$items[ $key ]['is_favorite'] = in_array( (int) ( $item['ID'] ?? 0 ), $favorites, true );
	}

	return $items;
}

/**
 * The units listing for the current filters, uncached.
 *
 * Split out of get_units() so the cache can rebuild it after the response; the
 * body is unchanged.
 *
 * @return array
 */
function core_query_units( $listing_type, $limit, $current_page, $current_language ): array {
	global $wpdb;

	if ( 0 > $limit ) {
		$limit = PROPERTIES_PER_PAGE;
	}
	$offset = ( $current_page - 1 ) * $limit;

	//TODO for more optimization we can split it by two queries, one for just ids and second for loading all data for this 20

	$joins  = array( "LEFT JOIN {$wpdb->postmeta} AS pm_property ON pm_property.post_id = u.ID AND pm_property.meta_key = 'property'" );
	$where  = array( 'u.post_type = %s', 'u.post_status = %s' );
	$params = array( 'unit', 'publish' );

	$joins[] = "LEFT JOIN {$wpdb->posts} AS p_property ON p_property.ID = CAST(pm_property.meta_value AS UNSIGNED)";

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

	$joins[] = "
			LEFT JOIN {$wpdb->postmeta} AS pm_beds
				ON pm_beds.post_id = u.ID
				AND pm_beds.meta_key = 'bedrooms'
			LEFT JOIN {$wpdb->postmeta} AS pm_baths
				ON pm_baths.post_id = u.ID
				AND pm_baths.meta_key = 'bathrooms'
			LEFT JOIN {$wpdb->postmeta} AS pm_area
				ON pm_area.post_id = u.ID
				AND pm_area.meta_key = 'area_size'
		";

	if ( ! empty( $_REQUEST['area'] ) && ( 'all' !== $_REQUEST['area'] ) ) {
		$where[]  = 'pm_area.meta_value <= %d';
		$params[] = (int) sanitize_text_field( $_REQUEST['area'] );
	}

	if ( ! empty( $_REQUEST['beds'] ) ) {
		$beds    = explode( ',', sanitize_text_field( $_REQUEST['beds'] ) );
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
	$joins[]          = "
			LEFT JOIN {$wpdb->postmeta} AS pm_price
				ON pm_price.post_id = u.ID
				AND pm_price.meta_key = 'price'
			LEFT JOIN {$wpdb->postmeta} AS pm_original_price
				ON pm_original_price.post_id = u.ID
				AND pm_original_price.meta_key = 'original_price'
			LEFT JOIN {$wpdb->postmeta} AS pm_discount
				ON pm_discount.post_id = u.ID
				AND pm_discount.meta_key = 'discount'
		";
	if ( null !== $filter_min_price ) {
		$where[]  = 'CAST(pm_price.meta_value AS UNSIGNED) >= %d';
		$params[] = $filter_min_price;
	}
	if ( null !== $filter_max_price ) {
		$where[]  = 'CAST(pm_price.meta_value AS UNSIGNED) <= %d';
		$params[] = $filter_max_price;
	}

	$joins[] = "
				LEFT JOIN {$wpdb->postmeta} AS pm_developer
					ON pm_developer.post_id = CAST(pm_property.meta_value AS UNSIGNED)
					AND pm_developer.meta_key = 'developer_rel'
				LEFT JOIN {$wpdb->posts} AS p_developer
					ON p_developer.ID = CAST(pm_developer.meta_value AS UNSIGNED)
			";
	if ( ! empty( $_REQUEST['developer'] ) && 'all' !== $_REQUEST['developer'] ) {
		$developer_filter = (int) sanitize_text_field( wp_unslash( $_REQUEST['developer'] ) );
		if ( $developer_filter > 0 ) {
			$where[]  = 'CAST(pm_developer.meta_value AS UNSIGNED) = %d';
			$params[] = $developer_filter;
		}
	}

	$joins[] = "
			LEFT JOIN {$wpdb->users} AS u_broker
				ON u_broker.ID = u.post_author
		";
	$joins[] = "
			LEFT JOIN {$wpdb->postmeta} AS pm_boost_score
				ON pm_boost_score.post_id = u.ID
				AND pm_boost_score.meta_key = 'boost_score'
		";

	$joins[] = "
			LEFT JOIN {$wpdb->postmeta} AS pm_label_delivery
				ON pm_label_delivery.post_id = p_property.ID
				AND pm_label_delivery.meta_key = 'delivery_date'
			LEFT JOIN {$wpdb->postmeta} AS pm_label_popular
				ON pm_label_popular.post_id = p_property.ID
				AND pm_label_popular.meta_key = 'is_popular'
			LEFT JOIN {$wpdb->postmeta} AS pm_label_premium
				ON pm_label_premium.post_id = p_property.ID
				AND pm_label_premium.meta_key = 'is_premium_developer'
			LEFT JOIN {$wpdb->postmeta} AS pm_wait_user_actions
				ON pm_wait_user_actions.post_id = u.ID
				AND pm_wait_user_actions.meta_key = 'is_wait_user_actions'
			LEFT JOIN {$wpdb->users} AS broker_user ON broker_user.ID = u_broker.ID
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
			u.post_title AS title,
			u.post_status,
			u.post_author AS author_id,
			
			broker_user.display_name AS broker_name,
			
			p_property.ID AS property_id,
			p_property.post_title AS property_name,
			pm_label_delivery.meta_value AS delivery_date,
			pm_label_popular.meta_value AS is_popular,
			pm_label_premium.meta_value AS is_premium_developer,
			
			p_developer.ID AS developer_id,
			p_developer.post_title AS developer_name,
			
			u_broker.ID AS broker_id,
			u_broker.display_name AS broker_name,
			
			pm_beds.meta_value AS bedrooms,
			pm_baths.meta_value AS bathrooms,
			pm_area.meta_value AS area_size,
			pm_price.meta_value AS price,
			pm_original_price.meta_value AS original_price,
			pm_discount.meta_value AS discount,
			pm_boost_score.meta_value AS boost_score,
			pm_wait_user_actions.meta_value AS is_wait_user_actions,
			
			COUNT(*) OVER() AS total_count
		FROM {$wpdb->posts} AS u
		{$join_sql}
		WHERE {$where_sql}
		ORDER BY pm_boost_score.meta_value DESC
		LIMIT %d OFFSET %d
	";
	$params[]  = (int) $limit;
	$params[]  = (int) $offset;

	$query = $wpdb->prepare( $sql, $params );

	$units_posts = $wpdb->get_results( $query, ARRAY_A );

	return array(
		'items' => $units_posts ?: array(),
		'total' => ! empty( $units_posts[0]['total_count'] ) ? (int) $units_posts[0]['total_count'] : 0,
	);
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
