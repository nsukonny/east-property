<?php

/**
 * Load all tool classes.
 *
 * Usage of the CSV import:
 *
 * wp tools import-properties --dry-run          # ничего не пишет
 * wp tools import-properties --limit=20         # сначала двадцать
 * wp tools import-properties --yes              # без вопроса
 * wp tools import-properties --status=draft     # по умолчанию publish
 * wp tools import-properties --create-developers
 * wp tools import-properties --file=/абсолютный/путь.csv
 *
 * Usage of the distress unit move (JSON from export-distress-units.php):
 *
 * wp tools import-distress-units --dry-run
 * wp tools import-distress-units --limit=3 --skip-media    # на стейдж без загрузки медиа
 * wp tools import-distress-units --author=daria
 * wp tools import-distress-units --redirects=/root/distress-301.map
 * wp tools import-distress-units --no-russian              # без русских версий
 */

require_once get_stylesheet_directory() . '/core/includes/tools/class-tool-property-importer.php';
require_once get_stylesheet_directory() . '/core/includes/tools/class-tool-distress-units-importer.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once get_stylesheet_directory() . '/core/includes/tools/class-tool-cli.php';
	Tools\CLI::register();
}
