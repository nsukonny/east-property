<?php
/**
 * Entity for "development" Segments (Проект / Building / Community Development)
 *
 * @var \Entities\Unit $unit
 */

namespace Entities;

use Developer;
use WP_Term;

final class Property {

	use EntityTrait;

	private array $units = array();
	private array $units_ids = array();
	private int $units_count;
	private int $middle_price;
	private $developer;
	private $specifications;

	/**
	 * Get specifications by included units
	 *
	 * @param int $limit
	 *
	 * @return array
	 */
	public function get_specifications( int $limit = 100 ): array {
		if ( ! empty( $this->specifications ) ) {
			return $this->specifications;
		}

		global $wpdb;

		$all_properties_specifications = get_transient( 'all_properties_specifications' );
		if ( empty( $all_properties_specifications ) ) {
			$sql = $wpdb->prepare(
				"SELECT
				CAST(property_meta.meta_value AS UNSIGNED) AS property_id,
				MAX(CASE WHEN unit_meta.meta_key = 'bedrooms' THEN CAST(unit_meta.meta_value AS UNSIGNED) END) AS max_beds,
				MIN(CASE WHEN unit_meta.meta_key = 'bedrooms' THEN CAST(unit_meta.meta_value AS UNSIGNED) END) AS min_beds,
				MAX(CASE WHEN unit_meta.meta_key = 'bathrooms' THEN CAST(unit_meta.meta_value AS UNSIGNED) END) AS max_baths,
				MIN(CASE WHEN unit_meta.meta_key = 'bathrooms' THEN CAST(unit_meta.meta_value AS UNSIGNED) END) AS min_baths,
				MAX(CASE WHEN unit_meta.meta_key = 'area_size' THEN CAST(unit_meta.meta_value AS DECIMAL(10,2)) END) AS max_area,
				MIN(CASE WHEN unit_meta.meta_key = 'area_size' THEN CAST(unit_meta.meta_value AS DECIMAL(10,2)) END) AS min_area
				FROM {$wpdb->posts} unit
						 INNER JOIN {$wpdb->postmeta} property_meta
									ON property_meta.post_id = unit.ID
										AND property_meta.meta_key = 'property'
						 INNER JOIN {$wpdb->posts} property
									ON property.ID = CAST(property_meta.meta_value AS UNSIGNED)
										AND property.post_status = 'publish'
						 LEFT JOIN {$wpdb->postmeta} unit_meta
								   ON unit_meta.post_id = unit.ID
									   AND unit_meta.meta_key IN ('bedrooms', 'bathrooms', 'area_size')
				WHERE unit.post_type = 'unit'
				  AND unit.post_status = 'publish'
				
				GROUP BY CAST(property_meta.meta_value AS UNSIGNED);"
			);

			$all_properties_specifications = $wpdb->get_results( $sql, ARRAY_A );
			set_transient( 'all_properties_specifications', $all_properties_specifications, DAY_IN_SECONDS );
		}

		$property_specifications = array();
		foreach ( $all_properties_specifications as $item ) {
			if ( (int) ( $item['property_id'] ?? 0 ) === $this->get_id() ) {
				$property_specifications = $item;
				break;
			}
		}

		$this->specifications = array();

		if ( ! empty( $property_specifications['min_beds'] ) && ! empty( $property_specifications['max_beds'] ) ) {
			$beds = $property_specifications['min_beds'];
			if ( $beds < $property_specifications['max_beds'] ) {
				$beds .= ' - ' . $property_specifications['max_beds'];
			}

			$this->specifications[] = array(
				'icon'  => THEME_URL . '/assets/img/bed.svg',
				'value' => $beds . ' ' . __( 'Beds', 'east-property' ),
			);
		}

		if ( ! empty( $property_specifications['min_baths'] ) && ! empty( $property_specifications['max_baths'] ) ) {
			$baths = $property_specifications['min_baths'];
			if ( $baths < $property_specifications['max_baths'] ) {
				$baths .= ' - ' . $property_specifications['max_baths'];
			}

			$this->specifications[] = array(
				'icon'  => THEME_URL . '/assets/img/bath.svg',
				'value' => $baths . ' ' . __( 'Baths', 'east-property' ),
			);
		}

		if ( ! empty( $property_specifications['min_area'] ) && ! empty( $property_specifications['max_area'] ) ) {
			$area = round( $property_specifications['min_area'] );
			if ( $area < round( $property_specifications['max_area'] ) ) {
				$area .= ' - ' . round( $property_specifications['max_area'] );
			}

			$this->specifications[] = array(
				'icon'  => THEME_URL . '/assets/img/meters.svg',
				'value' => $area . ' ' . __( 'sqft', 'east-property' ),
			);
		}

		return $this->specifications;
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
	 * Get Handover date
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

		$developer_id    = $developer->ID ?? $developer;
		$this->developer = new Developer( $developer_id );
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

		return sprintf( '%s %s', __( 'AED', 'east-property' ), number_format( (float) $price, 0, '.', ',' ) );
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
	 * Get ownership type
	 *
	 * @return string
	 */
	public function get_ownership_type(): string {
		return $this->get_field( 'ownership_type' );
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
	 * Get property units ids
	 *
	 * @return array
	 */
	public function get_units_ids(): array {
		if ( ! empty( $this->units_ids ) ) {
			return $this->units_ids;
		}

		$units = get_posts(
			array(
				'post_type'      => 'unit',
				'posts_per_page' => - 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'   => 'property',
						'value' => $this->get_id(),
					),
				),
			)
		);

		$this->units = empty( $units ) ? array() : $units;

		return $this->units;
	}

