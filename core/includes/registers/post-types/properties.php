<?php

/**
 * Register Custom Post Type: Properties / Project
 */
function register_property_post_type(): void {
	$labels = array(
		'name'                     => _x( 'Properties', 'Post Type General Name' , 'east-property' ),
		'singular_name'            => _x( 'Property', 'Post Type Singular Name' , 'east-property' ),
		'menu_name'                => __( 'Properties' , 'east-property' ),
		'name_admin_bar'           => __( 'Property' , 'east-property' ),
		'archives'                 => __( 'Property Archives' , 'east-property' ),
		'attributes'               => __( 'Property Attributes' , 'east-property' ),
		'parent_item_colon'        => __( 'Parent Property:' , 'east-property' ),
		'all_items'                => __( 'All Properties' , 'east-property' ),
		'add_new_item'             => __( 'Add New Property' , 'east-property' ),
		'add_new'                  => __( 'Add New' , 'east-property' ),
		'new_item'                 => __( 'New Property' , 'east-property' ),
		'edit_item'                => __( 'Edit Property' , 'east-property' ),
		'update_item'              => __( 'Update Property' , 'east-property' ),
		'view_item'                => __( 'View Property' , 'east-property' ),
		'view_items'               => __( 'View Properties' , 'east-property' ),
		'search_items'             => __( 'Search Property' , 'east-property' ),
		'not_found'                => __( 'Not found' , 'east-property' ),
		'not_found_in_trash'       => __( 'Not found in Trash' , 'east-property' ),
		'featured_image'           => __( 'Featured Image' , 'east-property' ),
		'set_featured_image'       => __( 'Set featured image' , 'east-property' ),
		'remove_featured_image'    => __( 'Remove featured image' , 'east-property' ),
		'use_featured_image'       => __( 'Use as featured image' , 'east-property' ),
		'insighted_by_this_author' => __( 'Properties insighted by this author' , 'east-property' ),
		'all_insighted_items'      => __( 'All Properties' , 'east-property' ),
	);
	$args   = array(
		'label'               => __( 'Property' , 'east-property' ),
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
		'menu_position'       => 5,
		'menu_icon'           => 'dashicons-admin-home',
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'capability_type'     => 'post',
		'rewrite'             => array(
			'slug'       => 'project',
			'with_front' => false,
		),
		'has_archive'         => false,
	);
	register_post_type( 'property', $args );
}

add_action( 'init', 'register_property_post_type', 0 );

/**
 * Redirect from old /properties/%location%/%property%/ to /project/%property%/
 * and /off-plan/%property%/ and /secondary/%property%/ to /project/%property%/
 */
add_action(
	'template_redirect',
	static function () {
		if ( ! is_singular( 'property' ) ) {
			return;
		}

		$property_id = get_queried_object_id();
		if ( ! $property_id ) {
			return;
		}

		$is_old_property_url = get_query_var( 'is_old_property_url' );
		if ( 1 === (int) $is_old_property_url ) {
			wp_redirect( get_permalink( $property_id ), 301 );
			exit;
		}
	}
);

/**
 * Add support pagination for projects list page
 *
 * old url example http://eastproperty.local/properties/dubailand-residence-complex/samana-ivy-gardens/
 */
add_action(
	'init',
	static function () {
		add_rewrite_rule(
			'^properties/page-([0-9]+)/?$',
			'index.php?pagename=properties&cur_page=$matches[1]',
			'top'
		);

		add_rewrite_rule(
			'^projects/page-([0-9]+)/?$',
			'index.php?pagename=projects&cur_page=$matches[1]',
			'top'
		);

		//support old properties pages
		add_rewrite_rule(
			'^properties/([^/]+)/([^/]+)/?$',
			'index.php?post_type=property&location_slug=$matches[1]&name=$matches[2]&is_old_property_url=1',
			'top'
		);

		add_rewrite_tag( '%is_old_property_url%', '([^&]+)' );
	}
);

add_filter(
	'query_vars',
	static function ( $vars ) {
		$vars[] = 'is_old_property_url';

		return $vars;
	}
);

/**
 * Use single-project template instead of single-property
 */
add_filter(
	'single_template',
	function ( $template ) {
		if ( is_singular( 'property' ) ) {
			$custom_template = locate_template( 'single-project.php' );

			if ( $custom_template ) {
				return $custom_template;
			}
		}

		return $template;
	}
);
