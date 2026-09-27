<?php
/**
 * Regression tests of the cached listing data.
 */

namespace EastProperty\Tests\Integration;

use EastProperty\Tests\SpyObjectCache;
use EastProperty\Tests\TestCase;

/**
 * The object cache must not change what the filters and listings return, and personal lists must stay out of it.
 *
 * get_search_tabs_data() and get_units() also keep results in a static variable for the whole PHP process, so every
 * test here asks for its own listing type or project and none of them compares two calls with the same arguments.
 */
class CacheTest extends TestCase {

	/**
	 * Object cache of the test run.
	 *
	 * @var object
	 */
	private $original_cache;

	/**
	 * Recording cache used during the test.
	 *
	 * @var SpyObjectCache
	 */
	private SpyObjectCache $cache;

	/**
	 * Swap in the recording cache.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->original_cache      = $GLOBALS['wp_object_cache'];
		$this->cache               = new SpyObjectCache();
		$GLOBALS['wp_object_cache'] = $this->cache;
	}

	/**
	 * Put the original cache back.
	 *
	 * @return void
	 */
	public function tear_down() {
		$GLOBALS['wp_object_cache'] = $this->original_cache;

		parent::tear_down();
	}

	/**
	 * On a cache miss the filter data equals an uncached build, and the copy written to the cache survives serialization intact.
	 */
	public function test_filter_data_equals_the_uncached_build_and_is_cached_intact() {
		$developer = $this->create_developer( array( 'title' => 'Emaar' ) );
		$project   = $this->create_property(
			array(
				'developer'     => $developer,
				'delivery_date' => ( (int) gmdate( 'Y' ) + 1 ) . '0630',
			)
		);

		$this->create_unit(
			array(
				'listing_type' => 'secondary',
				'property'     => $project,
				'price'        => 1500000,
				'area_size'    => 80,
			)
		);

		$built  = core_build_search_tabs_data( 'unit', 'en', 'secondary' );
		$served = get_search_tabs_data( 'unit', 'secondary' );
		$stored = $this->cache->writes['search_tabs_data'] ?? array();

		$this->assertContains( (string) $developer, wp_list_pluck( $built['filters']['developer']['options'], 'value' ) );
		$this->assertSame( $built, $served );
		$this->assertCount( 1, $stored );
		$this->assertSame( $served, unserialize( serialize( reset( $stored ) ) ) );
	}

	/**
	 * A warm cache entry is served as it is, without querying the listing again.
	 */
	public function test_warm_filter_data_is_served_without_rebuilding() {
		$unit = $this->create_unit(
			array(
				'listing_type' => 'distress',
				'price'        => 900000,
				'property'     => $this->create_property( array( 'developer' => $this->create_developer() ) ),
			)
		);
		$warm = core_build_search_tabs_data( 'unit', 'en', 'distress' );

		wp_update_post(
			array(
				'ID'          => $unit,
				'post_status' => 'draft',
			)
		);

		$this->assertSame( array(), core_build_search_tabs_data( 'unit', 'en', 'distress' ), 'Without published units there are no filters.' );

		$this->cache->preset( 'search_tabs_data', $warm );

		$this->assertSame( $warm, get_search_tabs_data( 'unit', 'distress' ) );
	}

	/**
	 * A cached units listing, its cache copy and the same listing built without cache are identical.
	 */
	public function test_units_listing_is_identical_with_and_without_cache() {
		$project = $this->create_property();

		foreach ( array( 3, 2, 1 ) as $score ) {
			$this->create_unit(
				array(
					'listing_type' => 'secondary',
					'property'     => $project,
					'boost_score'  => $score,
				)
			);
		}

		$cached   = get_units( 'secondary', 20, array( 'property_id' => $project ) );
		$stored   = $this->cache->writes['units'] ?? array();
		$uncached = get_units(
			'secondary',
			20,
			array(
				'property_id' => $project,
				'no_cache'    => true,
			)
		);

		$this->assertCount( 3, $cached['items'] );
		$this->assertSame( 3, $cached['total'] );
		$this->assertCount( 1, $stored );
		$this->assertSame( $cached, unserialize( serialize( reset( $stored ) ) ) );
		$this->assertSame( $cached, $uncached );
	}

	/**
	 * The account list of a broker's units neither reads nor writes the shared units cache, so a new unit shows at once.
	 */
	public function test_account_listing_bypasses_the_shared_units_cache() {
		$broker = self::factory()->user->create();
		$draft  = $this->create_unit(
			array(
				'author'   => $broker,
				'status'   => 'draft',
				'property' => $this->create_property(),
			)
		);
		$args   = array(
			'author_id' => $broker,
			'draft'     => true,
		);

		$account = get_units( '', 20, array_merge( $args, array( 'no_cache' => true ) ) );

		$this->assertSame( array( $draft ), $this->ids_of( $account ) );
		$this->assertArrayNotHasKey( 'units', $this->cache->reads );
		$this->assertArrayNotHasKey( 'units', $this->cache->writes );

		get_units( '', 20, $args );

		$this->assertCount( 1, $this->cache->writes['units'] ?? array(), 'The same list without no_cache goes to the cache.' );
	}

	/**
	 * Flushing W3 Total Cache also empties the object cache and deletes the transients.
	 */
	public function test_flush_all_empties_the_object_cache_and_transients() {
		wp_cache_set( 'probe', 'cached', 'search_tabs_data' );
		set_transient( 'ep_tests_probe', 'stored', HOUR_IN_SECONDS );

		do_action( 'w3tc_flush_all' );

		$this->assertFalse( wp_cache_get( 'probe', 'search_tabs_data' ) );
		$this->assertFalse( get_transient( 'ep_tests_probe' ) );
	}
}
