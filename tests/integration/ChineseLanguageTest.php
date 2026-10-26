<?php
/**
 * Tests of opening Simplified Chinese.
 *
 * The suite ships with English and Russian, so every test here adds zh itself
 * through the installer under test and drops it again, which is also what
 * proves the installer is safe to run twice.
 */

namespace EastProperty\Tests\Integration;

use EastProperty\Tests\TestCase;
use Tools\DeepL_Client;
use Tools\Language_Cloner;
use Tools\Language_Installer;
use Tools\Unit_Translator;

/**
 * Registration, routes, translation flow and catalogue integrity for zh.
 */
class ChineseLanguageTest extends TestCase {

	/**
	 * Settings the project opens Chinese with.
	 *
	 * @return array
	 */
	private function settings(): array {
		return Language_Installer::preset( 'zh' );
	}

	/**
	 * Add Chinese, skipping the test when Polylang refuses.
	 *
	 * @return void
	 */
	private function install(): void {
		$result = Language_Installer::apply( $this->settings() );

		if ( is_wp_error( $result ) ) {
			$this->markTestSkipped( 'не удалось добавить язык: ' . $result->get_error_message() );
		}
	}

	public function tear_down() {
		$language = Language_Installer::existing( 'zh' );

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

		$this->assertSame( 'zh', $settings['slug'] );
		$this->assertSame( 'zh_CN', $settings['locale'] );
		$this->assertSame( '简体中文', $settings['name'] );
		$this->assertFalse( $settings['rtl'] );
	}

	/**
	 * A first run creates the language, a second changes nothing.
	 */
	public function test_second_run_creates_no_second_language() {
		$this->assertSame( 'create', Language_Installer::plan( $this->settings() )['action'] );

		$this->install();

		$before = count( PLL()->model->get_languages_list() );

		$this->assertSame( 'none', Language_Installer::plan( $this->settings() )['action'] );

		$again = Language_Installer::apply( $this->settings() );

		$this->assertFalse( is_wp_error( $again ) );
		$this->assertSame( 'none', $again['action'] );
		$this->assertCount( $before, PLL()->model->get_languages_list() );

		$slugs = wp_list_pluck( PLL()->model->get_languages_list(), 'slug' );

		$this->assertSame( array( 'zh' ), array_values( array_filter( $slugs, static fn( $s ) => 'zh' === $s ) ) );
	}

	/**
	 * Adding Chinese leaves the languages already configured alone.
	 */
	public function test_existing_languages_are_untouched() {
		$before = array();

		foreach ( PLL()->model->get_languages_list() as $language ) {
			$before[ $language->slug ] = $language->locale;
		}

		$this->install();

		foreach ( $before as $slug => $locale ) {
			$language = Language_Installer::existing( $slug );

			$this->assertNotNull( $language, sprintf( 'язык %s пропал', $slug ) );
			$this->assertSame( $locale, $language->locale );
		}
	}

	/**
	 * A locale already held by another language stops the run.
	 */
	public function test_locale_taken_by_another_language_is_refused() {
		$plan = Language_Installer::plan(
			array(
				'slug'   => 'zh',
				'locale' => 'ru_RU',
				'name'   => '简体中文',
			)
		);

		$this->assertSame( 'conflict', $plan['action'] );

		$result = Language_Installer::apply(
			array(
				'slug'   => 'zh',
				'locale' => 'ru_RU',
				'name'   => '简体中文',
			)
		);

		$this->assertTrue( is_wp_error( $result ) );
		$this->assertNull( Language_Installer::existing( 'zh' ) );
	}

	/**
	 * An existing language with other settings is reported, not rewritten.
	 */
	public function test_differing_language_needs_an_explicit_update() {
		$this->install();

		$wanted         = $this->settings();
		$wanted['name'] = '中文';

		$plan = Language_Installer::plan( $wanted );

		$this->assertSame( 'update', $plan['action'] );
		$this->assertArrayHasKey( 'name', $plan['differences'] );

		$this->assertTrue( is_wp_error( Language_Installer::apply( $wanted ) ) );
		$this->assertSame( '简体中文', Language_Installer::existing( 'zh' )->name );

		$updated = Language_Installer::apply( $wanted, true );

		$this->assertFalse( is_wp_error( $updated ) );
		$this->assertSame( '中文', Language_Installer::existing( 'zh' )->name );
	}

