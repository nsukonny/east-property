<?php
/**
 * WP-CLI commands for the tools in this directory.
 *
 * @package east-property
 */

namespace Tools;

use WP_CLI;
use WP_CLI\Utils;

/**
 * Exposes the tools as `wp tools <command>`.
 */
final class CLI {

	/**
	 * Default CSV, resolved inside the uploads directory.
	 */
	private const DEFAULT_FILE = 'import/properties_list.csv';

	/**
	 * Default unit export, resolved inside the uploads directory.
	 */
	private const DEFAULT_UNITS_FILE = 'import/distress-units.json';

	/**
	 * Register every command this class provides.
	 *
	 * @return void
	 */
	public static function register(): void {
		WP_CLI::add_command( 'tools import-properties', array( __CLASS__, 'import_properties' ) );
		WP_CLI::add_command( 'tools import-distress-units', array( __CLASS__, 'import_distress_units' ) );
	}

	/**
	 * Move the units a distressuae.com export lists onto this site as distress listings.
	 *
	 * Reads the JSON produced by export-distress-units.php. Every unit is created
	 * with listing_type = distress, owned by the given author, who also becomes
	 * its broker. A project the site does not have is created as a published
	 * stub carrying a noindex robots meta: resolve_property_id() accepts a
	 * project only in `publish`, so a draft stub would degrade the unit's url
	 * to /property/no-project/... and make the unit answer 404. The robots meta
	 * is what keeps the empty page out of the index and the sitemap instead.
	 *
	 * The run is idempotent: each post carries _core_import_source with the
	 * source host and id, so a second run skips what it already made, and the
	 * whole batch stays findable afterwards:
	 *
	 *     wp post list --post_type=unit --meta_key=_core_import_source \
	 *       --meta_compare=LIKE --meta_value=distressuae
	 *
	 * ## OPTIONS
	 *
	 * [--file=<path>]
	 * : JSON export. Relative paths resolve inside uploads.
	 * ---
	 * default: import/distress-units.json
	 * ---
	 *
	 * [--author=<login>]
	 * : User to own the units and act as their broker. Defaults to the author
	 * login the export itself names, so the same command works everywhere. Must
	 * exist on this site and be able to act as a broker, otherwise the card
	 * renders without one. Pass this only when the login differs here.
	 *
	 * [--status=<status>]
	 * : Status for created units.
	 * ---
	 * default: publish
	 * ---
	 *
	 * [--project-status=<status>]
	 * : Status for project stubs. Must stay publish: resolve_property_id() accepts
	 * a project only in publish, and a draft stub makes the unit answer 404.
	 * ---
	 * default: publish
	 * ---
	 *
	 * [--limit=<number>]
	 * : Stop after this many units. Zero means all of them.
	 * ---
	 * default: 0
	 * ---
	 *
	 * [--redirects=<path>]
	 * : Write an nginx map of old path => new url, for the 301s on distressuae.
	 *
	 * [--skip-media]
	 * : Do not fetch gallery images. Useful for a fast structural rehearsal.
	 *
	 * [--no-russian]
	 * : Skip the Russian counterpart. By default one is always created, carrying
	 * the English text and flagged need_translate = 1.
	 *
	 * [--dry-run]
	 * : Report what would happen and write nothing.
	 *
	 * [--yes]
	 * : Do not ask for confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tools import-distress-units --dry-run
	 *     wp tools import-distress-units --limit=3 --skip-media
	 *     wp tools import-distress-units --redirects=/root/distress-301.map
	 *
	 * @param array $args Positional arguments, unused.
	 * @param array $assoc_args Flags.
	 *
	 * @return void
	 */
	public static function import_distress_units( array $args, array $assoc_args ): void {
		$file = self::resolve_json( (string) Utils\get_flag_value( $assoc_args, 'file', self::DEFAULT_UNITS_FILE ) );

		$dry_run        = (bool) Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$limit          = (int) Utils\get_flag_value( $assoc_args, 'limit', 0 );
		$status         = (string) Utils\get_flag_value( $assoc_args, 'status', 'publish' );
		$project_status = (string) Utils\get_flag_value( $assoc_args, 'project-status', 'publish' );
		$skip_media     = (bool) Utils\get_flag_value( $assoc_args, 'skip-media', false );
		$with_russian   = ! (bool) Utils\get_flag_value( $assoc_args, 'no-russian', false );
		$redirects      = (string) Utils\get_flag_value( $assoc_args, 'redirects', '' );
		$author_flag    = (string) Utils\get_flag_value( $assoc_args, 'author', '' );

		$importer = new Distress_Units_Importer( $file );
		$preview  = $importer->preview();

		// Автор по умолчанию берётся из самой выгрузки: файл знает, чьи это
		// записи, а логин, прописанный в коде, разошёлся бы с ним при первой же
		// смене. Флаг остаётся для случая, когда на приёмнике логин другой.
		$author_login = '' !== $author_flag ? $author_flag : $preview['author_login'];
		$author       = self::resolve_broker( $author_login );

		WP_CLI::log( sprintf( 'File:        %s', $file ) );
		WP_CLI::log( sprintf( 'Source:      %s (выгружено %s)', $preview['source'], $preview['exported_at'] ) );
		WP_CLI::log( sprintf( 'Units:       %d', $preview['units'] ) );
		WP_CLI::log( sprintf( 'Already in:  %d', $preview['already'] ) );
		WP_CLI::log(
			sprintf(
				'Gallery:     %d файлов, юнитов без галереи: %d',
				$preview['gallery_files'],
				$preview['no_gallery']
			)
		);
		WP_CLI::log( sprintf( 'No project:  %d', $preview['no_project'] ) );
		WP_CLI::log(
			sprintf(
				'Author:      #%d %s%s',
				$author,
				$author_login,
				'' === $author_flag ? ' (из выгрузки: ' . $preview['author_name'] . ')' : ' (задан флагом)'
			)
		);

		if ( ! empty( $preview['new_projects'] ) ) {
			WP_CLI::log( sprintf( 'Projects to create as %s: %d', $project_status, count( $preview['new_projects'] ) ) );
			foreach ( array_slice( $preview['new_projects'], 0, 15, true ) as $slug => $title ) {
				WP_CLI::log( sprintf( '  %-46s %s', $slug, $title ) );
			}
			if ( count( $preview['new_projects'] ) > 15 ) {
				WP_CLI::log( sprintf( '  ... ещё %d', count( $preview['new_projects'] ) - 15 ) );
			}
		}

		if ( ! empty( $preview['unknown_terms'] ) ) {
			WP_CLI::warning(
				sprintf(
					'локаций нет в таксономии и они НЕ будут созданы: %s',
					implode( ', ', array_slice( $preview['unknown_terms'], 0, 20 ) )
				)
			);
		}

		foreach ( array_slice( $preview['warnings'], 0, 10 ) as $warning ) {
			WP_CLI::warning( sprintf( 'выгрузка: %s', $warning ) );
		}

		$planned = $preview['units'] - $preview['already'];
		if ( $limit > 0 ) {
			$planned = min( $limit, $planned );
		}

		// С --redirects проходим по файлу даже когда создавать нечего: карта
		// собирается и для уже перенесённых юнитов, а нужна она отдельным
		// шагом позже, когда придёт время ставить 301 на distressuae.
		if ( 0 === $planned && '' === $redirects ) {
			WP_CLI::success( 'нечего переносить: всё уже на месте' );

			return;
		}

		if ( 0 === $planned ) {
			WP_CLI::log( 'Создавать нечего — собираю только карту редиректов.' );
		}

		if ( ! $dry_run && $planned > 0 ) {
			WP_CLI::confirm(
				sprintf(
					'Создать %d units со статусом %s%s?',
					$planned,
					$status,
					$with_russian ? ' и русские версии к ним' : ''
				),
				$assoc_args
			);
		}

		$report = $importer->import(
			array(
				'dry_run'        => $dry_run,
				'limit'          => $limit,
				'status'         => $status,
				'project_status' => $project_status,
				'author'         => $author,
				'skip_media'     => $skip_media,
				'with_russian'   => $with_russian,
			)
		);

		self::report_units_result( $report );
		self::report_units_errors( $importer );

		if ( '' !== $redirects && ! $dry_run ) {
			self::write_redirect_map( $redirects, $importer->get_redirects() );
		}

		if ( $dry_run ) {
			WP_CLI::success( 'dry-run завершён, ничего не записано' );

			return;
		}

		self::flush_caches();
		WP_CLI::success( 'перенос завершён' );
	}

