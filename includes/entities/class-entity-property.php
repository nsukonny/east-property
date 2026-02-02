<?php
/**
 * Entity for "development" Segments (Проект / Building / Community Development)
 *
 * @var \Entities\Unit $unit
 */

namespace Entities;

use Developer;

final class Property {

	use EntityTrait;

	private array $units = array();
	private int $middle_price;
	private array $gallery;
	private $developer;

	/**
	 * Get amenities list for this building
	 *
	 * @param int $limit
	 *
	 * @return array
	 */
	public function get_apartments_params( int $limit = 100 ): array { //TODO get real amenities
		return array(
			array( 'icon' => THEME_URL . '/assets/img/bed.svg', 'value' => '2-5 Beds' ),
			array( 'icon' => THEME_URL . '/assets/img/bath.svg', 'value' => '1-3 Baths' ),
			array( 'icon' => THEME_URL . '/assets/img/meters.svg', 'value' => '90-210 sqft' ),
		);
	}

	/**
	 * Get amenities list for this building
	 *
	 * @return array
	 */
	public function get_amenities(): array {
		$amenities = array();

		$amenities_data = $this->get_field( 'amenities' );
		if ( empty( $amenities_data ) ) {
			return $amenities;
		}

		$amenities_list = explode( "\n", $amenities_data );
		foreach ( $amenities_list as $amenity ) {
			$amenity = trim( $amenity );
			if ( ! empty( $amenity ) ) {
				$amenities[] = $amenity;
			}
		}

		return $amenities;
	}

	/**
	 * Get delivery date
	 *
	 * @param bool $in_date_format
	 *
	 * @return string
	 */
	public function get_delivery_date( bool $in_date_format = true ): string {
		$date = $this->get_field( 'delivery_date' );
		if ( empty( $date ) ) {
			return '';
		}

		if ( $in_date_format ) {
			return date_i18n( get_option( 'date_format' ), strtotime( $date ) );
		}

		return $date;
	}

	/**
	 * Get developer info
	 *
	 * @return Developer|null
	 */
	public function get_developer(): ?Developer {
		if ( ! empty( $this->developer ) ) {
			return $this->developer;
		}

		$developer = $this->get_field( 'developer_rel' );
		if ( empty( $developer ) ) {
			return null;
		}

		$this->developer = new Developer( $developer->ID );
		if ( empty( $this->developer ) ) {
			return null;
		}

		return $this->developer;
	}

