<?php
/**
 * Tests of opening a new language for units and projects.
 */

namespace EastProperty\Tests\Integration;

use EastProperty\Tests\TestCase;
use Tools\Distress_Units_Importer;
use Tools\Language_Cloner;

/**
 * Locale resolution, selection, and what a copy carries over.
 */
class LanguageClonerTest extends TestCase {

	/**
	 * @return Language_Cloner
	 */
	private function cloner(): Language_Cloner {
		return new Language_Cloner();
	}

	/**
	 * A locale, a slug and an unknown value each resolve as they should.
	 */
	public function test_language_is_resolved_by_locale_and_by_slug() {
		$this->assertSame( 'ru', Language_Cloner::language( 'ru_RU' ) );
		$this->assertSame( 'ru', Language_Cloner::language( 'RU_ru' ) );
		$this->assertSame( 'ru', Language_Cloner::language( 'ru' ) );
		$this->assertSame( 'en', Language_Cloner::language( 'en_US' ) );
		$this->assertSame( '', Language_Cloner::language( 'de_DE' ) );
		$this->assertSame( '', Language_Cloner::language( '' ) );
	}

	/**
	 * The error message names the locales Polylang knows.
	 */
	public function test_known_languages_carry_slug_and_locale() {
		$this->assertSame( array( 'en (en_US)', 'ru (ru_RU)' ), Language_Cloner::known_languages() );
	}

	/**
	 * Only default-language posts without the target language are picked.
	 */
	public function test_find_takes_default_language_posts_without_the_target() {
		$lonely     = $this->create_unit( array( 'slug' => 'lonely-unit' ) );
		$translated = $this->create_unit( array( 'slug' => 'translated-unit' ) );
		$russian    = $this->create_unit(
			array(
				'slug'     => 'russian-unit',
				'language' => 'ru',
			)
		);

		$this->link_translations(
			array(
				'en' => $translated,
				'ru' => $russian,
			)
		);

		$found = $this->cloner()->find( 'ru', array( 'unit' ), array( 'publish' ), 50 );

		$this->assertContains( $lonely, $found );
		$this->assertNotContains( $translated, $found );
		$this->assertNotContains( $russian, $found );
	}

	/**
	 * find() narrows to the asked types and honours the limit.
	 */
	public function test_find_filters_by_type_and_respects_the_limit() {
		$unit    = $this->create_unit( array( 'slug' => 'lonely-unit' ) );
		$project = $this->create_property( array( 'slug' => 'lonely-project' ) );

		$only_projects = $this->cloner()->find( 'ru', array( 'property' ), array( 'publish' ), 50 );

		$this->assertContains( $project, $only_projects );
		$this->assertNotContains( $unit, $only_projects );

		$this->create_unit( array( 'slug' => 'second-lonely-unit' ) );

		$this->assertCount( 1, $this->cloner()->find( 'ru', array( 'unit' ), array( 'publish' ), 1 ) );
	}

	/**
	 * Everything that cannot be copied is refused by its own error code.
	 */
	public function test_refusals() {
		$unit    = $this->create_unit( array( 'slug' => 'lonely-unit' ) );
		$article = self::factory()->post->create( array( 'post_type' => 'post' ) );
		$russian = $this->create_unit(
			array(
				'slug'     => 'russian-unit',
				'language' => 'ru',
			)
		);

		$this->assertSame( 'clone_missing', $this->cloner()->plan( 99999999, 'ru' )->get_error_code() );
		$this->assertSame( 'clone_type', $this->cloner()->plan( $article, 'ru' )->get_error_code() );
		$this->assertSame( 'clone_language', $this->cloner()->plan( $unit, 'de' )->get_error_code() );
		$this->assertSame( 'clone_same_language', $this->cloner()->plan( $russian, 'ru' )->get_error_code() );

		$copy = $this->cloner()->clone_post( $unit, 'ru' );

		$this->assertIsInt( $copy );
		$this->assertSame( 'clone_exists', $this->cloner()->plan( $unit, 'ru' )->get_error_code() );
	}

	/**
	 * The copy carries the text, the language, the shared slug and the flag.
	 */
	public function test_copy_carries_text_language_slug_and_flag() {
		$unit = $this->create_unit(
			array(
				'title' => 'Studio in Marina',
				'slug'  => 'studio-in-marina',
			)
		);

		wp_update_post(
			array(
				'ID'           => $unit,
				'post_content' => '<p>A studio with a sea view.</p>',
				'post_excerpt' => 'A studio with a sea view.',
			)
		);

		$copy = $this->cloner()->clone_post( $unit, 'ru' );

		$this->assertIsInt( $copy );

		$source = get_post( $unit );
		$clone  = get_post( $copy );

		$this->assertSame( $source->post_title, $clone->post_title );
		$this->assertSame( $source->post_content, $clone->post_content );
		$this->assertSame( $source->post_excerpt, $clone->post_excerpt );
		$this->assertSame( $source->post_status, $clone->post_status );

		// Слаг берётся от источника, а не от заголовка: иначе кириллический
		// заголовок превратился бы в процентную кодировку в адресе.
		$this->assertStringStartsWith( $source->post_name, $clone->post_name );

		// Один слаг на все языки даёт модуль share-slug, он только в Polylang
		// Pro, который стоит на проде. Без него WordPress разводит переводы
		// суффиксом, и это не ошибка копии: CI гоняет набор на бесплатной
		// версии. Проверка та же, что в RewritesTest.
		if ( isset( PLL()->share_post_slug ) ) {
			$this->assertSame( $source->post_name, $clone->post_name );
		}

		$this->assertSame( 'ru', pll_get_post_language( $copy, 'slug' ) );
		$this->assertSame( $copy, (int) pll_get_post( $unit, 'ru' ) );
		$this->assertSame( $unit, (int) pll_get_post( $copy, 'en' ) );
	}

