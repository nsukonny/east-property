<?php

use Entities\Unit;

/**
 * Register Post Type: Units
 */
function register_units_post_type(): void {
	$labels = array(
		'name'                     => _x( 'Units', 'Post Type General Name' , 'east-property' ),
		'singular_name'            => _x( 'Unit', 'Post Type Singular Name' , 'east-property' ),
		'menu_name'                => __( 'Units' , 'east-property' ),
		'name_admin_bar'           => __( 'Unit' , 'east-property' ),
		'archives'                 => __( 'Unit Archives' , 'east-property' ),
		'attributes'               => __( 'Unit Attributes' , 'east-property' ),
		'parent_item_colon'        => __( 'Parent Unit:' , 'east-property' ),
		'all_items'                => __( 'All Units' , 'east-property' ),
		'add_new_item'             => __( 'Add New Unit' , 'east-property' ),
		'add_new'                  => __( 'Add New' , 'east-property' ),
		'new_item'                 => __( 'New Unit' , 'east-property' ),
		'edit_item'                => __( 'Edit Unit' , 'east-property' ),
		'update_item'              => __( 'Update Unit' , 'east-property' ),
		'view_item'                => __( 'View Unit' , 'east-property' ),
		'view_items'               => __( 'View Units' , 'east-property' ),
		'search_items'             => __( 'Search Unit' , 'east-property' ),
		'not_found'                => __( 'Not found' , 'east-property' ),
		'not_found_in_trash'       => __( 'Not found in Trash' , 'east-property' ),
		'featured_image'           => __( 'Featured Image' , 'east-property' ),
		'set_featured_image'       => __( 'Set featured image' , 'east-property' ),
		'remove_featured_image'    => __( 'Remove featured image' , 'east-property' ),
		'use_featured_image'       => __( 'Use as featured image' , 'east-property' ),
		'insighted_by_this_author' => __( 'Units insighted by this author' , 'east-property' ),
		'all_insighted_items'      => __( 'All Units' , 'east-property' ),
	);
	$args   = array(
		'label'               => __( 'Unit' , 'east-property' ),
		'description'         => __( 'Post Type Description' , 'east-property' ),
		'labels'              => $labels,
		'supports'            => array(
			'title',
			'editor',
			'excerpt',
			'author',
			'thumbnail',
			'revisions',
			'custom-fields',
		),
		'taxonomies'          => array( 'location' ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 6,
		'menu_icon'           => 'dashicons-building',
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'capability_type'     => 'post',
		'rewrite'             => array(
			'slug'       => 'property/%project_slug%',
			'with_front' => false,
		),
		'has_archive'         => false,
	);
	register_post_type( 'unit', $args );
}

add_action( 'init', 'register_units_post_type', 0 );

/**
 * Slug of the project a unit belongs to.
 *
 * The one place that decides whether a unit has a project at all, so the
 * permalink and the canonical redirect can never disagree about it. A project
 * that was deleted counts as no project: the meta row still holds its id, but
 * there is no slug left to put in a URL.
 *
 * @param Unit $unit Unit to look at.
 *
 * @return string Empty string when the unit has no project.
 */
function core_unit_project_slug( Unit $unit ): string {
	$property = $unit->get_property();

	if ( null === $property || ! $property->exists() ) {
		return '';
	}

	return $property->get_slug();
}

/**
 * URL path prefixes of every language, the default language first.
 *
 * Polylang only prefixes the rewrite rules WordPress generates itself; rules
 * added by hand it leaves alone. Every such rule therefore has to be
 * registered once per language, or the Russian copy of the page answers 404.
 *
 * @return string[] Empty string for the default language, `ru/` and so on for the rest.
 */
function core_language_url_prefixes(): array {
	$prefixes = array( '' );

	if ( ! function_exists( 'pll_languages_list' ) || ! function_exists( 'pll_default_language' ) ) {
		return $prefixes;
	}

	$default_language = (string) pll_default_language( 'slug' );

	foreach ( (array) pll_languages_list() as $language ) {
		if ( '' === (string) $language || $language === $default_language ) {
			continue;
		}

		$prefixes[] = $language . '/';
	}

	return $prefixes;
}

/**
 * Build the unit permalink.
 *
 * /property/%project%/%unit%/ with a project, /property/%unit%/ without one.
 * The short shape replaced /property/no-project/%unit%/, which was a 404 by
 * construction — the project segment was a literal that matched no project, so
 * the page it named could not exist.
 */
add_filter( 'post_type_link', static function ( $permalink, $post ) {
	if ( 'unit' !== $post->post_type ) {
		return $permalink;
	}

	$unit         = new Unit( $post );
	$project_slug = core_unit_project_slug( $unit );
	$project_path = '' === $project_slug ? '' : $project_slug . '/';

	return core_home_url( '/property/' . $project_path . $unit->get_slug() . '/' );
}, 10, 2 );

/**
 * Add rewrite rules for unit URLs
 * Structure: /property/%property%/%unit%/
 *
 * Old Unit Example
 * http://eastproperty.local/properties/business-bay/omniyat-the-opus-by-omniyat/unit-ra114-simplex-on-16-floor/
 */
add_action( 'init', static function () {
	add_rewrite_rule(
		'^property/([^/]+)/([^/]+)/?$',
		'index.php?post_type=unit&project_slug=$matches[1]&name=$matches[2]',
		'top'
	);

	/*
	 * A unit without a project lives one segment higher, so that shape needs a
	 * rule of its own. WordPress does generate one from the post type's
	 * permastruct — property/([^&]+)/?$ — but it only sets project_slug and
	 * leaves the query pointing at nothing, so every single-segment address
	 * under /property/ rendered the home page under a 200. A soft 404, and the
	 * reason this rule is registered on top rather than left to core.
	 */
	foreach ( core_language_url_prefixes() as $prefix ) {
		$lang = '' === $prefix ? '' : '&lang=' . rtrim( $prefix, '/' );

		add_rewrite_rule(
			'^' . $prefix . 'property/([^/]+)/?$',
			'index.php?post_type=unit&name=$matches[1]' . $lang,
			'top'
		);
	}

	//old unit pages
	add_rewrite_rule(
		'^properties/([^/]+)/([^/]+)/([^/]+)/?$',
		'index.php?post_type=unit&location_slug=$matches[1]&project_slug=$matches[2]&name=$matches[3]&is_old_url=1',
		'top'
	);

	add_rewrite_tag( '%project_slug%', '([^&]+)' );
	add_rewrite_tag( '%is_old_url%', '([^&]+)' );
}, 20 );

add_filter( 'query_vars', static function ( $vars ) {
	$vars[] = 'project_slug';
	$vars[] = 'is_old_url';

	return $vars;
} );

/**
 * Keep every unit on a single URL.
 *
 * Anything that is not the canonical shape answers 301 to it: the old
 * /properties/%location%/%project%/%unit%/ links, an address carrying the
 * wrong project, the retired /property/no-project/%unit%/, and the case this
 * was extended for — /property/%unit%/ when the unit does have a project.
 *
 * Comparing the two slugs is the whole rule. An empty project_slug means the
 * request came through the single-segment rule, and an empty expected slug
 * means the unit has no project, so the two agree only when the address is
 * already right.
 *
 * Drafts and previews are left alone: they are reached by ?p= or
 * ?preview=true, which carry no project segment, and redirecting them to the
 * permalink would break the preview from the account form.
 */
add_action( 'template_redirect', static function () {
	if ( ! is_singular( 'unit' ) ) {
		return;
	}

	$unit_id = get_queried_object_id();
	if ( ! $unit_id ) {
		return;
	}

	$unit_post = get_post( $unit_id );
	if ( ! $unit_post ) {
		return;
	}

	if ( is_preview() || 'publish' !== $unit_post->post_status ) {
		return;
	}

	$unit              = new Unit( $unit_post );
	$expected_project  = core_unit_project_slug( $unit );
	$requested_project = (string) get_query_var( 'project_slug' );
	$is_old_url        = 1 === (int) get_query_var( 'is_old_url' );

	if ( $is_old_url || $requested_project !== $expected_project ) {
		wp_redirect( get_permalink( $unit_id ), 301 );
		exit;
	}
} );

/**
 * Slugs of the unit listing pages, all of which paginate the same way.
 *
 * The page slug is shared across languages — Polylang keeps `off-plan` for the
 * Russian translation too — so one list covers every language.
 *
 * @return string[]
 */
function core_unit_listing_slugs(): array {
	return array( 'off-plan', 'secondary', 'distress' );
}

/**
 * Register pagination for the unit listing pages, in every language.
 *
 * Two things were broken here and both are worth naming.
 *
 * `distress` had no rule at all: the page was added after this block was
 * written and never got one, so /distress/page-2/ answered 404 while
 * /off-plan/page-2/ worked.
 *
 * And no rule carried a language prefix. Polylang does not build prefixed
 * variants of hand written rewrite rules, so /ru/off-plan/page-2/,
 * /ru/secondary/page-2/ and /ru/distress/page-2/ all answered 404 — every
 * Russian listing lost everything past the first page. The news section hit
 * exactly this and solved it the same way; see
 * core_register_news_pagination_rewrite().
 *
 * Both URL shapes are registered: `page-2` is what the theme's own pagination
 * links use, `page/2` is what WordPress and outside links produce.
 *
 * @return void
 */
function core_register_unit_listing_pagination(): void {
	foreach ( core_language_url_prefixes() as $prefix ) {
		$lang = '' === $prefix ? '' : '&lang=' . rtrim( $prefix, '/' );

		foreach ( core_unit_listing_slugs() as $slug ) {
			$target = 'index.php?pagename=' . $slug . '&cur_page=$matches[1]' . $lang;

			add_rewrite_rule( '^' . $prefix . $slug . '/page-([0-9]+)/?$', $target, 'top' );
			add_rewrite_rule( '^' . $prefix . $slug . '/page/([0-9]+)/?$', $target, 'top' );
		}
	}
}

add_action( 'init', 'core_register_unit_listing_pagination' );