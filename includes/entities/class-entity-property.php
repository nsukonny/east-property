<?php
/**
 * Entity for "development" Segments (Проект / Building / Community Development)
 */

namespace MessiaTheme\Entities;

final class Property {

	use EntityTrait;

	private array $units = array();

	/**
	 * Get amenities list for this building
	 *
	 * @param int $limit
	 *
	 * @return array
	 */
	public function get_amenities( int $limit = 100 ): array { //TODO get real amenities
		return array(
			array( 'icon' => THEME_URL . '/assets/img/bed.svg', 'value' => '3 Beds' ),
			array( 'icon' => THEME_URL . '/assets/img/bath.svg', 'value' => '2 Bath' ),
			array( 'icon' => THEME_URL . '/assets/img/meters.svg', 'value' => '120 sqft' ),
		);
	}

	/**
	 * Get delivery date
	 *
	 * @return string
	 */
	public function get_delivery_date(): string {
		$date = $this->get_field( 'delivery_date' );
		if ( empty( $date ) ) {
			return '';
		}

		return date_i18n( get_option( 'date_format' ), strtotime( $date ) );
	}

	/**
	 * Get developer info
	 *
	 * @return array
	 */
	public function get_developer(): array {
		$developer          = array();
		$developer['title'] = $this->get_field( 'developer' );
		$developer['url']   = $this->get_field( 'developer_url' );
		$logo               = $this->get_field( 'developer_logo' );
		if ( ! empty( $logo ) ) {
			$logo              = json_decode( $logo, true );
			$developer['logo'] = isset( $logo[0]['id'] ) ? wp_get_attachment_url( (int) $logo[0]['id'] ) : null;
		}

		return $developer;
	}

	/**
	 * Get gallery images
	 *
	 * @return array
	 */
	public function get_gallery(): array {
		$gallery = array();

		$gallery_json = $this->get_field( 'gallery' );
		if ( ! is_string( $gallery_json ) ) {
			return $gallery;
		}

		$gallery_ids = json_decode( $gallery_json, true );
		if ( ! is_array( $gallery_ids ) ) {
			return $gallery;
		}

		foreach ( $gallery_ids as $gallery_id ) {
			$gallery[] = wp_get_attachment_url( (int) $gallery_id['id'] );
		}

		return $gallery;
	}

	/**
	 * Get property price
	 *
	 * @return float
	 */
	public function get_price(): float { //TODO get price by all units
		$price = carbon_get_the_post_meta( 'crb6_price' );

		return $price ? (float) $price : 1600800;
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
	 * Get property units
	 *
	 * @return array
	 */
	public function get_units(): array {
		if ( ! empty( $this->units ) ) {
			return $this->units;
		}

		$units = array();

		//$units[] = new Unit();

		return $this->units;
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
	 * @return array
	 */
	public function get_location(): array {
		$location = $this->get_field( 'location' );
		if ( empty( $location ) ) {
			return array();
		}

		$search_link = get_term_link( $this->get_segment_id() );

		return array(
			'location' => $location,
			'url'      => $search_link,
		);
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
			$information[] = array( 'label' => __( 'Property Type' ), 'value' => (string) $property_type );
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