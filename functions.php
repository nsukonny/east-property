<?php
/**
 * Functions and definitions
 */

define( 'THEME_PATH', get_template_directory() );
define( 'THEME_URL', get_template_directory_uri() );
define( 'THEME_VERSION', time() ); //TODO change to version like 1.0.1
const THEME_NAME   = 'dutch-seeds';
const PROJECT_NAME = 'Dutch Seeds';

/**
 * Add theme menus
 *
 * @return void
 */
function add_menus(): void {
	register_nav_menus(
		array(
			'header_menu'           => __( 'Меню в шапке.' ),
			'header_menu_mobile'    => __( 'Мобильное меню в шапке.' ),
			'footer_menu'           => __( 'Меню в подвале. Навигация' ),
			'footer_menu_politics'  => __( 'Меню в подвале. Правовая информация' ),
			'footer_menu_copyright' => __( 'Меню в подвале. Копирайт' ),
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
		THEME_URL . '/assets/css/main.min.css',
		null,
		THEME_VERSION,
		false
	);

	wp_enqueue_style( THEME_NAME . '-style' );

	wp_register_script(
		THEME_NAME . '-app',
		THEME_URL . '/assets/js/main.min.js',
		null,
		THEME_VERSION,
		true
	);

	wp_enqueue_script( THEME_NAME . '-app' );

	wp_localize_script( THEME_NAME . '-app', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}

add_action( 'wp_enqueue_scripts', 'add_theme_styles' );

if ( function_exists( 'acf_add_options_page' ) ) {
	$option_page = acf_add_options_page(
		array(
			'page_title' => __( 'Настройки' ) . ' ' . PROJECT_NAME,
			'menu_title' => __( 'Настройки' ) . ' ' . PROJECT_NAME,
			'menu_slug'  => 'theme-options',
			'capability' => 'edit_posts',
			'redirect'   => false,
		)
	);
}

add_filter( 'show_admin_bar', '__return_false' );

add_image_size( PROJECT_NAME . '-product-thumb', 143, 171, true );

add_filter( 'woocommerce_enqueue_styles', '__return_false' );

require_once 'template-parts/template-parts.php';
