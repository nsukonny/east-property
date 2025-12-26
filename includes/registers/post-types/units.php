<?php
/**
 * Register Post Type: Units
 */
function register_units_post_type(): void {
	$labels = array(
		'name'                     => _x( 'Units', 'Post Type General Name', 'east-property' ),
		'singular_name'            => _x( 'Unit', 'Post Type Singular Name', 'east-property' ),
		'menu_name'                => __( 'Units', 'east-property' ),
		'name_admin_bar'           => __( 'Unit', 'east-property' ),
		'archives'                 => __( 'Unit Archives', 'east-property' ),
		'attributes'               => __( 'Unit Attributes', 'east-property' ),
		'parent_item_colon'        => __( 'Parent Unit:', 'east-property' ),
		'all_items'                => __( 'All Units', 'east-property' ),
		'add_new_item'             => __( 'Add New Unit', 'east-property' ),
		'add_new'                  => __( 'Add New', 'east-property' ),
		'new_item'                 => __( 'New Unit', 'east-property' ),
		'edit_item'                => __( 'Edit Unit', 'east-property' ),
		'update_item'              => __( 'Update Unit', 'east-property' ),
		'view_item'                => __( 'View Unit', 'east-property' ),
		'view_items'               => __( 'View Units', 'east-property' ),
		'search_items'             => __( 'Search Unit', 'east-property' ),
		'not_found'                => __( 'Not found', 'east-property' ),
		'not_found_in_trash'       => __( 'Not found in Trash', 'east-property' ),
		'featured_image'           => __( 'Featured Image', 'east-property' ),
		'set_featured_image'       => __( 'Set featured image', 'east-property' ),
		'remove_featured_image'    => __( 'Remove featured image', 'east-property' ),
		'use_featured_image'       => __( 'Use as featured image', 'east-property' ),
		'insighted_by_this_author' => __( 'Units insighted by this author', 'east-property' ),
		'all_insighted_items'      => __( 'All Units', 'east-property' ),
	);
	$args   = array(
		'label'               => __( 'Unit', 'east-property' ),
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
		'menu_position'       => 6,
		'menu_icon'           => 'dashicons-building',
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'has_archive'         => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'capability_type'     => 'post',
	);
	register_post_type( 'unit', $args );
}

add_action( 'init', 'register_units_post_type', 0 );