	/**
	 * Get property price
	 *
	 * @return int
	 */
	public function get_price(): int {
		if ( ! empty( $this->middle_price ) ) {
			return $this->middle_price;
		}

		$units = $this->get_units();
		if ( empty( $units ) ) {
			$this->middle_price = 0;

			return $this->middle_price;
		}

		$price = 0;
		foreach ( $units as $unit ) {
			$price += $unit->get_price();
		}

		$this->middle_price = round( $price / count( $units ) );

		return $this->middle_price;
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
	 * Get property type
	 *
	 * @return array
	 */
	public function get_property_type(): array {
		return $this->get_field( 'property_type' );
	}

	/**
	 * Get property units
	 *
	 * @return array
	 */
	public function get_units(): array {
		if ( ! empty( $this->units ) ) {
			return $this->units;
		}

		$units = get_posts(
			array(
				'post_type'      => 'unit',
				'posts_per_page' => - 1,
				'meta_query'     => array(
					array(
						'key'   => 'property',
						'value' => $this->get_id(),
					),
				),
			)
		);

		if ( empty( $units ) ) {
			return array();
		}

		$this->units = array();
		foreach ( $units as $unit ) {
			$this->units[] = new Unit( $unit );
		}

		return $this->units;
	}

	/**
	 * Get random units from the same property instead of the given unit ID
	 *
	 * @param int $count
	 * @param int $exclude_unit_id
	 *
	 * @return array
	 */
	public function get_random_units( int $count = 3, int $exclude_unit_id = 0 ): array {
		$units = $this->get_units();
		if ( empty( $units ) ) {
			return array();
		}

		$filtered_units = array();
		foreach ( $units as $unit ) {
			if ( $unit->get_id() !== $exclude_unit_id ) {
				$filtered_units[] = $unit;
			}
		}

		shuffle( $filtered_units );

		return array_slice( $filtered_units, 0, $count );
	}

	/**
	 * Get latitude
	 *
	 * @return string
	 */
	public function get_latitude(): string {
		return $this->get_field( 'latitude' ) ?: '';
	}

	/**
	 * Get longitude
	 *
	 * @return string
	 */
	public function get_longitude(): string {
		return $this->get_field( 'longitude' ) ?: '';
	}

	/**
	 * Get property floors
	 *
	 * @return string
	 */
	public function get_floors(): string {
		return $this->get_field( 'floors' ) ?: '';
	}

	/**
	 * Get property units grouped by beds count
	 *
	 * @return array
	 */
	public function get_units_by_beds(): array {
		$units         = $this->get_units();
		$grouped_units = array();

		foreach ( $units as $unit ) {
			$beds = $unit->get_beds();
			if ( ! isset( $grouped_units[ $beds ] ) ) {
				$grouped_units[ $beds ] = array(
					'beds'      => $beds,
					'min_baths' => $unit->get_baths(),
					'max_baths' => $unit->get_baths(),
					'min_area'  => $unit->get_area(),
					'max_area'  => $unit->get_area(),
					'price'     => $unit->get_price(),
					'units'     => array(),
				);
			}
			$grouped_units[ $beds ]['units'][] = $unit;

			if ( $grouped_units[ $beds ]['min_baths'] > $unit->get_baths() ) {
				$grouped_units[ $beds ]['min_baths'] = $unit->get_baths();
			}

			if ( $grouped_units[ $beds ]['max_baths'] < $unit->get_baths() ) {
				$grouped_units[ $beds ]['max_baths'] = $unit->get_baths();
			}

			if ( $grouped_units[ $beds ]['min_area'] > $unit->get_area() ) {
				$grouped_units[ $beds ]['min_area'] = $unit->get_area();
			}

			if ( $grouped_units[ $beds ]['max_area'] < $unit->get_area() ) {
				$grouped_units[ $beds ]['max_area'] = $unit->get_area();
			}

			if ( $grouped_units[ $beds ]['price'] > $unit->get_price() ) {
				$grouped_units[ $beds ]['price'] = $unit->get_price();
			}
		}

		ksort( $grouped_units );

		return $grouped_units;
	}

	/**
	 * Get property labels
	 *
	 * @return array
	 */
	public function get_labels(): array {
		$labels = array();

		$delivery_date = $this->get_delivery_date();
		if ( ! empty( $delivery_date ) ) {
			$labels[] = array( 'name' => $delivery_date, 'color' => 'orange' );
		}

		$is_popular = $this->get_field( 'is_popular' );
		if ( ! empty( $is_popular ) ) {
			$labels[] = array( 'name' => 'Popular', 'color' => 'red' );
		}

		$is_premium_developer = $this->get_field( 'is_premium_developer' );
		if ( ! empty( $is_premium_developer ) ) {
			$labels[] = array( 'name' => 'Premium Developer', 'color' => 'black' );
		}

		return $labels;
	}

	/**
	 * Get location
	 *
	 * @return string
	 */
	public function get_location(): string {
		$location = $this->get_field( 'location' );
		if ( empty( $location ) ) {
			return '';
		}

		return $location;
	}

	/**
	 * Get down payment info
	 *
	 * @return array
	 */
	public function get_down_payment_group(): array {
		return $this->get_field( 'down_payment_group' );
	}

	/**
	 * Get key information array
	 *
	 * @return array
	 */
	public function get_key_information(): array {
		$information = array();

		$delivery_date = $this->get_delivery_date();
		if ( ! empty( $delivery_date ) ) {
			$information[] = array( 'label' => __( 'Delivery Date' ), 'value' => $delivery_date );
		}

		$construction_started = $this->get_field( 'construction_started' );
		if ( ! empty( $construction_started ) ) {
			$information[] = array(
				'label' => __( 'Construction Started' ),
				'value' => date_i18n( get_option( 'date_format' ), strtotime( $construction_started ) )
			);
		}

		$number_of_buildings = $this->get_field( 'number_of_buildings' );
		if ( ! empty( $number_of_buildings ) ) {
			$information[] = array( 'label' => __( 'Number of Buildings' ), 'value' => (string) $number_of_buildings );
		}

		$property_type = $this->get_field( 'property_type' );
		if ( ! empty( $property_type ) ) {
			$information[] = array( 'label' => __( 'Property Type' ), 'value' => (string) $property_type['value'] );
		}

		$government_fee = $this->get_field( 'government_fee' );
		if ( ! empty( $government_fee ) ) {
			$information[] = array( 'label' => __( 'Government Fee' ), 'value' => (string) $government_fee );
		}

		$ownership_type = $this->get_field( 'ownership_type' );
		if ( ! empty( $ownership_type ) ) {
			$information[] = array( 'label' => __( 'Ownership Type' ), 'value' => (string) $ownership_type );
		}

		return $information;
	}
}