	/**
	 * Resolve the user who will own the units and act as their broker.
	 *
	 * Refuses rather than guessing: Unit::get_broker() returns null unless the
	 * user can act as a broker, and a silently brokerless card is worse than a
	 * stopped import. Creating the account is deliberately left to a human.
	 *
	 * @param string $login User login or id.
	 *
	 * @return int User id.
	 */
	private static function resolve_broker( string $login ): int {
		if ( '' === $login ) {
			WP_CLI::error( 'в выгрузке нет автора — укажите --author=<логин>' );
		}

		$user = is_numeric( $login ) ? get_user_by( 'id', (int) $login ) : get_user_by( 'login', $login );

		if ( ! $user ) {
			WP_CLI::error(
				sprintf(
					"пользователь '%s' не найден. Создайте его и повторите:\n" .
					'  wp user create %s %s@eastproperty.com --role=broker --display_name=Daria',
					$login,
					$login,
					$login
				)
			);
		}

		if ( ! user_can( $user->ID, 'broker' ) && ! user_can( $user->ID, 'administrator' ) ) {
			WP_CLI::error(
				sprintf(
					"у пользователя '%s' (#%d) нет права broker, и карточки останутся без брокера.\n" .
					'  wp user add-cap %d broker',
					$login,
					$user->ID,
					$user->ID
				)
			);
		}

		return (int) $user->ID;
	}

