<?php
/**
 * Translate the theme catalogue into one language and dump the raw result.
 * Writes JSON only; the .po is built in a second step after review.
 */
require_once ABSPATH . 'wp-includes/pomo/po.php';

$out_dir = getenv( 'EP_OUT' ) ?: sys_get_temp_dir();
$target  = getenv( 'EP_TARGET' ) ?: 'ZH-HANS';

$options = array_filter(
	array(
		'source_lang' => getenv( 'EP_SOURCE' ) ?: 'EN',
		'model_type'  => getenv( 'EP_MODEL' ) ?: 'quality_optimized',
		'context'     => getenv( 'EP_CONTEXT' ) ?: '',
	)
);

$hints = array();

if ( is_readable( $out_dir . '/hints.json' ) ) {
	$hints = (array) json_decode( (string) file_get_contents( $out_dir . '/hints.json' ), true );
}

$po = new PO();
$po->import_from_file( get_stylesheet_directory() . '/languages/east-property.pot' );

$plain  = array();
$html   = array();
$hinted = 0;

foreach ( $po->entries as $key => $entry ) {
	foreach ( array( 'singular', 'plural' ) as $slot ) {
		$text = (string) ( $entry->{$slot} ?? '' );

		if ( '' === trim( $text ) ) {
			continue;
		}

		// Подсказка подменяет английский источник, а не ответ: в каталоге
		// «Bed» значит спальню, и DeepL об этом знать неоткуда.
		$lookup = ( '' !== (string) $entry->context ? $entry->context . "\x04" : '' ) . $text;
		$sent   = (string) ( $hints[ $lookup ] ?? $hints[ $text ] ?? $text );

		if ( $sent !== $text ) {
			++ $hinted;
		}

		$bucket = preg_match( '~<[a-z/]~i', $sent ) ? 'html' : 'plain';
		${$bucket}[ $slot . "\x01" . $key ] = $sent;
	}
}

printf(
	"строк: plain=%d html=%d, с подсказкой %d; модель %s, источник %s\n",
	count( $plain ),
	count( $html ),
	$hinted,
	$options['model_type'] ?? 'по умолчанию',
	$options['source_lang'] ?? 'автодетект'
);

$client = new Tools\DeepL_Client();
$result = array();

foreach ( array( array( $plain, false ), array( $html, true ) ) as [$texts, $is_html] ) {
	if ( empty( $texts ) ) {
		continue;
	}

	$got = $client->translate( $texts, $target, $is_html, true, $options );

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
