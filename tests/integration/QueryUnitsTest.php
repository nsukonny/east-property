<?php
/**
 * Regression tests of the units listing query.
 */

namespace EastProperty\Tests\Integration;

use EastProperty\Tests\TestCase;

/**
 * core_query_units(): statuses, filters, language, pagination and row shape of the listing pages.
 */
class QueryUnitsTest extends TestCase {

	/**
	 * Drafts, pending, private and trashed units stay out of the public listing.
	 */
	public function test_returns_only_published_units_by_default() {
		$published = $this->create_unit( array( 'listing_type' => 'secondary' ) );

		foreach ( array( 'draft', 'pending', 'private', 'trash' ) as $status ) {
			$this->create_unit(
				array(
					'listing_type' => 'secondary',
					'status'       => $status,
				)
			);
		}

		$this->assertSame( array( $published ), $this->listed( 'secondary' ) );
	}

	/**
	 * The account passes `draft` to show a broker every unit they own, and today that includes the trash.
	 *
	 * @group known-issues
	 */
	public function test_draft_argument_returns_units_in_every_status_including_trash() {
		$units = array();

		foreach ( array( 'publish', 'draft', 'pending', 'private', 'trash' ) as $status ) {
			$units[] = $this->create_unit(
				array(
					'listing_type' => 'secondary',
					'status'       => $status,
				)
			);
		}

		$this->assertEqualsCanonicalizing( $units, $this->listed( 'secondary', array( 'draft' => true ) ) );
	}

	/**
	 * The listing type argument filters units, `all` and empty lift the filter, and the request value wins over the argument.
	 */
	public function test_filters_by_listing_type_and_the_request_overrides_the_argument() {
		$secondary = $this->create_unit( array( 'listing_type' => 'secondary' ) );
		$off_plan  = $this->create_unit( array( 'listing_type' => 'off-plan' ) );
		$distress  = $this->create_unit( array( 'listing_type' => 'distress' ) );
		$untyped   = $this->create_unit();
		$every     = array( $secondary, $off_plan, $distress, $untyped );

		$this->assertSame( array( $secondary ), $this->listed( 'secondary' ) );
		$this->assertSame( array( $distress ), $this->listed( 'distress' ) );
		$this->assertEqualsCanonicalizing( $every, $this->listed( 'all' ) );
		$this->assertEqualsCanonicalizing( $every, $this->listed( '' ) );

		$_REQUEST['listing_type'] = 'off-plan';

		$this->assertSame( array( $off_plan ), $this->listed( 'secondary' ) );
	}

	/**
	 * A developer ID from any language matches the projects of all its Polylang translations.
	 */
	public function test_developer_filter_matches_the_developer_and_its_translations() {
		$developer_en = $this->create_developer();
		$developer_ru = $this->create_developer( array( 'language' => 'ru' ) );
		$other        = $this->create_developer( array( 'language' => 'ru' ) );

		$this->link_translations(
			array(
				'en' => $developer_en,
				'ru' => $developer_ru,
			)
		);

		$unit_en = $this->create_unit(
			array(
				'listing_type' => 'secondary',
				'property'     => $this->create_property( array( 'developer' => $developer_en ) ),
			)
		);
		$unit_ru = $this->create_unit(
			array(
				'listing_type' => 'secondary',
				'language'     => 'ru',
				'property'     => $this->create_property(
					array(
						'developer' => $developer_ru,
						'language'  => 'ru',
					)
				),
			)
		);
		$this->create_unit(
			array(
				'listing_type' => 'secondary',
				'language'     => 'ru',
				'property'     => $this->create_property(
					array(
						'developer' => $other,
						'language'  => 'ru',
					)
				),
			)
		);

		$_REQUEST['developer'] = (string) $developer_en;

		$this->assertSame( array( $unit_en ), $this->listed( 'secondary' ) );
		$this->assertSame( array( $unit_ru ), $this->listed( 'secondary', array(), 'ru' ) );
	}

	/**
	 * The location filter keeps units tagged with that location slug; `all` lifts it.
	 */
	public function test_location_filter_matches_the_location_slug() {
		$marina = $this->create_location( 'dubai-marina' );
		$jvc    = $this->create_location( 'jumeirah-village-circle' );

		$in_marina = $this->create_unit(
			array(
				'listing_type' => 'secondary',
				'locations'    => array( $marina ),
			)
		);
		$in_both   = $this->create_unit(
			array(
				'listing_type' => 'secondary',
				'locations'    => array( $marina, $jvc ),
			)
		);
		$in_jvc    = $this->create_unit(
			array(
				'listing_type' => 'secondary',
				'locations'    => array( $jvc ),
			)
		);
		$untagged  = $this->create_unit( array( 'listing_type' => 'secondary' ) );

		$_REQUEST['location'] = $marina;

		$this->assertEqualsCanonicalizing( array( $in_marina, $in_both ), $this->listed( 'secondary' ) );

		$_REQUEST['location'] = 'all';

		$this->assertEqualsCanonicalizing( array( $in_marina, $in_both, $in_jvc, $untagged ), $this->listed( 'secondary' ) );
	}

