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
 * Make permalink for unit like /property/%property%/%unit%/
 */
add_filter( 'post_type_link', static function ( $permalink, $post ) {
	if ( $post->post_type !== 'unit' ) {
		return $permalink;
	}

	$unit     = new Unit( $post );
	$property = $unit->get_property();

	if ( ! $property ) {
		return core_home_url( '/property/no-project/' . $unit->get_slug() . '/' );
	}

	return core_home_url( '/property/' . $property->get_slug() . '/' . $unit->get_slug() . '/' );
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
 * Show 404 if unit's property doesn't match URL
 */
add_action( 'template_redirect', static function () {
	if ( ! is_singular( 'unit' ) ) {
		return;
	}

	$requested_property_slug = get_query_var( 'project_slug' );
	if ( ! $requested_property_slug ) {
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

	$unit     = new Unit( $unit_post );
	$property = $unit->get_property();

	if ( ! $property || ! $property->exists() ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();

		return;
	}

	$is_old_url               = 1 === (int) get_query_var( 'is_old_url' );
	$is_wrong_property_in_url = $property->get_slug() !== $requested_property_slug;
	if ( $is_old_url || $is_wrong_property_in_url ) {
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
	$prefixes = array( '' );

	if ( function_exists( 'pll_languages_list' ) && function_exists( 'pll_default_language' ) ) {
		$default_language = (string) pll_default_language( 'slug' );

		foreach ( (array) pll_languages_list() as $language ) {
			if ( '' === (string) $language || $language === $default_language ) {
				continue;
			}

			$prefixes[] = $language . '/';
		}
	}

	foreach ( $prefixes as $prefix ) {
		$lang = '' === $prefix ? '' : '&lang=' . rtrim( $prefix, '/' );

		foreach ( core_unit_listing_slugs() as $slug ) {
			$target = 'index.php?pagename=' . $slug . '&cur_page=$matches[1]' . $lang;

			add_rewrite_rule( '^' . $prefix . $slug . '/page-([0-9]+)/?$', $target, 'top' );
			add_rewrite_rule( '^' . $prefix . $slug . '/page/([0-9]+)/?$', $target, 'top' );
		}
	}
}

add_action( 'init', 'core_register_unit_listing_pagination' );