	/**
	 * Write the nginx map for the 301s that have to live on distressuae.
	 *
	 * A map file rather than WordPress rules on purpose: it keeps answering
	 * after the old site's theme and database are gone, which is the point of
	 * retiring it.
	 *
	 * @param string $path      Where to write.
	 * @param array  $redirects Old path => new url.
	 *
	 * @return void
	 */
	private static function write_redirect_map( string $path, array $redirects ): void {
		if ( empty( $redirects ) ) {
			WP_CLI::warning( 'карта редиректов пустая — нечего писать' );

			return;
		}

		$lines = array(
			'# 301 с distressuae.com на eastproperty.com',
			'# Сгенерировано ' . gmdate( 'c' ) . ', записей: ' . count( $redirects ),
			'#',
			'# Подключение в nginx на distressuae:',
			'#   map $request_uri $ep_redirect { include ' . basename( $path ) . '; default ""; }',
			'#   if ($ep_redirect != "") { return 301 $ep_redirect; }',
			'',
		);

		foreach ( $redirects as $old => $new ) {
			$lines[] = sprintf( '%s %s;', $old, $new );
		}

		if ( false === file_put_contents( $path, implode( "\n", $lines ) . "\n" ) ) {
			WP_CLI::warning( sprintf( 'не удалось записать %s', $path ) );

			return;
		}

		WP_CLI::log( sprintf( 'Карта редиректов: %s (%d записей)', $path, count( $redirects ) ) );
	}

