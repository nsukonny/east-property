<?php
/**
 * Regression tests of the developer options of the unit filters.
 */

namespace EastProperty\Tests\Integration;

use EastProperty\Tests\TestCase;

/**
 * core_unit_filter_developers(): which developers the listing filters offer, once each, in the page language.
 *
 * Only the returned options are checked; see tests/README.md for how to benchmark the query itself.
 */
class UnitFilterDevelopersTest extends TestCase {

	/**
	 * A developer with several projects and units is offered once; options are sorted by label with entities decoded.
	 */
	public function test_lists_each_developer_once_sorted_by_label() {
		$zeta  = $this->create_developer( array( 'title' => 'Zeta Estates' ) );
		$alpha = $this->create_developer( array( 'title' => 'Alpha & Sons' ) );

		foreach ( array( $alpha, $alpha, $zeta ) as $developer ) {
			$project = $this->create_property( array( 'developer' => $developer ) );

			$this->create_units( $project, 'secondary', 3 );
		}

		$this->assertSame(
			array(
				array(
					'value' => (string) $alpha,
					'label' => 'Alpha & Sons',
				),
				array(
					'value' => (string) $zeta,
					'label' => 'Zeta Estates',
				),
			),
			$this->options()
		);
	}

	/**
	 * Developers whose projects hold only unpublished units, no units at all, or no projects are not offered.
	 */
	public function test_skips_developers_without_published_units() {
		$listed      = $this->create_developer();
		$only_drafts = $this->create_developer();
		$no_units    = $this->create_developer();
		$this->create_developer();

		$this->create_units( $this->create_property( array( 'developer' => $listed ) ), 'secondary' );
		$this->create_property( array( 'developer' => $no_units ) );

		$drafts_project = $this->create_property( array( 'developer' => $only_drafts ) );

		foreach ( array( 'draft', 'pending', 'private', 'trash' ) as $status ) {
			$this->create_unit(
				array(
					'listing_type' => 'secondary',
					'property'     => $drafts_project,
					'status'       => $status,
				)
			);
		}

		$this->assertSame( array( (string) $listed ), $this->option_values() );
	}

	/**
	 * Only units of the requested listing type count; `all` counts every type.
	 */
	public function test_respects_the_listing_type() {
		$secondary = $this->create_developer( array( 'title' => 'A Secondary' ) );
		$off_plan  = $this->create_developer( array( 'title' => 'B Off-plan' ) );

		$this->create_units( $this->create_property( array( 'developer' => $secondary ) ), 'secondary' );
		$this->create_units( $this->create_property( array( 'developer' => $off_plan ) ), 'off-plan' );

		$this->assertSame( array( (string) $secondary ), $this->option_values( 'en', 'secondary' ) );
		$this->assertSame( array( (string) $off_plan ), $this->option_values( 'en', 'off-plan' ) );
		$this->assertSame( array(), $this->option_values( 'en', 'distress' ) );
		$this->assertSame( array( (string) $secondary, (string) $off_plan ), $this->option_values( 'en', 'all' ) );
	}

	/**
	 * A draft project hides its developer, and a draft developer is never offered.
	 */
	public function test_ignores_unpublished_projects_and_developers() {
		$listed        = $this->create_developer();
		$draft_project = $this->create_developer();
		$draft         = $this->create_developer( array( 'status' => 'draft' ) );

		$this->create_units( $this->create_property( array( 'developer' => $listed ) ), 'secondary' );
		$this->create_units( $this->create_property( array( 'developer' => $draft ) ), 'secondary' );
		$this->create_units(
			$this->create_property(
				array(
					'developer' => $draft_project,
					'status'    => 'draft',
				)
			),
			'secondary'
		);

		$this->assertSame( array( (string) $listed ), $this->option_values() );
	}

	/**
	 * Units without a project and projects without a developer, or pointing at a deleted one, are skipped quietly.
	 */
	public function test_tolerates_missing_project_and_developer_relations() {
		$listed  = $this->create_developer();
		$deleted = $this->create_developer();

		$this->create_units( $this->create_property( array( 'developer' => $listed ) ), 'secondary' );
		$this->create_units( $this->create_property(), 'secondary' );
		$this->create_units( $this->create_property( array( 'developer' => $deleted ) ), 'secondary' );
		$this->create_unit( array( 'listing_type' => 'secondary' ) );

		wp_delete_post( $deleted, true );

		$this->assertSame( array( (string) $listed ), $this->option_values() );
	}

