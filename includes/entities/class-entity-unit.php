<?php
/**
 * Entity for "Unit" Segments (Ads from Broker we call Unit)
 */

namespace Entities;

final class Unit {

	use EntityTrait;

	/**
	 * Get area size
	 *
	 * @return int
	 */
	public function get_area(): int {
		return (int) $this->get_field( 'area_size' );
	}

	/**
	 * Get beds count
	 *
	 * @return int
	 */
	public function get_beds(): int {
		return (int) $this->get_field( 'bedrooms' ) ?: 1;
	}

	/**
	 * Get baths count
	 *
	 * @return int
	 */
	public function get_baths(): int {
		return (int) $this->get_field( 'bathrooms' ) ?: 1;
	}

	/**
	 * Get price
	 *
	 * @return int
	 */
	public function get_price(): int {
		return (int) $this->get_field( 'price' );
	}

	/**
	 * Get property price
	 *
	 * @return string
	 */
	public function get_price_html(): string {
		$price = $this->get_price();

		return sprintf( '%s %s', __( 'AED' ), number_format( (float) $price, 0, '.', ',' ) );
	}
}