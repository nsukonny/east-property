<?php
/**
 * Methods for up security of our projects
 */

/**
 * Close users API for not authorized users
 */
add_filter( 'rest_pre_dispatch', function ( $result, $server, $request ) {
	$route = $request->get_route();

	if ( 0 === strpos( $route, '/wp/v2/users' ) && ! is_user_logged_in() ) {
		return new WP_Error(
			'rest_forbidden',
			__( 'Sorry, you are not allowed to access this resource.' , 'east-property' ),
			array( 'status' => 403 )
		);
	}

	return $result;
}, 10, 3 );