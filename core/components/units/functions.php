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
 * Get a listing of units by filters
 *
 * @param string $listing_type
 * @param int $limit
 * @param array $args
 *
 * @return array
 */
function get_units( $listing_type = '', int $limit = 25, $args = array() ): array {
	$args['current_language'] = core_get_current_language();
	$args['current_page']     = pagination_get_current_page() ?? 1;
	$args['limit']            = $limit;
	$args['listing_type']     = $listing_type;

	$cache_key = core_generate_cache_key(
		'units',
		'units_listings',
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
			'listing_type',
		),
		$args
	);

	static $memo = array();
	if ( isset( $memo[ $cache_key ] ) ) {
		return $memo[ $cache_key ];
	}

	$is_cached = empty( $args['no_cache'] );
	$units     = $is_cached ? wp_cache_get( $cache_key, 'units' ) : false;
	if ( false !== $units ) {
		$memo[ $cache_key ] = $units;

		return $units;
	}

	$units = core_query_units( $listing_type, $args );

	$property_ids = wp_list_pluck( $units['items'], 'property_id' );
	$unit_ids     = wp_list_pluck( $units['items'], 'ID' );
	$merged_ids   = array_unique( array_merge( $property_ids, $unit_ids ) );
	$merged_ids   = array_values( array_filter( $merged_ids, fn( $value ) => $value !== '' && $value !== null ) );

	_prime_post_caches( $merged_ids, false, false );

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

	if ( $is_cached ) {
		wp_cache_set( $cache_key, $units, 'units', DAY_IN_SECONDS );
	}

	$memo[ $cache_key ] = $units;

	return $units;
}

/**
 * The units listing for the current filters, uncached.
 *
 * Split out of get_units() so the cache can rebuild it after the response; the
 * body is unchanged.
 *
 * @return array
 */
