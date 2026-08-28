<?php
/**
 * Entity for "Broker"
 */

namespace Entities;

use WP_User;

final class Broker {

	private WP_User $wp_user;
	private int $id;

	public function __construct( int|WP_User $wp_user = null ) {
		if ( $wp_user instanceof WP_User ) {
			$this->wp_user = $wp_user;
			$this->id      = (int) $wp_user->ID;
		} else {
			$this->id      = (int) $wp_user;
			$this->wp_user = get_user( $this->id );
		}
	}

	/**
	 * Get user id
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Add all needed data for start as a broker
	 *
	 * @return void
	 */
	public function register_broker(): void {
		update_field( 'boost_points', 1000, 'user_' . $this->id );
	}

	/**
	 * Check if user exists and is a broker
	 *
	 * @return bool
	 */
	public function exists(): bool {
		if ( ! $this->wp_user instanceof WP_User ) {
			return false;
		}

		return $this->wp_user->ID === $this->id;
	}

	/**
	 * Get field value from ACF
	 *
	 * @param $field
	 *
	 * @return mixed
	 */
	public function get_field( $field ): mixed {
		return get_field( $field, 'user_' . $this->id );
	}

	/**
	 * Get boost points
	 *
	 * @return int
	 */
	public function get_boost_points(): int {
		if ( user_can( $this->id, 'administrator' ) ) {
			return 1000;
		}

		return (int) $this->get_field( 'boost_points' );
	}

	/**
	 * Get boost points
	 *
	 * @param int $spend_points
	 *
	 * @return bool
	 */
	public function spend_boost_points( int $spend_points = 100 ): bool {
		$boost_points = $this->get_boost_points();

		if ( $boost_points < $spend_points ) {
			return false;
		}

		$boost_points -= $spend_points;
		$this->set_boost_points( $boost_points );

		return true;
	}

	/**
	 * Update boost points
	 *
	 * @param int $boost_points
	 *
	 * @return void
	 */
	private function set_boost_points( int $boost_points ): void {
		update_field( 'boost_points', $boost_points, 'user_' . $this->id );
	}
}