	/**
	 * The flag goes on the copy and stays off the source.
	 */
	public function test_flag_lands_on_the_copy_only() {
		$unit = $this->create_unit( array( 'slug' => 'lonely-unit' ) );
		$copy = $this->cloner()->clone_post( $unit, 'ru' );

		$this->assertSame(
			'1',
			(string) get_post_meta( $copy, Distress_Units_Importer::NEED_TRANSLATE_META, true )
		);
		$this->assertSame(
			'',
			(string) get_post_meta( $unit, Distress_Units_Importer::NEED_TRANSLATE_META, true )
		);
	}

	/**
	 * Custom fields and locations come across, Polylang's own metas do not.
	 */
	public function test_copy_carries_fields_and_terms() {
		$location = $this->create_location( 'business-bay' );
		$project  = $this->create_property( array( 'slug' => 'marina-heights' ) );
		$unit     = $this->create_unit(
			array(
				'slug'         => 'lonely-unit',
				'property'     => $project,
				'price'        => 1500000,
				'bedrooms'     => 2,
				'listing_type' => 'off-plan',
				'locations'    => array( $location ),
			)
		);

		update_post_meta( $unit, '_edit_lock', '123:1' );

		$copy = $this->cloner()->clone_post( $unit, 'ru' );

		$this->assertSame( '1500000', (string) get_post_meta( $copy, 'price', true ) );
		$this->assertSame( '2', (string) get_post_meta( $copy, 'bedrooms', true ) );
		$this->assertSame( 'off-plan', (string) get_post_meta( $copy, 'listing_type', true ) );
		$project_ru = (int) pll_get_post( $project, 'ru' );

		$this->assertGreaterThan( 0, $project_ru );
		$this->assertSame( (string) $project_ru, (string) get_post_meta( $copy, 'property', true ) );

		// Ключи полей ACF нужны копии, служебные метки редактора — нет.
		$this->assertNotSame( '', (string) get_post_meta( $copy, '_price', true ) );
		$this->assertSame( '', (string) get_post_meta( $copy, '_edit_lock', true ) );

		$this->assertSame(
			array( 'business-bay' ),
			wp_get_object_terms( $copy, 'location', array( 'fields' => 'slugs' ) )
		);
	}

	/**
	 * The copy can be given a status of its own, to stage it before publishing.
	 */
	public function test_copy_status_can_be_forced() {
		$unit = $this->create_unit( array( 'slug' => 'lonely-unit' ) );
		$copy = $this->cloner()->clone_post( $unit, 'ru', 'draft' );

		$this->assertSame( 'draft', get_post( $copy )->post_status );
		$this->assertSame( 'publish', get_post( $unit )->post_status );
	}

	/**
	 * Opening a third language keeps the links the group already had.
	 *
	 * Polylang's save_translations() deletes every language missing from the
	 * array it is given, so a copy must hand over the whole group rather than
	 * just the source and itself.
	 */
	public function test_third_language_keeps_the_existing_links() {
		$added = PLL()->model->languages->add(
			array(
				'name'           => 'Deutsch',
				'slug'           => 'de',
				'locale'         => 'de_DE',
				'rtl'            => false,
				'term_group'     => 2,
				'no_default_cat' => true,
			)
		);

		if ( is_wp_error( $added ) ) {
			$this->markTestSkipped( 'не удалось добавить язык: ' . $added->get_error_message() );
		}

		PLL()->model->clean_languages_cache();

		$english = $this->create_unit( array( 'slug' => 'studio-in-marina' ) );
		$russian = $this->create_unit(
			array(
				'slug'     => 'studio-in-marina-ru',
				'language' => 'ru',
			)
		);

		$this->link_translations(
			array(
				'en' => $english,
				'ru' => $russian,
			)
		);

		$copy = $this->cloner()->clone_post( $english, 'de' );

		$this->assertIsInt( $copy );

		$group = pll_get_post_translations( $english );

		$this->assertSame( $english, (int) ( $group['en'] ?? 0 ) );
		$this->assertSame( $russian, (int) ( $group['ru'] ?? 0 ), 'связь с русским переводом не должна рваться' );
		$this->assertSame( $copy, (int) ( $group['de'] ?? 0 ) );
		$this->assertSame( 'de', pll_get_post_language( $copy, 'slug' ) );

		PLL()->model->languages->delete( (int) PLL()->model->get_language( 'de' )->term_id );
		PLL()->model->clean_languages_cache();
	}

	/**
	 * Copying leaves the source post untouched.
	 */
	public function test_source_is_not_changed() {
		$unit   = $this->create_unit( array( 'slug' => 'lonely-unit' ) );
		$before = (array) get_post( $unit );
		$metas  = get_post_meta( $unit );

		$this->cloner()->clone_post( $unit, 'ru' );

		$after = (array) get_post( $unit );

		foreach ( array( 'post_title', 'post_content', 'post_excerpt', 'post_status', 'post_name' ) as $field ) {
			$this->assertSame( $before[ $field ], $after[ $field ], $field );
		}

		$this->assertSame( $metas, get_post_meta( $unit ) );
	}
}
