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
	$template_path = 'template-parts/' . $template_path;
	$template      = locate_template( $template_path . '.php' );
	if ( ! $template ) {
		$template_path = 'core/' . $template_path;
		$template      = locate_template( $template_path . '.php' );
	}

	if ( ! $template ) {
		return;
	}

	get_template_part(
		$template_path,
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