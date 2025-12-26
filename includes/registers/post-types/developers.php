<?php
/**
 * Register Post Type: Developers
 */
function register_post_type_developers() {
	$labels = array(
		'name'                  => _x( 'Developers', 'Post Type General Name', 'east-property' ),
		'singular_name'         => _x( 'Developer', 'Post Type Singular Name', 'east-property' ),
		'menu_name'             => __( 'Developers', 'east-property' ),
		'name_admin_bar'        => __( 'Developer', 'east-property' ),
		'archives'              => __( 'Developer Archives', 'east-property' ),
		'attributes'            => __( 'Developer Attributes', 'east-property' ),
		'parent_item_colon'     => __( 'Parent Developer:', 'east-property' ),
		'all_items'             => __( 'All Developers', 'east-property' ),
		'add_new_item'          => __( 'Add New Developer', 'east-property' ),
		'add_new'               => __( 'Add New', 'east-property' ),
		'new_item'              => __( 'New Developer', 'east-property' ),
		'edit_item'             => __( 'Edit Developer', 'east-property' ),
		'update_item'           => __( 'Update Developer', 'east-property' ),
		'view_item'             => __( 'View Developer', 'east-property' ),
		'view_items'            => __( 'View Developers', 'east-property' ),
		'search_items'          => __( 'Search Developer', 'east-property' ),
		'not_found'             => __( 'Not found', 'east-property' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'east-property' ),
		'featured_image'        => __( 'Featured Image', 'east-property' ),
		'set_featured_image'    => __( 'Set featured image', 'east-property' ),
		'remove_featured_image' => __( 'Remove featured image', 'east-property' ),
		'use_featured_image'    => __( 'Use as featured image', 'east-property' ),
		'insert_into_item'      => __( 'Insert into developer', 'east-property' ),
		'uploaded_to_this_item' => __( 'Uploaded to this developer', 'east-property' ),
		'items_list'            => __( 'Developers list', 'east-property' ),
		'items_list_navigation' => __( 'Developers list navigation', 'east-property' ),
		'filter_items_list'     => __( 'Filter developers list', 'east-property' ),
	);
	$args   = array(
		'label'               => __( 'Developer', 'east-property' ),
		'description'         => __( 'Post Type Description', 'east-property' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions' ),
		'taxonomies'          => array( 'category', 'post_tag' ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 5,
		'menu_icon'           => 'dashicons-image-filter',
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'has_archive'         => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'capability_type'     => 'post',
	);
	register_post_type( 'developers', $args );
}

add_action( 'init', 'register_post_type_developers', 0 );