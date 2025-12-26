<?php
/**
 * Register Custom Post Type: Properties
 */
function register_properties_post_type(): void {
	$labels = array(
		'name'                     => _x( 'Properties', 'Post Type General Name', 'east-property' ),
		'singular_name'            => _x( 'Property', 'Post Type Singular Name', 'east-property' ),
		'menu_name'                => __( 'Properties', 'east-property' ),
		'name_admin_bar'           => __( 'Property', 'east-property' ),
		'archives'                 => __( 'Property Archives', 'east-property' ),
		'attributes'               => __( 'Property Attributes', 'east-property' ),
		'parent_item_colon'        => __( 'Parent Property:', 'east-property' ),
		'all_items'                => __( 'All Properties', 'east-property' ),
		'add_new_item'             => __( 'Add New Property', 'east-property' ),
		'add_new'                  => __( 'Add New', 'east-property' ),
		'new_item'                 => __( 'New Property', 'east-property' ),
		'edit_item'                => __( 'Edit Property', 'east-property' ),
		'update_item'              => __( 'Update Property', 'east-property' ),
		'view_item'                => __( 'View Property', 'east-property' ),
		'view_items'               => __( 'View Properties', 'east-property' ),
		'search_items'             => __( 'Search Property', 'east-property' ),
		'not_found'                => __( 'Not found', 'east-property' ),
		'not_found_in_trash'       => __( 'Not found in Trash', 'east-property' ),
		'featured_image'           => __( 'Featured Image', 'east-property' ),
		'set_featured_image'       => __( 'Set featured image', 'east-property' ),
		'remove_featured_image'    => __( 'Remove featured image', 'east-property' ),
		'use_featured_image'       => __( 'Use as featured image', 'east-property' ),
		'insighted_by_this_author' => __( 'Properties insighted by this author', 'east-property' ),
		'all_insighted_items'      => __( 'All Properties', 'east-property' ),
	);
	$args   = array(
		'label'               => __( 'Property', 'east-property' ),
		'description'         => __( 'Post Type Description', 'east-property' ),
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
		'taxonomies'          => array( 'category', 'post_tag' ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 5,
		'menu_icon'           => 'dashicons-admin-home',
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'has_archive'         => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'capability_type'     => 'post',
	);
	register_post_type( 'properties', $args );
}

add_action( 'init', 'register_properties_post_type', 0 );