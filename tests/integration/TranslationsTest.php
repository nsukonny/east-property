<?php
/**
 * Regression tests of the Polylang relations between units, projects and their links.
 */

namespace EastProperty\Tests\Integration;

use EastProperty\Tests\TestCase;
use Entities\Unit;

/**
 * Unit links per language and the project a translation borrows from its siblings.
 */
class TranslationsTest extends TestCase {

	/**
	 * Links follow /property/{project}/{unit}/, carry /ru/ for Russian and drop the project segment when there is none.
	 */
	public function test_unit_links_carry_the_project_slug_and_the_language_prefix() {
		$fixture = $this->create_translated_unit();
		$orphan  = $this->create_unit( array( 'slug' => 'orphan-unit' ) );

		$this->assertSame( home_url( '/property/marina-heights/sea-view-studio/' ), get_permalink( $fixture['unit_en'] ) );
		$this->assertSame( home_url( '/property/orphan-unit/' ), get_permalink( $orphan ) );

		$this->switch_language( 'ru' );

		$this->assertSame( home_url( '/ru/property/marina-heights-ru/studiya-s-vidom/' ), get_permalink( $fixture['unit_ru'] ) );
	}

	/**
	 * A translation without its own project takes the sibling's project in its own language, in any page language.
	 */
	public function test_translation_without_a_project_borrows_the_sibling_project_in_its_language() {
		$fixture = $this->create_translated_unit( false );

		$this->assertSame( $fixture['project_ru'], ( new Unit( $fixture['unit_ru'] ) )->get_property()->get_id() );
		$this->assertSame( home_url( '/ru/property/marina-heights-ru/studiya-s-vidom/' ), get_permalink( $fixture['unit_ru'] ) );

		$this->switch_language( 'ru' );

		$this->assertSame( home_url( '/ru/property/marina-heights-ru/studiya-s-vidom/' ), get_permalink( $fixture['unit_ru'] ) );
	}

	/**
	 * When the sibling's project has no translation, the translation takes that project as it is.
	 */
	public function test_translation_without_a_project_falls_back_to_the_untranslated_sibling_project() {
		$project = $this->create_property( array( 'slug' => 'palm-residences' ) );
		$unit_en = $this->create_unit( array( 'property' => $project ) );
		$unit_ru = $this->create_unit(
			array(
				'slug'     => 'palma',
				'language' => 'ru',
			)
		);

		$this->link_translations(
			array(
				'en' => $unit_en,
				'ru' => $unit_ru,
			)
		);
		$this->switch_language( 'ru' );

		$this->assertSame( $project, ( new Unit( $unit_ru ) )->get_property()->get_id() );
		$this->assertSame( home_url( '/ru/property/palm-residences/palma/' ), get_permalink( $unit_ru ) );
	}

	/**
	 * A unit without translations loses its project segment when its link is built in another language, and the link then answers 301.
	 *
	 * ACF loads the project through WP_Query, which Polylang limits to the current language.
	 *
	 * @group known-issues
	 */
	public function test_link_built_in_another_language_loses_the_project_segment() {
		$project_ru = $this->create_property(
			array(
				'slug'     => 'marina-heights-ru',
				'language' => 'ru',
			)
		);
		$unit_ru    = $this->create_unit(
			array(
				'slug'     => 'studiya-s-vidom',
				'language' => 'ru',
				'property' => $project_ru,
			)
		);

		$this->assertSame( home_url( '/ru/property/studiya-s-vidom/' ), get_permalink( $unit_ru ) );
	}

	/**
	 * English and Russian project and unit, linked as translations.
	 *
	 * @param bool $russian_has_project Whether the Russian unit stores its own project.
	 *
	 * @return array<string, int> IDs by role.
	 */
	private function create_translated_unit( bool $russian_has_project = true ): array {
		$project_en = $this->create_property( array( 'slug' => 'marina-heights' ) );
		$project_ru = $this->create_property(
			array(
				'slug'     => 'marina-heights-ru',
				'language' => 'ru',
			)
		);
		$unit_en    = $this->create_unit(
			array(
				'slug'     => 'sea-view-studio',
				'property' => $project_en,
			)
		);
		$unit_ru    = $this->create_unit(
			array_merge(
				array(
					'slug'     => 'studiya-s-vidom',
					'language' => 'ru',
				),
				$russian_has_project ? array( 'property' => $project_ru ) : array()
			)
		);

		$this->link_translations(
			array(
				'en' => $project_en,
				'ru' => $project_ru,
			)
		);
		$this->link_translations(
			array(
				'en' => $unit_en,
				'ru' => $unit_ru,
			)
		);

		return compact( 'project_en', 'project_ru', 'unit_en', 'unit_ru' );
	}
}
