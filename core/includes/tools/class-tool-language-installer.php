<?php
/**
 * Registration of a Polylang language, safe to run again.
 *
 * @package east-property
 */

namespace Tools;

use PLL_Language;
use WP_Error;

/**
 * Adds a language to Polylang once and reports what a second run would change.
 */
final class Language_Installer {

	/**
	 * Settings of the languages the project opens through this tool.
	 *
	 * Polylang carries a table of its own in `src/settings/languages.php`; the
	 * entries below are the ones the project has decided on, so a run never
	 * depends on which defaults the plugin ships.
	 */
	public const PRESETS = array(
		'zh' => array(
			'slug'   => 'zh',
			'locale' => 'zh_CN',
			'name'   => '简体中文',
			'flag'   => 'cn',
			'rtl'    => false,
			'order'  => 40,
		),
		'hi' => array(
			'slug'   => 'hi',
			'locale' => 'hi_IN',
			'name'   => 'हिन्दी',
			'flag'   => 'in',
			'rtl'    => false,
			'order'  => 50,
		),
		'ar' => array(
			'slug'   => 'ar',
			'locale' => 'ar',
			'name'   => 'العربية',
			'flag'   => 'ae',
			'rtl'    => true,
			'order'  => 60,
		),
	);

	/**
	 * Settings of a language by slug, taken from the presets.
	 *
	 * @param string $slug Polylang slug.
	 *
	 * @return array Empty when the project has no preset for it.
	 */
	public static function preset( string $slug ): array {
		return self::PRESETS[ strtolower( $slug ) ] ?? array();
	}

	/**
	 * Language object Polylang holds for a slug.
	 *
	 * @param string $slug Polylang slug.
	 *
	 * @return PLL_Language|null
	 */
	public static function existing( string $slug ): ?PLL_Language {
		if ( ! function_exists( 'PLL' ) || empty( PLL()->model ) ) {
			return null;
		}

		foreach ( PLL()->model->get_languages_list() as $language ) {
			if ( $language->slug === $slug ) {
				return $language;
			}
		}

		return null;
	}

	/**
	 * Language object holding a locale, whatever its slug.
	 *
	 * @param string $locale WordPress locale.
	 *
	 * @return PLL_Language|null
	 */
	public static function existing_by_locale( string $locale ): ?PLL_Language {
		if ( ! function_exists( 'PLL' ) || empty( PLL()->model ) ) {
			return null;
		}

		foreach ( PLL()->model->get_languages_list() as $language ) {
			if ( $language->locale === $locale ) {
				return $language;
			}
		}

		return null;
	}

	/**
	 * What a run would do, without touching anything.
	 *
	 * The action is one of: create, update, none, conflict. A conflict is a
	 * locale already taken by another slug — adding the language anyway would
	 * leave the site with two of them, so the caller is expected to stop.
	 *
	 * @param array $settings Keys slug, locale, name, flag, rtl, order.
	 *
	 * @return array Keys action, language, differences, message.
	 */
	public static function plan( array $settings ): array {
		$slug   = (string) ( $settings['slug'] ?? '' );
		$locale = (string) ( $settings['locale'] ?? '' );

		if ( '' === $slug || '' === $locale ) {
			return array(
				'action'      => 'conflict',
				'language'    => null,
				'differences' => array(),
				'message'     => 'slug and locale are required',
			);
		}

		$by_locale = self::existing_by_locale( $locale );

		if ( null !== $by_locale && $by_locale->slug !== $slug ) {
			return array(
				'action'      => 'conflict',
				'language'    => $by_locale,
				'differences' => array(),
				'message'     => sprintf(
					'locale %s already belongs to the language %s',
					$locale,
					$by_locale->slug
				),
			);
		}

		$language = self::existing( $slug );

		if ( null === $language ) {
			return array(
				'action'      => 'create',
				'language'    => null,
				'differences' => array(),
				'message'     => sprintf( 'language %s would be created', $slug ),
			);
		}

		$differences = self::differences( $language, $settings );

		return array(
			'action'      => empty( $differences ) ? 'none' : 'update',
			'language'    => $language,
			'differences' => $differences,
			'message'     => empty( $differences )
				? sprintf( 'language %s is already configured', $slug )
				: sprintf( 'language %s exists with other settings', $slug ),
		);
	}

	/**
	 * Settings of an existing language that differ from the wanted ones.
	 *
	 * @param PLL_Language $language Language Polylang holds.
	 * @param array        $settings Wanted settings.
	 *
	 * @return array<string, array{have: string, want: string}>
	 */
	public static function differences( PLL_Language $language, array $settings ): array {
		$have = array(
			'locale' => (string) $language->locale,
			'name'   => (string) $language->name,
			'flag'   => (string) ( $language->flag_code ?? '' ),
			'rtl'    => $language->is_rtl ? '1' : '0',
			'order'  => (string) (int) $language->term_group,
		);

		$want = array(
			'locale' => (string) ( $settings['locale'] ?? $have['locale'] ),
			'name'   => (string) ( $settings['name'] ?? $have['name'] ),
			'flag'   => (string) ( $settings['flag'] ?? $have['flag'] ),
			'rtl'    => ! empty( $settings['rtl'] ) ? '1' : '0',
			'order'  => (string) (int) ( $settings['order'] ?? $have['order'] ),
		);

		$differences = array();

		foreach ( $want as $key => $value ) {
			if ( $have[ $key ] !== $value ) {
				$differences[ $key ] = array(
					'have' => $have[ $key ],
					'want' => $value,
				);
			}
		}

		return $differences;
	}

	/**
	 * Create the language, or bring an existing one in line.
	 *
	 * Polylang flushes the rewrite rules itself on a successful add; an update
	 * of the name or the order does not change them.
	 *
	 * @param array $settings Keys slug, locale, name, flag, rtl, order.
	 * @param bool  $update   Whether an existing language may be changed.
	 *
	 * @return array|WP_Error Keys action, language.
	 */
	public static function apply( array $settings, bool $update = false ) {
		if ( ! function_exists( 'PLL' ) || empty( PLL()->model->languages ) ) {
			return new WP_Error( 'ep_no_polylang', 'Polylang is not available' );
		}

		$plan = self::plan( $settings );

		if ( 'conflict' === $plan['action'] ) {
			return new WP_Error( 'ep_language_conflict', $plan['message'] );
		}

		if ( 'none' === $plan['action'] ) {
			return array(
				'action'   => 'none',
				'language' => $plan['language'],
			);
		}

		$args = array(
			'slug'       => (string) $settings['slug'],
			'locale'     => (string) $settings['locale'],
			'name'       => (string) $settings['name'],
			'rtl'        => ! empty( $settings['rtl'] ),
			'term_group' => (int) ( $settings['order'] ?? 0 ),
		);

		if ( ! empty( $settings['flag'] ) ) {
			$args['flag'] = (string) $settings['flag'];
		}

		if ( 'update' === $plan['action'] ) {
			if ( ! $update ) {
				return new WP_Error( 'ep_language_differs', $plan['message'] );
			}

			$args['lang_id'] = (int) $plan['language']->term_id;
			$result          = PLL()->model->languages->update( $args );
		} else {
			$result = PLL()->model->languages->add( $args );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		PLL()->model->clean_languages_cache();

		return array(
			'action'   => $plan['action'],
			'language' => self::existing( (string) $settings['slug'] ),
		);
	}
}
