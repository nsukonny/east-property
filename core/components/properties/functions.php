<?php
/**
 * Al functionality for properties. Search, filters, etc.
 */

/**
 * Get properties list by filters if they is set
 *
 * @param int $limit
 * @param bool $skip_filters
 *
 * @return array
 */
function get_properties( int $limit = - 1, bool $skip_filters = false, array $args = array() ): array {
	$current_language = core_get_current_language();
	$current_page     = pagination_get_current_page() ?? 1;
	$cache_key        = 'properties_' . $current_language . '_'
	                    . md5( (string) $limit . (string) $skip_filters . (string) $current_page . wp_json_encode( $args ) );

	static $memo = array();
	if ( isset( $memo[ $cache_key ] ) ) {
		return $memo[ $cache_key ];
	}

	$properties = wp_cache_get( $cache_key, 'properties' );
	if ( false === $properties ) {
		$properties = core_query_properties( $limit, $skip_filters, $current_page, $current_language, $args );
	}

	if ( ! empty( $args['specifications'] ) ) {
		$specifications = \Entities\Property::get_specifications( array_column( $properties['items'], 'ID' ) );

		foreach ( $properties['items'] as $key => $item ) {
			$properties['items'][ $key ]['specifications'] = $specifications[ $item['ID'] ] ?? array();
		}
	}

	if ( ! empty( $args['galleries'] ) ) {
		$galleries = \Entities\Property::get_galleries( array_column( $properties['items'], 'ID' ) );

		foreach ( $properties['items'] as $key => $item ) {
			$properties['items'][ $key ]['gallery'] = $galleries[ $item['ID'] ] ?? array();
		}
	}

	wp_cache_set( $cache_key, $properties, 'properties', DAY_IN_SECONDS );
	$memo[ $cache_key ] = $properties;

	return $properties;
}

/**
 * The properties listing for the current filters, uncached.
 *
 * Split out of get_properties() so the cache can rebuild it after the response;
 * the body is unchanged.
 *
 * @param int $limit
 * @param bool $skip_filters
 * @param int $current_page
 * @param string $current_language
 * @param array $args
 *
 * @return array
 */
