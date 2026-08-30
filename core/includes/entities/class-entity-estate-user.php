<?php
/**
 * Entity for "Estate_User"
 */

namespace Entities;

use WP_Post;
use WP_User;

final class Estate_User {

	private WP_User $wp_user;
	private int $id;
	private $agency;

	public $display_name;

	public function __construct( int|WP_User $wp_user ) {
		if ( empty( $wp_user ) ) {
			return;
		}

		if ( $wp_user instanceof WP_User ) {
			$this->wp_user      = $wp_user;
			$this->id           = (int) $wp_user->ID;
			$this->display_name = $this->wp_user->display_name;
		} else {
			$this->id           = (int) $wp_user;
			$this->wp_user      = get_user( $this->id );
			$this->display_name = $this->wp_user;
		}
	}

	/**
	 * @return string
	 */
	public function get_avatar(): string {
		$avatar = get_field( 'avatar', 'user_' . $this->id );

		return $avatar ? $avatar['sizes']['thumbnail'] : THEME_URL . '/assets/img/no-avatar-300.jpg';
	}

	/**
	 * @return array
	 */
	public function get_avatar_data(): array {
		return get_field( 'avatar', 'user_' . $this->id ) ?: array();
	}

	/**
	 * @return WP_Post|null
	 */
	public function get_agency(): ?WP_Post {
		if ( ! empty( $this->agency ) ) {
			return $this->agency;
		}

		if ( ! $this->is_broker() ) {
			$this->agency = null;

			return $this->agency;
		}

		$this->agency = get_field( 'agency', 'user_' . $this->id ) ?: null;

		return $this->agency;
	}

	/**
	 * @return false|string|null
	 */
	public function get_agency_logo(): ?string {
		$agency = $this->get_agency();

		return $agency ? get_the_post_thumbnail_url( $agency->ID, 'small' ) : null;
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
		if ( $this->is_admin() ) {
			return 1000;
		}

		if ( ! $this->is_broker() ) {
			return 0;
		}

		return (int) $this->get_field( 'boost_points' );
	}

	/**
	 * Get phone by current or selected language
	 *
	 * @return string
	 */
	public function get_phone( $language = '', bool $is_show_old = false ): string {
		if ( empty( $language ) && function_exists( 'pll_current_language' ) ) {
			$language = pll_current_language();
		}

		$phones       = $this->get_field( 'phones' );
		$phone_number = '';
		if ( ! empty( $phones ) ) {
			foreach ( $phones as $phone ) {
				$phone_number = $phone['phone'];

				if ( $phone['language'] === $language ) {
					break;
				}
			}
		}

		//support old phone field
		if ( empty( $phone_number ) && $is_show_old ) {
			$phone_number = $this->get_field( 'phone' ) ?: '';
		}

		return $phone_number;
	}

	/**
	 * Get whatsapp by current or selected language
	 *
	 * @param string $language
	 * @param string $text
	 * @param bool $is_link
	 *
	 * @return string
	 */
	public function get_whatsapp( string $language = '', string $text = '', bool $is_link = true ): string {
		if ( empty( $language ) && function_exists( 'pll_current_language' ) ) {
			$language = pll_current_language();
		}

		$whatsapps       = $this->get_field( 'whatsapps' );
		$whatsapp_number = '';
		if ( ! empty( $whatsapps ) ) {
			foreach ( $whatsapps as $whatsapp ) {
				$whatsapp_number = $whatsapp['whatsapp'];

				if ( $whatsapp['language'] === $language ) {
					break;
				}
			}
		}

		//support old whatsapp field
		if ( empty( $whatsapp_number ) ) {
			$whatsapp_number = $this->get_field( 'whatsapp' ) ?: '';
		}

		if ( ! $is_link ) {
			return $whatsapp_number;
		}

		if ( str_starts_with( $whatsapp_number, 'https' ) ) {
			return $whatsapp_number;
		}

		return 'https://api.whatsapp.com/send/?phone=' . $whatsapp_number . '&text=' . $text;
	}

	/**
	 * Add all needed data for start as a broker
	 *
	 * @return void
	 */
	public function register_broker(): void {
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
	 * Check if this user is broker or admin
	 *
	 * @return bool
	 */
	public function is_broker(): bool {
		return user_can( $this->id, 'broker' ) || $this->is_admin();
	}

	/**
	 * Check if this user is client
	 *
	 * @return bool
	 */
	public function is_client(): bool {
		return user_can( $this->id, 'client' );
	}

	/**
	 * Check if this user is administrator
	 *
	 * @return bool
	 */
	public function is_admin(): bool {
		return user_can( $this->id, 'administrator' );
	}

	/**
	 * Check verification of this user
	 *
	 * @return bool
	 */
	public function is_verified(): bool {
		return (bool) $this->get_field( 'email_verified' );
	}

	/**
	 * Mark email as verified
	 *
	 * @return void
	 */
	public function mark_email_as_verified(): void {
		update_field( 'email_verified', 1, 'user_' . $this->id );
		update_field( 'boost_points', 1000, 'user_' . $this->id );

		$html = __( 'Your email address has been successfully verified.', 'east-property' );
		$html .= __( 'You can now enjoy all the features of your account and start using our services.',
			'east-property' );

		get_template_part(
			'core/components/email/send',
			null,
			array(
				'email'   => $this->wp_user->user_email,
				'subject' => __( 'Email Verified', 'east-property' ),
				'content' => $html,
			)
		);
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

	/**
	 * @return string|null
	 */
	public function get_email(): ?string {
		return $this->wp_user->user_email;
	}

	/**
	 * Get default broker for units without broker
	 *
	 * @return ?WP_User
	 */
	public static function get_default_broker(): ?WP_User {
		$dda_user = get_user_by( 'login', 'dda' );
		if ( ! $dda_user ) {
			return null;
		}

		return $dda_user;
	}

	/**
	 * Update phones for user
	 *
	 * @param array $phones
	 *
	 * @return void
	 */
	public function update_phones( mixed $phones ): void {
		if ( ! is_array( $phones ) && ! empty( $phones ) ) {
			$language = function_exists( 'pll_current_language' ) ? pll_current_language() : 'en';
			$phones   = array( $language => $phones );
		}

		$phones_meta = array();
		foreach ( $phones as $lang => $phone ) {
			$phones_meta[] = array(
				'language' => $lang,
				'phone'    => $phone,
			);
		}

		update_field( 'phones', $phones_meta, 'user_' . $this->id );
	}

	/**
	 * Update whatsapp for all languages
	 *
	 * @param mixed $whatsapp
	 *
	 * @return void
	 */
	public function update_whatsapp( mixed $whatsapp ): void {
		if ( ! is_array( $whatsapp ) && ! empty( $whatsapp ) ) {
			$language = function_exists( 'pll_current_language' ) ? pll_current_language() : 'en';
			$whatsapp = array( $language => $whatsapp );
		}

		$whatsapps_meta = array();
		foreach ( $whatsapp as $lang => $number ) {
			$whatsapps_meta[] = array(
				'language' => $lang,
				'whatsapp' => $number,
			);
		}

		update_field( 'whatsapps', $whatsapps_meta, 'user_' . $this->id );
	}
}
