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
 * Slug of the project a unit belongs to. A deleted project counts as none.
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
 * Polylang prefixes only the rules WordPress generates, so hand written ones
 * have to be registered per language.
 *
 * @return string[] Empty string for the default language, `ru/` for the rest.
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
 * Build the unit permalink: /property/%project%/%unit%/, or /property/%unit%/
 * when the unit has no project.
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
 * Keep every unit on a single URL, answering 301 from any other shape.
 *
 * Drafts and previews are skipped: they carry no project segment and would be
 * redirected away from the preview.
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
 * Slugs of the unit listing pages. Shared across languages by Polylang.
 *
 * @return string[]
 */
function core_unit_listing_slugs(): array {
	return array( 'off-plan', 'secondary', 'distress' );
}

/**
 * Register pagination for the unit listing pages, in every language.
 *
 * Both `page-2` and `page/2` are registered: the theme links the first shape,
 * WordPress and outside links produce the second.
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