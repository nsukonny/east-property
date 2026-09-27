<?php
/**
 * Base class of the theme integration tests.
 */

namespace EastProperty\Tests;

use WP_UnitTestCase;

/**
 * Fixture builders for developers, projects and units, with request and language state reset around each test.
 */
abstract class TestCase extends WP_UnitTestCase {

	/**
	 * ACF keys of the unit fields, by fixture argument.
	 */
	private const UNIT_FIELDS = array(
		'property'     => 'field_694e9b3902bb4',
		'listing_type' => 'field_694ea5354ae1e',
		'price'        => 'field_694ea59e4ae20',
		'bedrooms'     => 'field_694ea5b34ae21',
		'area_size'    => 'field_694ea5d04ae23',
		'boost_score'  => 'field_69fdf42ff3d70',
	);

	/**
	 * ACF keys of the project fields, by fixture argument.
	 */
	private const PROPERTY_FIELDS = array(
		'developer'     => 'field_694ea6c0114e3',
		'delivery_date' => 'field_694ea7f2a1146',
		'property_type' => 'field_694ea826a1147',
	);

	/**
	 * Start every test with an empty request in the default language.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$_REQUEST = array();
		$this->switch_language( 'en' );
	}

	/**
	 * Leave no request data or language behind.
	 *
	 * @return void
	 */
	public function tear_down() {
		$_REQUEST = array();
		$this->switch_language( 'en' );

		parent::tear_down();
	}

	/**
	 * Bring back the languages the test library deletes with the rest of the terms.
	 *
	 * @return void
	 */
	public static function tear_down_after_class() {
		parent::tear_down_after_class();

		Languages::restore();
		self::commit_transaction();
	}

	/**
	 * Create a developer.
	 *
	 * @param array $args Optional `title`, `slug`, `status`, `language`.
	 *
	 * @return int Developer ID.
	 */
	protected function create_developer( array $args = array() ): int {
		return $this->create_post( 'developers', $args, array() );
	}

	/**
	 * Create a project.
	 *
	 * @param array $args Post arguments as in create_developer() plus `developer`, `delivery_date` (Ymd), `property_type`.
	 *
	 * @return int Project ID.
	 */
	protected function create_property( array $args = array() ): int {
		return $this->create_post( 'property', $args, self::PROPERTY_FIELDS );
	}

	/**
	 * Create a unit.
	 *
	 * @param array $args Post arguments as in create_developer() plus `author`, `property`, `listing_type`, `price`,
	 *                    `bedrooms`, `area_size`, `boost_score` and `locations` (location slugs).
	 *
	 * @return int Unit ID.
	 */
	protected function create_unit( array $args = array() ): int {
		$unit_id = $this->create_post( 'unit', $args, self::UNIT_FIELDS );

		if ( ! empty( $args['locations'] ) ) {
			wp_set_object_terms( $unit_id, $args['locations'], 'location' );
		}

		return $unit_id;
	}

	/**
	 * Create a location term.
	 *
	 * @param string $slug Location slug.
	 *
	 * @return string The slug.
	 */
	protected function create_location( string $slug ): string {
		self::factory()->term->create(
			array(
				'taxonomy' => 'location',
				'slug'     => $slug,
				'name'     => ucwords( str_replace( '-', ' ', $slug ) ),
			)
		);

		return $slug;
	}

	/**
	 * Link posts as Polylang translations of each other.
	 *
	 * @param array<string, int> $translations Post IDs by language slug.
	 *
	 * @return void
	 */
	protected function link_translations( array $translations ): void {
		pll_save_post_translations( $translations );
	}

	/**
	 * Make a language the current Polylang language.
	 *
	 * @param string $slug Language slug.
	 *
	 * @return void
	 */
	protected function switch_language( string $slug ): void {
		PLL()->curlang = PLL()->model->get_language( $slug );
	}

	/**
	 * Unit IDs of a core_query_units() result, in result order.
	 *
	 * @param array $result Result of core_query_units() or get_units().
	 *
	 * @return int[]
	 */
	protected function ids_of( array $result ): array {
		return array_map( 'intval', wp_list_pluck( $result['items'], 'ID' ) );
	}

	/**
	 * Insert a post and save its ACF fields the way a request in the post's language does.
	 *
	 * @param string $post_type Post type.
	 * @param array  $args      Fixture arguments.
	 * @param array  $fields    ACF keys by fixture argument.
	 *
	 * @return int Post ID.
	 */
	private function create_post( string $post_type, array $args, array $fields ): int {
		$language = $args['language'] ?? 'en';
		$current  = PLL()->curlang;
		$post     = array(
			'post_type'   => $post_type,
			'post_status' => $args['status'] ?? 'publish',
		);

		foreach ( array( 'title' => 'post_title', 'slug' => 'post_name', 'author' => 'post_author' ) as $arg => $column ) {
			if ( isset( $args[ $arg ] ) ) {
				$post[ $column ] = $args[ $arg ];
			}
		}

		$this->switch_language( $language );

		$post_id = self::factory()->post->create( $post );

		pll_set_post_language( $post_id, $language );

		foreach ( $fields as $arg => $field_key ) {
			if ( array_key_exists( $arg, $args ) ) {
				update_field( $field_key, $args[ $arg ], $post_id );
			}
		}

		PLL()->curlang = $current;

		return $post_id;
	}
}
