<?php
/**
 * Commenting is switched off site-wide.
 */

/**
 * Post type features that carry commenting.
 *
 * @return array
 */
function core_comment_post_type_supports(): array {
	return array( 'comments', 'trackbacks' );
}

/**
 * Take commenting away from every registered post type.
 *
 * Runs late on init, after the theme has registered its own post types.
 *
 * @return void
 */
function core_remove_comment_support(): void {
	foreach ( get_post_types() as $post_type ) {
		foreach ( core_comment_post_type_supports() as $support ) {
			if ( post_type_supports( $post_type, $support ) ) {
				remove_post_type_support( $post_type, $support );
			}
		}
	}
}

add_action( 'init', 'core_remove_comment_support', 100 );

/**
 * Report every discussion as closed.
 *
 * comments_open() gates the form, wp-comments-post.php and the REST controller
 * alike.
 */
add_filter( 'comments_open', '__return_false', 100 );
add_filter( 'pings_open', '__return_false', 100 );
add_filter( 'comments_array', '__return_empty_array', 100 );
add_filter( 'get_comments_number', '__return_zero', 100 );
add_filter( 'feed_links_show_comments_feed', '__return_false' );

/**
 * Status for records created from now on.
 *
 * @return string
 */
function core_closed_comment_status(): string {
	return 'closed';
}

add_filter( 'pre_option_default_comment_status', 'core_closed_comment_status' );
add_filter( 'pre_option_default_ping_status', 'core_closed_comment_status' );

/**
 * Drop the comment routes from the REST API.
 *
 * @param array $routes Registered REST routes.
 *
 * @return array
 */
function core_remove_comment_rest_routes( array $routes ): array {
	foreach ( array_keys( $routes ) as $route ) {
		if ( str_starts_with( (string) $route, '/wp/v2/comments' ) ) {
			unset( $routes[ $route ] );
		}
	}

	return $routes;
}

add_filter( 'rest_endpoints', 'core_remove_comment_rest_routes' );

/**
 * Remove the XML-RPC pingback methods, which bypass the comment form.
 *
 * @param array $methods Exposed XML-RPC methods.
 *
 * @return array
 */
function core_remove_pingback_methods( array $methods ): array {
	unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );

	return $methods;
}

add_filter( 'xmlrpc_methods', 'core_remove_pingback_methods' );

/**
 * Stop advertising the pingback endpoint in the response headers.
 *
 * @param array $headers Headers WordPress is about to send.
 *
 * @return array
 */
function core_remove_pingback_header( array $headers ): array {
	unset( $headers['X-Pingback'] );

	return $headers;
}

add_filter( 'wp_headers', 'core_remove_pingback_header' );

/**
 * Answer 404 for a comment feed.
 *
 * @return void
 */
function core_block_comment_feed(): void {
	if ( ! is_comment_feed() ) {
		return;
	}

	global $wp_query;

	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}

add_action( 'template_redirect', 'core_block_comment_feed' );

/**
 * Take the comment screens out of the dashboard menu.
 *
 * @return void
 */
function core_remove_comment_admin_menu(): void {
	remove_menu_page( 'edit-comments.php' );
	remove_submenu_page( 'options-general.php', 'options-discussion.php' );
}

add_action( 'admin_menu', 'core_remove_comment_admin_menu' );

/**
 * Take the comment bubble out of the admin bar.
 *
 * @param WP_Admin_Bar $admin_bar Admin bar instance.
 *
 * @return void
 */
function core_remove_comment_admin_bar_node( WP_Admin_Bar $admin_bar ): void {
	$admin_bar->remove_node( 'comments' );
}

add_action( 'admin_bar_menu', 'core_remove_comment_admin_bar_node', 100 );

/**
 * Send anyone who reaches a comment screen by its URL back to the dashboard.
 *
 * @return void
 */
function core_block_comment_admin_pages(): void {
	global $pagenow;

	$blocked = array( 'edit-comments.php', 'options-discussion.php' );

	if ( ! in_array( $pagenow, $blocked, true ) ) {
		return;
	}

	wp_safe_redirect( admin_url(), 302 );
	exit;
}

add_action( 'admin_init', 'core_block_comment_admin_pages' );
