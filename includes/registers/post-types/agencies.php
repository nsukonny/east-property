<?php
/**
 * Register Post Type: Agencies
 */
function east_property_register_post_type_agencies() {
	$labels = array(
		'name'                  => _x( 'Agencies', 'Post Type General Name', 'east-property' ),
		'singular_name'         => _x( 'Agency', 'Post Type Singular Name', 'east-property' ),
		'menu_name'             => __( 'Agencies', 'east-property' ),
		'name_admin_bar'        => __( 'Agency', 'east-property' ),
		'archives'              => __( 'Agency Archives', 'east-property' ),
		'attributes'            => __( 'Agency Attributes', 'east-property' ),
		'parent_item_colon'     => __( 'Parent Agency:', 'east-property' ),
		'all_items'             => __( 'All Agencies', 'east-property' ),
		'add_new_item'          => __( 'Add New Agency', 'east-property' ),
		'add_new'               => __( 'Add New', 'east-property' ),
		'new_item'              => __( 'New Agency', 'east-property' ),
		'edit_item'             => __( 'Edit Agency', 'east-property' ),
		'update_item'           => __( 'Update Agency', 'east-property' ),
		'view_item'             => __( 'View Agency', 'east-property' ),
		'view_items'            => __( 'View Agencies', 'east-property' ),
		'search_items'          => __( 'Search Agency', 'east-property' ),
		'not_found'             => __( 'Not found', 'east-property' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'east-property' ),
		'featured_image'        => __( 'Featured Image', 'east-property' ),
		'set_featured_image'    => __( 'Set featured image', 'east-property' ),
		'remove_featured_image' => __( 'Remove featured image', 'east-property' ),
		'use_featured_image'    => __( 'Use as featured image', 'east-property' ),
		'insert_into_item'      => __( 'Insert into agency', 'east-property' ),
		'uploaded_to_this_item' => __( 'Uploaded to this agency', 'east-property' ),
		'items_list'            => __( 'Agencies list', 'east-property' ),
		'items_list_navigation' => __( 'Agencies list navigation', 'east-property' ),
		'filter_items_list'     => __( 'Filter agencies list', 'east-property' ),
	);
	$args   = array(
		'label'               => __( 'Agency', 'east-property' ),
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
		'menu_position'       => 7,
		'menu_icon'           => 'dashicons-building',
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'has_archive'         => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'capability_type'     => 'post',
	);
	register_post_type( 'agency', $args );
}

add_action( 'init', 'east_property_register_post_type_agencies', 0 );