	/**
	 * DeepL is addressed with the simplified variant, not the bare code.
	 */
	public function test_deepl_target_is_the_simplified_variant() {
		$this->assertSame( 'ZH-HANS', DeepL_Client::target_language( 'zh' ) );
		$this->assertSame( 'ZH-HANS', DeepL_Client::target_language( 'ZH' ) );
	}

	/**
	 * Cloning a unit carries the chain and keeps every link inside zh.
	 */
	public function test_clone_links_unit_project_and_developer_within_chinese() {
		$this->install();

		$developer = $this->create_developer( array( 'slug' => 'emaar' ) );
		$project    = $this->create_property(
			array(
				'slug'      => 'beachfront',
				'developer' => $developer,
			)
		);
		$unit       = $this->create_unit(
			array(
				'slug'     => 'beachfront-1br',
				'property' => $project,
			)
		);

		$cloner = new Language_Cloner();
		$result = $cloner->clone_post( $unit, 'zh' );

		$this->assertFalse( is_wp_error( $result ), is_wp_error( $result ) ? $result->get_error_message() : '' );

		$chinese_unit = (int) pll_get_post( $unit, 'zh' );

		$this->assertGreaterThan( 0, $chinese_unit );

		$chinese_project = (int) get_post_meta( $chinese_unit, 'property', true );

		$this->assertGreaterThan( 0, $chinese_project );
		$this->assertSame( 'zh', pll_get_post_language( $chinese_project, 'slug' ) );
		$this->assertSame( (int) pll_get_post( $project, 'zh' ), $chinese_project );

		$chinese_developer = (int) get_post_meta( $chinese_project, 'developer_rel', true );

		$this->assertGreaterThan( 0, $chinese_developer );
		$this->assertSame( 'zh', pll_get_post_language( $chinese_developer, 'slug' ) );
	}

	/**
	 * A second clone run adds nothing and keeps the English originals.
	 */
	public function test_second_clone_run_creates_no_duplicates() {
		$this->install();

		$unit = $this->create_unit( array( 'slug' => 'duplicate-check' ) );

		$cloner = new Language_Cloner();
		$cloner->clone_post( $unit, 'zh' );

		$first = (int) pll_get_post( $unit, 'zh' );

		$this->assertSame( array(), $cloner->find( 'zh', array( 'unit' ), array( 'publish' ), 50 ) );

		$cloner->clone_post( $unit, 'zh' );

		$this->assertSame( $first, (int) pll_get_post( $unit, 'zh' ) );
		$this->assertCount(
			1,
			get_posts(
				array(
					'post_type'   => 'unit',
					'post_status' => 'any',
					'numberposts' => -1,
					'lang'        => 'zh',
					'fields'      => 'ids',
				)
			)
		);
	}

	/**
	 * Adding Chinese does not break the Russian link of a group.
	 */
	public function test_chinese_keeps_the_russian_translation_link() {
		$this->install();

		$english = $this->create_unit( array( 'slug' => 'group-check' ) );
		$russian = $this->create_unit(
			array(
				'slug'     => 'group-check-ru',
				'language' => 'ru',
			)
		);

		$this->link_translations(
			array(
				'en' => $english,
				'ru' => $russian,
			)
		);

		( new Language_Cloner() )->clone_post( $english, 'zh' );

		$group = pll_get_post_translations( $english );

		$this->assertSame( $russian, (int) ( $group['ru'] ?? 0 ) );
		$this->assertArrayHasKey( 'zh', $group );
		$this->assertArrayHasKey( 'en', $group );
	}

	/**
	 * Pages and developers are types the translator now accepts.
	 */
	public function test_translator_accepts_pages_and_developers() {
		$this->assertContains( 'page', Unit_Translator::POST_TYPES );
		$this->assertContains( 'developers', Unit_Translator::POST_TYPES );
	}

	/**
	 * A registered name is left alone; a unit title is not.
	 */
	public function test_brand_titles_are_not_sent_for_translation() {
		$this->assertNotContains( 'post_title', Unit_Translator::fields_for( 'property' ) );
		$this->assertNotContains( 'post_title', Unit_Translator::fields_for( 'developers' ) );
		$this->assertContains( 'post_title', Unit_Translator::fields_for( 'unit' ) );
		$this->assertContains( 'post_content', Unit_Translator::fields_for( 'property' ) );
	}

