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

/**
 * The paginated listing a template is about to render.
 *
 * The listings paginate through custom /page-N/ rewrite rules instead of through
 * WP_Query, so WordPress never learns that a page number is out of range and
 * renders an empty listing under a 200 — a soft 404 — while Yoast, seeing no
 * paged query, resolves %%page%% to nothing and ships one title for the whole
 * sequence. Asking the same getter the template is about to ask answers both:
 * an empty page past the first one is out of range, and the total says how many
 * pages there are.
 *
 * @param string $template Basename of the template chosen by the loader.
 *
 * @return array|null Items, total and page size; null when this is no listing.
 */
function core_paginated_listing( string $template ): ?array {
	static $memo = array();

	$page = pagination_get_current_page();
	$key  = $template . '|' . $page;

	if ( array_key_exists( $key, $memo ) ) {
		return $memo[ $key ];
	}

	$per_page = defined( 'PROPERTIES_PER_PAGE' ) ? PROPERTIES_PER_PAGE : 20;
	$listing  = null;

	switch ( $template ) {
		case 'page-off-plan.php':
			$listing = core_listing_from_units( 'off-plan', $per_page );
			break;

		case 'page-secondary.php':
			$listing = core_listing_from_units( 'secondary', $per_page );
			break;

		case 'page-distress.php':
			$listing = core_listing_from_units( 'distress', $per_page );
			break;

		case 'page-properties.php':
		case 'taxonomy-location.php':
			$properties = get_properties( $per_page );
			$listing    = array(
				'items'    => $properties['items'] ?? array(),
				'total'    => (int) ( $properties['total'] ?? 0 ),
				'per_page' => $per_page,
			);
			break;

		case 'page-news.php':
			$query   = core_get_news( array( 'paged' => $page ) );
			$listing = array(
				'items'    => $query->posts,
				'total'    => (int) $query->found_posts,
				'per_page' => max( 1, (int) $query->get( 'posts_per_page' ) ),
			);
			break;

		case 'single-developers.php':
			$developer  = new Developer( get_queried_object_id() );
			$properties = $developer->get_properties();
			$listing    = array(
				'items'    => array_slice( $properties, ( $page - 1 ) * $per_page, $per_page ),
				'total'    => count( $properties ),
				'per_page' => $per_page,
			);
			break;
	}

	$memo[ $key ] = $listing;

	return $listing;
}

/**
 * One page of a units listing, shaped for core_paginated_listing().
 *
 * @param string $listing_type Listing type slug.
 * @param int    $per_page     Page size.
 *
 * @return array
 */
function core_listing_from_units( string $listing_type, int $per_page ): array {
	$units = get_units( $listing_type, $per_page );

	return array(
		'items'    => $units['items'] ?? array(),
		'total'    => (int) ( $units['total'] ?? 0 ),
		'per_page' => $per_page,
	);
}

/**
 * Template of the paginated listing being rendered past its first page.
 *
 * Set once the guard has confirmed the page exists, so the canonical and title
 * filters that run later during wp_head can tell a genuine page of a listing
 * from any other request without repeating the work.
 *
 * @param string|null $template Value to store, null to read the stored one.
 *
 * @return string Empty when this request is not such a page.
 */
function core_paginated_listing_template( ?string $template = null ): string {
	static $stored = '';

	if ( null !== $template ) {
		$stored = $template;
	}

	return $stored;
}

/**
 * Turn an out-of-range listing page into a real 404.
 *
 * The template_include filter is the last hook before the template starts
 * writing output, so a status set here still reaches the browser.
 *
 * @param string $template Template chosen by the template loader.
 *
 * @return string
 */
function core_pagination_soft_404( string $template ): string {
	if ( 2 > pagination_get_current_page() ) {
		return $template;
	}

	$basename = basename( $template );
	$listing  = core_paginated_listing( $basename );

	if ( null === $listing ) {
		return $template;
	}

	if ( ! empty( $listing['items'] ) ) {
		core_paginated_listing_template( $basename );

		return $template;
	}

	global $wp_query;

	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();

	return get_404_template();
}

add_filter( 'template_include', 'core_pagination_soft_404' );

/**
 * Point a page of a listing at itself.
 *
 * Yoast derives the canonical from the page permalink, which knows nothing about
 * the /page-N/ rewrites, so every page of a listing claimed to be page one.
 * Google treats each page of a sequence as a URL in its own right, so page N
 * canonicalises to page N — but only where the paginated URL is the canonical
 * path to begin with. /projects/{district}/ and the developer aliases
 * deliberately canonicalise onto a different path, and pinning a page number
 * onto those would invent a URL that does not resolve.
 *
 * @param string $canonical Canonical URL built by Yoast.
 *
 * @return string
 */
