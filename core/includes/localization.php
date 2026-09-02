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
 * The language home page is handed back by pll_the_languages() for every language the
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

/**
 * Cyrillic to Latin table used when building slugs.
 *
 * @return array
 */
function core_transliteration_table(): array {
	return array(
		// Russian.
		'А' => 'A',
		'Б' => 'B',
		'В' => 'V',
		'Г' => 'G',
		'Д' => 'D',
		'Е' => 'E',
		'Ё' => 'Yo',
		'Ж' => 'Zh',
		'З' => 'Z',
		'И' => 'I',
		'Й' => 'Y',
		'К' => 'K',
		'Л' => 'L',
		'М' => 'M',
		'Н' => 'N',
		'О' => 'O',
		'П' => 'P',
		'Р' => 'R',
		'С' => 'S',
		'Т' => 'T',
		'У' => 'U',
		'Ф' => 'F',
		'Х' => 'H',
		'Ц' => 'Ts',
		'Ч' => 'Ch',
		'Ш' => 'Sh',
		'Щ' => 'Shch',
		'Ъ' => '',
		'Ы' => 'Y',
		'Ь' => '',
		'Э' => 'E',
		'Ю' => 'Yu',
		'Я' => 'Ya',
		'а' => 'a',
		'б' => 'b',
		'в' => 'v',
		'г' => 'g',
		'д' => 'd',
		'е' => 'e',
		'ё' => 'yo',
		'ж' => 'zh',
		'з' => 'z',
		'и' => 'i',
		'й' => 'y',
		'к' => 'k',
		'л' => 'l',
		'м' => 'm',
		'н' => 'n',
		'о' => 'o',
		'п' => 'p',
		'р' => 'r',
		'с' => 's',
		'т' => 't',
		'у' => 'u',
		'ф' => 'f',
		'х' => 'h',
		'ц' => 'ts',
		'ч' => 'ch',
		'ш' => 'sh',
		'щ' => 'shch',
		'ъ' => '',
		'ы' => 'y',
		'ь' => '',
		'э' => 'e',
		'ю' => 'yu',
		'я' => 'ya',
		// Ukrainian and Belarusian letters that turn up in copied listings.
		'Є' => 'Ye',
		'І' => 'I',
		'Ї' => 'Yi',
		'Ґ' => 'G',
		'Ў' => 'U',
		'є' => 'ye',
		'і' => 'i',
		'ї' => 'yi',
		'ґ' => 'g',
		'ў' => 'u',
		// Typographic characters sanitize_title would otherwise percent-encode.
		'№' => 'no',
		'«' => '',
		'»' => '',
		'“' => '',
		'”' => '',
	);
}

/**
 * Transliterate Cyrillic before a slug is built.
 *
 * Only Latin diacritics are covered by remove_accents(), so anything Cyrillic is
 * percent-encoded by sanitize_title_with_dashes(): a Russian title became a slug like
 * %d0%ba%d0%b2%d0%b0%d1%80%d1%82%d0%b8%d1%80%d0%b0. The same happens to an
 * English title carrying a single Cyrillic homoglyph — "hillсrest" typed with a
 * Russian с produced hill%d1%81rest, a URL that reads as Latin but is not.
 *
 * Only the save context is touched: the query context resolves a request back to
 * a stored post_name, and those already hold percent-encoded slugs for every
 * entry created before this filter existed — transliterating there would stop
 * their URLs from matching.
 *
 * @param string $title     Title after remove_accents().
 * @param string $raw_title Title before sanitisation.
 * @param string $context   Either 'save' or 'query'.
 *
 * @return string
 */
function core_transliterate_title( $title, $raw_title = '', $context = 'save' ) {
	if ( 'save' !== $context || ! is_string( $title ) || '' === $title ) {
		return $title;
	}

	return strtr( $title, core_transliteration_table() );
}

add_filter( 'sanitize_title', 'core_transliterate_title', 9, 3 );

/**
 * Keep a renamed-slug redirect inside the language that was asked for.
 *
 * A post is looked up by _wp_old_slug alone in wp_old_slug_redirect(). Translations that
 * shared a slug before it was cleaned therefore carry the same old value, and the
 * first row wins — a Russian URL answered with a 301 to the English page, which
 * reads as a language mismatch to a crawler. Swapping in the translation of the
 * requested language keeps the redirect where the visitor already was.
 *
 * @param int $post_id Post the old slug was found on.
 *
 * @return int
 */
function core_old_slug_redirect_language( $post_id ) {
	$post_id = (int) $post_id;

	if ( 0 === $post_id
		|| ! function_exists( 'pll_current_language' )
		|| ! function_exists( 'pll_get_post_language' )
		|| ! function_exists( 'pll_get_post' ) ) {
		return $post_id;
	}

	$language = (string) pll_current_language( 'slug' );

	if ( '' === $language || $language === (string) pll_get_post_language( $post_id, 'slug' ) ) {
		return $post_id;
	}

	$translated = (int) pll_get_post( $post_id, $language );

	return 0 < $translated ? $translated : $post_id;
}

add_filter( 'old_slug_redirect_post_id', 'core_old_slug_redirect_language' );