	/**
	 * Print the counters the unit import returned.
	 *
	 * @param array $report Report from Distress_Units_Importer::import().
	 *
	 * @return void
	 */
	private static function report_units_result( array $report ): void {
		$rows = array();

		foreach ( array(
			'created'          => 'Units создано',
			'created_ru'       => 'Русских версий создано (need_translate = 1)',
			'projects_created' => 'Проектов-заготовок создано',
			'media_fetched'    => 'Файлов галереи скачано',
			'media_reused'     => 'Файлов переиспользовано без закачки',
			'terms_assigned'   => 'Привязок location',
			'skipped_existing' => 'Уже было на сайте, пропущено',
			'failed'           => 'Не удалось',
		) as $key => $label ) {
			$rows[] = array(
				'what'  => $label,
				'count' => (int) ( $report[ $key ] ?? 0 ),
			);
		}

		Utils\format_items( 'table', $rows, array( 'what', 'count' ) );
	}

	/**
	 * Print what the unit importer complained about.
	 *
	 * @param Distress_Units_Importer $importer Importer that has finished.
	 *
	 * @return void
	 */
	private static function report_units_errors( Distress_Units_Importer $importer ): void {
		$errors = $importer->get_errors();

		if ( empty( $errors ) ) {
			return;
		}

		$shown = 0;

		foreach ( $errors as $id => $messages ) {
			foreach ( (array) $messages as $message ) {
				if ( $shown >= 20 ) {
					break 2;
				}

				WP_CLI::warning( sprintf( '%s: %s', $id, $message ) );
				++$shown;
			}
		}

		$total = array_sum( array_map( 'count', $errors ) );

		if ( $total > $shown ) {
			WP_CLI::log( sprintf( '... ещё %d замечаний', $total - $shown ) );
		}
	}

	/**
	 * Resolve a JSON export path, listing what is actually there when it is missing.
	 *
	 * @param string $file Absolute path, or one relative to uploads.
	 *
	 * @return string
	 */
	private static function resolve_json( string $file ): string {
		$path = $file;

		if ( '/' !== substr( $file, 0, 1 ) ) {
			$uploads = wp_get_upload_dir();
			$path    = trailingslashit( $uploads['basedir'] ) . ltrim( $file, '/' );
		}

		if ( is_readable( $path ) ) {
			return $path;
		}

		$found = glob( dirname( $path ) . '/*.[jJ][sS][oO][nN]' );

		if ( empty( $found ) ) {
			WP_CLI::error( sprintf( 'JSON не найден: %s', $path ) );
		}

		WP_CLI::error(
			sprintf(
				"%s не существует. Есть эти, и регистр имени на сервере важен:\n  %s",
				$path,
				implode( "\n  ", array_map( 'basename', $found ) )
			)
		);
	}

