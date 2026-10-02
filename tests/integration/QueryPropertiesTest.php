<?php
/**
 * Regression tests of the projects query behind the account.
 */

namespace EastProperty\Tests\Integration;

use EastProperty\Tests\TestCase;

/**
 * core_query_properties() as the account calls it: every status of one broker, with the listing filters skipped.
 */
class QueryPropertiesTest extends TestCase {

	/**
	 * Search, location and developer filters narrow a broker's projects even with the listing filters skipped.
	 */
	public function test_account_arguments_narrow_a_broker_projects() {
		$broker    = self::factory()->user->create();
		$developer = $this->create_developer();
		$marina    = $this->create_location( 'dubai-marina' );
		$jvc       = $this->create_location( 'jumeirah-village-circle' );
		$projects  = array(
			'heights'   => array( 'Marina Heights', 'draft', $developer, $marina ),
			'park'      => array( 'Marina Park', 'publish', $developer, $marina ),
			'elsewhere' => array( 'Circle Gardens', 'draft', $developer, $jvc ),
			'other_dev' => array( 'Marina Crown', 'draft', $this->create_developer(), $marina ),
		);

		foreach ( $projects as $role => list( $title, $status, $developer_id, $location ) ) {
			$projects[ $role ] = $this->create_property(
				array(
					'title'     => $title,
					'status'    => $status,
					'author'    => $broker,
					'developer' => $developer_id,
				)
			);

			wp_set_object_terms( $projects[ $role ], $location, 'location' );
			update_post_meta( $projects[ $role ], 'units_count', 1 );
		}

		$filters = array(
			'author_id'        => $broker,
			'draft'            => true,
			'location'         => $marina,
			'developer'        => (string) $developer,
			'current_language' => 'en',
		);

		$this->assertSame( array( $projects['heights'], $projects['park'] ), $this->listed( $filters ) );
		$this->assertSame( array( $projects['heights'] ), $this->listed( $filters + array( 'search' => 'heights' ) ) );
		$this->assertSame( array( $projects['heights'] ),
			$this->listed( array(
				'author_id'        => $broker,
				'draft'            => true,
				'search'           => (string) $projects['heights'],
				'current_language' => 'en',
			) ) );

		$_REQUEST['developer'] = (string) $developer;

		$this->assertCount( 4,
			$this->listed( array( 'author_id' => $broker, 'draft' => true, 'current_language' => 'en' ) ),
			'Request filters stay skipped.' );
	}

	/**
	 * Project IDs of an account projects query, in listing order.
	 *
	 * @param array $args Query arguments.
	 *
	 * @return int[]
	 */
	private function listed( array $args ): array {
		return $this->ids_of( core_query_properties( 20, true, $args ) );
	}
}