function core_pagination_canonical( $canonical ) {
	$page = pagination_get_current_page();
	if ( '' === core_paginated_listing_template() || 2 > $page || ! is_string( $canonical ) || '' === $canonical ) {
		return $canonical;
	}

	$canonical_path = (string) wp_parse_url( $canonical, PHP_URL_PATH );
	$request_uri    = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
	$request_path   = (string) wp_parse_url( $request_uri, PHP_URL_PATH );

	if ( '' === $canonical_path || ! str_starts_with( $request_path, $canonical_path ) ) {
		return $canonical;
	}

	return user_trailingslashit( trailingslashit( $canonical ) . 'page-' . $page );
}

add_filter( 'wpseo_canonical', 'core_pagination_canonical' );

/**
 * Fill in the page counter the title template already asks for.
 *
 * The configured template carries %%page%%, which Yoast resolves from
 * $wp_query->max_num_pages and the paged query var — neither of which the custom
 * rewrites set, so it collapsed to nothing and every page of a listing shipped
 * an identical title. Filling the replacement instead of appending to the
 * finished title keeps the counter where the template puts it and carries over
 * to any other place the variable is used.
 *
 * @param mixed $replacements Replacement per variable, keyed with delimiters.
 *
 * @return mixed
 */
function core_pagination_replacements( $replacements ) {
	/*
	 * The filter fires for every replace-var pass — titles, descriptions, social
	 * and breadcrumbs alike — and resolving the separator reaches back into the
	 * same machinery, so a reentrant call would recurse until memory ran out.
	 */
	static $inside = false;

	$template = core_paginated_listing_template();
	if ( $inside || ! is_array( $replacements ) || '' === $template ) {
		return $replacements;
	}

	$page    = pagination_get_current_page();
	$listing = core_paginated_listing( $template );
	if ( 2 > $page || null === $listing ) {
		return $replacements;
	}

	$per_page = max( 1, (int) ( $listing['per_page'] ?? 1 ) );
	$total    = (int) ceil( (int) ( $listing['total'] ?? 0 ) / $per_page );
	if ( 2 > $total ) {
		return $replacements;
	}

	$inside    = true;
	$separator = function_exists( 'YoastSEO' )
		? (string) YoastSEO()->helpers->options->get_title_separator()
		: '-';
	$inside    = false;

	/* translators: 1: current page number, 2: total number of pages. */
	$replacements['%%page%%'] = $separator . ' ' . sprintf( __( 'Page %1$d of %2$d', 'east-property' ), $page, $total );

	return $replacements;
}

add_filter( 'wpseo_replacements', 'core_pagination_replacements' );

/**
 * Complete the hreflang set Polylang prints into the head.
 *
 * Two gaps are closed here. Polylang adds x-default only on the front page and
 * only while the default language keeps a directory of its own — with
 * hide_default enabled, as it is here, the tag never appears at all, leaving
 * speakers of every other language without a declared fallback. And its guard
 * against paginated views leans on is_paged(), which the custom /page-N/
 * rewrites never set: page two therefore declared the unpaginated URL as its own
 * alternate, pointing each language at a page listing different properties.
 *
 * Nothing is added where Polylang printed nothing: it emits the block only once
 * a translation actually exists, which is exactly the rule an hreflang set has
 * to follow — a reference to a missing translation is an error, not a hint.
 *
 * @param array $hreflangs URL per language code, self link included.
 *
 * @return array
 */
function core_hreflang_attributes( $hreflangs ) {
	if ( ! is_array( $hreflangs ) || empty( $hreflangs ) ) {
		return $hreflangs;
	}

	if ( 1 < pagination_get_current_page() ) {
		return array();
	}

	if ( isset( $hreflangs['x-default'] ) || ! function_exists( 'pll_default_language' ) ) {
		return $hreflangs;
	}

	$default = core_hreflang_default_url( $hreflangs );
	if ( '' !== $default ) {
		$hreflangs['x-default'] = $default;
	}

	return $hreflangs;
}

add_filter( 'pll_rel_hreflang_attributes', 'core_hreflang_attributes' );

/**
 * URL of the default language within a printed hreflang set.
 *
 * Polylang keys the set by bare language code and falls back to the display
 * locale once two languages share a code, so both spellings are looked up.
 *
 * @param array $hreflangs URL per language code.
 *
 * @return string Empty when the default language is not part of the set.
 */
function core_hreflang_default_url( array $hreflangs ): string {
	$candidates = array( (string) pll_default_language( 'slug' ) );

	$locale = (string) pll_default_language( 'locale' );
	if ( '' !== $locale ) {
		$candidates[] = str_replace( '_', '-', $locale );
	}

	foreach ( $candidates as $candidate ) {
		if ( '' !== $candidate && ! empty( $hreflangs[ $candidate ] ) ) {
			return (string) $hreflangs[ $candidate ];
		}
	}

	return '';
}


//test is
