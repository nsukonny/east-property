<?php
/**
 * The location tree: explicit kinds, depth, and the address policy per country.
 */

namespace EastProperty\Tests\Integration;

use EastProperty\Tests\TestCase;
use WP_Term;

/**
 * Covers what used to be inferred from the shape of the tree.
 */
class LocationHierarchyTest extends TestCase {

	/**
	 * Create a location of a given kind.
	 *
	 * @param string $slug   Term slug.
	 * @param string $type   Kind, empty to leave it unclassified.
	 * @param int    $parent Parent term id.
	 *
	 * @return int Term id.
	 */
	private function location( string $slug, string $type = '', int $parent = 0 ): int {
		$term = wp_insert_term(
			ucwords( str_replace( '-', ' ', $slug ) ),
			'location',
			array( 'slug' => $slug, 'parent' => $parent )
		);

		$term_id = (int) $term['term_id'];

		if ( '' !== $type ) {
			update_term_meta( $term_id, CORE_LOCATION_TYPE_META, $type );
		}

		core_location_flush_type_caches();

		return $term_id;
	}

	/**
	 * A project of one district, counted by the listing.
	 *
	 * @param array $slugs District slugs to attach.
	 *
	 * @return int Project id.
	 */
	private function project_in( array $slugs ): int {
		$project_id = $this->create_property( array( 'title' => 'Tower ' . implode( '-', $slugs ) ) );

		wp_set_object_terms( $project_id, $slugs, 'location' );
		update_post_meta( $project_id, 'units_count', '3' );

		return $project_id;
	}

	public function test_a_root_district_is_not_taken_for_a_country() {
		$term_id = $this->location( 'rogue-district' );

		$this->assertSame( '', core_location_type( $term_id ) );
		$this->assertFalse( core_location_is_country( $term_id ) );
		$this->assertNotContains( $term_id, core_country_term_ids() );
		$this->assertNotContains( 'rogue-district', core_read_country_slugs() );
	}

	public function test_a_country_is_recognised_by_its_kind() {
		$country = $this->location( 'oman', 'country' );

		$this->assertTrue( core_location_is_country( $country ) );
		$this->assertContains( $country, core_country_term_ids() );
		$this->assertContains( 'oman', core_read_country_slugs() );
	}

	public function test_a_country_cannot_sit_under_another_location() {
		$country = $this->location( 'oman', 'country' );

		$this->assertNotSame( '', core_location_type_conflict( 'country', $country ) );
		$this->assertSame( '', core_location_type_conflict( 'area', $country ) );
	}

	public function test_a_district_finds_its_country_through_a_city() {
		$country = $this->location( 'oman', 'country' );
		$city    = $this->location( 'muscat', 'city', $country );
		$area    = $this->location( 'al-mouj', 'area', $city );

		$this->assertSame( $country, core_location_country( $area ) );
		$this->assertSame( $country, core_location_country( $city ) );
		$this->assertSame( $country, core_location_country( $country ) );
	}

	public function test_a_branch_without_a_country_reports_none() {
		$root = $this->location( 'orphan-root' );
		$area = $this->location( 'orphan-area', 'area', $root );

		$this->assertSame( 0, core_location_country( $area ) );
		$this->assertSame( 0, core_location_country( $root ) );
	}

	public function test_the_subtree_covers_both_depths() {
		$country = $this->location( 'oman', 'country' );
		$city    = $this->location( 'muscat', 'city', $country );
		$area    = $this->location( 'al-mouj', 'area', $city );
		$flat    = $this->location( 'salalah', 'area', $country );

		$this->assertCount( 4, core_location_subtree_tt_ids( $country ) );
		$this->assertCount( 2, core_location_subtree_tt_ids( $city ) );
		$this->assertCount( 1, core_location_subtree_tt_ids( $area ) );
		$this->assertCount( 1, core_location_subtree_tt_ids( $flat ) );
	}

	public function test_a_city_and_a_country_find_projects_of_their_districts() {
		$country = $this->location( 'oman', 'country' );
		$city    = $this->location( 'muscat', 'city', $country );
		$this->location( 'al-mouj', 'area', $city );

		$this->project_in( array( 'al-mouj' ) );

		$this->assertTrue( core_location_has_projects( $country ) );
		$this->assertTrue( core_location_has_projects( $city ) );
	}