	/**
	 * Get count of property units
	 *
	 * @return int
	 */
	public function get_units_count(): int {
		if ( ! empty( $this->units_count ) ) {
			return $this->units_count;
		}

		$units_count         = get_post_meta( $this->id, 'units_count', true );
		$units_count_expired = get_post_meta( $this->id, 'units_count_expired', true );
		if ( ! empty( $units_count ) && $units_count_expired > time() ) {
			$this->units_count = $units_count;

			return $this->units_count;
		}

		global $wpdb;

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"
						SELECT COUNT(pm.post_id)
						FROM {$wpdb->postmeta} pm
						WHERE pm.meta_key = %s
						  AND pm.meta_value = %s
				",
				'property',
				$this->get_id()
			)
		);

		$this->units_count = $count;
		update_post_meta( $this->id, 'units_count', $this->units_count );
		update_post_meta( $this->id, 'units_count_expired', time() + DAY_IN_SECONDS );

		return $this->units_count;
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
			if ( strtotime( $delivery_date ) < time() ) {
				$labels[] = array(
					'name'  => __( 'Ready', 'east-property' ),
					'color' => 'black',
				);
			} else {
				$labels[] = array(
					'name'  => __( 'Handover:', 'east-property' ) . ' ' . $delivery_date,
					'color' => 'orange',
				);
			}
		}

		$is_popular = $this->get_field( 'is_popular' );
		if ( ! empty( $is_popular ) ) {
			$labels[] = array(
				'name'  => 'Popular',
				'color' => 'red',
			);
		}

		$is_premium_developer = $this->get_field( 'is_premium_developer' );
		if ( ! empty( $is_premium_developer ) ) {
			$labels[] = array(
				'name'  => 'Premium Developer',
				'color' => 'black',
			);
		}

		return $labels;
	}

	/**
	 * Get location
	 *
	 * @return ?WP_Term
	 */
	public function get_location(): ?WP_Term {
		$post_id = $this->get_id();
		if ( empty( $post_id ) ) {
			return null;
		}

		$terms = wp_get_post_terms( $post_id, 'location' );
		if ( empty( $terms ) ) {
			return null;
		}

		return $terms[0];
	}

	/**
	 * Get down payment info
	 *
	 * @return array
	 */
	public function get_down_payment_group(): array {
		return $this->get_field( 'down_payment_group' ) ?: array();
	}

	/**
	 * Get key information array
	 *
	 * @return array
	 */
	public function get_key_information(): array {
		$information = array();

		$delivery_date = $this->is_completed() ? __( 'Ready', 'east-property' ) : $this->get_delivery_date();
		if ( ! empty( $delivery_date ) ) {
			$information[] = array(
				'label' => __( 'Handover Date', 'east-property' ),
				'value' => $delivery_date,
			);
		}

		$construction_started = $this->get_field( 'construction_started' );
		if ( ! empty( $construction_started ) ) {
			$information[] = array(
				'label' => __( 'Construction Started', 'east-property' ),
				'value' => date_i18n( get_option( 'date_format' ), strtotime( $construction_started ) ),
			);
		}

		$number_of_buildings = $this->get_field( 'number_of_buildings' );
		if ( ! empty( $number_of_buildings ) ) {
			$information[] = array(
				'label' => __( 'Number of Buildings', 'east-property' ),
				'value' => (string) $number_of_buildings,
			);
		}

		$property_type = $this->get_field( 'property_type' );
		if ( ! empty( $property_type[0] ) ) {
			$information[] = array(
				'label' => __( 'Property Type', 'east-property' ),
				'value' => (string) $property_type[0],
			);
		}

		$government_fee = $this->get_field( 'government_fee' );
		if ( ! empty( $government_fee ) ) {
			$information[] = array(
				'label' => __( 'Government Fee', 'east-property' ),
				'value' => (string) $government_fee,
			);
		}

		$ownership_type = $this->get_field( 'ownership_type' );
		if ( ! empty( $ownership_type ) ) {
			$information[] = array(
				'label' => __( 'Ownership Type', 'east-property' ),
				'value' => (string) $ownership_type,
			);
		}

		return $information;
	}

	/**
	 * Update all important data for this property (units count, price, specifications, etc.)
	 *
	 * @return void
	 */
	public function update_data() {
		global $wpdb;

		$time      = microtime();
		$units_ids = $this->get_units_ids();

		$lower_unit_price_sql = $wpdb->prepare(
			"SELECT MIN(CAST(pm.meta_value AS UNSIGNED)) AS min_price FROM wp_postmeta pm
					WHERE pm.meta_key = 'price' AND pm.post_id IN (%s)",
			implode( ',', $units_ids )
		);

		$lower_unit_price = $wpdb->get_results( $lower_unit_price_sql, ARRAY_A );
		update_field( 'price', $lower_unit_price[0]['min_price'], $this->id );
		update_field( 'units_count', count( $units_ids ), $this->id );
		update_field( 'last_updated', strtotime( '+ 8 hours' ), $this->id );
	}

	/**
	 * Set units count to this object
	 *
	 * @param int $count
	 *
	 * @return void
	 */
	public function set_units_count( int $count = 0 ): void {
		$this->units_count = $count;
	}

	/**
	 * Check if it completed and not have handover date
	 *
	 * @return bool
	 */
	public function is_completed(): bool {
		$delivery_date = $this->get_delivery_date();

		if ( empty( $delivery_date ) ) {
			return false;
		}

		return strtotime( '2000-01-01' ) === strtotime( $delivery_date );
	}

	/**
	 * Get property translations by pll_get_post_translations
	 *
	 * @return array
	 */
	public function get_translations(): array {
		if ( ! function_exists( 'pll_get_post_translations' ) ) {
			return array(
				'en' => $this,
			);
		}

		$translations = pll_get_post_translations( $this->get_id() );
		if ( empty( $translations ) ) {
			return array(
				'en' => $this,
			);
		}

		$properties = array();
		foreach ( $translations as $lang => $translation ) {
			if ( $translation === $this->get_id() ) {
				$properties[ $lang ] = $this;
			} else {
				$properties[ $lang ] = new Property( $translation );
			}
		}

		return $properties;
	}
}
