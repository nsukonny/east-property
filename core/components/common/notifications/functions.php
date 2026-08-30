<?php

/**
 * Redirect to show error messages
 *
 * @param $path
 * @param $code
 * @param string $why
 *
 * @return void
 */
function show_notify_error( $path, $code, string $why = '' ): void {
	show_notify( 'error', $path, $code, $why );
}

/**
 * Redirect to show error messages
 *
 * @param $path
 * @param $code
 * @param string $why
 *
 * @return void
 */
function show_notify_success( $path, $code, string $why = '' ): void {
	show_notify( 'success', $path, $code, $why );
}

/**
 * Redirect for show notify messages
 *
 * @param $status
 * @param $path
 * @param $code
 * @param string $why
 *
 * @return void
 */
function show_notify( $status, $path, $code, string $why = '' ): void {
	if ( session_status() !== PHP_SESSION_ACTIVE ) {
		session_start();
	}

	if ( ! isset( $_SESSION['core_notifications'] ) ) {
		$_SESSION['core_notifications'] = array();
	}

	if ( is_array( $code ) ) {
		foreach ( $code as $message ) {
			$_SESSION['core_notifications'][] = array(
				'status' => sanitize_key( (string) $status ),
				'why'    => sanitize_text_field( $message ),
			);
		}
	} else {
		$_SESSION['core_notifications'][] = array(
			'status' => sanitize_key( (string) $status ),
			'code'   => sanitize_key( (string) $code ),
			'why'    => sanitize_text_field( $why ),
		);
	}

	wp_safe_redirect( home_url( $path ) );
	exit;
}
