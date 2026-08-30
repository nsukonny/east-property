<?php
/**
 * Sitemap and indexing rules.
 *
 * The XML sitemap is produced by Yoast SEO. Everything here narrows it down to
 * URLs that actually resolve and keeps generation cheap enough that a cold
 * request never runs into the gateway timeout.
 */

/**
 * Post types that have no public template and must stay out of the sitemap.
 *
 * Both archive.php and single.php redirect anything without a dedicated
 * template to /404, so these URLs are dead by construction.
 *
 * @return array
 */
function core_get_unroutable_post_types(): array {
	return array( 'agency' );
}

/**
 * Taxonomies without a public template.
 *
 * `location` belongs in the sitemap as soon as taxonomy-location.php exists —
 * until then /areas/{slug}/ redirects to /404.
 *
 * @return array
 */
function core_get_unroutable_taxonomies(): array {
	return array( 'location' );
}

/**
 * Slugs of pages that must never be indexed. Shared by every language, since
 * Polylang keeps the slug per translation.
 *
 * @return array
 */
function core_get_noindex_page_slugs(): array {
	return array( 'account', 'register', 'reset-password' );
}

/**
 * Resolve the noindex page slugs to IDs across all languages.
 *
 * Only used while building the sitemap, so the queries run a handful of times
 * per generation rather than on every page view.
 *
 * @return array
 */
function core_get_noindex_page_ids(): array {
	static $ids = array();

	if ( ! empty( $ids ) ) {
		return $ids;
	}

	$found = array();
	foreach ( core_get_noindex_page_slugs() as $slug ) {
		$pages = get_posts(
			array(
				'post_type'        => 'page',
				'name'             => $slug,
				'post_status'      => 'publish',
				'numberposts'      => - 1,
				'fields'           => 'ids',
				'suppress_filters' => true,
			)
		);

		foreach ( (array) $pages as $page ) {
			$found[] = absint( $page );
		}
	}

	$ids = array_values( array_unique( $found ) );

	return $ids;
}

/**
 * Keep unroutable post types out of the sitemap index.
 *
 * The incoming value is deliberately untyped: other callbacks on this filter
 * may hand over anything, and null on a typed parameter would be fatal.
 *
 * @param mixed $excluded Current decision.
 * @param string $post_type Post type name.
 *
 * @return bool
 */
function core_sitemap_exclude_post_type( $excluded, $post_type ): bool {
	if ( in_array( (string) $post_type, core_get_unroutable_post_types(), true ) ) {
		return true;
	}

	return (bool) $excluded;
}

add_filter( 'wpseo_sitemap_exclude_post_type', 'core_sitemap_exclude_post_type', 10, 2 );

/**
 * Keep unroutable taxonomies out of the sitemap index.
 *
 * @param mixed $excluded Current decision.
 * @param string $taxonomy Taxonomy name.
 *
 * @return bool
 */
function core_sitemap_exclude_taxonomy( $excluded, $taxonomy ): bool {
	if ( in_array( (string) $taxonomy, core_get_unroutable_taxonomies(), true ) ) {
		return true;
	}

	return (bool) $excluded;
}

add_filter( 'wpseo_sitemap_exclude_taxonomy', 'core_sitemap_exclude_taxonomy', 10, 2 );

/**
 * Drop post type archive links that have no template.
 *
 * Yoast puts the archive of a post type first in its section. `developers` and
 * `agency` declare has_archive but the theme has no archive-{type}.php, so
 * archive.php redirects them to /404.
 *
 * Hooked on the whole list at a late priority rather than on the single archive
 * URL, because Polylang appends a translated archive of its own — /ru/developers/
 * was dead the same way. Detected by template rather than by a list, so the
 * links return on their own once a template is added.
 *
 * @param mixed $links Links Yoast puts first in the section.
 * @param string $post_type Post type the section is for.
 *
 * @return array
 */
function core_sitemap_drop_templateless_archives( $links, $post_type ): array {
	$links     = (array) $links;
	$post_type = (string) $post_type;

	// For pages this holds the front page, not an archive.
	if ( 'page' === $post_type || 'post' === $post_type ) {
		return $links;
	}

	if ( '' !== locate_template( array( 'archive-' . $post_type . '.php' ) ) ) {
		return $links;
	}

	return array();
}

add_filter( 'wpseo_sitemap_post_type_first_links', 'core_sitemap_drop_templateless_archives', 99, 2 );

/**
 * Drop the account and auth pages from the sitemap.
 *
 * @param mixed $excluded_ids Post IDs Yoast already excludes.
 *
 * @return array
 */
