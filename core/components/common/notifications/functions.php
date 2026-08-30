<?php

use JetBrains\PhpStorm\NoReturn;

/**
 * Redirect to show error messages
 *
 * @param $path
 * @param $code
 * @param string $why
 *
 * @return void
 */
#[NoReturn]
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
#[NoReturn]
function show_notify( $status, $path, $code, string $why = '' ): void {
	if ( core_notifications_session( true ) ) {
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

		session_write_close();
	}

	wp_safe_redirect( home_url( $path ) );
	exit;
}

/**
 * Open the notifications session.
 *
 * Starting a session costs every visitor a PHPSESSID cookie and, through PHP's
 * default cache limiter, an Expires: 1981 / no-store header pair that forbids
 * browsers and proxies from keeping the document. Reads therefore only resume a
 * session the visitor already owns: with no cookie there can be no stored
 * notification, so there is nothing to look for. The limiter is cleared as well,
 * because pages that do carry a notification opt out of caching explicitly.
 *
 * @param bool $create Create a session for a visitor who has none.
 *
 * @return bool
 */
function core_notifications_session( bool $create = false ): bool {
	if ( PHP_SESSION_ACTIVE === session_status() ) {
		return true;
	}

	if ( PHP_SESSION_DISABLED === session_status() || headers_sent() ) {
		return false;
	}

	if ( ! $create && empty( $_COOKIE[ session_name() ] ) ) {
		return false;
	}

	session_cache_limiter( '' );

	return session_start();
}

/**
 * Take the pending notifications and let the session go.
 *
 * @return array
 */
function core_take_notifications(): array {
	if ( ! core_notifications_session() ) {
		return array();
	}

	$notifications = array();
	if ( ! empty( $_SESSION['core_notifications'] ) && is_array( $_SESSION['core_notifications'] ) ) {
		$notifications = $_SESSION['core_notifications'];
		unset( $_SESSION['core_notifications'] );
	}

	core_close_notifications_session();

	return $notifications;
}

/**
 * Close the session, dropping it outright once it carries nothing.
 *
 * A cookie left behind would reopen a session on every later request and put the
 * visitor back on the uncacheable path, so an emptied session is destroyed and
 * its cookie expired. The file lock is released either way: with the files
 * handler an open session serialises every concurrent request of one visitor.
 *
 * @return void
 */
function core_close_notifications_session(): void {
	if ( PHP_SESSION_ACTIVE !== session_status() ) {
		return;
	}

	if ( ! empty( $_SESSION ) ) {
		session_write_close();

		return;
	}

	$params = session_get_cookie_params();
	$name   = session_name();

	session_destroy();

	if ( headers_sent() ) {
		return;
	}

	setcookie(
		$name,
		'',
		array(
			'expires' => time() - YEAR_IN_SECONDS,
			'path' => $params['path'],
			'domain' => $params['domain'],
			'secure' => $params['secure'],
			'httponly' => $params['httponly'],
			'samesite' => $params['samesite'] ?? 'Lax',
		)
	);
	unset( $_COOKIE[ $name ] );
}

/**
 * Notifications collected for the current request.
 *
 * @param array|null $notifications Value to store, null to read the stored one.
 *
 * @return array
 */
function core_notifications( ?array $notifications = null ): array {
	static $stored = array();

	if ( null !== $notifications ) {
		$stored = $notifications;
	}

	return $stored;
}

/**
 * Pick the pending notifications up before the template renders.
 *
 * Late enough to sit behind every template_redirect that may still send the
 * visitor elsewhere, early enough to still decide about headers.
 *
 * @return void
 */
function core_notifications_bootstrap(): void {
	$notifications = core_take_notifications();
	if ( empty( $notifications ) ) {
		return;
	}

	core_notifications( $notifications );

	// The page now carries a message meant for one visitor: keep it out of every shared cache.
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}

	nocache_headers();
}

add_action( 'template_redirect', 'core_notifications_bootstrap', 99 );
