<?php
/**
 * WordPress test suite configuration.
 *
 * Settings come from environment variables, then tests/.env, then the defaults
 * below. The installer that drops and recreates the test tables loads this file
 * too, so the safety checks run before any table is touched: the database has to
 * be empty or hold the ep_tests_marker table this file creates in an empty one.
 * Tables keep the live `wp_` prefix, since some theme queries name them directly.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

$ep_tests_settings = ( static function (): array {
	$defaults = array(
		'WP_CORE_DIR'          => dirname( __DIR__, 4 ),
		'WP_TESTS_DB_NAME'     => 'eastproperty_tests',
		'WP_TESTS_DB_USER'     => 'root',
		'WP_TESTS_DB_PASSWORD' => '',
		'WP_TESTS_DB_HOST'     => 'localhost',
		'WP_TESTS_POLYLANG'    => '',
	);
	$file     = array();

	if ( is_readable( __DIR__ . '/.env' ) ) {
		foreach ( (array) file( __DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $line ) {
			$line = trim( $line );

			if ( '' === $line || '#' === $line[0] || ! str_contains( $line, '=' ) ) {
				continue;
			}

			list( $name, $value ) = array_map( 'trim', explode( '=', $line, 2 ) );

			$file[ $name ] = trim( $value, '"\'' );
		}
	}

	$settings = array();

	foreach ( $defaults as $name => $default ) {
		$value = getenv( $name );

		$settings[ $name ] = false !== $value && '' !== $value ? $value : ( $file[ $name ] ?? $default );
	}

	return $settings;
} )();

define( 'ABSPATH', rtrim( $ep_tests_settings['WP_CORE_DIR'], '/' ) . '/' );
define( 'DB_NAME', $ep_tests_settings['WP_TESTS_DB_NAME'] );
define( 'DB_USER', $ep_tests_settings['WP_TESTS_DB_USER'] );
define( 'DB_PASSWORD', $ep_tests_settings['WP_TESTS_DB_PASSWORD'] );
define( 'DB_HOST', $ep_tests_settings['WP_TESTS_DB_HOST'] );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );
define( 'EP_TESTS_POLYLANG', $ep_tests_settings['WP_TESTS_POLYLANG'] );

$table_prefix = 'wp_';

( static function (): void {
	$abort = static function ( string $message ): void {
		fwrite( STDERR, 'East Property tests stopped: ' . $message . PHP_EOL );
		exit( 1 );
	};

	if ( ! is_file( ABSPATH . 'wp-settings.php' ) ) {
		$abort( 'WordPress is not found in ' . ABSPATH . ', set WP_CORE_DIR.' );
	}

	if ( ! preg_match( '/test/i', DB_NAME ) ) {
		$abort( 'the database "' . DB_NAME . '" is not a test database, its name must contain "test".' );
	}

	if ( is_file( ABSPATH . 'wp-content/object-cache.php' ) ) {
		$abort( 'wp-content/object-cache.php is installed and the installer would flush that cache.' );
	}

	$db_dropin = ABSPATH . 'wp-content/db.php';

	if ( is_file( $db_dropin ) && ! str_contains( (string) file_get_contents( $db_dropin ), 'Query Monitor' ) ) {
		$abort( 'wp-content/db.php is a database drop-in that could send queries to another server.' );
	}

	list( $host, $extra ) = array_pad( explode( ':', DB_HOST, 2 ), 2, null );

	mysqli_report( MYSQLI_REPORT_OFF );

	$link = mysqli_init();

	if ( ! @mysqli_real_connect( $link, $host, DB_USER, DB_PASSWORD, DB_NAME, is_numeric( $extra ) ? (int) $extra : null, is_numeric( $extra ) ? null : $extra ) ) {
		$abort( 'cannot connect to the test database "' . DB_NAME . '" on ' . DB_HOST . ': ' . mysqli_connect_error() );
	}

	$result = mysqli_query( $link, 'SHOW TABLES' );
	$tables = $result ? array_column( mysqli_fetch_all( $result ), 0 ) : array();

	if ( array() === $tables ) {
		mysqli_query( $link, 'CREATE TABLE ep_tests_marker ( created_at DATETIME NOT NULL )' );
		mysqli_query( $link, 'INSERT INTO ep_tests_marker VALUES ( NOW() )' );
	} elseif ( ! in_array( 'ep_tests_marker', $tables, true ) ) {
		$abort( 'the database "' . DB_NAME . '" holds tables this suite did not create, so it is not a dedicated test database.' );
	}

	mysqli_close( $link );
} )();

define( 'AUTH_KEY', 'east-property-tests' );
define( 'SECURE_AUTH_KEY', 'east-property-tests' );
define( 'LOGGED_IN_KEY', 'east-property-tests' );
define( 'NONCE_KEY', 'east-property-tests' );
define( 'AUTH_SALT', 'east-property-tests' );
define( 'SECURE_AUTH_SALT', 'east-property-tests' );
define( 'LOGGED_IN_SALT', 'east-property-tests' );
define( 'NONCE_SALT', 'east-property-tests' );

define( 'WP_DEBUG', true );
define( 'WPMU_PLUGIN_DIR', __DIR__ . '/mu-plugins' );
define( 'QM_DISABLED', true );
define( 'WPLANG', '' );

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'East Property Tests' );
define( 'WP_PHP_BINARY', escapeshellarg( PHP_BINARY ) );
