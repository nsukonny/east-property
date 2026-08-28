<?php

/**
 * Add new subscriber
 *
 * @return void
 */
function ajax_add_subscriber(): void {
	check_ajax_referer( 'get_filtered_properties' );

	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	if ( empty( $email ) || ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => __( 'Please provide a valid email address.' , 'east-property' ) ) );

		return;
	}

	$existing_query = new WP_Query(
		array(
			'post_type'              => 'subscriber',
			'post_status'            => array( 'publish', 'draft' ),
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'title'                  => $email,
		)
	);

	$existing_post = $existing_query->have_posts() ? (int) $existing_query->posts[0] : 0;
	wp_reset_postdata();
	if ( $existing_post ) {
		wp_send_json_error( array( 'message' => __( 'This email is already subscribed.' , 'east-property' ) ) );

		return;
	}

	$post_id = wp_insert_post(
		array(
			'post_title'  => $email,
			'post_type'   => 'subscriber',
			'post_status' => 'publish',
		)
	);

	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( array( 'message' => __( 'An error occurred while subscribing. Please try again later.' , 'east-property' ) ) );

		return;
	}

	wp_send_json_success( array( 'message' => __( 'Thank you for subscribing!' , 'east-property' ) ) );
}

add_action( 'wp_ajax_add_subscriber', 'ajax_add_subscriber' );
add_action( 'wp_ajax_nopriv_add_subscriber', 'ajax_add_subscriber' );