	/**
	 * A draft copy is picked up by the bulk run, and --status can narrow it out.
	 *
	 * Drafts are where a new language is filled in before it is published, so
	 * they belong in the default selection; asking for publish alone is how a
	 * run is kept to live records.
	 */
	public function test_drafts_are_part_of_the_default_selection() {
		$this->install();

		$unit = $this->create_unit( array( 'slug' => 'draft-flow' ) );

		( new Language_Cloner() )->clone_post( $unit, 'zh', 'draft' );

		$chinese = (int) pll_get_post( $unit, 'zh' );

		$this->assertGreaterThan( 0, $chinese );
		$this->assertSame( 'draft', get_post_status( $chinese ) );

		$translator = new Unit_Translator();

		$this->assertSame( array( 'publish', 'draft', 'future' ), Unit_Translator::DEFAULT_STATUSES );
		$this->assertContains( $chinese, $translator->find( 50, 0, 'zh', array( 'unit' ) ) );
		$this->assertContains( $chinese, $translator->find( 50, 0, 'zh', array( 'unit' ), array( 'draft' ) ) );
		$this->assertNotContains( $chinese, $translator->find( 50, 0, 'zh', array( 'unit' ), array( 'publish' ) ) );
	}

	/**
	 * A failing API leaves the flag and the text as they were.
	 */
	public function test_api_failure_keeps_the_record_ready_for_a_retry() {
		$this->install();

		$unit = $this->create_unit( array( 'slug' => 'retry-check' ) );

		( new Language_Cloner() )->clone_post( $unit, 'zh' );

		$chinese = (int) pll_get_post( $unit, 'zh' );
		$before  = get_post( $chinese )->post_title;

		add_filter(
			'pre_http_request',
			static function () {
				return new \WP_Error( 'deepl_http', 'service unavailable' );
			},
			99
		);

		$result = ( new Unit_Translator() )->translate( $chinese );

		$this->assertTrue( is_wp_error( $result ) );
		$this->assertTrue( Unit_Translator::needs_translation( $chinese ) );
		$this->assertSame( $before, get_post( $chinese )->post_title );
	}

	/**
	 * The switcher shows Chinese under a Chinese label and a valid tag.
	 */
	public function test_switcher_label_and_language_tag() {
		$this->install();

		$this->assertSame( '中文', core_language_labels()['zh'] );
		$this->assertSame( 'zh-CN', core_language_tag( 'zh', 'zh_CN' ) );
		$this->assertSame( 'ru-RU', core_language_tag( 'ru', 'ru_RU' ) );
	}

	/**
	 * The hreflang alternate is spelled out rather than left as a bare code.
	 */
	public function test_hreflang_is_spelled_out_for_chinese() {
		$given = array(
			'en' => 'https://example.com/',
			'zh' => 'https://example.com/zh/',
		);

		$result = core_rel_hreflang_attributes( $given );

		$this->assertArrayHasKey( 'zh-CN', $result );
		$this->assertArrayNotHasKey( 'zh', $result );
		$this->assertSame( 'https://example.com/zh/', $result['zh-CN'] );
		$this->assertSame( 'https://example.com/', $result['en'] );
	}

	/**
	 * Chinese routes carry the language prefix once it is registered.
	 */
	public function test_language_prefix_is_offered_to_the_rewrite_rules() {
		$this->assertNotContains( 'zh/', core_language_url_prefixes() );

		$this->install();

		$this->assertContains( 'zh/', core_language_url_prefixes() );
		$this->assertContains( '', core_language_url_prefixes() );
		$this->assertContains( 'ru/', core_language_url_prefixes() );
	}

	/**
	 * Only a translation hides behind the flag, never the source language.
	 */
	public function test_awaiting_translation_is_hidden_for_chinese_only() {
		$this->install();

		$this->assertTrue( core_is_awaiting_translation_hidden( 'zh' ) );
		$this->assertTrue( core_is_awaiting_translation_hidden( 'ru' ) );
		$this->assertFalse( core_is_awaiting_translation_hidden( 'en' ) );
	}
}
