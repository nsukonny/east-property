<?php
/**
 * Entity for "Unit" Segments (Ads from Broker we call Unit)
 */

namespace Entities;

use Developer;

final class Unit {

	use EntityTrait;

	protected $property;
	protected $developer;

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
		return (int) $this->get_field( 'bedrooms' ) ?: 0;
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

	/**
	 * Get amenities list for this Unit
	 *
	 * @return array
	 */
	public function get_amenities(): array {
		return array(
			array( 'icon' => THEME_URL . '/assets/img/bed.svg', 'value' => $this->get_beds() . ' ' . __( 'Beds' ) ),
			array( 'icon' => THEME_URL . '/assets/img/bath.svg', 'value' => $this->get_baths() . ' ' . __( 'Baths' ) ),
			array( 'icon' => THEME_URL . '/assets/img/meters.svg', 'value' => $this->get_area() . ' ' . __( 'sqft' ) ),
		);
	}

	/**
	 * Get Unit property
	 *
	 * @return Property|null
	 */
	public function get_property(): ?Property {
		if ( ! empty( $this->property ) ) {
			return $this->property;
		}

		$property = $this->get_field( 'property' );
		if ( empty( $property ) ) {
			return null;
		}

		$this->property = new Property( $property->ID );

		return $this->property;
	}

	/**
	 * Get Unit property
	 *
	 * @return Developer|null
	 */
	public function get_developer(): ?Developer {
		if ( ! empty( $this->developer ) ) {
			return $this->developer;
		}

		$property = $this->get_property();
		if ( empty( $property ) ) {
			return null;
		}

		$developer = $property->get_developer();
		if ( empty( $developer ) ) {
			return null;
		}

		$this->developer = $developer;

		return $this->developer;
	}

	/**
	 * Get labels
	 *
	 * Colors supported: red, orange, black, grey
	 * @return array
	 */
	public function get_labels(): array {
		$labels    = array();
		$property  = $this->get_property();
		$developer = $this->get_developer();

		$delivery_date = ! empty( $property ) ? $property->get_delivery_date() : '';
		if ( ! empty( $delivery_date ) ) {
			$labels[] = array( 'name' => $delivery_date, 'color' => 'grey' );
		}

		$is_popular = $property->get_field( 'is_popular' );
		if ( ! empty( $is_popular ) ) {
			$labels[] = array( 'name' => __( 'Popular' ), 'color' => 'red' );
		}

		$is_premium_developer = $developer->get_field( 'is_premium' );
		if ( ! empty( $is_premium_developer ) ) {
			$labels[] = array( 'name' => __( 'Premium Developer' ), 'color' => 'black' );
		}

		return $labels;
	}
}