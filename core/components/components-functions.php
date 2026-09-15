<?php

/**
 * Load template for component.
 *
 * If fine not exists in theme/template-parts, we load it from theme/core/template-parts
 *
 * @param string $template_path The slug name for the generic template.
 * @param array $args The arguments to pass to the template.
 *
 * @return void
 */
function get_component_template( string $template_path, array $args = array() ): void {
	static $resolved = array();

	if ( ! isset( $resolved[ $template_path ] ) ) {
		$candidate = 'template-parts/' . $template_path;
		if ( ! locate_template( $candidate . '.php' ) ) {
			$candidate = 'core/' . $candidate;
			if ( ! locate_template( $candidate . '.php' ) ) {
				$candidate = '';
			}
		}

		$resolved[ $template_path ] = $candidate;
	}

	if ( '' === $resolved[ $template_path ] ) {
		return;
	}

	get_template_part(
		$resolved[ $template_path ],
		null,
		$args
	);
}

/**
 * Reset transient on request reset cache in W3 Total cache
 */
function ep_flush_all_transients(): void {
	global $wpdb;

	$wpdb->query(
		"DELETE FROM {$wpdb->options}
		WHERE option_name LIKE '\_transient\_%'
		   OR option_name LIKE '\_transient\_timeout\_%'
		   OR option_name LIKE '\_site\_transient\_%'
		   OR option_name LIKE '\_site\_transient\_timeout\_%'"
	);

	wp_cache_flush();
}

add_action( 'w3tc_flush_all', 'ep_flush_all_transients', 9999 );

/**
 * Disable wp-admin access for any instead administrator
 */
function restrict_broker_admin_access(): void {
	if ( ! is_user_logged_in() ) {
		return;
	}

	if ( wp_doing_ajax() ) {
		return;
	}

	$user = wp_get_current_user();
	if ( empty( $user->roles ) || ! in_array( 'administrator', (array) $user->roles, true ) ) {
		wp_safe_redirect( core_get_account_page_url() );
		exit;
	}
}

add_action( 'admin_init', 'restrict_broker_admin_access' );

function restrict_wp_login(): void {
	if ( str_contains( $_SERVER['REQUEST_URI'] ?? '', 'wp-login.php' ) ) {
		wp_safe_redirect( core_get_account_page_url() );
		exit;
	}
}

add_action( 'init', 'restrict_wp_login' );

$current_user = wp_get_current_user();
if ( ! $current_user || ! in_array( 'administrator', (array) $current_user->roles, true ) ) {
	add_filter( 'show_admin_bar', '__return_false' );
}

/**
 * Wrapper for home_url. Support polylang.
 *
 * @param string $path
 *
 * @return string
 */
function core_home_url( string $path = '' ): string {
	if ( function_exists( 'pll_home_url' ) ) {
		$path = ltrim( $path, '/' );

		return pll_home_url() . $path;
	}

	return home_url( $path );
}

/**
 * Keep the account area out of every shared cache.
 *
 * The account template serves personal broker data to signed-in visitors and a
 * form carrying a fresh nonce to everyone else, so a copy held by a proxy, a CDN
 * or another visitor's browser is either a leak or an expired nonce that breaks
 * logging in. Matching on the resolved template rather than on a slug keeps this
 * true for every translation of the page.
 *
 * @param string $template Template chosen by the template loader.
 *
 * @return string
 */
function core_never_cache_account( string $template ): string {
	if ( 'page-account.php' !== basename( $template ) ) {
		return $template;
	}

	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}

	nocache_headers();

	return $template;
}

add_filter( 'template_include', 'core_never_cache_account' );

/**
 * Run a callback once the response has been sent.
 *
 * Under PHP-FPM the connection is closed first with fastcgi_finish_request(), so
 * the visitor does not wait for the work. Without it (Apache mod_php on the local
 * stand) the callback simply runs at the end of the request.
 *
 * @param callable $callback Work to run.
 *
 * @return void
 */