function core_query_properties(
	int $limit,
	bool $skip_filters,
	int $current_page,
	string $current_language,
	array $args = array()
): array {
	global $wpdb;

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

	$joins[] = "
			LEFT JOIN {$wpdb->term_relationships} AS tr_location
				ON tr_location.object_id = p.ID
			LEFT JOIN {$wpdb->term_taxonomy} AS tt_location
				ON tt_location.term_taxonomy_id = tr_location.term_taxonomy_id
				AND tt_location.taxonomy = 'location'
			LEFT JOIN {$wpdb->terms} AS t_location
				ON t_location.term_id = tt_location.term_id
		";
	if ( ! empty( $_REQUEST['location'] ) && 'all' !== $_REQUEST['location'] ) {
		$location = sanitize_title( wp_unslash( $_REQUEST['location'] ) );
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

	$joins[] = "
		LEFT JOIN {$wpdb->postmeta} AS pm_label_delivery
			ON pm_label_delivery.post_id = p.ID
			AND pm_label_delivery.meta_key = 'delivery_date'
		LEFT JOIN {$wpdb->postmeta} AS pm_label_popular
			ON pm_label_popular.post_id = p.ID
			AND pm_label_popular.meta_key = 'is_popular'
		LEFT JOIN {$wpdb->postmeta} AS pm_label_premium
			ON pm_label_premium.post_id = p.ID
			AND pm_label_premium.meta_key = 'is_premium_developer'
		LEFT JOIN {$wpdb->postmeta} AS pm_latitude
			ON pm_latitude.post_id = p.ID
			AND pm_latitude.meta_key = 'latitude'
		LEFT JOIN {$wpdb->postmeta} AS pm_longitude
			ON pm_longitude.post_id = p.ID
			AND pm_longitude.meta_key = 'longitude'
	";

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

	if ( ! empty( $args['author_id'] ) ) {
		$author_id = (int) $args['author_id'];
		if ( $author_id > 0 ) {
			$where[]  = 'p.post_author = %d';
			$params[] = $author_id;
		}
	}

	$join_sql  = implode( "\n", $joins );
	$where_sql = implode( "\nAND ", $where );
	$sql       = "
		SELECT 
			p.ID,
			p.post_title,
			p.post_author,
			t_location.term_id AS location_term_id,
			t_location.name AS location_name,
			pm_label_delivery.meta_value AS delivery_date,
			pm_label_popular.meta_value AS is_popular,
			pm_label_premium.meta_value AS is_premium_developer,
			pm_latitude.meta_value AS latitude,
			pm_longitude.meta_value AS longitude,
			pm_units_count.meta_value AS units_count,
			COUNT(*) OVER() AS total_count
		FROM {$wpdb->posts} AS p
		{$join_sql}
		WHERE {$where_sql}
		GROUP BY p.ID
		ORDER BY p.post_title ASC
		LIMIT %d OFFSET %d
	";
	$params[]  = $limit;
	$params[]  = $offset;

	$query            = $wpdb->prepare( $sql, $params );
	$properties_posts = $wpdb->get_results( $query, ARRAY_A );

	_prime_post_caches(
		array_column( $properties_posts, 'ID' ),
		false,
		false
	);

	foreach ( $properties_posts as $key => $row ) {
		$properties_posts[ $key ]['labels'] = core_property_labels( $row );
		$properties_posts[ $key ]['url']    = get_the_permalink( $row['ID'] );
	}

	return array(
		'items' => $properties_posts,
		'total' => ! empty( $properties_posts[0]['total_count'] ) ? (int) $properties_posts[0]['total_count'] : 0,
	);
}

/**
 * Labels of a project, built from the values core_query_properties() selects.
 *
 * Mirrors Property::get_labels(); the dates and the wording stay in PHP because
 * date_i18n() and the text domain have no SQL equivalent.
 *
 * @param array $row Row with delivery_date, is_popular and is_premium_developer.
 *
 * @return array
 */
function core_property_labels( array $row ): array {
	$labels = array();

	$delivery_date = $row['delivery_date'] ?? '';
	if ( ! empty( $delivery_date ) ) {
		if ( strtotime( $delivery_date ) < time() ) {
			$labels[] = array(
				'name'  => __( 'Ready', 'east-property' ),
				'color' => 'black',
			);
		} else {
			$labels[] = array(
				'name'  => __( 'Handover:', 'east-property' ) . ' ' . date_i18n( get_option( 'date_format' ),
						strtotime( $delivery_date ) ),
				'color' => 'orange',
			);
		}
	}

	if ( ! empty( $row['is_popular'] ) ) {
		$labels[] = array(
			'name'  => 'Popular',
			'color' => 'red',
		);
	}

	if ( ! empty( $row['is_premium_developer'] ) ) {
		$labels[] = array(
			'name'  => 'Premium Developer',
			'color' => 'black',
		);
	}

	return $labels;
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

	// Polylang держит язык в таксономии `language`, а функция работает сырым
	// SQL — плагин её не фильтрует, и язык приходится добавлять руками. Без
	// этого счётчик складывал языки: у проекта #921 значилось 28 юнитов, хотя
	// это 27 английских плюс один русский.
	$language = function_exists( 'pll_current_language' )
		? (string) pll_current_language( 'slug' )
		: '';

	// Ключ кэша обязан включать язык. Иначе первый прогретый язык отдавался бы
	// второму, и подмена была бы молчаливой — тот же приём уже применён для
	// units_count_by_bedrooms_ в core_flush_listing_caches().
	$cache_key = 'properties_by_count_of_units' . ( '' === $language ? '' : '_' . $language );

	$results = core_cache_remember(
		$cache_key,
		static function () use ( $language ) {
			global $wpdb;

			$language_join = '';
			$params        = array();

			if ( '' !== $language ) {
				$language_join = "
				JOIN {$wpdb->term_relationships} tr ON tr.object_id = u.ID
				JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'language'
				JOIN {$wpdb->terms} t ON t.term_id = tt.term_id AND t.slug = %s
			";

				$params[] = $language;
			}

			// Соединения внутренние, а не LEFT: считаем только существующие юниты
			// нужного языка, и HAVING остаётся как страховка на случай, если кто-то
			// вернёт LEFT обратно.
			$query = "
			SELECT p.ID, COUNT(u.ID) as units_count
			FROM {$wpdb->posts} p
				JOIN {$wpdb->postmeta} pm ON pm.meta_value = p.ID AND pm.meta_key = 'property'
				JOIN {$wpdb->posts} u ON u.ID = pm.post_id AND u.post_type = 'unit' AND u.post_status = 'publish'
				{$language_join}
			WHERE p.post_type = 'property' AND p.post_status = 'publish'
			GROUP BY p.ID
			HAVING units_count > 0
			ORDER BY units_count DESC
		";

			if ( ! empty( $params ) ) {
				$query = $wpdb->prepare( $query, $params );
			}

			$results = $wpdb->get_results( $query, ARRAY_A );

			return $results;
		},
		HOUR_IN_SECONDS,
		array( 'keep_empty' => true )
	);

	if ( empty( $results ) ) {
		return array();
	}

	// Юнит ссылается на проект того языка, на котором его заводили: измерено —
	// 64 русских юнита указывают на английский проект и только один на русский.
	// Поэтому после подсчёта проект переводится на текущий язык, иначе русская
	// главная выводила бы английские карточки с русскими счётчиками.
	$counts = array();

	_prime_post_caches( array_map( 'intval', wp_list_pluck( $results, 'ID' ) ), true, false );

	foreach ( $results as $result ) {
		$property_id = (int) $result['ID'];

		if ( '' !== $language && function_exists( 'pll_get_post' ) ) {
			$translated = (int) pll_get_post( $property_id, $language );

			// Перевода нет — лучше пропустить проект, чем показать его на
			// чужом языке.
			if ( 0 === $translated ) {
				continue;
			}

			$property_id = $translated;
		}

		// Складываем, а не перезаписываем: если часть юнитов указывает на
		// английский проект, а часть на его русский перевод, после перевода
		// обе группы сходятся в одну запись.
		$counts[ $property_id ] = ( $counts[ $property_id ] ?? 0 ) + (int) $result['units_count'];
	}

	// Слияние могло нарушить порядок, а функция обещает сортировку по числу
	// юнитов.
	arsort( $counts );

	_prime_post_caches( array_keys( $counts ), true, false );

	$properties = array();
	foreach ( $counts as $property_id => $units_count ) {
		$property = new \Entities\Property( $property_id );
		$property->set_units_count( $units_count );
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
