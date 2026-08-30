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
			'comments',
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
 * Add support pagination for units pages off-plan and secondary
 */
add_action( 'init', static function () {
	add_rewrite_rule(
		'^off-plan/page-([0-9]+)/?$',
		'index.php?pagename=off-plan&cur_page=$matches[1]',
		'top'
	);

	add_rewrite_rule(
		'^secondary/page-([0-9]+)/?$',
		'index.php?pagename=secondary&cur_page=$matches[1]',
		'top'
	);
} );