	/**
	 * Create the projects a CSV export lists and the database does not have.
	 *
	 * Matching is by the slug WordPress would build from the English title,
	 * which is what the URL is made of, so a project already on the site is
	 * recognised however differently the export spells it. Such a project is
	 * left as it is, except that a Russian version it lacks is added. Every
	 * project ends up with one: where the export says nothing in Russian the
	 * English text stands in and the post is flagged need_translate = 1, so the
	 * backlog stays findable. A second run of the same file therefore changes
	 * nothing.
	 *
	 * ## OPTIONS
	 *
	 * [--file=<path>]
	 * : CSV to read. A relative path resolves inside wp-content/uploads.
	 * ---
	 * default: import/properties_list.csv
	 * ---
	 *
	 * [--status=<status>]
	 * : Status the created projects are saved as.
	 * ---
	 * default: publish
	 * options:
	 *   - publish
	 *   - draft
	 *   - pending
	 * ---
	 *
	 * [--limit=<number>]
	 * : Stop after this many projects. Zero imports all of them.
	 * ---
	 * default: 0
	 * ---
	 *
	 * [--create-developers]
	 * : Create a developer post when the name in the export matches none. Off by
	 * default: the export spells one company several ways, and each spelling
	 * would become a developer of its own.
	 *
	 * [--dry-run]
	 * : Report what would be created and write nothing.
	 *
	 * [--yes]
	 * : Do not ask before writing.
	 *
	 * ## EXAMPLES
	 *
	 *     # See what the file would add.
	 *     $ wp tools import-properties --dry-run
	 *
	 *     # Try twenty of them first.
	 *     $ wp tools import-properties --limit=20
	 *
	 *     # Import the rest without a prompt.
	 *     $ wp tools import-properties --yes
	 *
	 *     # Keep them out of sight until reviewed.
	 *     $ wp tools import-properties --status=draft
	 *
	 * @param array $args       Positional arguments, none are used.
	 * @param array $assoc_args Options described above.
	 *
	 * @return void
	 */
	public static function import_properties( array $args, array $assoc_args ): void {
		$file = self::resolve_file( (string) Utils\get_flag_value( $assoc_args, 'file', self::DEFAULT_FILE ) );

		$dry_run = (bool) Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$limit   = (int) Utils\get_flag_value( $assoc_args, 'limit', 0 );
		$status  = (string) Utils\get_flag_value( $assoc_args, 'status', 'publish' );

		$importer = new Property_Importer( $file );
		$rows     = $importer->parse();

		if ( empty( $rows ) ) {
			self::report_errors( $importer );
			WP_CLI::error( sprintf( 'nothing to import from %s', $file ) );
		}

		$preview = $importer->preview();

		WP_CLI::log( sprintf( 'File:     %s', $file ) );
		WP_CLI::log( sprintf( 'Rows:     %d', $preview['in_file'] ) );
		WP_CLI::log( sprintf( 'On site:  %d', $preview['existing'] ) );
		WP_CLI::log( sprintf( 'New:      %d', $preview['new'] ) );
		WP_CLI::log(
			sprintf(
				'Russian:  %d of %d rows carry Russian text, the rest fall back to English',
				$preview['with_russian'],
				$preview['in_file']
			)
		);

		$planned = $limit > 0 ? min( $limit, $preview['new'] ) : $preview['new'];

		if ( ! $dry_run ) {
			// Even with nothing to create there may be translations to add, so the
			// question names both halves of the work.
			WP_CLI::confirm(
				0 === $planned
					? 'Nothing to create. Add the missing translations?'
					: sprintf( 'Create %d projects as %s, and add missing translations?', $planned, $status ),
				$assoc_args
			);
		}

		$bar = Utils\make_progress_bar(
			$dry_run ? 'Checking' : 'Importing',
			$preview['in_file']
		);

		// Recounting terms after every insert is the expensive part of a bulk run.
		wp_defer_term_counting( true );

		$report = $importer->import(
			array(
				'dry_run'           => $dry_run,
				'status'            => $status,
				'limit'             => $limit,
				'create_developers' => (bool) Utils\get_flag_value( $assoc_args, 'create-developers', false ),
				'on_progress'       => static function () use ( $bar ): void {
					$bar->tick();
				},
			)
		);

		$bar->finish();

		wp_defer_term_counting( false );

		self::report_result( $report );
		self::report_errors( $importer );

		if ( $dry_run ) {
			WP_CLI::success(
				sprintf(
					'%d projects and %d translations would be added, nothing was written',
					$report['created'],
					$report['translated']
				)
			);

			return;
		}

		self::flush_caches();

		WP_CLI::success(
			sprintf(
				'%d projects created as %s, %d translations added',
				$report['created'],
				$status,
				$report['translated']
			)
		);
	}

