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

		$this->properties = get_properties(
			20,
			false,
			array(
				'developer' => $this->get_id(),
				'specifications' => true,
				'galleries' => true,
			)
		);

		return $this->properties;
	}
}