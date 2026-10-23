<?php
/**
 * Social links of the footer.
 *
 * The addresses are kept in the `social_links` group on the theme options page,
 * a sub-group per language.
 */

/**
 * Networks the footer may show, in display order.
 *
 * The key matches a sub-field of `social_links` and is also the modifier of the
 * link class; the icon behind it is assigned in footer.scss, so the markup
 * carries no file name.
 *
 * @return array<string, string> Key to label.
 */
function core_social_networks(): array {
	return array(
		'facebook'  => 'Facebook',
		'instagram' => 'Instagram',
		'telegram'  => 'Telegram',
		'vk'        => 'VK',
		'linkedin'  => 'LinkedIn',
		'x'         => 'X',
	);
}

/**
 * Addresses of a language, with English as the fallback.
 *
 * A language that has no sub-group of its own, or whose sub-group is left
 * blank, shows the English addresses.
 *
 * @param array  $values   Value of the social_links group.
 * @param string $language Polylang slug.
 *
 * @return array<string, string> Network key to address.
 */
function core_social_set( array $values, string $language ): array {
	foreach ( array( $language, 'en' ) as $slug ) {
		$set = $values[ $slug ] ?? array();

		if ( is_array( $set ) && array_filter( array_map( 'trim', array_map( 'strval', $set ) ) ) ) {
			return $set;
		}
	}

	return array();
}

/**
 * Links of the current language, in the order of core_social_networks().
 *
 * @return array[] Keys key, label, url. Networks left blank are left out.
 */
function core_social_links(): array {
	if ( ! function_exists( 'get_field' ) ) {
		return array();
	}

	$values = get_field( 'social_links', 'option' );
	$values = is_array( $values ) ? $values : array();
	$set    = core_social_set( $values, core_get_current_language() );
	$links  = array();

	foreach ( core_social_networks() as $key => $label ) {
		$url = trim( (string) ( $set[ $key ] ?? '' ) );

		if ( '' === $url ) {
			continue;
		}

		$links[] = array(
			'key'   => $key,
			'label' => $label,
			'url'   => $url,
		);
	}

	return $links;
}
