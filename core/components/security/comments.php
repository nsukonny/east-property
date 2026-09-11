<?php
/**
 * Commenting is switched off site-wide.
 *
 * Nothing ever rendered comments: the theme has no comments.php and never
 * calls comments_template(). The write paths, though, stayed open — WordPress
 * accepted POSTs to wp-comments-post.php and to /wp-json/wp/v2/comments for
 * every record whose comment_status was open, and locally that was all 4691
 * properties, 1006 units, 163 agencies, 1488 attachments and 3 posts. Three of
 * the theme's post types also declared comments support, so the Discussion box
 * and the Comments column were still in the dashboard.
 *
 * These are filters rather than stored options, so the state cannot drift:
 * turning commenting back on means dropping this file from
 * load-components.php, not ticking a checkbox. The stored statuses are closed
 * as well, so anything reading $post->comment_status directly agrees with the
 * filters.
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
 * Late on init, because the theme registers its own post types on init at the
 * default priority. Dropping the support is what removes the Discussion meta
 * box, the Comments column of the list tables and the quick-edit fields — all
 * three are gated on post_type_supports().
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
 * comments_open() is the one gate that matters: the comment form asks it, so
 * does wp_handle_comment_submission() behind wp-comments-post.php, and so does
 * WP_REST_Comments_Controller before a REST write. pings_open() does the same
 * for trackbacks. The two below it only matter if a template ever starts
 * listing comments.
 */
add_filter( 'comments_open', '__return_false', 100 );
add_filter( 'pings_open', '__return_false', 100 );
add_filter( 'comments_array', '__return_empty_array', 100 );
add_filter( 'get_comments_number', '__return_zero', 100 );

/**
 * Keep the comments feed out of <head>.
 */
add_filter( 'feed_links_show_comments_feed', '__return_false' );

/**
 * Status for records created from now on.
 *
 * Settings → Discussion is unreachable (see core_block_comment_admin_pages()),
 * so these two short-circuit the stored options instead of trusting them.
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
 * A write would already be refused, because the controller checks
 * comments_open(), but the collection stays readable and advertises itself in
 * the API index. Nothing on the site consumes it.
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
 * Remove the XML-RPC pingback methods.
 *
 * A pingback is a comment written by another site, and it arrives over XML-RPC
 * rather than over wp-comments-post.php, so closing the form does not stop it.
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
 * /comments/feed/ and the per-post feeds are routed by WordPress itself and
 * would otherwise return an empty but valid channel, which invites crawlers to
 * keep asking.
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
 * Hiding a menu item does not protect the page behind it, and both screens
 * stay fully functional otherwise — edit-comments.php can still approve and
 * reply, options-discussion.php can still reopen everything.
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