	/**
	 * In Russian the translation of the developer is offered, once, whether the unit points at the English or the Russian project.
	 */
	public function test_returns_the_developer_translation_for_the_requested_language() {
		$developer_en = $this->create_developer( array( 'title' => 'Emaar Properties' ) );
		$developer_ru = $this->create_developer(
			array(
				'title'    => 'Эмаар',
				'language' => 'ru',
			)
		);

		$this->link_translations(
			array(
				'en' => $developer_en,
				'ru' => $developer_ru,
			)
		);

		$project_en = $this->create_property( array( 'developer' => $developer_en ) );
		$project_ru = $this->create_property(
			array(
				'developer' => $developer_ru,
				'language'  => 'ru',
			)
		);

		$this->create_units( $project_en, 'secondary' );
		$this->create_units( $project_ru, 'secondary', 1, 'ru' );
		$this->create_units( $project_en, 'secondary', 1, 'ru' );

		$this->assertSame(
			array(
				array(
					'value' => (string) $developer_ru,
					'label' => 'Эмаар',
				),
			),
			$this->options( 'ru' )
		);
		$this->assertSame(
			array(
				array(
					'value' => (string) $developer_en,
					'label' => 'Emaar Properties',
				),
			),
			$this->options( 'en' )
		);
	}

	/**
	 * Without a published translation in the requested language the original developer is offered.
	 */
	public function test_keeps_the_original_developer_when_the_translation_is_missing() {
		$untranslated = $this->create_developer( array( 'title' => 'A Untranslated' ) );
		$draft_source = $this->create_developer( array( 'title' => 'B Draft Translation' ) );
		$draft_ru     = $this->create_developer(
			array(
				'language' => 'ru',
				'status'   => 'draft',
			)
		);

		$this->link_translations(
			array(
				'en' => $draft_source,
				'ru' => $draft_ru,
			)
		);

		$this->create_units( $this->create_property( array( 'developer' => $untranslated ) ), 'secondary', 1, 'ru' );
		$this->create_units( $this->create_property( array( 'developer' => $draft_source ) ), 'secondary', 1, 'ru' );

		$this->assertSame( array( (string) $untranslated, (string) $draft_source ), $this->option_values( 'ru' ) );
	}

	/**
	 * Only units in the requested language count; without a language every unit counts.
	 */
	public function test_counts_only_units_in_the_requested_language() {
		$english = $this->create_developer( array( 'title' => 'A English Units' ) );
		$russian = $this->create_developer( array( 'title' => 'B Russian Units' ) );

		$this->create_units( $this->create_property( array( 'developer' => $english ) ), 'secondary' );
		$this->create_units( $this->create_property( array( 'developer' => $russian ) ), 'secondary', 1, 'ru' );

		$this->assertSame( array( (string) $english ), $this->option_values( 'en' ) );
		$this->assertSame( array( (string) $russian ), $this->option_values( 'ru' ) );
		$this->assertSame( array( (string) $english, (string) $russian ), $this->option_values( '' ) );
	}

	/**
	 * Developer options of the unit filters.
	 *
	 * @param string $language     Language slug.
	 * @param string $listing_type Listing type.
	 *
	 * @return array
	 */
	private function options( string $language = 'en', string $listing_type = 'secondary' ): array {
		return core_unit_filter_developers( 'unit', $language, $listing_type );
	}

	/**
	 * Values of the developer options, in option order.
	 *
	 * @param string $language     Language slug.
	 * @param string $listing_type Listing type.
	 *
	 * @return string[]
	 */
	private function option_values( string $language = 'en', string $listing_type = 'secondary' ): array {
		return wp_list_pluck( $this->options( $language, $listing_type ), 'value' );
	}

	/**
	 * Published units of one listing type in a project.
	 *
	 * @param int    $project      Project ID.
	 * @param string $listing_type Listing type.
	 * @param int    $count        Number of units.
	 * @param string $language     Language slug.
	 *
	 * @return void
	 */
	private function create_units( int $project, string $listing_type, int $count = 1, string $language = 'en' ): void {
		for ( $i = 0; $i < $count; $i++ ) {
			$this->create_unit(
				array(
					'listing_type' => $listing_type,
					'property'     => $project,
					'language'     => $language,
				)
			);
		}
	}
}
