<?php
/**
 * Functions and definitions
 */

use Entities\Property;
use Entities\Unit;

define( 'THEME_PATH', get_template_directory() );
define( 'THEME_URL', get_template_directory_uri() );
$is_dev = defined( 'WP_ENVIRONMENT_TYPE' ) && 'local' === WP_ENVIRONMENT_TYPE;
$is_dev = isset( $_GET['reset'] ) && '1' === $_GET['reset'] ? true : $is_dev;
$is_dev = isset( $_GET['w3tc_note'] ) ? true : $is_dev;
define( 'IS_DEV', $is_dev );
define( 'IS_DISTRESS', false );
define( 'THEME_VERSION', IS_DEV ? time() : '1.0.31' );

const THEME_NAME          = 'east-property';
const PROJECT_NAME        = 'East Property';
const PROJECT_PHONE       = '+971566809684';
const WHATS_APP_LINK      = 'https://wa.me/971566809684';
const PROPERTIES_PER_PAGE = 20;
const SUPPORT_EMAIL       = 'support@eastproperty.com';

/**
 * Add theme menus
 *
 * @return void
 */
function add_menus(): void {
	register_nav_menus(
		array(
			'header_menu'             => __( 'Header', 'east-property' ),
			'footer_menu_popular'     => __( 'Footer | Popular Search', 'east-property' ),
			'footer_menu_discovery'   => __( 'Footer | Discovery', 'east-property' ),
			'footer_menu_quick_links' => __( 'Footer | Quick Links', 'east-property' ),
		)
	);
}

add_action( 'after_setup_theme', 'add_menus' );

/**
 * Adding theme styles
 *
 * @return void
 */
function add_theme_styles(): void {
	wp_register_style(
		THEME_NAME . '-style',
		THEME_URL . '/assets/css/styles.min.css',
		null,
		filemtime( THEME_PATH . '/assets/css/styles.min.css' ),
		false
	);

	wp_enqueue_style( THEME_NAME . '-style' );

	wp_register_script(
		THEME_NAME . '-app',
		THEME_URL . '/assets/js/main.min.js',
		array( 'wp-i18n' ),
		filemtime( THEME_PATH . '/assets/js/main.min.js' ),
		true
	);

	wp_enqueue_script( THEME_NAME . '-app' );

	wp_localize_script( THEME_NAME . '-app',
		'ajax_object',
		array(
			'ajax_url'    => admin_url( 'admin-ajax.php' ),
			'_ajax_nonce' => wp_create_nonce( 'get_filtered_properties' ),
		)
	);

	wp_set_script_translations(
		THEME_NAME . '-app',
		'east-property',
		get_template_directory() . '/languages'
	);
}

add_action( 'wp_enqueue_scripts', 'add_theme_styles' );

add_action( 'init', static function (): void {
	if ( function_exists( 'acf_add_options_page' ) ) {
		acf_add_options_page(
			array(
				'page_title' => __( 'Настройки', 'east-property' ) . ' ' . PROJECT_NAME,
				'menu_title' => __( 'Настройки', 'east-property' ) . ' ' . PROJECT_NAME,
				'menu_slug'  => 'theme-options',
				'capability' => 'edit_posts',
				'redirect'   => false,
			)
		);
	}
} );

add_action( 'after_setup_theme', function (): void {
	add_theme_support( 'post-thumbnails' );
} );

add_image_size( 'featured-card', 740, 480, true );
add_image_size( 'unit-card', 500, 394, true );

add_filter( 'woocommerce_enqueue_styles', '__return_false' );

add_action(
	'template_redirect',
	static function () {
		if ( is_page( 'register' ) ) {
			wp_redirect( core_home_url( '/account/?tab=register' ) );
			exit;
		}
	}
);

add_action( 'after_setup_theme', static function (): void {
	load_theme_textdomain( 'east-property', THEME_PATH . '/languages' );
} );

require_once 'core/includes/entities/load.php';
require_once 'core/includes/registers/acf/loader.php';
require_once 'core/includes/registers/post-types/loader.php';
require_once 'core/includes/registers/user-roles/loader.php';
require_once 'core/includes/localization.php';
require_once 'core/includes/tools/load.php';
require_once 'core/components/load-components.php';
require_once 'template-parts/template-parts.php';
