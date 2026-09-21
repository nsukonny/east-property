<?php
/**
 * Al functionality for agencies
 */

/**
 * Agencies for the account dropdowns
 *
 * @param int $limit Rows to return, -1 for all of them.
 *
 * @return array
 */
function get_agencies( int $limit = - 1 ): array {
	$current_language = core_get_current_language();
	$cache_key        = md5( 'agencies_' . $current_language . '_' . (string) $limit );

	static $memo = array();
	if ( isset( $memo[ $cache_key ] ) ) {
		return $memo[ $cache_key ];
	}

	$agencies = wp_cache_get( $cache_key, 'agencies' );
	if ( false === $agencies ) {
		$agencies = core_query_agencies( $limit, $current_language );

		wp_cache_set( $cache_key, $agencies, 'agencies', DAY_IN_SECONDS );
	}

	$memo[ $cache_key ] = $agencies;

	return $agencies;
}

/**
 * The agency list, uncached
 *
 * Carries the two fields the dropdowns print and nothing else, so the cached
 * payload stays small; the rows keep the object shape get_posts() returned.
 *
 * @param int $limit Rows to return, -1 for all of them.
 * @param string $language Polylang slug; empty counts every language.
 *
 * @return array
 */
function core_query_agencies( int $limit = - 1, string $language = '' ): array {
	global $wpdb;

	$params = array( 'agency', 'publish' );

	$language_join  = '';
	$language_where = '';
	if ( '' !== $language ) {
		$language_join  = "INNER JOIN {$wpdb->term_relationships} AS pll_language_relation
				ON pll_language_relation.object_id = a.ID
			INNER JOIN {$wpdb->term_taxonomy} AS pll_language_taxonomy
				ON pll_language_taxonomy.term_taxonomy_id = pll_language_relation.term_taxonomy_id
				AND pll_language_taxonomy.taxonomy = 'language'
			INNER JOIN {$wpdb->terms} AS pll_language
				ON pll_language.term_id = pll_language_taxonomy.term_id";
		$language_where = 'AND pll_language.slug = %s';
		$params[]       = $language;
	}

	$limit_sql = '';
	if ( 0 < $limit ) {
		$limit_sql = 'LIMIT %d';
		$params[]  = $limit;
	}

	$sql = "SELECT a.ID, a.post_title
			FROM {$wpdb->posts} AS a
			{$language_join}
			WHERE a.post_type = %s
			  AND a.post_status = %s
			  {$language_where}
			ORDER BY a.post_title ASC
			{$limit_sql}";

	return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) ?: array();
}
