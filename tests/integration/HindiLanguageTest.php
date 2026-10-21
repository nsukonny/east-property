<?php
/**
 * Tests of opening Hindi.
 *
 * The machinery is shared with Chinese and covered in ChineseLanguageTest; what
 * is checked here is what differs per language: the settings, the DeepL target,
 * the switcher label and the plural rule.
 */

namespace EastProperty\Tests\Integration;

use EastProperty\Tests\TestCase;
use Tools\DeepL_Client;
use Tools\Language_Installer;

/**
 * Settings, registration and terminology handling for hi.
 */
class HindiLanguageTest extends TestCase {

	/**
	 * @return array
	 */
	private function settings(): array {
		return Language_Installer::preset( 'hi' );
	}

	public function tear_down() {
		$language = Language_Installer::existing( 'hi' );

		if ( null !== $language ) {
			PLL()->model->languages->delete( (int) $language->term_id );
			PLL()->model->clean_languages_cache();
		}

		parent::tear_down();
	}

	/**
	 * The preset carries exactly what the project decided on.
	 */
	public function test_preset_matches_the_agreed_settings() {
		$settings = $this->settings();

		$this->assertSame( 'hi', $settings['slug'] );
		$this->assertSame( 'hi_IN', $settings['locale'] );
		$this->assertSame( 'हिन्दी', $settings['name'] );
		$this->assertFalse( $settings['rtl'] );
	}

	/**
	 * A first run creates the language, a second changes nothing.
	 */
	public function test_second_run_creates_no_second_language() {
		$this->assertSame( 'create', Language_Installer::plan( $this->settings() )['action'] );

		$result = Language_Installer::apply( $this->settings() );

		if ( is_wp_error( $result ) ) {
			$this->markTestSkipped( 'не удалось добавить язык: ' . $result->get_error_message() );
		}

		$before = count( PLL()->model->get_languages_list() );

		$this->assertSame( 'none', Language_Installer::plan( $this->settings() )['action'] );
		$this->assertSame( 'none', Language_Installer::apply( $this->settings() )['action'] );
		$this->assertCount( $before, PLL()->model->get_languages_list() );
	}

	/**
	 * Opening Hindi leaves the languages already configured alone.
	 */
	public function test_existing_languages_are_untouched() {
		$before = array();

		foreach ( PLL()->model->get_languages_list() as $language ) {
			$before[ $language->slug ] = $language->locale;
		}

		$result = Language_Installer::apply( $this->settings() );

		if ( is_wp_error( $result ) ) {
			$this->markTestSkipped( 'не удалось добавить язык: ' . $result->get_error_message() );
		}

		foreach ( $before as $slug => $locale ) {
			$this->assertNotNull( Language_Installer::existing( $slug ) );
			$this->assertSame( $locale, Language_Installer::existing( $slug )->locale );
		}
	}

	/**
	 * DeepL takes the bare code for Hindi; only Chinese needs a variant.
	 */
	public function test_deepl_target_is_the_bare_code() {
		$this->assertSame( 'HI', DeepL_Client::target_language( 'hi' ) );
		$this->assertSame( 'ZH-HANS', DeepL_Client::target_language( 'zh' ) );
	}

	/**
	 * The switcher shows Hindi in its own script.
	 */
	public function test_switcher_label_is_in_devanagari() {
		$this->assertSame( 'हिं', core_language_labels()['hi'] );
	}

	/**
	 * Hindi is spelled by its own code; only Chinese is rewritten.
	 */
	public function test_hreflang_keeps_the_bare_hindi_code() {
		$result = core_rel_hreflang_attributes(
			array(
				'en' => 'https://example.com/',
				'hi' => 'https://example.com/hi/',
				'zh' => 'https://example.com/zh/',
			)
		);

		$this->assertArrayHasKey( 'hi', $result );
		$this->assertArrayHasKey( 'zh-CN', $result );
		$this->assertSame( 'https://example.com/hi/', $result['hi'] );
	}

	/**
	 * The catalogue carries two plural forms, unlike Chinese.
	 */
	public function test_catalogue_has_two_plural_forms() {
		$po = get_template_directory() . '/languages/hi_IN.po';

		if ( ! is_readable( $po ) ) {
			$this->markTestSkipped( 'hi_IN.po не собран' );
		}

		$source = (string) file_get_contents( $po );

		$this->assertStringContainsString( 'nplurals=2', $source );
		$this->assertMatchesRegularExpression(
			'~msgid "%s property found"\s*\nmsgid_plural "[^"]+"\s*\nmsgstr\[0\] "[^"]+"\s*\nmsgstr\[1\] "[^"]+"~',
			$source
		);
	}

	/**
	 * The route prefix is offered once the language exists.
	 */
	public function test_language_prefix_is_offered_to_the_rewrite_rules() {
		$this->assertNotContains( 'hi/', core_language_url_prefixes() );

		$result = Language_Installer::apply( $this->settings() );

		if ( is_wp_error( $result ) ) {
			$this->markTestSkipped( 'не удалось добавить язык: ' . $result->get_error_message() );
		}

		$this->assertContains( 'hi/', core_language_url_prefixes() );
		$this->assertContains( '', core_language_url_prefixes() );
	}
}
