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
	 * Register every command this class provides.
	 *
	 * @return void
	 */
	public static function register(): void {
		WP_CLI::add_command( 'tools import-properties', array( __CLASS__, 'import_properties' ) );
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
