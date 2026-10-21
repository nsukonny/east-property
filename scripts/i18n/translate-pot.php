<?php
/**
 * Translate the theme catalogue into one language and dump the raw result.
 * Writes JSON only; the .po is built in a second step after review.
 */
require_once ABSPATH . 'wp-includes/pomo/po.php';

$out_dir = getenv( 'EP_OUT' ) ?: sys_get_temp_dir();
$target  = getenv( 'EP_TARGET' ) ?: 'ZH-HANS';

$po = new PO();
$po->import_from_file( get_stylesheet_directory() . '/languages/east-property.pot' );

$plain = array();
$html  = array();

foreach ( $po->entries as $key => $entry ) {
	foreach ( array( 'singular', 'plural' ) as $slot ) {
		$text = (string) ( $entry->{$slot} ?? '' );

		if ( '' === trim( $text ) ) {
			continue;
		}

		$bucket = preg_match( '~<[a-z/]~i', $text ) ? 'html' : 'plain';
		${$bucket}[ $slot . "\x01" . $key ] = $text;
	}
}

printf( "строк: plain=%d html=%d\n", count( $plain ), count( $html ) );

$client = new Tools\DeepL_Client();
$result = array();

foreach ( array( array( $plain, false ), array( $html, true ) ) as [$texts, $is_html] ) {
	if ( empty( $texts ) ) {
		continue;
	}

	$got = $client->translate( $texts, $target, $is_html, true );

	if ( is_wp_error( $got ) ) {
		fwrite( STDERR, 'ОШИБКА DeepL: ' . $got->get_error_message() . "\n" );
		exit( 1 );
	}

	$result += $got;
}

file_put_contents(
	$out_dir . '/raw.json',
	wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE )
);

printf( "переведено: %d → %s/raw.json\n", count( $result ), $out_dir );
