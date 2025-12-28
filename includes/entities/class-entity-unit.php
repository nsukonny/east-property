<?php
/**
 * Entity for "Unit" Segments (Ads from Broker we call Unit)
 */

namespace Entities;

final class Unit {

	use EntityTrait;

	/**
	 * Get price
	 *
	 * @return int
	 */
	public function get_price(): int {
		return (int) $this->get_field( 'price' );
	}
}