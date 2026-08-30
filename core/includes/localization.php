<?php
/** All helpers for localization */

/**
 * Switch current language mo file
 *
 * @param string $locale
 *
 * @return void
 */
function switch_to_lang( string $locale ): void {
	$parts = preg_split( '/[-_]/', $locale );

	if ( count( $parts ) >= 2 ) {
		$locale = strtolower( $parts[0] ) . '_' . strtoupper( $parts[1] );
	}

	switch_to_locale( $locale );
}

/**
 * Get all supported languages or default English if Polylang is not installed
 *
 * @return array[]
 */
function get_all_languages(): array {
	return function_exists( 'pll_languages_list' ) ? pll_the_languages( array( 'raw' => 1 ) )
		: array(
			'en' => array(
				'slug'   => 'en',
				'name'   => 'English',
				'locale' => 'en_US',
			),
		);
}