	/**
	 * Bedrooms take a comma separated list, and the `studio` option matches units with zero bedrooms.
	 */
	public function test_bedrooms_filter_accepts_a_list_and_matches_studio_as_zero() {
		$units = array();

		foreach ( array( 0, 1, 2, 3 ) as $bedrooms ) {
			$units[ $bedrooms ] = $this->create_unit(
				array(
					'listing_type' => 'secondary',
					'bedrooms'     => $bedrooms,
				)
			);
		}

		$_REQUEST['beds'] = '1,3';

		$this->assertEqualsCanonicalizing( array( $units[1], $units[3] ), $this->listed( 'secondary' ) );

		$_REQUEST['beds'] = 'studio';

		$this->assertSame( array( $units[0] ), $this->listed( 'secondary' ) );
	}

	/**
	 * Price bounds are inclusive, either bound works alone and a non-numeric bound is ignored.
	 */
	public function test_price_filter_bounds_are_inclusive() {
		$cheap = $this->create_unit(
			array(
				'listing_type' => 'secondary',
				'price'        => 500000,
			)
		);
		$mid   = $this->create_unit(
			array(
				'listing_type' => 'secondary',
				'price'        => 1000000,
			)
		);
		$high  = $this->create_unit(
			array(
				'listing_type' => 'secondary',
				'price'        => 2000000,
			)
		);
		$this->create_unit( array( 'listing_type' => 'secondary' ) );

		$_REQUEST['min_price'] = '1000000';
		$_REQUEST['max_price'] = '2000000';

		$this->assertEqualsCanonicalizing( array( $mid, $high ), $this->listed( 'secondary' ) );

		$_REQUEST['min_price'] = 'any';
		$_REQUEST['max_price'] = '1000000';

		$this->assertEqualsCanonicalizing( array( $cheap, $mid ), $this->listed( 'secondary' ) );
	}

	/**
	 * The year of the handover filter reads as "before" for off-plan and "after" for other listings; `ready` means already handed over.
	 */
	public function test_handover_filter_direction_depends_on_the_listing_type() {
		$year    = (int) gmdate( 'Y' ) + 2;
		$handed  = $this->create_off_plan_unit( gmdate( 'Ymd', strtotime( '-1 year' ) ) );
		$earlier = $this->create_off_plan_unit( ( $year - 1 ) . '0630' );
		$within  = $this->create_off_plan_unit( $year . '1231' );
		$later   = $this->create_off_plan_unit( ( $year + 1 ) . '0101' );
		$this->create_unit( array( 'listing_type' => 'off-plan' ) );

		$_REQUEST['available']    = (string) $year;
		$_REQUEST['listing_type'] = 'off-plan';

		$this->assertEqualsCanonicalizing( array( $handed, $earlier, $within ), $this->listed( 'off-plan' ), 'Off-plan: handover before the end of the year.' );

		$_REQUEST['listing_type'] = 'all';

		$this->assertEqualsCanonicalizing( array( $within, $later ), $this->listed( 'off-plan' ), 'Other listings: completion from the start of the year.' );

		$_REQUEST['available'] = 'ready';

		$this->assertSame( array( $handed ), $this->listed( 'off-plan' ) );
	}

	/**
	 * Units are listed in the requested Polylang language only, and an empty language lists nothing.
	 */
	public function test_returns_only_units_in_the_requested_language() {
		$english = $this->create_unit( array( 'listing_type' => 'secondary' ) );
		$russian = $this->create_unit(
			array(
				'listing_type' => 'secondary',
				'language'     => 'ru',
			)
		);

		$this->assertSame( array( $english ), $this->listed( 'secondary', array(), 'en' ) );
		$this->assertSame( array( $russian ), $this->listed( 'secondary', array(), 'ru' ) );
		$this->assertSame( array(), $this->listed( 'secondary', array(), '' ) );
	}

