<?php
/**
 * PHPUnit bootstrap: WordPress on the test database with the theme, SCF and Polylang active.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

define( 'WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config.php' );

$ep_tests_library = (string) getenv( 'WP_PHPUNIT__DIR' );

require_once $ep_tests_library . '/includes/functions.php';
require_once __DIR__ . '/includes/Languages.php';

$GLOBALS['wp_tests_options'] = array(
	'template'            => 'east-property',
	'stylesheet'          => 'east-property',
	'permalink_structure' => '/%year%/%monthnum%/%day%/%postname%/',
);

tests_add_filter( 'enable_loading_object_cache_dropin', '__return_false' );

tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		$polylang = EP_TESTS_POLYLANG;

		if ( '' === $polylang ) {
			$polylang = is_file( WP_PLUGIN_DIR . '/polylang-pro/polylang.php' ) ? 'polylang-pro/polylang.php' : 'polylang/polylang.php';
		}

		require WP_PLUGIN_DIR . '/secure-custom-fields/secure-custom-fields.php';
		require WP_PLUGIN_DIR . '/' . $polylang;
	}
);

tests_add_filter( 'plugins_loaded', array( EastProperty\Tests\Languages::class, 'install' ), 0 );

require $ep_tests_library . '/includes/bootstrap.php';

require_once __DIR__ . '/includes/TestCase.php';
require_once __DIR__ . '/includes/SpyObjectCache.php';
require_once __DIR__ . '/includes/StoppedResponse.php';

EastProperty\Tests\Languages::remember();
