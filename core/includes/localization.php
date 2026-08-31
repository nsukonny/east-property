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
/**
 * Language switcher entries for the page being rendered.
 *
 * pll_the_languages() hands back the language home page for every language the
 * current entry has no translation in, so a visitor reading a unit who switched
 * to Russian landed on the Russian front page, several clicks away from anything
 * comparable. Each language now gets its translation where one exists, the
 * nearest section that does exist in that language where it does not, and is
 * dropped from the switcher when even that has nothing to offer.
 *
 * @return array
 */
function core_get_language_switcher(): array {
	$current = function_exists( 'pll_current_language' ) ? (string) pll_current_language( 'slug' ) : '';
	$entries = array();

	foreach ( get_all_languages() as $language ) {
		$slug = (string) ( $language['slug'] ?? '' );
		if ( '' === $slug || $slug === $current ) {
			continue;
		}

		$is_translation = empty( $language['no_translation'] );
		$url            = $is_translation
			? (string) ( $language['url'] ?? '' )
			: core_get_nearest_section_url( $slug );

		if ( '' === $url ) {
			continue;
		}

		$entries[] = array(
			'slug'           => $slug,
			'locale'         => (string) ( $language['locale'] ?? $slug ),
			'url'            => $url,
			'is_translation' => $is_translation,
			'label'          => 'ru' === $slug ? 'РУ' : strtoupper( $slug ),
		);
	}

	return $entries;
}

/**
 * Closest section of the site that exists in the given language.
 *
 * A unit lands on the listing of its own type, anything project shaped on the
 * projects listing, a post on the news listing. The section pages are looked up
 * through their translations rather than by building a URL by hand, so a section
 * that is not translated — or not published — is reported as missing instead of
 * sending the visitor to a 404.
 *
 * @param string $lang Language slug.
 *
 * @return string Empty when the language has no comparable section.
 */
function core_get_nearest_section_url( string $lang ): string {
	$section = '';

	if ( is_singular( 'unit' ) ) {
		$listing_type = (string) get_post_meta( get_queried_object_id(), 'listing_type', true );
		$section      = function_exists( 'core_sanitize_listing_type' )
			? core_sanitize_listing_type( $listing_type )
			: 'off-plan';
	} elseif ( is_singular( array( 'property', 'developers' ) ) || is_tax( 'location' ) ) {
		$section = 'projects';
	} elseif ( is_singular( 'post' ) ) {
		$section = 'news';
	}

	if ( '' !== $section ) {
		$url = core_get_translated_page_url( $section, $lang );
		if ( '' !== $url ) {
			return $url;
		}
	}

	return function_exists( 'pll_home_url' ) ? (string) pll_home_url( $lang ) : '';
}

/**
 * URL of a page in the given language, addressed by the slug of its original.
 *
 * @param string $slug Slug of the page in the default language.
 * @param string $lang Language slug.
 *
 * @return string Empty when that language has no published translation.
 */
function core_get_translated_page_url( string $slug, string $lang ): string {
	$page = get_page_by_path( $slug );
	if ( ! $page || ! function_exists( 'pll_get_post_translations' ) ) {
		return '';
	}

	$target = pll_get_post_translations( $page->ID )[ $lang ] ?? 0;
	if ( ! $target || 'publish' !== get_post_status( $target ) ) {
		return '';
	}

	return (string) get_permalink( $target );
}
