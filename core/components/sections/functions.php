<?php

/**
 * Get count of properties by locations
 *
 * @param $locations_ids
 *
 * @return array
 */
function get_projects_count_by_locations( $locations_ids ): array {
	$language = '';
	if ( function_exists( 'pll_current_language' ) ) {
		$language = (string) pll_current_language( 'slug' );

		if ( '' === $language && function_exists( 'pll_default_language' ) ) {
			$language = (string) pll_default_language( 'slug' );
		}
	}

	$where_in = $locations_ids ? implode( ',', array_map( 'intval', $locations_ids ) ) : '0';

	$cache_key      = md5( 'projects_count_by_locations_' . $language . $where_in );
	$projects_count = wp_cache_get( $cache_key, 'core' );
	if ( false !== $projects_count ) {
		return $projects_count;
	}

	global $wpdb;
	$results = $wpdb->get_results(
		"SELECT t_location.term_id, t_location.name, COUNT(DISTINCT p.ID) AS properties_count
				FROM {$wpdb->posts} p
				    INNER JOIN {$wpdb->term_relationships} AS tr_location
                    ON tr_location.object_id = p.ID
					INNER JOIN {$wpdb->term_taxonomy} AS tt_location
							ON tt_location.term_taxonomy_id = tr_location.term_taxonomy_id
								AND tt_location.taxonomy = 'location'
					INNER JOIN {$wpdb->terms} AS t_location
							ON t_location.term_id = tt_location.term_id
					
					INNER JOIN {$wpdb->postmeta} AS pm_units_count
							ON pm_units_count.post_id = p.ID
								AND pm_units_count.meta_key = 'units_count'
					
					INNER JOIN {$wpdb->term_relationships} AS pll_language_relation
							ON pll_language_relation.object_id = p.ID
					INNER JOIN {$wpdb->term_taxonomy} AS pll_language_taxonomy
							ON pll_language_taxonomy.term_taxonomy_id =
							   pll_language_relation.term_taxonomy_id
								AND pll_language_taxonomy.taxonomy = 'language'
					INNER JOIN {$wpdb->terms} AS pll_language
							ON pll_language.term_id = pll_language_taxonomy.term_id
								AND pll_language.slug = '{$language}'
				    
				WHERE t_location.term_id IN ({$where_in})
				  AND p.post_type = 'property'
				  AND p.post_status = 'publish'
				  AND CAST(pm_units_count.meta_value AS UNSIGNED) > 0
				GROUP BY t_location.term_id",
		ARRAY_A
	);

	$projects_count = array();
	foreach ( $results as $location ) {
		$projects_count[ $location['term_id'] ] = $location['properties_count'];
	}

	wp_cache_set( $cache_key, $projects_count, 'core', DAY_IN_SECONDS );

	return $projects_count;
}

/**
 * Get count of post type unit by number of bedrooms in meta key bedrooms (number)
 *
 * @param string $listing_type Listing type slug ('off-plan', 'secondary'). Empty or 'all' for any.
 *
 * @return array
 */
function get_units_count_by_bedrooms( string $listing_type = '' ): array {
	$current_language = '';
	if ( function_exists( 'pll_current_language' ) ) {
		$current_language = (string) pll_current_language( 'slug' );

		if ( '' === $current_language && function_exists( 'pll_default_language' ) ) {
			$current_language = (string) pll_default_language( 'slug' );
		}
	}

	$cache_key = md5( 'units_count_by_bedrooms_' . $listing_type . $current_language );

	$cached = wp_cache_get( $cache_key, 'core' );
	if ( false !== $cached ) {
		return $cached;
	}

	global $wpdb;

	$joins  = array(
		"INNER JOIN {$wpdb->postmeta} AS pm_beds
					ON pm_beds.post_id = u.ID
					AND pm_beds.meta_key = 'bedrooms'",
	);
	$where  = array( 'u.post_type = %s', 'u.post_status = %s' );
	$params = array( 'unit', 'publish' );

	if ( ! empty( $listing_type ) && 'all' !== $listing_type ) {
		$joins[]  = "
					INNER JOIN {$wpdb->postmeta} AS pm_listing_type
						ON pm_listing_type.post_id = u.ID
						AND pm_listing_type.meta_key = 'listing_type'
				";
		$where[]  = 'pm_listing_type.meta_value = %s';
		$params[] = sanitize_text_field( wp_unslash( $listing_type ) );
	}

	// Add polylang support.
	if ( '' !== $current_language ) {
		$joins[]  = "INNER JOIN {$wpdb->term_relationships} AS pll_language_relation
		    					ON pll_language_relation.object_id = u.ID";
		$joins[]  = "INNER JOIN {$wpdb->term_taxonomy} AS pll_language_taxonomy
								ON pll_language_taxonomy.term_taxonomy_id =
								   pll_language_relation.term_taxonomy_id
								AND pll_language_taxonomy.taxonomy = 'language'";
		$joins[]  = "INNER JOIN {$wpdb->terms} AS pll_language
								ON pll_language.term_id = pll_language_taxonomy.term_id";
		$where[]  = 'pll_language.slug = %s';
		$params[] = $current_language;
	}

	$join_sql  = implode( "\n", $joins );
	$where_sql = implode( "\nAND ", $where );
	$sql       = "
				SELECT
					pm_beds.meta_value AS bedrooms,
					COUNT(DISTINCT u.ID) AS count
				FROM {$wpdb->posts} AS u
				{$join_sql}
				WHERE {$where_sql}
				GROUP BY pm_beds.meta_value
				ORDER BY CAST(pm_beds.meta_value AS UNSIGNED)
			";

	$results     = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
	$units_count = array();
	foreach ( (array) $results as $row ) {
		$units_count[ (int) $row['bedrooms'] ] = (int) $row['count'];
	}

	wp_cache_set( $cache_key, $units_count, 'core', DAY_IN_SECONDS );

	return $units_count;
}