	/**
	 * Boosted units come first, pages follow the limit and every page reports the total of the whole listing.
	 */
	public function test_paginates_by_boost_score_and_reports_the_total() {
		$units = array();

		foreach ( array( 5, 4, 3, 2, 1 ) as $score ) {
			$units[] = $this->create_unit(
				array(
					'listing_type' => 'secondary',
					'boost_score'  => $score,
				)
			);
		}

		$first = core_query_units( 'secondary', self::query_args( array(), 'en', 2, 1 ) );
		$last  = core_query_units( 'secondary', self::query_args( array(), 'en', 2, 3 ) );
		$past  = core_query_units( 'secondary', self::query_args( array(), 'en', 2, 4 ) );

		$this->assertSame( array( $units[0], $units[1] ), $this->ids_of( $first ) );
		$this->assertSame( 5, $first['total'] );
		$this->assertSame( array( $units[4] ), $this->ids_of( $last ) );
		$this->assertSame( 5, $last['total'] );
		$this->assertSame( array(), $past['items'] );
		$this->assertSame( 0, $past['total'], 'A page past the end reports no total.' );
		$this->assertSame( $units, $this->listed( 'secondary' ), 'A limit of zero returns the whole listing.' );
	}

	/**
	 * A unit without a project and a project without developer or handover date still list, with empty project fields.
	 */
	public function test_units_with_missing_project_data_are_listed_with_empty_fields() {
		$bare_project = $this->create_property( array( 'title' => 'Bare Project' ) );
		$orphan       = $this->create_unit(
			array(
				'listing_type' => 'secondary',
				'boost_score'  => 2,
			)
		);
		$in_bare      = $this->create_unit(
			array(
				'listing_type' => 'secondary',
				'boost_score'  => 1,
				'property'     => $bare_project,
			)
		);

		$rows = core_query_units( 'secondary', self::query_args() )['items'];

		$this->assertSame( array( $orphan, $in_bare ), $this->ids_of( array( 'items' => $rows ) ) );
		$this->assertNull( $rows[0]['property_id'] );
		$this->assertNull( $rows[0]['property_name'] );
		$this->assertNull( $rows[0]['developer_id'] );
		$this->assertSame( (string) $bare_project, $rows[1]['property_id'] );
		$this->assertSame( 'Bare Project', $rows[1]['property_name'] );
		$this->assertNull( $rows[1]['developer_id'] );
		$this->assertNull( $rows[1]['delivery_date'] );
	}

	/**
	 * A published unit of a draft project stays in the listing, although the filters skip that project's developer.
	 *
	 * @group known-issues
	 */
	public function test_unit_of_an_unpublished_project_is_still_listed() {
		$project = $this->create_property(
			array(
				'status'    => 'draft',
				'developer' => $this->create_developer(),
			)
		);
		$unit    = $this->create_unit(
			array(
				'listing_type' => 'secondary',
				'property'     => $project,
			)
		);

		$rows = core_query_units( 'secondary', self::query_args() )['items'];

		$this->assertSame( array( $unit ), $this->ids_of( array( 'items' => $rows ) ) );
		$this->assertSame( (string) $project, $rows[0]['property_id'] );
	}

	/**
	 * Rows carry the fields the unit cards read, one row per unit even with several locations and translated developers.
	 */
	public function test_rows_carry_the_card_fields_without_duplicate_units() {
		$marina       = $this->create_location( 'dubai-marina' );
		$jvc          = $this->create_location( 'jumeirah-village-circle' );
		$developer_en = $this->create_developer( array( 'title' => 'Emaar' ) );
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

		$project = $this->create_property(
			array(
				'title'         => 'Marina Heights',
				'developer'     => $developer_en,
				'delivery_date' => '20281231',
			)
		);

		foreach ( array( 3, 2 ) as $score ) {
			$this->create_unit(
				array(
					'listing_type' => 'secondary',
					'property'     => $project,
					'locations'    => array( $marina, $jvc ),
					'bedrooms'     => 2,
					'price'        => 1500000,
					'boost_score'  => $score,
				)
			);
		}

		$_REQUEST['location']  = $marina;
		$_REQUEST['developer'] = (string) $developer_ru;

		$result = core_query_units( 'secondary', self::query_args() );
		$ids    = wp_list_pluck( $result['items'], 'ID' );

		$this->assertSame( 2, $result['total'] );
		$this->assertSame( $ids, array_values( array_unique( $ids ) ) );

		foreach ( $result['items'] as $row ) {
			$this->assertSame( array(), array_diff( self::card_fields(), array_keys( $row ) ), 'Missing card fields.' );
			$this->assertMatchesRegularExpression( '/^[1-9][0-9]*$/', $row['ID'] );
			$this->assertSame( (string) $project, $row['property_id'] );
			$this->assertSame( 'Marina Heights', $row['property_name'] );
			$this->assertSame( (string) $developer_en, $row['developer_id'] );
			$this->assertSame( 'Emaar', $row['developer_name'] );
			$this->assertSame( '20281231', $row['delivery_date'] );
			$this->assertSame( '2', $row['bedrooms'] );
			$this->assertSame( '1500000', $row['price'] );
			$this->assertSame( 'publish', $row['post_status'] );
		}
	}

