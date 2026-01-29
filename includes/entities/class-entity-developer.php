<?php
/**
 * Entity for "Developers"
 */

final class Developer {

	use \Entities\EntityTrait;

	/**
	 * Get developer URL
	 *
	 * @return string
	 */
	public function get_developer_url(): string {
		$developer_url = $this->get_field( 'developer_url' );

		return ! empty( $developer_url ) ? esc_url( $developer_url ) : '';
	}
}