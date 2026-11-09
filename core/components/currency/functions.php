<?php
/**
 * Currencies of the countries the catalogue covers.
 *
 * AED, SAR, OMR and USD are all pegged to the dollar, so the rates are fixed
 * numbers rather than a feed: nothing to fetch, nothing to cache, nothing to
 * fall back on when the feed is down.
 */

const CORE_BASE_CURRENCY = 'AED';

/**
 * Currency of every country, from term meta or the built-in map.
 *
 * @param string $country Country slug.
 *
 * @return string Currency code.
 */
function core_country_currency( string $country ): string {
	static $known = array();

	if ( isset( $known[ $country ] ) ) {
		return $known[ $country ];
	}

	$defaults = array(
		'uae'          => 'AED',
		'saudi-arabia' => 'SAR',
		'oman'         => 'OMR',
	);

	$term = '' === $country ? false : get_term_by( 'slug', $country, 'location' );
	$code = '';

	if ( $term instanceof WP_Term ) {
		$code = strtoupper( (string) get_term_meta( $term->term_id, 'currency', true ) );
	}

	if ( ! isset( core_currency_rates()[ $code ] ) ) {
		$code = $defaults[ $country ] ?? CORE_BASE_CURRENCY;
	}

	$known[ $country ] = $code;

	return $code;
}

/**
 * How much of the base currency one unit of each currency is worth.
 *
 * @return array<string,float>
 */
function core_currency_rates(): array {
	static $rates = null;

	if ( null !== $rates ) {
		return $rates;
	}

	$defaults = array(
		'AED' => 1.0,
		'SAR' => 0.97933,
		'OMR' => 9.55130,
		'USD' => 3.67250,
	);

	$stored = get_option( 'core_currency_rates', array() );
	$rates  = is_array( $stored ) ? array_merge( $defaults, array_map( 'floatval', $stored ) ) : $defaults;

	$rates[ CORE_BASE_CURRENCY ] = 1.0;

	return $rates;
}

/**
 * Currency the visitor reads prices in.
 *
 * @return string
 */
function core_display_currency(): string {
	static $code = null;

	if ( null !== $code ) {
		return $code;
	}

	$requested = $_REQUEST['currency'] ?? $_COOKIE['ep_currency'] ?? '';
	$requested = is_scalar( $requested ) ? strtoupper( sanitize_text_field( (string) $requested ) ) : '';

	$code = isset( core_currency_rates()[ $requested ] ) ? $requested : CORE_BASE_CURRENCY;

	return $code;
}

/**
 * Convert between two currencies through the base one.
 *
 * @param float  $amount Amount in $from.
 * @param string $from   Source currency.
 * @param string $to     Target currency.
 *
 * @return float
 */
function core_convert_price( float $amount, string $from, string $to ): float {
	$rates = core_currency_rates();
	$from  = strtoupper( $from );
	$to    = strtoupper( $to );

	if ( $from === $to || ! isset( $rates[ $from ], $rates[ $to ] ) || 0.0 === (float) $rates[ $to ] ) {
		return $amount;
	}

	return $amount * $rates[ $from ] / $rates[ $to ];
}

/**
 * Price in the base currency, for sorting and filtering across countries.
 *
 * @param float  $amount   Amount in $currency.
 * @param string $currency Source currency.
 *
 * @return int
 */
function core_price_to_base( float $amount, string $currency ): int {
	return (int) round( core_convert_price( $amount, $currency, CORE_BASE_CURRENCY ) );
}

/**
 * Price with its currency, converted to the one the visitor reads.
 *
 * @param mixed  $amount   Amount in $currency.
 * @param string $currency Currency the amount is stored in.
 *
 * @return string Empty string for an empty amount.
 */
function core_format_price( $amount, string $currency = CORE_BASE_CURRENCY ): string {
	if ( ! is_numeric( $amount ) || 0.0 === (float) $amount ) {
		return '';
	}

	$display = core_display_currency();
	$value   = core_convert_price( (float) $amount, $currency, $display );

	return sprintf( '%s %s', core_currency_label( $display ), number_format( $value, 0, '.', ',' ) );
}

/**
 * Translatable label of a currency code.
 *
 * @param string $code Currency code.
 *
 * @return string
 */
function core_currency_label( string $code ): string {
	$labels = array(
		'AED' => __( 'AED', 'east-property' ),
		'SAR' => __( 'SAR', 'east-property' ),
		'OMR' => __( 'OMR', 'east-property' ),
		'USD' => __( 'USD', 'east-property' ),
	);

	return $labels[ strtoupper( $code ) ] ?? strtoupper( $code );
}

/**
 * Currency a post's prices are stored in, from the country of its location.
 *
 * @param int $post_id Unit or property id.
 *
 * @return string
 */
function core_post_currency( int $post_id ): string {
	$country = core_post_country( $post_id );

	return null === $country ? CORE_BASE_CURRENCY : core_country_currency( (string) $country->slug );
}

/**
 * Price of a post in the base currency.
 *
 * @param int   $post_id  Unit or property id.
 * @param mixed $fallback Stored price, used while price_base is missing.
 *
 * @return float
 */
function core_base_price( int $post_id, $fallback = 0 ): float {
	$stored = $post_id > 0 ? get_post_meta( $post_id, 'price_base', true ) : '';

	return is_numeric( $stored ) ? (float) $stored : (float) $fallback;
}

/**
 * Write the base-currency price of a post from its own currency.
 *
 * @param int $post_id Unit or property id.
 *
 * @return void
 */
function core_sync_base_price( int $post_id ): void {
	$price = get_post_meta( $post_id, 'price', true );

	if ( ! is_numeric( $price ) || 0.0 === (float) $price ) {
		delete_post_meta( $post_id, 'price_base' );

		return;
	}

	update_post_meta( $post_id, 'price_base', core_price_to_base( (float) $price, core_post_currency( $post_id ) ) );
}

/**
 * Keep price_base in step with every write of price.
 *
 * Hooked on the meta rather than on save_post: prices are written by the
 * account forms, the importers and the CLI alike.
 *
 * @param int    $meta_id  Unused.
 * @param int    $post_id  Post the meta belongs to.
 * @param string $meta_key Meta being written.
 *
 * @return void
 */
function core_sync_base_price_on_meta( $meta_id, $post_id, $meta_key ): void {
	if ( 'price' !== $meta_key ) {
		return;
	}

	core_sync_base_price( (int) $post_id );
}

add_action( 'added_post_meta', 'core_sync_base_price_on_meta', 10, 3 );
add_action( 'updated_post_meta', 'core_sync_base_price_on_meta', 10, 3 );

/**
 * Remember the currency the visitor picked.
 *
 * @return void
 */
function core_remember_display_currency(): void {
	$requested = isset( $_REQUEST['currency'] ) ? strtoupper( sanitize_text_field( (string) $_REQUEST['currency'] ) ) : '';

	if ( '' === $requested || ! isset( core_currency_rates()[ $requested ] ) ) {
		return;
	}

	if ( headers_sent() || ( $_COOKIE['ep_currency'] ?? '' ) === $requested ) {
		return;
	}

	setcookie( 'ep_currency', $requested, time() + YEAR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl() );
}

add_action( 'init', 'core_remember_display_currency' );
