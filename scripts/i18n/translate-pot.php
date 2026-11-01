<?php
/**
 * Translate the theme catalogue into one language and dump the raw result.
 * Writes JSON only; the .po is built in a second step after review.
 */
require_once ABSPATH . 'wp-includes/pomo/po.php';

$out_dir = getenv( 'EP_OUT' ) ?: sys_get_temp_dir();
$client  = Tools\Translation_Client::make();

// EP_LANG — слаг Polylang, провайдер сам скажет, как он называет этот язык.
// EP_TARGET остаётся для прежних вызовов, где код DeepL задавался напрямую.
$lang   = getenv( 'EP_LANG' ) ?: '';
$target = '' !== $lang ? $client->target_for( $lang ) : ( getenv( 'EP_TARGET' ) ?: 'ZH-HANS' );

if ( '' === $lang && ! $client instanceof Tools\DeepL_Client ) {
	fwrite( STDERR, "нужен EP_LANG: EP_TARGET — это код языка DeepL\n" );
	exit( 1 );
}

if ( '' === $target ) {
	fwrite( STDERR, sprintf( "язык «%s» провайдер не поддерживает\n", $lang ) );
	exit( 1 );
}

$options = $client instanceof Tools\DeepL_Client
	? array_filter(
		array(
			'source_lang' => getenv( 'EP_SOURCE' ) ?: 'EN',
			'model_type'  => getenv( 'EP_MODEL' ) ?: 'quality_optimized',
			'context'     => getenv( 'EP_CONTEXT' ) ?: '',
		)
	)
	: array();

$hints = array();

if ( is_readable( $out_dir . '/hints.json' ) ) {
	$hints = (array) json_decode( (string) file_get_contents( $out_dir . '/hints.json' ), true );
}

$po = new PO();
$po->import_from_file( get_stylesheet_directory() . '/languages/east-property.pot' );

$plain    = array();
$html     = array();
$contexts = array();
$hinted   = 0;

foreach ( $po->entries as $key => $entry ) {
	foreach ( array( 'singular', 'plural' ) as $slot ) {
		$text = (string) ( $entry->{$slot} ?? '' );

		if ( '' === trim( $text ) ) {
			continue;
		}

		// Подсказка подменяет английский источник, а не ответ: в каталоге
		// «Bed» значит спальню, и переводчику об этом знать неоткуда.
		$lookup = ( '' !== (string) $entry->context ? $entry->context . "\x04" : '' ) . $text;
		$sent   = (string) ( $hints[ $lookup ] ?? $hints[ $text ] ?? $text );

		if ( $sent !== $text ) {
			++ $hinted;
		}

		$bucket = preg_match( '~<[a-z/]~i', $sent ) ? 'html' : 'plain';
		$field  = $slot . "\x01" . $key;

		${$bucket}[ $field ] = $sent;

		// msgctxt в каталоге есть ровно затем, чтобы различать одинаковые
		// msgid: «Q1» как квартал сдачи и «Q1» как что угодно ещё. Но ярлыки
		// типов записи WordPress — «Post Type Singular Name» и подобные —
		// про слот в массиве labels, а не про смысл слова. Замер: с ними в
		// партии модель принимает короткие строки за системные коды и
		// возвращает «Q1» вместо «पहली तिमाही», без них переводит.
		$context = (string) $entry->context;

		if ( '' !== $context && ! preg_match( '~^(Post Type|Taxonomy) .*Name$~', $context ) ) {
			$contexts[ $field ] = $context;
		}
	}
}

printf(
	"строк: plain=%d html=%d, с подсказкой %d, с контекстом %d; провайдер %s, цель %s\n",
	count( $plain ),
	count( $html ),
	$hinted,
	count( $contexts ),
	( new ReflectionClass( $client ) )->getShortName(),
	$target
);

$result = array();

foreach ( array( array( $plain, false ), array( $html, true ) ) as [$texts, $is_html] ) {
	if ( empty( $texts ) ) {
		continue;
	}

	$got = $client->translate(
		$texts,
		$target,
		$is_html,
		true,
		$options,
		array_intersect_key( $contexts, $texts )
	);

	if ( is_wp_error( $got ) ) {
		fwrite( STDERR, 'ОШИБКА перевода: ' . $got->get_error_message() . "\n" );
		exit( 1 );
	}

	$result += $got;
}

file_put_contents(
	$out_dir . '/raw.json',
	wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE )
);

printf( "переведено: %d → %s/raw.json\n", count( $result ), $out_dir );
