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

	// Chinese has a single plural form, so a plural entry keeps one string.
	$entry->translations = $entry->is_plural ? array( $singular ) : array( $singular );
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
