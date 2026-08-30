<?php

/**
 * Get count of properties by locations
 *
 * @param $locations_ids
 *
 * @return array
 */
function get_units_count_by_locations( $locations_ids ): array {
	global $wpdb;

	$units_count = get_transient( 'units_count_by_locations' ) ?? array();
	if ( ! empty( $units_count ) ) {
		return $units_count;
	}

	$where_in = $locations_ids ? implode( ',', array_map( 'intval', $locations_ids ) ) : '0';
	$query    = $wpdb->prepare( "SELECT t.term_id, t.name, COUNT(p.ID) as posts_count
	 FROM {$wpdb->posts} p
	 INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
     INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
     INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
		WHERE t.term_id IN (" . $where_in . ")
		  AND p.post_type = 'unit'
		  AND p.post_status = 'publish'
		  GROUP BY t.term_id, t.name;
     " );

	$results = $wpdb->get_results( $query, ARRAY_A );

	$units_count = array();
	foreach ( $results as $location ) {
		$units_count[ $location['term_id'] ] = $location['posts_count'];
	}

	set_transient( 'units_count_by_locations', $units_count, HOUR_IN_SECONDS );

	return $units_count;
}

/**
 * Get count of post type unit by number of bedrooms in meta key bedrooms (number)
 *
 * @param string $listing_type Listing type slug ('off-plan', 'secondary'). Empty or 'all' for any.
 *
 * @return array
 */
function get_units_count_by_bedrooms( string $listing_type = '' ): array {
	global $wpdb;

	$current_language = '';
	if ( function_exists( 'pll_current_language' ) ) {
		$current_language = (string) pll_current_language( 'slug' );

		if ( '' === $current_language && function_exists( 'pll_default_language' ) ) {
			$current_language = (string) pll_default_language( 'slug' );
		}
	}

	$transient_key = 'units_count_by_bedrooms_' . md5( $listing_type . '|' . $current_language );
	$units_count   = ! IS_DEV ? get_transient( $transient_key ) : false;
	if ( false !== $units_count ) {
		return $units_count;
	}

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

	set_transient( $transient_key, $units_count, HOUR_IN_SECONDS );

	return $units_count;
}
