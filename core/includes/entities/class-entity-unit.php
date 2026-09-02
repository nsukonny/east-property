<?php
/**
 * Entity for "Unit" Segments (Ads from Broker we call Unit)
 */

namespace Entities;

use Developer;
use WP_Term;

final class Unit {

	use EntityTrait;

	protected $property;
	protected $developer;
	protected $is_favorite;
	protected $broker;

	/**
	 * Get area size
	 *
	 * @return int
	 */
	public function get_area(): float {
		return (float) $this->get_field( 'area_size' );
	}

	/**
	 * Get errors
	 *
	 * @return mixed
	 */
	public function get_approve_errors(): mixed {
		return $this->get_post_meta( 'auto_approve_errors' )[0] ?? '';
	}

	public function get_discount() {
		$discount = $this->get_field( 'discount' );

		if ( empty( $discount ) && IS_DISTRESS ) {
			return 15; //TODO Temp solution for test database
		}

		return $discount;
	}

	public function get_price_per_square() {
		return $this->get_field( 'price_per_square_foot' ) ?? 0;
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
		return (int) $this->get_field( 'bathrooms' ) ?: 0;
	}

	/**
	 * Get broker info
	 *
	 * @return Estate_User|null
	 */
	public function get_broker(): ?Estate_User {
		if ( ! empty( $this->broker ) ) {
			return $this->broker;
		}

		$broker_wp_user = $this->get_field( 'broker' );
		if ( empty( $broker_wp_user ) ) {
			$broker_wp_user = Estate_User::get_default_broker();
		}

		$broker = new Estate_User( $broker_wp_user );
		if ( ! $broker->exists() || ! $broker->is_broker() ) {
			$this->broker = null;

			return $this->broker;
		}

		$this->broker = $broker;

		return $this->broker;
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
	 * Get unit translations by pll_get_post_translations
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

		$units = array();
		foreach ( $translations as $lang => $translation ) {
			if ( $translation === $this->get_id() ) {
				$units[ $lang ] = $this;
			} else {
				$units[ $lang ] = new Unit( $translation );
			}
		}

		return $units;
	}

	/**
	 * Get old price
	 *
	 * @return mixed
	 */
	public function get_original_price(): mixed {
		$original_price = $this->get_field( 'original_price' );

		if ( empty( $original_price ) && IS_DISTRESS ) {
			return round( ( $this->get_price() / 0.85 ) / 1000 ) * 1000;
		}

		return $original_price;
	}

	/**
	 * Get url to image of floor plan
	 *
	 * @return array
	 */
	public function get_floor_plan(): ?array {
		return $this->get_field( 'floor_plans' ) ?: array();
	}

