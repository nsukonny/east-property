<?php
/**
 * Functions and definitions
 */

use Entities\Property;

define( 'THEME_PATH', get_template_directory() );
define( 'THEME_URL', get_template_directory_uri() );
$is_dev = ( isset( $_SERVER['HTTP_HOST'] ) && str_ends_with( $_SERVER['HTTP_HOST'], '.local' ) );
$is_dev = isset( $_GET['reset'] ) && '1' === $_GET['reset'] ? true : $is_dev;
$is_dev = isset( $_GET['w3tc_note'] ) ? true : $is_dev;
define( 'IS_DEV', $is_dev );
define( 'IS_DISTRESS', false );
define( 'THEME_VERSION', IS_DEV ? time() : '1.0.31' );

const THEME_NAME          = 'east-property';
const PROJECT_NAME        = 'East Property';
const PROJECT_PHONE       = '+971585235351';
const WHATS_APP_LINK      = 'https://api.whatsapp.com/send/?phone=971585235351';
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
			'header_menu'             => __( 'Header' ),
			'footer_menu_popular'     => __( 'Footer | Popular Search' ),
			'footer_menu_discovery'   => __( 'Footer | Discovery' ),
			'footer_menu_quick_links' => __( 'Footer | Quick Links' ),
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

	wp_localize_script( THEME_NAME . '-app',
		'ajax_object',
		array(
			'ajax_url'    => admin_url( 'admin-ajax.php' ),
			'_ajax_nonce' => wp_create_nonce( 'get_filtered_properties' ),
		)
	);
}

add_action( 'wp_enqueue_scripts', 'add_theme_styles' );

add_action( 'init', static function (): void {
	if ( function_exists( 'acf_add_options_page' ) ) {
		acf_add_options_page(
			array(
				'page_title' => __( 'Настройки' ) . ' ' . PROJECT_NAME,
				'menu_title' => __( 'Настройки' ) . ' ' . PROJECT_NAME,
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

add_image_size( 'product-thumb', 143, 171, true );
add_image_size( 'featured-card', 740, 480, true );
add_image_size( 'unit-card', 500, 394, true );

add_filter( 'woocommerce_enqueue_styles', '__return_false' );

function add_google_analytics() {
	?>
	<!-- Google tag (gtag.js) -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-02BCJXDNFN"></script>
	<script>
		window.dataLayer = window.dataLayer || [];

		function gtag() {
			dataLayer.push(arguments);
		}

		gtag('js', new Date());

		gtag('config', 'G-02BCJXDNFN');
	</script>
	<?php
}

function add_yandex_metrica() {
	?>
	<!-- Yandex.Metrika counter -->
	<script type="text/javascript">
		(function (m, e, t, r, i, k, a) {
			m[i] = m[i] || function () {
				(m[i].a = m[i].a || []).push(arguments)
			};
			m[i].l = 1 * new Date();
			for (var j = 0; j < document.scripts.length; j++) {
				if (document.scripts[j].src === r) {
					return;
				}
			}
			k = e.createElement(t), a = e.getElementsByTagName(t)[0], k.async = 1, k.src = r, a.parentNode.insertBefore(k, a)
		})(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js?id=109545373', 'ym');

		ym(109545373, 'init', {
			ssr: true,
			webvisor: true,
			clickmap: true,
			ecommerce: "dataLayer",
			referrer: document.referrer,
			url: location.href,
			accurateTrackBounce: true,
			trackLinks: true
		});
	</script>
	<noscript>
		<div><img src="https://mc.yandex.ru/watch/109545373" style="position:absolute; left:-9999px;" alt=""/></div>
	</noscript>
	<!-- /Yandex.Metrika counter -->
	<?php
}

if ( ! IS_DEV ) {
	add_action( 'wp_head', 'add_google_analytics' );
	add_action( 'wp_head', 'add_yandex_metrica' );
}

add_action(
	'template_redirect',
	static function () {
		if ( is_page( 'register' ) ) {
			wp_redirect( home_url( '/account/?tab=register' ) );
			exit;
		}
	}
);

require_once 'core/includes/entities/load.php';
require_once 'core/includes/registers/acf/loader.php';
require_once 'core/includes/registers/post-types/loader.php';
require_once 'core/includes/registers/user-roles/loader.php';
require_once 'core/components/load-components.php';
require_once 'template-parts/template-parts.php';