function core_after_response( callable $callback ): void {
	static $queue = null;

	if ( null === $queue ) {
		$queue = array();

		add_action(
			'shutdown',
			static function () use ( &$queue ) {
				if ( empty( $queue ) ) {
					return;
				}

				if ( function_exists( 'fastcgi_finish_request' ) ) {
					fastcgi_finish_request();
				}

				$jobs  = $queue;
				$queue = array();

				foreach ( $jobs as $job ) {
					$job();
				}
			},
			PHP_INT_MAX
		);
	}

	$queue[] = $callback;
}

/**
 * A cached value that is refreshed without making a visitor wait for it.
 *
 * Replaces the get_transient() / set_transient() pairs with the same key and the
 * same lifetime, so data is exactly as fresh as before. What changes is the
 * moment the lifetime runs out: the stored value is still returned, and the new
 * one is built after the response and stored for the next request, instead of
 * that visitor waiting for the rebuild. With nothing stored at all the value is
 * built on the spot, as before.
 *
 * Options mirror the checks each call site used to make:
 *  - respect_dev: do not read in dev mode (the "! IS_DEV ?" checks);
 *  - keep_empty:  an empty stored value is a hit (the "false !==" checks) rather
 *                 than a miss (the "! empty()" checks).
 *
 * @param string    $key     Transient key.
 * @param callable  $build   Builds the value.
 * @param int       $ttl     Lifetime in seconds.
 * @param array     $options See above.
 * @param bool|null $hit     Set to true when the value came from storage.
 *
 * @return mixed
 */
function core_cache_remember( string $key, callable $build, int $ttl, array $options = array(), ?bool &$hit = null ) {
	$hit   = false;
	$entry = ( ! empty( $options['respect_dev'] ) && IS_DEV ) ? false : get_transient( $key );

	if (
		is_array( $entry )
		&& isset( $entry['core_cache'], $entry['expires'] )
		&& array_key_exists( 'value', $entry )
		&& ( ! empty( $options['keep_empty'] ) || ! empty( $entry['value'] ) )
	) {
		$hit = true;

		if ( (int) $entry['expires'] <= time() ) {
			core_after_response(
				static function () use ( $key, $build, $ttl ) {
					core_cache_rebuild( $key, $build, $ttl );
				}
			);
		}

		return $entry['value'];
	}

	$value = $build();
	core_cache_store( $key, $value, $ttl );

	return $value;
}

/**
 * Store a value for core_cache_remember().
 *
 * The transient itself lives for two lifetimes, so an expired value is still
 * there to be served while the next one is built.
 *
 * @param string $key   Transient key.
 * @param mixed  $value Value.
 * @param int    $ttl   Lifetime in seconds.
 *
 * @return void
 */
function core_cache_store( string $key, $value, int $ttl ): void {
	set_transient(
		$key,
		array(
			'core_cache' => 1,
			'expires'    => time() + $ttl,
			'value'      => $value,
		),
		2 * $ttl
	);
}

/**
 * Rebuild one expired value, at most once at a time per key.
 *
 * Concurrent requests that find the same expired value keep serving it rather
 * than all rebuilding it together.
 *
 * @param string   $key   Transient key.
 * @param callable $build Builds the value.
 * @param int      $ttl   Lifetime in seconds.
 *
 * @return void
 */
function core_cache_rebuild( string $key, callable $build, int $ttl ): void {
	$lock     = 'core_cache_lock_' . md5( $key );
	$external = wp_using_ext_object_cache();

	if ( $external ) {
		if ( ! wp_cache_add( $lock, 1, 'core_cache', 5 * MINUTE_IN_SECONDS ) ) {
			return;
		}
	} elseif ( ! add_option( $lock, time(), '', false ) ) {
		if ( (int) get_option( $lock ) > time() - 5 * MINUTE_IN_SECONDS ) {
			return;
		}

		update_option( $lock, time(), false );
	}

	try {
		core_cache_store( $key, $build(), $ttl );
	} finally {
		if ( $external ) {
			wp_cache_delete( $lock, 'core_cache' );
		} else {
			delete_option( $lock );
		}
	}
}