	/**
	 * Clear everything that would otherwise keep serving the old catalogue.
	 *
	 * The order is deliberate. Rewrite rules go first, so any page rebuilt after
	 * this runs against the rules the new projects need. The page cache goes
	 * last, because emptying it earlier would let a stale page be cached again
	 * while the rest was still settling.
	 *
	 * @return void
	 */
	private static function flush_caches(): void {
		WP_CLI::log( 'Flushing rewrite rules' );
		WP_CLI::runcommand(
			'rewrite flush --hard',
			array(
				'launch'     => false,
				'exit_error' => false,
			)
		);

		WP_CLI::log( 'Deleting transients' );
		WP_CLI::runcommand(
			'transient delete --all',
			array(
				'launch'     => false,
				'exit_error' => false,
			)
		);

		// Keyed by a filter hash and versioned apart from the transients, so
		// deleting those does not on its own retire a listing already in memory.
		if ( function_exists( 'core_flush_listing_caches' ) ) {
			WP_CLI::log( 'Retiring the listing caches' );
			core_flush_listing_caches();
		}

		if ( function_exists( 'w3tc_flush_all' ) ) {
			// Also fires the w3tc_flush_all action, which the theme uses to purge
			// transients from the object cache that `transient delete` cannot see.
			WP_CLI::log( 'Flushing W3 Total Cache' );
			w3tc_flush_all();

			return;
		}

		WP_CLI::log( 'W3 Total Cache is not active, page cache left alone' );
	}

	/**
	 * Turn the --file value into a path on disk.
	 *
	 * A relative path is taken as living in the uploads directory, which is
	 * where an export gets dropped. When nothing is there, the CSV files that
	 * are get listed: on a case sensitive filesystem Properties_List.csv and
	 * properties_list.csv are two different names, and the export tends to
	 * arrive with the capitals still on.
	 *
	 * @param string $file Value of --file.
	 *
	 * @return string
	 */
	private static function resolve_file( string $file ): string {
		$path = $file;

		if ( '/' !== substr( $file, 0, 1 ) ) {
			$uploads = wp_get_upload_dir();
			$path    = trailingslashit( $uploads['basedir'] ) . ltrim( $file, '/' );
		}

		if ( is_readable( $path ) ) {
			return $path;
		}

		$found = glob( dirname( $path ) . '/*.[cC][sS][vV]' );

		if ( empty( $found ) ) {
			WP_CLI::error( sprintf( 'no CSV found at %s', $path ) );
		}

		WP_CLI::error(
			sprintf(
				"%s does not exist. These do, and the name is case sensitive on the server:\n  %s",
				$path,
				implode( "\n  ", array_map( 'basename', $found ) )
			)
		);
	}

	/**
	 * Print the counters an import returned.
	 *
	 * @param array $report Report from Property_Importer::import().
	 *
	 * @return void
	 */
	private static function report_result( array $report ): void {
		$rows = array();

		foreach ( array(
			'created'             => 'Created',
			'translated'          => 'Russian version added to an existing project',
			'placeholder_ru'      => 'Russian copied from English (need_translate = 1)',
			'skipped'             => 'Already on site, nothing to add',
			'failed'              => 'Failed',
			'without_developer'   => 'Created without a developer',
			'without_coordinates' => 'Created without coordinates',
		) as $key => $label ) {
			$rows[] = array(
				'what'  => $label,
				'count' => (int) ( $report[ $key ] ?? 0 ),
			);
		}

		Utils\format_items( 'table', $rows, array( 'what', 'count' ) );
	}

	/**
	 * Print what the importer complained about, capped so a long run stays readable.
	 *
	 * @param Property_Importer $importer Importer that has finished.
	 *
	 * @return void
	 */
	private static function report_errors( Property_Importer $importer ): void {
		$errors = $importer->get_errors();

		if ( empty( $errors ) ) {
			return;
		}

		$shown = array_slice( $errors, 0, 20 );

		foreach ( $shown as $error ) {
			WP_CLI::warning( sprintf( '%s: %s', $error['id'], $error['message'] ) );
		}

		if ( count( $errors ) > count( $shown ) ) {
			WP_CLI::log( sprintf( '... and %d more', count( $errors ) - count( $shown ) ) );
		}
	}
}