function core_sitemap_exclude_utility_pages( $excluded_ids ): array {
	return array_merge( (array) $excluded_ids, core_get_noindex_page_ids() );
}

add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', 'core_sitemap_exclude_utility_pages' );

/**
 * Mark the account and auth pages noindex, so the sitemap and the meta robots
 * tag tell search engines the same thing.
 *
 * Matches on the slug of the page being rendered, which is already loaded — no
 * extra queries on a filter that runs for every request.
 *
 * @param mixed $robots Robots directives.
 *
 * @return array
 */
function core_noindex_utility_pages( $robots ): array {
	$robots = (array) $robots;

	if ( ! is_page() ) {
		return $robots;
	}

	$page = get_queried_object();
	if ( ! $page instanceof WP_Post ) {
		return $robots;
	}

	if ( ! in_array( $page->post_name, core_get_noindex_page_slugs(), true ) ) {
		return $robots;
	}

	$robots['index'] = 'noindex';

	return $robots;
}

add_filter( 'wpseo_robots_array', 'core_noindex_utility_pages' );

/*
 * ---------------------------------------------------------------------------
 * Only units that resolve
 * ---------------------------------------------------------------------------
 *
 * A unit URL is built from its project slug. Without a published project the
 * permalink falls back to /property/no-project/{slug}/, which is a 404. Filter
 * those out in SQL rather than per entry, so the page counts in the sitemap
 * index stay consistent with the pages themselves.
 *
 * EXISTS rather than a JOIN on purpose: Yoast counts rows with a plain
 * COUNT(wp_posts.ID), so a duplicated meta row would inflate the count and
 * repeat URLs. Duplicated postmeta is a real condition in this database.
 */

/**
 * Restrict the unit sitemap to units whose project is a published property.
 *
 * Appended after Yoast's own WHERE conditions, hence the leading AND.
 *
 * @param mixed $where Existing SQL, false or an empty string by default.
 * @param string $post_type Post type being queried.
 *
 * @return string
 */
function core_sitemap_units_where( $where, $post_type ): string {
	global $wpdb;

	$where = is_string( $where ) ? $where : '';

	if ( 'unit' !== (string) $post_type ) {
		return $where;
	}

	return $where . "
		AND EXISTS (
			SELECT 1
			FROM {$wpdb->postmeta} AS core_pm_property
			INNER JOIN {$wpdb->posts} AS core_project
				ON core_project.ID = CAST( core_pm_property.meta_value AS UNSIGNED )
			WHERE core_pm_property.post_id = {$wpdb->posts}.ID
				AND core_pm_property.meta_key = 'property'
				AND core_project.post_type = 'property'
				AND core_project.post_status = 'publish'
		)
	";
}

add_filter( 'wpseo_posts_where', 'core_sitemap_units_where', 10, 2 );
add_filter( 'wpseo_typecount_where', 'core_sitemap_units_where', 10, 2 );

/*
 * ---------------------------------------------------------------------------
 * Generation cost
 * ---------------------------------------------------------------------------
 */

/**
 * Turn on Yoast's sitemap cache, which ships off — every request otherwise
 * rebuilds the whole sitemap.
 *
 * Skipped when Polylang serves languages from separate domains or subdomains:
 * it disables this cache on purpose there, because Yoast would keep only one
 * domain in the cached copy.
 *
 * @param mixed $enabled Current decision.
 *
 * @return bool
 */
function core_enable_sitemap_cache( $enabled ): bool {
	$options = get_option( 'polylang' );
	if ( is_array( $options ) && isset( $options['force_lang'] ) && (int) $options['force_lang'] > 1 ) {
		return (bool) $enabled;
	}

	return true;
}

add_filter( 'wpseo_enable_xml_sitemap_transient_caching', 'core_enable_sitemap_cache' );

// Images are the bulk of both the build time and the payload, and this catalogue
// does not rely on image search.
add_filter( 'wpseo_xml_sitemap_include_images', '__return_false' );

/**
 * Smaller pages: the cold build of a 1000 URL page was the source of the 504.
 *
 * @return int
 */
function core_sitemap_entries_per_page(): int {
	return 500;
}

add_filter( 'wpseo_sitemap_entries_per_page', 'core_sitemap_entries_per_page' );

/**
 * Let the page cache and the crawlers hold on to a sitemap. Yoast sends no
 * caching headers of its own.
 *
 * @param mixed $headers Headers to send.
 *
 * @return array
 */
function core_sitemap_cache_headers( $headers ): array {
	$headers = (array) $headers;

	$headers['Cache-Control: public, max-age=3600, stale-while-revalidate=86400'] = '';

	return $headers;
}

add_filter( 'wpseo_sitemap_http_headers', 'core_sitemap_cache_headers' );