function core_query_units( $listing_type, $args ): array {
	global $wpdb;

	//TODO for more optimization we can split it by two queries, one for just ids and second for loading all data for this 20

	$joins  = array( "LEFT JOIN {$wpdb->postmeta} AS pm_property ON pm_property.post_id = u.ID AND pm_property.meta_key = 'property'" );
	$where  = array( 'u.post_type = %s' );
	$params = array( 'unit' );

	if ( empty( $args['draft'] ) ) {
		$where[]  = 'u.post_status = %s';
		$params[] = 'publish';
	} else {
		$where[]  = 'u.post_status <> %s';
		$params[] = 'archived';
	}

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

	if ( ! empty( $args['unit_type'] ) ) {
		$joins[]  = "
			INNER JOIN {$wpdb->postmeta} AS pm_unit_type
				ON pm_unit_type.post_id = u.ID
				AND pm_unit_type.meta_key = 'unit_type'
		";
		$where[]  = 'pm_unit_type.meta_value = %s';
		$params[] = (string) $args['unit_type'];
	}

	$status = (string) ( $args['status'] ?? '' );

	if ( 'waiting' === $status ) {
		$where[]  = 'u.post_status = %s';
		$params[] = 'draft';
	}

	if ( 'promoted' === $status ) {
		$joins[] = "
			INNER JOIN {$wpdb->postmeta} AS pm_status
				ON pm_status.post_id = u.ID
				AND pm_status.meta_key = 'boost_score'
		";
		$where[] = 'CAST(pm_status.meta_value AS SIGNED) > 0';
	}

	if ( 'error' === $status ) {
		$joins[] = "
			INNER JOIN {$wpdb->postmeta} AS pm_status
				ON pm_status.post_id = u.ID
				AND pm_status.meta_key = 'is_wait_user_actions'
		";
		$where[] = 'CAST(pm_status.meta_value AS SIGNED) = 1';
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

	$beds_filter = $args['beds'] ?? $_REQUEST['beds'] ?? '';
	$beds_filter = is_scalar( $beds_filter ) ? (string) $beds_filter : '';
	if ( '' !== $beds_filter ) {
		$beds = array_values(
			array_unique(
				array_map( 'intval', explode( ',', sanitize_text_field( wp_unslash( $beds_filter ) ) ) )
			)
		);

		$where[] = 'pm_beds.meta_value IN (' . implode( ',', array_fill( 0, count( $beds ), '%d' ) ) . ')';
		$params  = array_merge( $params, $beds );
	}

	if ( ! empty( $_REQUEST['available'] ) && 'all' !== $_REQUEST['available'] ) {
		$available = sanitize_text_field( wp_unslash( $_REQUEST['available'] ) );

		if ( 'ready' === $available ) {
			$date_to = date( 'Ymd' );
		} elseif ( 'in_construction' === $available ) {
			$date_from = date( 'Ymd' );
		} elseif ( 'off-plan' === ( $_REQUEST['listing_type'] ?? '' ) ) {
			$date_to = date( 'Ymd', strtotime( $available . '1231' ) );
		} else {
			$date_from = date( 'Ymd', strtotime( $available . '0101' ) );
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

	$location_filter = $args['location'] ?? $_REQUEST['location'] ?? '';
	$location_filter = is_scalar( $location_filter ) ? (string) $location_filter : '';
	if ( '' !== $location_filter && 'all' !== $location_filter ) {
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
		$params[] = sanitize_title( wp_unslash( $location_filter ) );
	}

	$filter_min_price = is_numeric( $_REQUEST['min_price'] ?? null )
		? (int) sanitize_text_field( wp_unslash( $_REQUEST['min_price'] ) )
		: null;
	$filter_max_price = is_numeric( $_REQUEST['max_price'] ?? null )
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
			$developer_ids = array( $developer_filter );

			if ( function_exists( 'pll_get_post_translations' ) ) {
				foreach ( pll_get_post_translations( $developer_filter ) as $translation ) {
					$developer_ids[] = (int) $translation;
				}
			}

			$developer_ids = array_values( array_unique( array_filter( $developer_ids ) ) );
			$where[]       = 'CAST(pm_developer.meta_value AS UNSIGNED) IN ('
			                 . implode( ',', array_fill( 0, count( $developer_ids ), '%d' ) ) . ')';
			$params        = array_merge( $params, $developer_ids );
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
		$joins[] = "INNER JOIN {$wpdb->term_relationships} AS pll_language_relation
    					ON pll_language_relation.object_id = u.ID";
		$joins[] = "INNER JOIN {$wpdb->term_taxonomy} AS pll_language_taxonomy
						ON pll_language_taxonomy.term_taxonomy_id =
						   pll_language_relation.term_taxonomy_id
						AND pll_language_taxonomy.taxonomy = 'language'";
		$joins[] = "INNER JOIN {$wpdb->terms} AS pll_language
						ON pll_language.term_id = pll_language_taxonomy.term_id
						AND pll_language.slug = '{$args['current_language']}'";
	}

	if ( ! empty( $args['property_id'] ) ) {
		$where[]  = 'CAST(pm_property.meta_value AS UNSIGNED) = %d';
		$params[] = (int) $args['property_id'];
	}

	if ( ! empty( $args['author_id'] ) ) {
		$author_id = (int) $args['author_id'];
		if ( $author_id > 0 ) {
			$where[]  = 'u.post_author = %d';
			$params[] = $author_id;
		}
	}

	if ( ! empty( $args['unit_ids'] ) ) {
		$unit_ids = array_values( array_unique( array_filter( array_map( 'intval', (array) $args['unit_ids'] ) ) ) );

		if ( empty( $unit_ids ) ) {
			$where[] = '1 = 0';
		} else {
			$where[] = 'u.ID IN (' . implode( ',', array_fill( 0, count( $unit_ids ), '%d' ) ) . ')';
			$params  = array_merge( $params, $unit_ids );
		}
	}

	if ( ! empty( $args['search'] ) ) {
		$search   = 'u.post_title LIKE %s OR p_property.post_title LIKE %s';
		$like     = '%' . $wpdb->esc_like( (string) $args['search'] ) . '%';
		$params[] = $like;
		$params[] = $like;

		if ( ctype_digit( (string) $args['search'] ) ) {
			$search   .= ' OR u.ID = %d';
			$params[] = (int) $args['search'];
		}

		$where[] = '( ' . $search . ' )';
	}

	$orders    = array(
		'price_desc'   => 'COALESCE(CAST(pm_price.meta_value AS UNSIGNED), 0) = 0, CAST(pm_price.meta_value AS UNSIGNED) DESC, u.ID DESC',
		'price_asc'    => 'COALESCE(CAST(pm_price.meta_value AS UNSIGNED), 0) = 0, CAST(pm_price.meta_value AS UNSIGNED) ASC, u.ID DESC',
		'newest'       => 'u.post_date DESC, u.ID DESC',
		'oldest'       => 'u.post_date ASC, u.ID ASC',
		'handover_asc' => "COALESCE(pm_label_delivery.meta_value, '') = '', pm_label_delivery.meta_value ASC, u.ID DESC",
	);
	$order_sql = $orders[ core_get_listing_sort( $args['sort'] ?? null ) ] ?? 'pm_boost_score.meta_value DESC';
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
		ORDER BY {$order_sql}
	";

	if ( 0 < $args['limit'] ) {
		$sql .= " LIMIT %d OFFSET %d";

		$offset   = ( $args['current_page'] - 1 ) * $args['limit'];
		$params[] = (int) $args['limit'];
		$params[] = (int) $offset;
	}

	$query       = $wpdb->prepare( $sql, $params );
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
 * Count of published units whose project is handed over inside the dates
 *
 * @param string $date_from Lower bound of the delivery date, Y-m-d.
 * @param string $date_to Upper bound of the delivery date, Y-m-d.
 *
 * @return int
 */
function get_count_of_units_by_date( $date_from = '2000-01-01', $date_to = '2050-01-01' ): int {
	$current_language = core_get_current_language();
	$cache_key        = core_generate_cache_key(
		'units_count_by_date',
		'units_listings',
		array(),
		array(
			'current_language' => $current_language,
			'date_from'        => $date_from,
			'date_to'          => $date_to,
		)
	);

	static $memo = array();
	if ( isset( $memo[ $cache_key ] ) ) {
		return $memo[ $cache_key ];
	}

	$count = wp_cache_get( $cache_key, 'units' );
	if ( false === $count ) {
		$count = core_query_count_of_units_by_date( $date_from, $date_to, $current_language );

		wp_cache_set( $cache_key, $count, 'units', DAY_IN_SECONDS );
	}

	$memo[ $cache_key ] = (int) $count;

	return $memo[ $cache_key ];
}

/**
 * The count itself, uncached
 *
 * @param string $date_from Lower bound of the delivery date, Y-m-d.
 * @param string $date_to Upper bound of the delivery date, Y-m-d.
 * @param string $language Polylang slug; empty counts every language.
 *
 * @return int
 */
function core_query_count_of_units_by_date( string $date_from, string $date_to, string $language = '' ): int {
	global $wpdb;

	$params = array( 'unit', 'publish', $date_from, $date_to );

	/*
	 * The language condition sits in WHERE rather than in ON: from the ON clause
	 * its placeholder would reach prepare() before the others and the parameters
	 * would go out of order.
	 */
	$language_join  = '';
	$language_where = '';
	if ( '' !== $language ) {
		$language_join  = "INNER JOIN {$wpdb->term_relationships} AS pll_language_relation
				ON pll_language_relation.object_id = u.ID
			INNER JOIN {$wpdb->term_taxonomy} AS pll_language_taxonomy
				ON pll_language_taxonomy.term_taxonomy_id = pll_language_relation.term_taxonomy_id
				AND pll_language_taxonomy.taxonomy = 'language'
			INNER JOIN {$wpdb->terms} AS pll_language
				ON pll_language.term_id = pll_language_taxonomy.term_id";
		$language_where = 'AND pll_language.slug = %s';
		$params[]       = $language;
	}

	$sql = "
		SELECT COUNT(DISTINCT u.ID)
		FROM {$wpdb->posts} AS u
		LEFT JOIN {$wpdb->postmeta} AS pm_property
			ON pm_property.post_id = u.ID
			AND pm_property.meta_key = 'property'
		INNER JOIN {$wpdb->postmeta} AS pm_delivery_date
			ON pm_delivery_date.post_id = CAST(pm_property.meta_value AS UNSIGNED)
			AND pm_delivery_date.meta_key = 'delivery_date'
		{$language_join}
		WHERE u.post_type = %s
		  AND u.post_status = %s
		  AND pm_delivery_date.meta_value >= %s
		  AND pm_delivery_date.meta_value <= %s
		  {$language_where}
	";

	return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
}
