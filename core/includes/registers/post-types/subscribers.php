<?php

/**
 * Register Post Type: Subscribers
 */
function register_post_type_subscribers(): void {
	$labels = array(
		'name'                  => _x( 'Subscribers', 'Post Type General Name' , 'east-property' ),
		'singular_name'         => _x( 'Subscriber', 'Post Type Singular Name' , 'east-property' ),
		'menu_name'             => __( 'Subscribers' , 'east-property' ),
		'name_admin_bar'        => __( 'Subscriber' , 'east-property' ),
		'archives'              => __( 'Subscriber Archives' , 'east-property' ),
		'attributes'            => __( 'Subscriber Attributes' , 'east-property' ),
		'parent_item_colon'     => __( 'Parent Subscriber:' , 'east-property' ),
		'all_items'             => __( 'All Subscribers' , 'east-property' ),
		'add_new_item'          => __( 'Add New Subscriber' , 'east-property' ),
		'add_new'               => __( 'Add New' , 'east-property' ),
		'new_item'              => __( 'New Subscriber' , 'east-property' ),
		'edit_item'             => __( 'Edit Subscriber' , 'east-property' ),
		'update_item'           => __( 'Update Subscriber' , 'east-property' ),
		'view_item'             => __( 'View Subscriber' , 'east-property' ),
		'view_items'            => __( 'View Subscribers' , 'east-property' ),
		'search_items'          => __( 'Search Subscriber' , 'east-property' ),
		'not_found'             => __( 'Not found' , 'east-property' ),
		'not_found_in_trash'    => __( 'Not found in Trash' , 'east-property' ),
		'featured_image'        => __( 'Featured Image' , 'east-property' ),
		'set_featured_image'    => __( 'Set featured image' , 'east-property' ),
		'remove_featured_image' => __( 'Remove featured image' , 'east-property' ),
		'use_featured_image'    => __( 'Use as featured image' , 'east-property' ),
		'insert_into_item'      => __( 'Insert Subscriber' , 'east-property' ),
		'uploaded_to_this_item' => __( 'Uploaded to this Subscriber' , 'east-property' ),
		'items_list'            => __( 'Subscribers list' , 'east-property' ),
		'items_list_navigation' => __( 'Subscribers list navigation' , 'east-property' ),
		'filter_items_list'     => __( 'Filter Subscribers list' , 'east-property' ),
	);
	$args   = array(
		'label'               => __( 'Subscriber' , 'east-property' ),
		'description'         => __( 'Post Type Description' , 'east-property' ),
		'labels'              => $labels,
		'supports'            => array(
			'title',
		),
		'taxonomies'          => array(),
		'hierarchical'        => false,
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 7,
		'menu_icon'           => 'dashicons-email',
		'show_in_admin_bar'   => false,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'has_archive'         => false,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'capability_type'     => 'post',
	);
	register_post_type( 'subscriber', $args );
}

add_action( 'init', 'register_post_type_subscribers', 0 );
