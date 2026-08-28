<?php
/**
 * News module helper functions, optimized queries, caching, and AJAX handlers
 */

defined( 'ABSPATH' ) || exit;

const NEWS_PER_PAGE = 6;

/**
 * Get news query (Optimized)
 *
 * @param array $custom_args
 * @return WP_Query
 */
function core_get_news( array $custom_args = array() ): WP_Query {
	$paged = $custom_args['paged'] ?? pagination_get_current_page();

	$default_args = array(
		'post_type'              => 'post',
		'post_status'            => 'publish',
		'posts_per_page'         => NEWS_PER_PAGE,
		'paged'                  => max( 1, (int) $paged ),
		'orderby'                => 'date',
		'order'                  => 'DESC',
		'update_post_term_cache' => false,
		'update_post_meta_cache' => true,
		'cache_results'          => true,
		'no_found_rows'          => false,
	);

	$args = wp_parse_args( $custom_args, $default_args );

	$query = new WP_Query( $args );

	// Batch prime thumbnail caches in a single SQL query to prevent N+1 queries
	if ( function_exists( 'update_post_thumbnail_cache' ) && $query->have_posts() ) {
		update_post_thumbnail_cache( $query );
	}

	return $query;
}

/**
 * Get other news (excluding specific post ID)
 *
 * @param int $exclude_id
 * @param int $count
 * @return WP_Query
 */
function core_get_other_news( int $exclude_id = 0, int $count = 3 ): WP_Query {
	$args = array(
		'post_type'              => 'post',
		'post_status'            => 'publish',
		'posts_per_page'         => $count,
		'orderby'                => 'date',
		'order'                  => 'DESC',
		'update_post_term_cache' => false,
		'update_post_meta_cache' => true,
		'cache_results'          => true,
		'no_found_rows'          => true,
	);

	if ( $exclude_id > 0 ) {
		$args['post__not_in'] = array( $exclude_id );
	}

	$query = new WP_Query( $args );

	if ( function_exists( 'update_post_thumbnail_cache' ) && $query->have_posts() ) {
		update_post_thumbnail_cache( $query );
	}

	return $query;
}

/**
 * AJAX handler for loading more news with caching
 */
function ajax_load_more_news(): void {
	$page = isset( $_POST['page'] ) ? max( 1, (int) $_POST['page'] ) : 1;

	$cache_key = 'news_ajax_page_' . $page;
	$cached    = wp_cache_get( $cache_key, 'east_news' );

	if ( false !== $cached ) {
		wp_send_json_success( $cached );
	}

	$query = core_get_news( array(
		'paged' => $page,
	) );

	if ( ! $query->have_posts() ) {
		$response = array(
			'html'        => '',
			'has_more'    => false,
			'total_pages' => $query->max_num_pages,
			'page'        => $page,
		);
		wp_cache_set( $cache_key, $response, 'east_news', 3600 );
		wp_send_json_success( $response );
	}

	ob_start();
	while ( $query->have_posts() ) {
		$query->the_post();
		get_template_part( 'template-parts/cards/news-card', null, array(
			'post_id' => get_the_ID(),
		) );
	}
	wp_reset_postdata();
	$html = ob_get_clean();

	$has_more = ( $page < $query->max_num_pages );

	$response = array(
		'html'        => $html,
		'has_more'    => $has_more,
		'next_page'   => $page + 1,
		'total_pages' => $query->max_num_pages,
		'page'        => $page,
	);

	wp_cache_set( $cache_key, $response, 'east_news', 3600 );

	wp_send_json_success( $response );
}

add_action( 'wp_ajax_load_more_news', 'ajax_load_more_news' );
add_action( 'wp_ajax_nopriv_load_more_news', 'ajax_load_more_news' );

/**
 * Invalidate news cache on post save/delete
 *
 * @param int $post_id
 */
function core_invalidate_news_cache( int $post_id ): void {
	if ( 'post' !== get_post_type( $post_id ) ) {
		return;
	}

	wp_cache_flush_group( 'east_news' );
}

add_action( 'save_post', 'core_invalidate_news_cache' );
add_action( 'deleted_post', 'core_invalidate_news_cache' );

/**
 * Custom permalink for posts to be /news/%postname%/
 *
 * @param string $permalink
 * @param WP_Post $post
 * @return string
 */
function core_news_post_link( string $permalink, WP_Post $post ): string {
	if ( 'post' === $post->post_type ) {
		return core_home_url( '/news/' . $post->post_name . '/' );
	}

	return $permalink;
}

add_filter( 'post_link', 'core_news_post_link', 10, 2 );

/**
 * Register rewrite rules for news posts and pagination
 */
function core_register_news_pagination_rewrite(): void {
	add_rewrite_rule(
		'^news/page-([0-9]+)/?$',
		'index.php?pagename=news&cur_page=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^news/page/([0-9]+)/?$',
		'index.php?pagename=news&cur_page=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^news/([^/]+)/?$',
		'index.php?name=$matches[1]',
		'top'
	);
}

add_action( 'init', 'core_register_news_pagination_rewrite' );