	public function test_an_empty_city_has_no_projects() {
		$country = $this->location( 'oman', 'country' );
		$city    = $this->location( 'muscat', 'city', $country );
		$this->location( 'empty-area', 'area', $city );

		$this->assertFalse( core_location_has_projects( $city ) );
		$this->assertFalse( core_location_has_projects( $country ) );
	}

	public function test_a_project_of_two_terms_of_one_branch_is_counted_once() {
		$country = $this->location( 'oman', 'country' );
		$city    = $this->location( 'muscat', 'city', $country );
		$this->location( 'al-mouj', 'area', $city );

		// Проект привязан и к городу, и к его району: под страну подходят оба.
		$this->project_in( array( 'muscat', 'al-mouj' ) );

		$_REQUEST['location'] = 'oman';
		$listing              = get_properties( 20, false, array( 'no_cache' => true ) );

		$this->assertSame( 1, (int) $listing['total'] );
		$this->assertCount( 1, $listing['items'] );
	}

	public function test_a_country_listing_reaches_a_district_under_a_city() {
		$country = $this->location( 'oman', 'country' );
		$city    = $this->location( 'muscat', 'city', $country );
		$this->location( 'al-mouj', 'area', $city );

		$project_id = $this->project_in( array( 'al-mouj' ) );

		$_REQUEST['location'] = 'oman';
		$listing              = get_properties( 20, false, array( 'no_cache' => true ) );

		$this->assertSame( array( $project_id ), $this->ids_of( $listing ) );
	}

	public function test_a_district_of_another_country_does_not_resolve() {
		$this->location( 'oman', 'country' );
		$uae = $this->location( 'uae', 'country' );
		$this->location( 'business-bay', 'area', $uae );

		$this->assertSame( 'business-bay', core_country_location_slug( 'business-bay', 'uae' ) );
		$this->assertSame( '', core_country_location_slug( 'business-bay', 'oman' ) );
	}

	public function test_a_country_without_a_prefix_keeps_its_addresses() {
		$uae = $this->location( 'uae', 'country' );
		update_term_meta( $uae, CORE_COUNTRY_URL_MODE_META, 'none' );
		$this->location( 'business-bay', 'area', $uae );

		$project_id = $this->project_in( array( 'business-bay' ) );

		$this->assertStringNotContainsString( '/uae/', get_permalink( $project_id ) );
		$this->assertStringContainsString( '/project/', get_permalink( $project_id ) );
	}

	public function test_adding_a_prefixed_country_leaves_the_other_alone() {
		$uae = $this->location( 'uae', 'country' );
		update_term_meta( $uae, CORE_COUNTRY_URL_MODE_META, 'none' );
		$this->location( 'business-bay', 'area', $uae );

		$project_id = $this->project_in( array( 'business-bay' ) );
		$before     = get_permalink( $project_id );

		$oman = $this->location( 'oman', 'country' );
		update_term_meta( $oman, CORE_COUNTRY_URL_MODE_META, 'prefix' );
		$this->location( 'al-mouj', 'area', $oman );
		core_location_flush_type_caches();

		$omani = $this->project_in( array( 'al-mouj' ) );

		$this->assertSame( $before, get_permalink( $project_id ) );
		$this->assertStringContainsString( '/oman/project/', get_permalink( $omani ) );
	}

	public function test_a_prefixed_country_carries_it_in_term_links() {
		$oman = $this->location( 'oman', 'country' );
		update_term_meta( $oman, CORE_COUNTRY_URL_MODE_META, 'prefix' );
		$city = $this->location( 'muscat', 'city', $oman );
		core_location_flush_type_caches();

		$this->assertStringContainsString( '/oman/', get_term_link( get_term( $oman, 'location' ) ) );
		$this->assertStringContainsString( '/oman/areas/muscat/', get_term_link( get_term( $city, 'location' ) ) );
	}

	public function test_the_district_filter_matches_only_its_own_branch() {
		$oman = $this->location( 'oman', 'country' );
		$city = $this->location( 'muscat', 'city', $oman );
		$this->location( 'al-mouj', 'area', $city );
		$this->location( 'salalah', 'area', $oman );

		$wanted = $this->project_in( array( 'al-mouj' ) );
		$this->project_in( array( 'salalah' ) );

		$_REQUEST['location'] = 'muscat';
		$listing              = get_properties( 20, false, array( 'no_cache' => true ) );

		$this->assertSame( array( $wanted ), $this->ids_of( $listing ) );
	}
}
