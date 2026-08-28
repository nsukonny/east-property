<?php
/**
 * Entity for "Developers"
 */

final class Developer {

	use \Entities\EntityTrait;

	private array $properties = array();

	/**
	 * Get developer URL
	 *
	 * @return string
	 */
	public function get_developer_url(): string {
		$developer_url = $this->get_field( 'developer_url' );

		return ! empty( $developer_url ) ? esc_url( $developer_url ) : '';
	}

	/**
	 * Get projects
	 *
	 * @return array
	 */
	public function get_properties(): array {
		if ( ! empty( $this->properties ) ) {
			return $this->properties;
		}

		$property_posts = get_posts(
			array(
				'post_type'      => 'property',
				'posts_per_page' => - 1,
				'post_status'    => 'publish',
				'meta_query'     => array(
					array(
						'key'     => 'developer_rel',
						'value'   => $this->get_id(),
						'compare' => '=',
					),
				),
			)
		);

		$this->properties = array();
		foreach ( $property_posts as $property_post ) {
			$this->properties[] = new \Entities\Property( $property_post );
		}

		return $this->properties;
	}

	/**
	 * Get projects count
	 *
	 * @return int
	 */
	public function get_properties_count(): int {
		$properties = get_posts(
			array(
				'post_type'      => 'property',
				'posts_per_page' => - 1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => 'developer_rel',
						'value'   => $this->get_id(),
						'compare' => '=',
					),
				),
			)
		);

		return count( $properties );
	}
}