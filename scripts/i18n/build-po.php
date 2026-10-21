<?php
/**
 * Build languages/<locale>.po from the catalogue, the DeepL output and the
 * project overrides. Rerunning it is safe: the file is written from scratch.
 */
require_once ABSPATH . 'wp-includes/pomo/po.php';

$dir       = getenv( 'EP_OUT' );
$locale    = getenv( 'EP_LOCALE' ) ?: 'zh_CN';
$plural    = getenv( 'EP_PLURAL' ) ?: 'nplurals=1; plural=0;';
$lang_dir  = get_stylesheet_directory() . '/languages';

$raw       = json_decode( (string) file_get_contents( $dir . '/raw.json' ), true );
$overrides = json_decode( (string) file_get_contents( $dir . '/overrides.json' ), true );

$po = new PO();
$po->import_from_file( $lang_dir . '/east-property.pot' );

$po->set_headers(
	array(
		'Project-Id-Version'        => 'East Property',
		'Report-Msgid-Bugs-To'      => '',
		'Last-Translator'           => '',
		'Language-Team'             => '',
		'MIME-Version'              => '1.0',
		'Content-Type'              => 'text/plain; charset=UTF-8',
		'Content-Transfer-Encoding' => '8bit',
		'PO-Revision-Date'          => gmdate( 'Y-m-d H:iO' ),
		'Language'                  => $locale,
		'Plural-Forms'              => $plural,
		'X-Generator'               => 'east-property make-translations',
	)
);

// Сколько форм множественного числа у языка — читаем из заголовка Plural-Forms.
preg_match( '~nplurals\s*=\s*(\d+)~', $plural, $forms );
$nplurals = max( 1, (int) ( $forms[1] ?? 1 ) );

$counts = array( 'deepl' => 0, 'override' => 0, 'empty' => 0 );

foreach ( $po->entries as $key => $entry ) {
	$lookup = ( '' !== (string) $entry->context ? $entry->context . "\x04" : '' ) . $entry->singular;

	$singular = $overrides[ $lookup ] ?? $overrides[ $entry->singular ] ?? null;

	if ( null !== $singular ) {
		++ $counts['override'];
	} else {
		$singular = $raw[ 'singular' . "\x01" . $key ] ?? '';
		if ( '' !== $singular ) {
			++ $counts['deepl'];
		} else {
			++ $counts['empty'];
		}
	}

	if ( ! $entry->is_plural ) {
		$entry->translations = array( $singular );

		continue;
	}

	// Вторая форма берётся из перевода msgid_plural. Языку с одной формой
	// (китайский) достаётся только первая, и хвост отбрасывается.
	$other = $overrides[ $entry->plural ] ?? $raw[ 'plural' . "\x01" . $key ] ?? $singular;

	$entry->translations = array_slice( array( $singular, $other ), 0, $nplurals );
}

$po->export_to_file( $lang_dir . '/' . $locale . '.po' );

printf(
	"%s.po: записей %d, из DeepL %d, переопределено %d, пусто %d\n",
	$locale,
	count( $po->entries ),
	$counts['deepl'],
	$counts['override'],
	$counts['empty']
);