	/**
	 * Get url to image of floor plan
	 *
	 * @return string|null
	 */
	public function get_floor_plan_image(): ?string {
		$floor_plan = $this->get_floor_plan();

		return $floor_plan[0]['layout']['url'] ?? null;
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
	 * Get property price
	 *
	 * @return string
	 */
	public function get_original_price_html(): string {
		$price = $this->get_original_price();

		if ( empty( $price ) ) {
			return '';
		}

		return sprintf( '%s %s', __( 'AED', 'east-property' ), number_format( (float) $price, 0, '.', ',' ) );
	}

	/**
	 * Get amenities list for this Unit
	 *
	 * @return array
	 */
	public function get_amenities(): array {
		$amenities = array();

		$beds = $this->get_beds();
		if ( 0 === $beds ) {
			$beds = __( 'Studio', 'east-property' );
		} else {
			$beds .= ' ' . __( 'Beds', 'east-property' );
		}
		$amenities[] = array(
			'icon'  => THEME_URL . '/assets/img/bed.svg',
			'value' => $beds,
		);

		$baths = $this->get_baths();
		if ( 0 < $baths ) {
			$amenities[] = array(
				'icon'  => THEME_URL . '/assets/img/bath.svg',
				'value' => ( 1 === $baths ) ? __( '1 Bath', 'east-property' ) : $baths . ' ' . __(
						'Baths',
						'east-property'
					),
			);
		}

		$area = $this->get_area();
		if ( 0 < $area ) {
			$amenities[] = array(
				'icon'  => THEME_URL . '/assets/img/meters.svg',
				'value' => $this->get_area() . ' ' . __( 'sqft', 'east-property' ),
			);
		}

		return $amenities;
	}

	/**
	 * Get property location
	 *
	 * @return ?WP_Term
	 */
	public function get_location(): ?WP_Term {
		return $this->get_property()?->get_location();
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

		$property_id = $this->resolve_property_id();

		if ( 0 === $property_id ) {
			return null;
		}

		$this->property = new Property( $property_id );

		return $this->property;
	}

	/**
	 * Resolve the project this unit belongs to.
	 *
	 * Translations created before the account form carried the field over hold no
	 * project of their own, and the permalink then degrades to
	 * /property/no-project/{slug}/. In the default language that URL still heals
	 * itself through the wrong-slug redirect, so the damage stayed invisible; under
	 * /ru/ it is a plain 404, and most cards of the Russian listings pointed there.
	 *
	 * A sibling translation describes the same unit in the same building, so its
	 * project stands in — preferring that project's own translation in this unit's
	 * language when one exists, so the URL stays inside its language.
	 *
	 * @return int Zero when no sibling knows the project either.
	 */
	private function resolve_property_id(): int {
		$property = $this->get_field( 'property' );

		if ( ! empty( $property->ID ) ) {
			return (int) $property->ID;
		}

		if ( ! function_exists( 'pll_get_post_translations' ) ) {
			return 0;
		}

		$id           = $this->get_id();
		$translations = pll_get_post_translations( $id );
		$language     = function_exists( 'pll_get_post_language' )
			? (string) pll_get_post_language( $id, 'slug' )
			: '';

		// The default language holds the original data, so it is asked first.
		if ( function_exists( 'pll_default_language' ) ) {
			$default = (string) pll_default_language( 'slug' );

			if ( isset( $translations[ $default ] ) ) {
				$translations = array( $default => $translations[ $default ] ) + $translations;
			}
		}

		foreach ( $translations as $sibling_id ) {
			if ( (int) $sibling_id === $id ) {
				continue;
			}

			$candidate = (int) get_post_meta( $sibling_id, 'property', true );

			if ( 0 === $candidate ) {
				continue;
			}

			if ( '' !== $language && function_exists( 'pll_get_post' ) ) {
				$translated = (int) pll_get_post( $candidate, $language );

				if ( 0 < $translated ) {
					$candidate = $translated;
				}
			}

			if ( 'property' === get_post_type( $candidate ) && 'publish' === get_post_status( $candidate ) ) {
				return $candidate;
			}
		}

		return 0;
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

		$delivery_date = $property?->get_delivery_date() ?: '';
		if ( ! empty( $delivery_date ) ) {
			if ( strtotime( $delivery_date ) < time() ) {
				$labels[] = array(
					'name'  => __( 'Ready', 'east-property' ),
					'color' => 'grey',
				);
			} else {
				$labels[] = array(
					'name'  => __( 'Handover:', 'east-property' ) . ' ' . $delivery_date,
					'color' => 'grey',
				);
			}
		}

		$is_popular = $property && $property->get_field( 'is_popular' );
		if ( ! empty( $is_popular ) ) {
			$labels[] = array(
				'name'  => __( 'Popular', 'east-property' ),
				'color' => 'red',
			);
		}

		$is_premium_developer = $developer && $developer->get_field( 'is_premium' );
		if ( ! empty( $is_premium_developer ) ) {
			$labels[] = array(
				'name'  => __( 'Premium Developer', 'east-property' ),
				'color' => 'black',
			);
		}

		if ( $this->is_boosted() ) {
			$labels[] = array(
				'name'  => __( 'Promoted', 'east-property' ),
				'color' => 'red',
				'icon'  => THEME_URL . '/assets/img/star_white.svg',
			);
		}

		$is_draft = 'draft' === $this->post->post_status;
		if ( ! empty( $is_draft ) ) {
			$labels[] = array(
				'name'  => __( 'Waiting for approval', 'east-property' ),
				'color' => 'red',
			);
		}

		if ( $this->is_wait_user_actions() ) {
			$labels[] = array(
				'name'  => __( 'Contains an error', 'east-property' ),
				'color' => 'red',
			);
		}

		return $labels;
	}

	/**
	 * Get unit type
	 *
	 * @return ?string
	 */
	public function get_unit_type(): ?string {
		return $this->get_field( 'unit_type' );
	}

	/**
	 * Get unit type
	 *
	 * @return string
	 */
	public function get_listing_type(): string {
		return $this->get_field( 'listing_type' ) ?: 'off-plan';
	}

	/**
	 * Check this unit is a below-market (distress) deal
	 *
	 * @return bool
	 */
	public function is_distress(): bool {
		return 'distress' === $this->get_listing_type();
	}

	/**
	 * Check this unit has a valid discount off the original price
	 *
	 * @return bool
	 */
	public function has_discount(): bool {
		if ( ! IS_DISTRESS && ! $this->is_distress() ) {
			return false;
		}

		$original_price = (float) $this->get_original_price();

		return $original_price > (float) $this->get_price();
	}

	/**
	 * Add points for boost units in the search results
	 *
	 * @param mixed $boost_plan_key
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public function boost( mixed $boost_plan_key, int $user_id = 0 ): bool {
		$boost_plan = BOOST_PLANS[ (int) $boost_plan_key ];

		if ( empty( $boost_plan ) || 0 === $user_id ) {
			return false;
		}

		$broker = new Estate_User( $user_id );
		if ( ! $broker->exists() || ! $broker->is_broker() ) {
			return false;
		}

		$boost_point    = 1;
		$boost_end_date = strtotime( $this->get_field( 'boost_end_date' ) );
		if ( empty( $boost_end_date ) || $boost_end_date < time() ) {
			$boost_end_date = time();
		}

		$new_boost_end_date = date( 'Ymd H:i:s', strtotime( '+' . $boost_plan['days'] . ' days', $boost_end_date ) );
		if ( $broker->spend_boost_points( $boost_plan['points'] ) ) {
			update_field( 'boost_end_date', $new_boost_end_date, $this->get_id() );
			update_field( 'boost_score', $boost_point, $this->get_id() );

			return true;
		}

		return false;
	}

	/**
	 * Disable boost score after boost_end_date expire
	 *
	 * @return void
	 */
	public function boost_expires(): void {
		$boost_end_date = strtotime( $this->get_field( 'boost_end_date' ) );
		if ( ! empty( $boost_end_date ) && strtotime( $boost_end_date ) < time() ) {
			update_field( 'boost_score', 0 );
		}
	}

	/**
	 * True if unit is boosted by broker
	 *
	 * @return bool
	 */
	public function is_boosted(): bool {
		$boost_score = (int) $this->get_field( 'boost_score' );

		return ( 0 < $boost_score );
	}

	/**
	 * True if unit is in user favorites
	 *
	 * @return bool
	 */
	public function is_favorite(): bool {
		if ( ! empty( $this->is_favorite ) ) {
			return $this->is_favorite;
		}

		global $current_user;
		if ( ! $current_user || ! is_user_logged_in() ) {
			$this->is_favorite = false;

			return $this->is_favorite;
		}

		$user_favorites = get_user_meta( $current_user->ID, 'favorite_units', true );
		if ( ! empty( $user_favorites )
		     && is_array( $user_favorites )
		     && in_array( $this->id, $user_favorites, true ) ) {
			$this->is_favorite = true;

			return $this->is_favorite;
		}

		$this->is_favorite = false;

		return $this->is_favorite;
	}

	/**
	 * @return bool
	 */
	private function is_wait_user_actions(): bool {
		$is_wait_user_actions = get_post_meta( $this->id, 'is_wait_user_actions', true );

		if ( ! empty( $is_wait_user_actions ) && 1 === (int) $is_wait_user_actions ) {
			return true;
		}

		return false;
	}

	/**
	 * Check unit for minimum requirements and publish it.
	 *
	 * @return void
	 */
	public function auto_approve(): void {
		if ( $this->is_wait_user_actions() ) {
			return;
		}

		$errors_messages = '';

		$gallery = $this->get_gallery();
		if ( empty( $gallery ) ) {
			$errors_messages .= '- ' . __( 'Gallery should not be empty', 'east-property' ) . '<br>';
		}

		$area = $this->get_area();
		if ( empty( $area ) || 200 > $area ) {
			$errors_messages .= '- ' . __(
					'Area should not be empty and must bigger than 200 sqft',
					'east-property'
				) . '<br>';
		}

		$price = $this->get_price();
		if ( empty( $price ) || 200000 > $price ) {
			$errors_messages .= '- ' . __(
					'Price should not be empty and must bigger than 200 000 AED',
					'east-property'
				) . '<br>';
		}

		$title = $this->get_title();
//		if ( empty( $title ) || 20 > strlen( $title ) ) {
//			$errors_messages .= '- ' . __(
//					'Title should not be empty and must bigger than 20 characters',
//					'east-property'
//				) . '<br>';
//		}

		if ( ! empty( $errors_messages ) ) {
			update_post_meta( $this->id, 'auto_approve_errors', $errors_messages );
			update_post_meta( $this->id, 'is_wait_user_actions', 1 );

			$html = '<p>' . __(
					'Your unit has been submitted and is waiting for approval. Please fix the following issues:',
					'east-property'
				) . '</p>';
			$html .= '<p>' . $errors_messages . '</p>';

			$broker = $this->get_broker();

			get_template_part(
				'core/components/email/send',
				null,
				array(
					'email'   => $broker->get_email(),
					'subject' => __( 'Need to fix errors in your submitted property', 'east-property' ),
					'content' => $html,
				)
			);

			return;
		}

		wp_update_post(
			array(
				'ID'          => $this->id,
				'post_status' => 'publish',
			)
		);
		delete_post_meta( $this->id, 'auto_approve_errors' );
		delete_post_meta( $this->id, 'is_wait_user_actions' );
	}
}