	/**
	 * The importer stores the project type as a string, the ACF checkbox as a serialized array, and the filter matches the string only.
	 *
	 * @group known-issues
	 */
	public function test_property_type_filter_skips_projects_saved_through_the_checkbox() {
		$imported = $this->create_property();
		$edited   = $this->create_property( array( 'property_type' => array( 'villa' ) ) );

		update_post_meta( $imported, 'property_type', 'villa' );

		$from_import = $this->create_unit(
			array(
				'listing_type' => 'secondary',
				'property'     => $imported,
			)
		);
		$this->create_unit(
			array(
				'listing_type' => 'secondary',
				'property'     => $edited,
			)
		);

		$_REQUEST['property_type'] = 'villa';

		$this->assertSame( array( $from_import ), $this->listed( 'secondary' ) );
	}

	/**
	 * The account filters arrive as arguments: they narrow a broker's list and win over request parameters of the same name.
	 */
	public function test_account_arguments_narrow_a_broker_list() {
		$broker = self::factory()->user->create();
		$marina = $this->create_location( 'dubai-marina' );
		$jvc    = $this->create_location( 'jumeirah-village-circle' );
		$base   = array(
			'author'       => $broker,
			'status'       => 'draft',
			'listing_type' => 'secondary',
			'bedrooms'     => 2,
			'unit_type'    => 'villa',
			'locations'    => array( $marina ),
		);
		$target = $this->create_unit(
			array_merge(
				$base,
				array(
					'title'    => 'Sea view villa',
					'property' => $this->create_property( array( 'title' => 'Marina Heights' ) ),
				)
			)
		);

		$this->create_unit( array_merge( $base, array( 'unit_type' => 'apartment' ) ) );
		$this->create_unit( array_merge( $base, array( 'bedrooms' => 3 ) ) );
		$this->create_unit( array_merge( $base, array( 'locations' => array( $jvc ) ) ) );
		$this->create_unit( array_merge( $base, array( 'author' => self::factory()->user->create() ) ) );

		$_REQUEST['location'] = $jvc;

		$filters = array(
			'author_id' => $broker,
			'draft'     => true,
			'location'  => $marina,
			'beds'      => '2',
			'unit_type' => 'villa',
		);

		$this->assertSame( array( $target ), $this->listed( 'secondary', $filters ) );

		$_REQUEST = array();

		foreach ( array( 'sea view', 'Marina Heights', (string) $target ) as $search ) {
			$this->assertSame(
				array( $target ),
				$this->listed( '', array( 'author_id' => $broker, 'draft' => true, 'search' => $search ) ),
				"Search by \"{$search}\"."
			);
		}
	}

	/**
	 * Unit IDs of the whole listing, in listing order.
	 *
	 * @param string $listing_type Listing type argument.
	 * @param array  $args         Extra arguments.
	 * @param string $language     Language slug.
	 *
	 * @return int[]
	 */
	private function listed( string $listing_type, array $args = array(), string $language = 'en' ): array {
		return $this->ids_of( core_query_units( $listing_type, self::query_args( $args, $language ) ) );
	}

	/**
	 * Arguments of core_query_units() with the language, page and limit get_units() puts in them.
	 *
	 * @param array  $args     Extra arguments.
	 * @param string $language Language slug.
	 * @param int    $limit    Units per page, zero for the whole listing.
	 * @param int    $page     Page number.
	 *
	 * @return array
	 */
	private static function query_args( array $args = array(), string $language = 'en', int $limit = 0, int $page = 1 ): array {
		return array_merge(
			$args,
			array(
				'current_language' => $language,
				'current_page'     => $page,
				'limit'            => $limit,
			)
		);
	}

	/**
	 * Off-plan unit in its own project with the given handover date.
	 *
	 * @param string $delivery_date Handover date, Ymd.
	 *
	 * @return int Unit ID.
	 */
	private function create_off_plan_unit( string $delivery_date ): int {
		return $this->create_unit(
			array(
				'listing_type' => 'off-plan',
				'property'     => $this->create_property( array( 'delivery_date' => $delivery_date ) ),
			)
		);
	}

	/**
	 * Columns the listing cards and Unit::build_labels() read from a row.
	 *
	 * @return string[]
	 */
	private static function card_fields(): array {
		return array(
			'ID',
			'title',
			'post_status',
			'author_id',
			'broker_id',
			'broker_name',
			'property_id',
			'property_name',
			'delivery_date',
			'is_popular',
			'is_premium_developer',
			'developer_id',
			'developer_name',
			'bedrooms',
			'bathrooms',
			'area_size',
			'price',
			'original_price',
			'discount',
			'boost_score',
			'is_wait_user_actions',
		);
	}
}
