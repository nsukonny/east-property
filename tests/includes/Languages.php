<?php
/**
 * Polylang languages of the test site.
 */

namespace EastProperty\Tests;

use PLL_Model;
use WP_Syntex\Polylang\Options\Options;
use WP_Syntex\Polylang\Options\Registry;

/**
 * English and Russian as on the live site, created once per run and kept for every test class.
 */
final class Languages {

	/**
	 * Polylang settings of the live site without keys and machine translation. Sync is off to keep fixtures explicit.
	 */
	private const SETTINGS = array(
		'force_lang'    => 1,
		'hide_default'  => true,
		'rewrite'       => true,
		'redirect_lang' => true,
		'browser'       => false,
		'media_support' => false,
		'post_types'    => array( 'property', 'unit', 'agency', 'developers' ),
		'taxonomies'    => array(),
		'sync'          => array(),
		'default_lang'  => 'en',
	);

	private const LANGUAGES = array(
		array(
			'name'           => 'English',
			'slug'           => 'en',
			'locale'         => 'en_US',
			'rtl'            => false,
			'term_group'     => 0,
			'no_default_cat' => true,
		),
		array(
			'name'           => 'Русский',
			'slug'           => 'ru',
			'locale'         => 'ru_RU',
			'rtl'            => false,
			'term_group'     => 1,
			'no_default_cat' => true,
		),
	);

	/**
	 * Rows of the language terms by table.
	 *
	 * @var array<string, array<int, array<string, mixed>>>
	 */
	private static array $rows = array();

	/**
	 * Store the settings and create the languages on a fresh test database.
	 *
	 * Runs on plugins_loaded ahead of Polylang, so the plugin boots in its frontend context and the theme registers its per-language routes.
	 *
	 * @return void
	 */
	public static function install(): void {
		global $wpdb;

		if ( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'language'" ) ) {
			return;
		}

		update_option( 'polylang', array_merge( self::SETTINGS, array( 'version' => POLYLANG_VERSION ) ) );

		add_action( 'pll_init_options_for_blog', array( Registry::class, 'register' ) );
		add_filter( 'doing_it_wrong_trigger_error', array( self::class, 'allow_early_language_list' ), 10, 2 );

		$model = new PLL_Model( new Options() );

		foreach ( self::LANGUAGES as $language ) {
			$result = $model->languages->add( $language );

			if ( is_wp_error( $result ) ) {
				fwrite( STDERR, 'East Property tests stopped: cannot create the language ' . $language['slug'] . ': ' . $result->get_error_message() . PHP_EOL );
				exit( 1 );
			}
		}

		remove_filter( 'doing_it_wrong_trigger_error', array( self::class, 'allow_early_language_list' ) );
	}

	/**
	 * Silence the notice Polylang raises when languages are listed before it boots, which install() does on purpose.
	 *
	 * @param bool   $trigger       Whether to trigger the notice.
	 * @param string $function_name Function the notice is about.
	 *
	 * @return bool
	 */
	public static function allow_early_language_list( $trigger, $function_name ) {
		return str_contains( (string) $function_name, 'Languages::get_list' ) ? false : $trigger;
	}

	/**
	 * Keep a copy of the language terms once WordPress has loaded.
	 *
	 * @return void
	 */
	public static function remember(): void {
		global $wpdb;

		$term_ids = implode( ',', array_map( 'intval', $wpdb->get_col( "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ( 'language', 'term_language' )" ) ) );

		foreach ( array( $wpdb->terms, $wpdb->term_taxonomy, $wpdb->termmeta ) as $table ) {
			self::$rows[ $table ] = $wpdb->get_results( "SELECT * FROM {$table} WHERE term_id IN ( {$term_ids} )", ARRAY_A );
		}
	}

	/**
	 * Put the language terms back with their original IDs.
	 *
	 * The test library deletes every term after each class; recreating the languages would change the IDs the theme keeps in static caches.
	 *
	 * @return void
	 */
	public static function restore(): void {
		global $wpdb;

		foreach ( self::$rows as $table => $rows ) {
			foreach ( $rows as $row ) {
				$wpdb->replace( $table, $row );
			}
		}

		PLL()->model->clean_languages_cache();
	}
}
