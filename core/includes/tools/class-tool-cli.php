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
		WP_CLI::add_command( 'tools flag-untranslated', array( __CLASS__, 'flag_untranslated' ) );
		WP_CLI::add_command( 'tools relink-units', array( __CLASS__, 'relink_units' ) );
		WP_CLI::add_command( 'tools recount-units', array( __CLASS__, 'recount_units' ) );
		WP_CLI::add_command( 'tools setup-countries', array( __CLASS__, 'setup_countries' ) );
		WP_CLI::add_command( 'tools classify-locations', array( __CLASS__, 'classify_locations' ) );
		WP_CLI::add_command( 'tools migrate-url-policy', array( __CLASS__, 'migrate_url_policy' ) );
		WP_CLI::add_command( 'tools sync-prices', array( __CLASS__, 'sync_prices' ) );
		WP_CLI::add_command( 'tools warm-cache', array( __CLASS__, 'warm_cache' ) );
		WP_CLI::add_command( 'tools strip-unit-floors', array( __CLASS__, 'strip_unit_floors' ) );
		WP_CLI::add_command( 'tools translate-units', array( __CLASS__, 'translate_units' ) );
		WP_CLI::add_command( 'tools translate-locations', array( __CLASS__, 'translate_locations' ) );
		WP_CLI::add_command( 'tools translate-bakeoff', array( __CLASS__, 'translate_bakeoff' ) );
		WP_CLI::add_command( 'tools clone-language', array( __CLASS__, 'clone_language' ) );
		WP_CLI::add_command( 'tools add-language', array( __CLASS__, 'add_language' ) );
		WP_CLI::add_command( 'tools clone-menus', array( __CLASS__, 'clone_menus' ) );
	}

	/**
	 * Build a language's navigation from the menus the default language uses.
	 *
	 * Every theme location that has a menu in the default language gets a copy
	 * pointed at the same pages in the chosen language. An item whose target
	 * has no translation is left out and listed, rather than linking a visitor
	 * out of the language they are reading.
	 *
	 * Safe to run again: a copy that already exists is left alone, and so is a
	 * location the language already fills with a menu of its own. Both are
	 * taken over only with --force.
	 *
	 * ## OPTIONS
	 *
	 * --locale=<locale>
	 * : Locale or slug of the language to build, for example zh_CN or zh.
	 *
	 * [--location=<locations>]
	 * : Comma-separated theme locations; all of them when left out.
	 *
	 * [--labels=<mode>]
	 * : copy keeps the source labels; inherit lets them follow the translation.
	 * ---
	 * default: copy
	 * options:
	 *   - copy
	 *   - inherit
	 * ---
	 *
	 * [--force]
	 * : Rebuild the items of a copy that already exists.
	 *
	 * [--dry-run]
	 * : Report the plan and write nothing.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tools clone-menus --locale=zh_CN --dry-run
	 *     wp tools clone-menus --locale=zh_CN
	 *     wp tools clone-menus --locale=hi_IN --labels=inherit
	 *     wp tools clone-menus --locale=zh_CN --location=header_menu --force
	 *
	 * @param array $args Positional arguments, unused.
	 * @param array $assoc_args Flags.
	 *
	 * @return void
	 */
	public static function clone_menus( array $args, array $assoc_args ): void {
		$wanted  = (string) Utils\get_flag_value( $assoc_args, 'locale', '' );
		$dry_run = (bool) Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$force   = (bool) Utils\get_flag_value( $assoc_args, 'force', false );
		$inherit = 'inherit' === (string) Utils\get_flag_value( $assoc_args, 'labels', 'copy' );

		if ( ! function_exists( 'pll_languages_list' ) ) {
			WP_CLI::error( 'Polylang не активен — языки взять негде' );
		}

		if ( '' === trim( $wanted ) ) {
			WP_CLI::error(
				sprintf( 'нужен --locale, известные языки: %s',
					implode( ', ', Language_Cloner::known_languages() ) )
			);
		}

		$language = Language_Cloner::language( $wanted );

		if ( '' === $language ) {
			WP_CLI::error(
				sprintf( 'Polylang не знает «%s». Известные: %s',
					$wanted, implode( ', ', Language_Cloner::known_languages() ) )
			);
		}

		$cloner = new Menu_Cloner();
		$plan   = $cloner->plan( $language );

		if ( is_wp_error( $plan ) ) {
			WP_CLI::error( $plan->get_error_message() );
		}

		$only = array_filter(
			array_map( 'trim', explode( ',', (string) Utils\get_flag_value( $assoc_args, 'location', '' ) ) )
		);

		if ( ! empty( $only ) ) {
			$plan = array_values(
				array_filter( $plan, static fn( $row ) => in_array( $row['location'], $only, true ) )
			);
		}

		if ( empty( $plan ) ) {
			WP_CLI::success( 'копировать нечего' );

			return;
		}

		$totals = array(
			'created'  => 0,
			'updated'  => 0,
			'kept'     => 0,
			'items'    => 0,
			'skipped'  => 0,
			'occupied' => 0,
		);

		foreach ( $plan as $row ) {
			$copy     = $row['copy'];
			$occupied = 'occupied' === $row['action'] && ! $force;

			if ( $occupied ) {
				$current = get_term( (int) $row['assigned'], 'nav_menu' );

				WP_CLI::warning(
					sprintf(
						'%s: у языка уже стоит «%s» (#%d) — не трогаю. Чтобы заменить: --force',
						$row['location'],
						$current instanceof \WP_Term ? $current->name : '?',
						(int) $row['assigned']
					)
				);

				++ $totals['occupied'];

				continue;
			}

			WP_CLI::log(
				sprintf(
					'%s: «%s» (#%d) → %s',
					$row['location'],
					$row['source']->name,
					(int) $row['source']->term_id,
					null === $copy
						? sprintf( 'новое меню «%s»', Menu_Cloner::copy_name( $row['source'], $language ) )
						: sprintf( 'уже есть «%s» (#%d)%s', $copy->name, (int) $copy->term_id, $force ? ', пересоберу' : '' )
				)
			);

			if ( 'occupied' === $row['action'] ) {
				$current = get_term( (int) $row['assigned'], 'nav_menu' );

				WP_CLI::warning(
					sprintf( '        --force: место займёт копия, «%s» (#%d) останется без назначения',
						$current instanceof \WP_Term ? $current->name : '?', (int) $row['assigned'] )
				);
			}

			WP_CLI::log(
				sprintf( '        пунктов к переносу: %d, пропущено: %d',
					count( $row['mapped'] ), count( $row['skipped'] ) )
			);

			foreach ( $row['skipped'] as $miss ) {
				WP_CLI::log( sprintf( '          — «%s»: %s', $miss['title'], $miss['reason'] ) );
			}

			$totals['skipped'] += count( $row['skipped'] );

			if ( $dry_run ) {
				continue;
			}

			$result = $cloner->clone_menu( (int) $row['source']->term_id, $language, $force, $inherit );

			if ( is_wp_error( $result ) ) {
				WP_CLI::warning( $result->get_error_message() );

				continue;
			}

			if ( 'none' === $result['action'] ) {
				++ $totals['kept'];
			} else {
				$totals[ 'create' === $result['action'] ? 'created' : 'updated' ] += 1;
				$totals['items'] += (int) $result['created'];
			}

			Menu_Cloner::assign( $row['location'], $language, (int) $result['menu_id'] );

			WP_CLI::log(
				sprintf( '        меню #%d назначено на %s для %s',
					(int) $result['menu_id'], $row['location'], $language )
			);
		}

		if ( $dry_run ) {
			WP_CLI::success( 'Проверка закончена, ничего не записано' );

			return;
		}

		WP_CLI::log(
			sprintf( 'Создано: %d, пересобрано: %d, оставлено как есть: %d, чужих назначений не тронуто: %d, пунктов: %d, пропущено: %d',
				$totals['created'], $totals['updated'], $totals['kept'], $totals['occupied'],
				$totals['items'], $totals['skipped'] )
		);

		WP_CLI::success( 'Готово' );
	}

	/**
	 * Register a language in Polylang, or report that it is already there.
	 *
	 * Safe to run again: an existing language with the same settings is left
	 * alone, one that differs is reported and only changed with --update, and a
	 * locale already taken by another slug stops the run instead of producing a
	 * second language.
	 *
	 * ## OPTIONS
	 *
	 * --slug=<slug>
	 * : Polylang language code, for example zh.
	 *
	 * [--locale=<locale>]
	 * : WordPress locale. Taken from the project preset when left out.
	 *
	 * [--name=<name>]
	 * : Name shown in the switcher. Taken from the project preset when left out.
	 *
	 * [--flag=<code>]
	 * : Country code of the flag.
	 *
	 * [--rtl]
	 * : Mark the language right to left.
	 *
	 * [--order=<number>]
	 * : Position in the switcher.
	 *
	 * [--update]
	 * : Bring an existing language in line with these settings.
	 *
	 * [--dry-run]
	 * : Report the plan and write nothing.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tools add-language --slug=zh --dry-run
	 *     wp tools add-language --slug=zh
	 *     wp tools add-language --slug=zh --update
	 *
	 * @param array $args Positional arguments, unused.
	 * @param array $assoc_args Flags.
	 *
	 * @return void
	 */
	public static function add_language( array $args, array $assoc_args ): void {
		$slug = strtolower( trim( (string) Utils\get_flag_value( $assoc_args, 'slug', '' ) ) );

		if ( '' === $slug ) {
			WP_CLI::error(
				sprintf( 'нужен --slug, готовые наборы: %s',
					implode( ', ', array_keys( Language_Installer::PRESETS ) ) )
			);
		}

		if ( ! function_exists( 'PLL' ) || empty( PLL()->model ) ) {
			WP_CLI::error( 'Polylang не активен — язык добавить некуда' );
		}

		$settings = Language_Installer::preset( $slug ) ?: array( 'slug' => $slug );

		foreach ( array( 'locale', 'name', 'flag' ) as $key ) {
			$value = (string) Utils\get_flag_value( $assoc_args, $key, '' );

			if ( '' !== $value ) {
				$settings[ $key ] = $value;
			}
		}

		if ( Utils\get_flag_value( $assoc_args, 'rtl', false ) ) {
			$settings['rtl'] = true;
		}

		$order = Utils\get_flag_value( $assoc_args, 'order', null );

		if ( null !== $order ) {
			$settings['order'] = (int) $order;
		}

		foreach ( array( 'locale', 'name' ) as $key ) {
			if ( empty( $settings[ $key ] ) ) {
				WP_CLI::error( sprintf( 'нет --%s и нет готового набора для «%s»', $key, $slug ) );
			}
		}

		$plan = Language_Installer::plan( $settings );

		WP_CLI::log(
			sprintf(
				'%s / %s / %s — %s',
				$settings['slug'],
				$settings['locale'],
				$settings['name'],
				$plan['message']
			)
		);

		foreach ( $plan['differences'] as $key => $pair ) {
			WP_CLI::log( sprintf( '  %-7s сейчас «%s», нужно «%s»', $key, $pair['have'], $pair['want'] ) );
		}

		if ( 'conflict' === $plan['action'] ) {
			WP_CLI::error( $plan['message'] );
		}

		if ( Utils\get_flag_value( $assoc_args, 'dry-run', false ) ) {
			WP_CLI::success( sprintf( 'dry-run: действие «%s», ничего не записано', $plan['action'] ) );

			return;
		}

		if ( 'none' === $plan['action'] ) {
			WP_CLI::success( 'язык уже настроен, изменений нет' );

			return;
		}

		$result = Language_Installer::apply(
			$settings,
			(bool) Utils\get_flag_value( $assoc_args, 'update', false )
		);

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		WP_CLI::success(
			sprintf(
				'язык %s: %s. Дальше: wp rewrite flush, затем wp tools clone-language --locale=%s',
				$settings['slug'],
				'create' === $result['action'] ? 'создан' : 'обновлён',
				$settings['locale']
			)
		);
	}

	/**
	 * Copy units and projects into a language added in Polylang.
	 *
	 * Every translation group gains one copy, made from its default-language
	 * post and carrying its text, flagged need_translate so translate-units
	 * fills it afterwards. Re-running only picks up what is still missing.
	 * Links to other posts are repointed at the new language: a unit to the
	 * project, a project to the developer, and what the language has none of is
	 * cloned first.
	 *
	 * ## OPTIONS
	 *
	 * --locale=<locale>
	 * : Locale or slug of the language to open, for example de_DE or de.
	 *
	 * [--post-type=<types>]
	 * : Comma-separated types to copy: unit, property, page.
	 * ---
	 * default: unit,property
	 * ---
	 *
	 * [--status=<statuses>]
	 * : Comma-separated statuses to copy from.
	 * ---
	 * default: publish,draft,future
	 * ---
	 *
	 * [--copy-status=<status>]
	 * : Status to give the copies. The source status when left out.
	 *
	 * [--limit=<number>]
	 * : How many posts to copy.
	 * ---
	 * default: 50
	 * ---
	 *
	 * [--offset=<number>]
	 * : Candidates to pass over first.
	 *
	 * [--post-id=<id>]
	 * : Copy this post alone.
	 *
	 * [--dry-run]
	 * : Report the plan and write nothing.
	 *
	 * [--yes]
	 * : Do not ask before writing.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tools clone-language --locale=de_DE --dry-run
	 *     wp tools clone-language --locale=de_DE --limit=50
	 *     wp tools clone-language --locale=de_DE --post-type=property --yes
	 *     wp tools clone-language --locale=de_DE --post-type=page --yes
	 *     wp tools clone-language --locale=de_DE --post-id=12345
	 *
	 * @param array $args Positional arguments, unused.
	 * @param array $assoc_args Flags.
	 *
	 * @return void
	 */
	public static function clone_language( array $args, array $assoc_args ): void {
		$dry_run = (bool) Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$post_id = (int) Utils\get_flag_value( $assoc_args, 'post-id', 0 );
		$limit   = max( 1, (int) Utils\get_flag_value( $assoc_args, 'limit', 50 ) );
		$offset  = max( 0, (int) Utils\get_flag_value( $assoc_args, 'offset', 0 ) );
		$wanted  = (string) Utils\get_flag_value( $assoc_args, 'locale', '' );
		$copy_as = (string) Utils\get_flag_value( $assoc_args, 'copy-status', '' );

		if ( ! function_exists( 'pll_languages_list' ) ) {
			WP_CLI::error( 'Polylang не активен — языки взять негде' );
		}

		if ( '' === trim( $wanted ) ) {
			WP_CLI::error(
				sprintf( 'нужен --locale, известные языки: %s',
					implode( ', ', Language_Cloner::known_languages() ) )
			);
		}

		$language = Language_Cloner::language( $wanted );

		if ( '' === $language ) {
			WP_CLI::error(
				sprintf( 'Polylang не знает «%s». Сначала добавьте язык в Polylang. Известные: %s',
					$wanted, implode( ', ', Language_Cloner::known_languages() ) )
			);
		}

		if ( $language === Language_Cloner::default_language() ) {
			WP_CLI::error( sprintf( '«%s» — язык по умолчанию, копировать его в себя нечего', $language ) );
		}

		$types = array_filter(
			array_map(
				'trim',
				explode( ',', (string) Utils\get_flag_value( $assoc_args, 'post-type', implode( ',', Language_Cloner::DEFAULT_POST_TYPES ) ) )
			)
		);

		$unknown = array_diff( $types, Language_Cloner::POST_TYPES );

		if ( ! empty( $unknown ) ) {
			WP_CLI::error(
				sprintf( 'копии не делаются для %s, доступны: %s',
					implode( ', ', $unknown ), implode( ', ', Language_Cloner::POST_TYPES ) )
			);
		}

		$statuses = array_filter(
			array_map(
				'trim',
				explode( ',', (string) Utils\get_flag_value( $assoc_args, 'status', implode( ',', Language_Cloner::DEFAULT_STATUSES ) ) )
			)
		);

		$cloner = new Language_Cloner();
		$ids    = $post_id > 0
			? array( $post_id )
			: $cloner->find( $language, $types, $statuses, $limit, $offset );

		if ( empty( $ids ) ) {
			WP_CLI::success( sprintf( 'на %s копировать нечего — всё уже есть', strtoupper( $language ) ) );

			return;
		}

		$total = count( $ids );

		if ( ! $dry_run ) {
			WP_CLI::confirm(
				sprintf( 'Создать %d записей на языке %s?', $total, strtoupper( $language ) ),
				$assoc_args
			);
		}

		$totals = array(
			'ready'   => 0,
			'created' => 0,
			'linked'  => 0,
			'skipped' => 0,
			'failed'  => 0,
		);

		foreach ( array_values( $ids ) as $index => $id ) {
			$plan = $cloner->plan( (int) $id, $language );

			if ( is_wp_error( $plan ) ) {
				++ $totals['skipped'];
				WP_CLI::warning( sprintf( '[%d/%d] пропущен: %s', $index + 1, $total, $plan->get_error_message() ) );

				continue;
			}

			WP_CLI::log(
				sprintf( '[%d/%d] %s #%d "%s" — группа: %s',
					$index + 1, $total, $plan['type'], $plan['id'],
					mb_substr( $plan['title'], 0, 48 ),
					implode( ', ', array_keys( $plan['group'] ) ) ?: 'одиночная' )
			);

			$links = (array) ( $plan['links'] ?? array() );
			$extra = 0;

			foreach ( $links as $link ) {
				$needed = 0 === (int) $link['translated'];
				$extra += $needed ? 1 : 0;

				WP_CLI::log(
					$needed
						? sprintf( '        %s #%d на %s будет создан и привязан через %s',
							$link['type'], $link['source'], strtoupper( $language ), $link['meta'] )
						: sprintf( '        %s на %s уже есть — #%d (%s)',
							$link['type'], strtoupper( $language ), $link['translated'], $link['meta'] )
				);
			}

			if ( $dry_run ) {
				++ $totals['ready'];
				$totals['linked'] += $extra;

				WP_CLI::log( sprintf( '        [DRY RUN] копия на %s не создана', strtoupper( $language ) ) );

				continue;
			}

			$copy = $cloner->clone_post( (int) $id, $language, $copy_as );

			if ( is_wp_error( $copy ) ) {
				++ $totals['failed'];
				WP_CLI::warning( sprintf( '        ошибка: %s', $copy->get_error_message() ) );

				continue;
			}

			++ $totals['created'];
			$totals['linked'] += $extra;

			$pointers = array();

			foreach ( $links as $link ) {
				if ( (int) $link['owner'] !== (int) $plan['id'] ) {
					continue;
				}

				$pointers[] = sprintf( '%s #%s', $link['meta'],
					get_post_meta( (int) $copy, (string) $link['meta'], true ) ?: '—' );
			}

			WP_CLI::log(
				sprintf( '        создана #%d, помечена need_translate%s',
					$copy, $pointers ? ', ' . implode( ', ', $pointers ) : '' )
			);
		}

		WP_CLI::log( '' );

		if ( $dry_run ) {
			WP_CLI::log( sprintf( 'К созданию: %d', $totals['ready'] ) );
		} else {
			WP_CLI::log( sprintf( 'Создано: %d', $totals['created'] ) );
			WP_CLI::log( sprintf( 'Ошибок:  %d', $totals['failed'] ) );
		}

		if ( $totals['linked'] > 0 ) {
			WP_CLI::log( sprintf( 'Связанных записей заодно: %d', $totals['linked'] ) );
		}

		WP_CLI::log( sprintf( 'Пропущено: %d', $totals['skipped'] ) );

		if ( $totals['failed'] > 0 ) {
			WP_CLI::warning( 'Часть копий не создана — команду можно запустить повторно' );

			return;
		}

		if ( ! $dry_run && $totals['created'] > 0 ) {
			WP_CLI::log( sprintf( 'Дальше: wp tools translate-units --language=%s --limit=…', $language ) );
		}

		WP_CLI::success( $dry_run ? 'Проверка закончена, ничего не записано' : 'Готово' );
	}

	/**
	 * Fill the per-language description of locations from the default language.
	 *
	 * The taxonomy is not translated by Polylang, so every language keeps its
	 * own description in term meta next to the term's own field.
	 *
	 * ## OPTIONS
	 *
	 * [--language=<slug>]
	 * : Language to fill. Every extra language when left out.
	 *
	 * [--limit=<number>]
	 * : How many locations to take per language. Ignored with --term-id.
	 * ---
	 * default: 20
	 * ---
	 *
	 * [--offset=<number>]
	 * : Locations to pass over first.
	 *
	 * [--term-id=<id>]
	 * : Work on this location alone.
	 *
	 * [--dry-run]
	 * : Report the plan, send nothing to DeepL and write nothing.
	 *
	 * [--force]
	 * : Overwrite a description that is already there. Needs --term-id.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tools translate-locations --limit=10 --dry-run
	 *     wp tools translate-locations --language=ru --limit=10
	 *     wp tools translate-locations --term-id=141 --force
	 *
	 * @param array $args Positional arguments, unused.
	 * @param array $assoc_args Flags.
	 *
	 * @return void
	 */
	public static function translate_locations( array $args, array $assoc_args ): void {
		$dry_run  = (bool) Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$force    = (bool) Utils\get_flag_value( $assoc_args, 'force', false );
		$term_id  = (int) Utils\get_flag_value( $assoc_args, 'term-id', 0 );
		$limit    = max( 1, (int) Utils\get_flag_value( $assoc_args, 'limit', 20 ) );
		$offset   = max( 0, (int) Utils\get_flag_value( $assoc_args, 'offset', 0 ) );
		$language = (string) Utils\get_flag_value( $assoc_args, 'language', '' );

		if ( $force && $term_id <= 0 ) {
			WP_CLI::error( '--force требует --term-id, иначе перезапишутся все описания' );
		}

		$languages = Location_Translator::languages();

		if ( empty( $languages ) ) {
			WP_CLI::error( 'кроме языка по умолчанию языков нет — заполнять нечего' );
		}

		if ( '' !== $language ) {
			if ( ! in_array( $language, $languages, true ) ) {
				WP_CLI::error(
					sprintf( 'язык «%s» не из списка: %s', $language, implode( ', ', $languages ) )
				);
			}

			$languages = array( $language );
		}

		$translator = new Location_Translator();

		if ( ! $dry_run && ! $translator->ready() ) {
			WP_CLI::error( 'DEEPL_API_KEY не задан в .env — переводить нечем' );
		}

		$totals = array(
			'ready'   => 0,
			'filled'  => 0,
			'skipped' => 0,
			'failed'  => 0,
			'chars'   => 0,
		);

		foreach ( $languages as $slug ) {
			$ids = $term_id > 0
				? array( $term_id )
				: $translator->find( $slug, $limit, $offset, $force );

			if ( empty( $ids ) ) {
				WP_CLI::log( sprintf( '%s: заполнять нечего', strtoupper( $slug ) ) );

				continue;
			}

			$total = count( $ids );

			foreach ( array_values( $ids ) as $index => $id ) {
				$plan = $translator->plan( (int) $id, $slug, $force );

				if ( is_wp_error( $plan ) ) {
					++ $totals['skipped'];
					WP_CLI::warning( sprintf( '%s [%d/%d] пропущен: %s', strtoupper( $slug ), $index + 1, $total,
						$plan->get_error_message() ) );

					continue;
				}

				WP_CLI::log(
					sprintf( '%s [%d/%d] %s — %d символов исходника',
						strtoupper( $slug ), $index + 1, $total, $plan['name'], mb_strlen( $plan['source'] ) )
				);

				if ( $dry_run ) {
					++ $totals['ready'];
					WP_CLI::log( '        [DRY RUN] запрос не отправлен, ничего не записано' );

					continue;
				}

				$written = $translator->translate( (int) $id, $slug, $force );

				if ( is_wp_error( $written ) ) {
					++ $totals['failed'];
					WP_CLI::warning( sprintf( '        ошибка: %s — описание не изменено', $written->get_error_message() ) );

					continue;
				}

				++ $totals['filled'];
				$totals['chars'] += (int) $written;
				WP_CLI::log( sprintf( '        записано %d символов', $written ) );

				if ( $index + 1 < $total ) {
					usleep( 200000 );
				}
			}
		}

		WP_CLI::log( '' );

		if ( $dry_run ) {
			WP_CLI::log( sprintf( 'К заполнению: %d', $totals['ready'] ) );
		} else {
			WP_CLI::log( sprintf( 'Заполнено: %d (%s символов)', $totals['filled'], number_format( $totals['chars'] ) ) );
			WP_CLI::log( sprintf( 'Ошибок:    %d', $totals['failed'] ) );
		}

		WP_CLI::log( sprintf( 'Пропущено: %d', $totals['skipped'] ) );

		if ( $totals['failed'] > 0 ) {
			WP_CLI::warning( 'Часть описаний не заполнена — команду можно запустить повторно' );

			return;
		}

		WP_CLI::success( $dry_run ? 'Проверка закончена, ничего не записано' : 'Готово' );
	}

	/**
	 * Fill units, projects and news posts flagged need_translate with DeepL output.
	 *
	 * The flag sits on posts an importer created by copying the other language,
	 * so the target is the post's own Polylang language. Posts already in the
	 * default language are skipped, flag untouched: their text is the source.
	 * Nothing is written until every field of a post came back translated.
	 *
	 * ## OPTIONS
	 *
	 * [--post-type=<types>]
	 * : Comma-separated types to walk: unit, property, post, page, developers.
	 * ---
	 * default: unit,property,post,page,developers
	 * ---
	 *
	 * [--status=<statuses>]
	 * : Comma-separated statuses to walk; pass publish to skip drafts.
	 * ---
	 * default: publish,draft,future
	 * ---
	 *
	 * [--limit=<number>]
	 * : How many flagged posts to take. Ignored with --post-id.
	 * ---
	 * default: 20
	 * ---
	 *
	 * [--offset=<number>]
	 * : Flagged posts to pass over first.
	 *
	 * [--post-id=<id>]
	 * : Work on this unit alone, flagged or not.
	 *
	 * [--language=<slug>]
	 * : Only units in this Polylang language; with --post-id it is the target.
	 *
	 * [--dry-run]
	 * : Report the plan, send nothing to DeepL and write nothing.
	 *
	 * [--force]
	 * : Kept for older scripts; --post-id already skips the flag check.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tools translate-units --limit=10 --dry-run
	 *     wp tools translate-units --limit=10
	 *     wp tools translate-units --post-id=12345
	 *     wp tools translate-units --post-id=12345 --force
	 *     wp tools translate-units --limit=5 --language=ru
	 *     wp tools translate-units --post-type=property --limit=5 --dry-run
	 *     wp tools translate-units --post-type=post --language=de --limit=5
	 *     wp tools translate-units --language=zh --status=draft --limit=5
	 *     wp tools translate-units --post-id=149352 --language=en
	 *
	 * @param array $args Positional arguments, unused.
	 * @param array $assoc_args Flags.
	 *
	 * @return void
	 */
	public static function translate_units( array $args, array $assoc_args ): void {
		$dry_run  = (bool) Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$force    = (bool) Utils\get_flag_value( $assoc_args, 'force', false );
		$post_id  = (int) Utils\get_flag_value( $assoc_args, 'post-id', 0 );
		$limit    = max( 1, (int) Utils\get_flag_value( $assoc_args, 'limit', 20 ) );
		$offset   = max( 0, (int) Utils\get_flag_value( $assoc_args, 'offset', 0 ) );
		$language = (string) Utils\get_flag_value( $assoc_args, 'language', '' );
		$statuses = array_filter(
			array_map(
				'trim',
				explode( ',', (string) Utils\get_flag_value( $assoc_args, 'status', implode( ',', Unit_Translator::DEFAULT_STATUSES ) ) )
			)
		);
		$types    = array_filter(
			array_map(
				'trim',
				explode( ',', (string) Utils\get_flag_value( $assoc_args, 'post-type', implode( ',', Unit_Translator::POST_TYPES ) ) )
			)
		);

		$unknown = array_diff( $types, Unit_Translator::POST_TYPES );

		if ( ! empty( $unknown ) ) {
			WP_CLI::error(
				sprintf( 'перевод не поддержан для %s, доступны: %s',
					implode( ', ', $unknown ), implode( ', ', Unit_Translator::POST_TYPES ) )
			);
		}

		if ( $force && $post_id <= 0 ) {
			WP_CLI::error( '--force требует --post-id, иначе под перевод уйдёт весь каталог' );
		}

		// Названный юнит переводим независимо от флага, а указанный язык
		// становится целью: запись может числиться на одном языке и нести текст
		// на другом.
		$force = $force || $post_id > 0;
		$into  = $post_id > 0 ? $language : '';

		if ( '' !== $into && '' === Translation_Client::make()->target_for( $into ) ) {
			WP_CLI::error( sprintf( 'язык «%s» переводчик не поддерживает', $into ) );
		}

		if ( ! function_exists( 'pll_get_post_language' ) ) {
			WP_CLI::error( 'Polylang не активен — язык поста определить нечем' );
		}

		$translator = new Unit_Translator();

		if ( ! $dry_run && ! $translator->ready() ) {
			WP_CLI::error( 'DEEPL_API_KEY не задан в .env — переводить нечем' );
		}

		$ids = $post_id > 0
			? array( $post_id )
			: $translator->find( $limit, $offset, $language, $types, $statuses );

		if ( empty( $ids ) ) {
			WP_CLI::success( 'Записей с флагом need_translate не нашлось' );

			return;
		}

		$totals = array(
			'processed' => 0,
			'ready'     => 0,
			'saved'     => 0,
			'skipped'   => 0,
			'failed'    => 0,
		);

		$total = count( $ids );

		foreach ( array_values( $ids ) as $index => $id ) {
			++ $totals['processed'];

			WP_CLI::log( sprintf( '[%d/%d] %s #%d', $index + 1, $total, get_post_type( $id ) ?: 'post', $id ) );

			$plan = $translator->plan( (int) $id, $force, $into );

			if ( is_wp_error( $plan ) ) {
				++ $totals['skipped'];
				WP_CLI::warning( sprintf( 'пропущен: %s', $plan->get_error_message() ) );

				continue;
			}

			if ( empty( $plan['strings'] ) ) {
				++ $totals['skipped'];
				WP_CLI::warning( sprintf( 'пропущен: у #%d нет текста для перевода', $id ) );

				continue;
			}

			WP_CLI::log( sprintf( '        язык: %s, цель DeepL: %s', $plan['language'], $plan['target'] ) );
			WP_CLI::log( sprintf( '        поля: %s', implode( ', ', $plan['summary'] ) ) );
			WP_CLI::log( sprintf( '        строк на перевод: %d', count( $plan['strings'] ) ) );

			if ( $dry_run ) {
				++ $totals['ready'];
				WP_CLI::log( '        [DRY RUN] запрос не отправлен, ничего не записано' );

				continue;
			}

			$saved = $translator->translate( (int) $id, $force, $into );

			if ( is_wp_error( $saved ) ) {
				++ $totals['failed'];
				WP_CLI::warning(
					sprintf( 'ошибка: %s — #%d не изменён, флаг оставлен', $saved->get_error_message(), $id )
				);

				continue;
			}

			++ $totals['saved'];
			WP_CLI::log( sprintf( '        сохранено: %s, флаг снят', implode( ', ', $saved ) ) );

			if ( $index + 1 < $total ) {
				usleep( 200000 );
			}
		}

		WP_CLI::log( '' );
		WP_CLI::log( sprintf( 'Обработано: %d', $totals['processed'] ) );

		if ( $dry_run ) {
			WP_CLI::log( sprintf( 'К переводу: %d', $totals['ready'] ) );
		} else {
			WP_CLI::log( sprintf( 'Переведено: %d', $totals['saved'] ) );
			WP_CLI::log( sprintf( 'Ошибок:     %d', $totals['failed'] ) );
		}

		WP_CLI::log( sprintf( 'Пропущено:  %d', $totals['skipped'] ) );

		if ( $totals['failed'] > 0 ) {
			WP_CLI::warning( 'Флаг у неудачных юнитов остался — команду можно запустить повторно' );

			return;
		}

		WP_CLI::success( $dry_run ? 'Проверка закончена, ничего не записано' : 'Готово' );
	}

	/**
	 * Dry run of a translation provider over real posts.
	 *
	 * Writes nothing: no post is touched and the answers are not cached, so the
	 * same strings can be sent to another provider straight after. Reports what
	 * the validator rejected, what the call cost and where the texts landed, so
	 * a provider is judged on this project's own content instead of on
	 * somebody's benchmark.
	 *
	 * Terminology and grammar still need a human — the machine side only proves
	 * that markup, placeholders and brand names survived.
	 *
	 * With --marker the model is asked to end every translation with a
	 * terminator, and the run reports how often it forgot. That is the one
	 * measurement worth having before trusting the terminator as a check: a
	 * model that forgets even rarely turns it into a rejecter of good work.
	 * Such a run is not comparable with one without the flag — the prompt
	 * differs, so the cache is cold and nothing is reused.
	 *
	 * ## OPTIONS
	 *
	 * [--language=<slug>]
	 * : Polylang slug to translate into.
	 * ---
	 * default: ar
	 * ---
	 *
	 * [--limit=<number>]
	 * : How many random published English posts to take.
	 * ---
	 * default: 20
	 * ---
	 *
	 * [--post-type=<type>]
	 * : Post type to sample.
	 * ---
	 * default: unit
	 * ---
	 *
	 * [--provider=<name>]
	 * : gemini or deepl, overriding TRANSLATE_PROVIDER from the .env.
	 *
	 * [--model=<id>]
	 * : Model to ask for, Gemini only.
	 *
	 * [--marker]
	 * : Gemini only. Ask for a terminator and count how often it is forgotten.
	 *
	 * [--out=<path>]
	 * : Directory for the dump of source and translation.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tools translate-bakeoff
	 *     wp tools translate-bakeoff --language=ar --limit=20
	 *     wp tools translate-bakeoff --model=gemini-2.5-flash-lite
	 *     wp tools translate-bakeoff --provider=deepl --limit=5
	 *     wp tools translate-bakeoff --post-type=property --limit=10
	 *     wp tools translate-bakeoff --limit=30 --marker
	 *
	 * @param array $args Positional arguments, unused.
	 * @param array $assoc_args Flags.
	 *
	 * @return void
	 */
	public static function translate_bakeoff( array $args, array $assoc_args ): void {
		$language = (string) Utils\get_flag_value( $assoc_args, 'language', 'ar' );
		$limit    = max( 1, (int) Utils\get_flag_value( $assoc_args, 'limit', 20 ) );
		$type     = (string) Utils\get_flag_value( $assoc_args, 'post-type', 'unit' );
		$provider = (string) Utils\get_flag_value( $assoc_args, 'provider', '' );
		$model    = (string) Utils\get_flag_value( $assoc_args, 'model', '' );
		$marker   = (bool) Utils\get_flag_value( $assoc_args, 'marker', false );
		$out      = (string) Utils\get_flag_value( $assoc_args, 'out', sys_get_temp_dir() );

		// Прайсы провайдеров на 27.10.2026, за миллион токенов: вход, выход.
		// Пакетный режим вдвое дешевле и здесь не учитывается.
		$prices = array(
			'gemini-3.1-flash-lite' => array( 0.25, 1.50 ),
			'gemini-2.5-flash-lite' => array( 0.10, 0.40 ),
			'gemini-3-flash'        => array( 0.50, 3.00 ),
		);

		if ( ! post_type_exists( $type ) ) {
			WP_CLI::error( sprintf( 'типа записи «%s» нет', $type ) );
		}

		$client = Translation_Client::make( $provider );

		if ( '' !== $model ) {
			if ( ! $client instanceof Gemini_Client ) {
				WP_CLI::error( '--model понимает только gemini' );
			}

			$client->use_model( $model );
		}

		if ( $marker ) {
			if ( ! $client instanceof Gemini_Client ) {
				WP_CLI::error( '--marker понимает только gemini' );
			}

			$client->use_marker( true );
			WP_CLI::log( 'маркер конца включён: промпт другой, кэш холодный' );
		}

		$target = $client->target_for( $language );

		if ( '' === $target ) {
			WP_CLI::error( sprintf( 'язык «%s» переводчик не поддерживает', $language ) );
		}

		if ( ! $client->has_key() ) {
			WP_CLI::error( 'ключ провайдера не задан в .env' );
		}

		$posts = get_posts(
			array(
				'post_type'        => $type,
				'post_status'      => 'publish',
				'numberposts'      => $limit,
				'orderby'          => 'rand',
				'suppress_filters' => false,
				'lang'             => 'en',
			)
		);

		if ( empty( $posts ) ) {
			WP_CLI::error( sprintf( 'английских записей типа «%s» не нашлось', $type ) );
		}

		WP_CLI::log(
			sprintf(
				'провайдер %s, цель %s, записей %d',
				( new \ReflectionClass( $client ) )->getShortName(),
				$target,
				count( $posts )
			)
		);

		$started = microtime( true );
		$rows    = array();
		$failed  = 0;
		$chars   = 0;

		foreach ( $posts as $post ) {
			$fields = array_filter(
				array(
					'title'   => (string) $post->post_title,
					'excerpt' => (string) $post->post_excerpt,
					'content' => (string) $post->post_content,
				),
				static fn( string $value ): bool => '' !== trim( $value )
			);

			$plain = array_diff_key( $fields, array( 'content' => 1 ) );
			$got   = $client->translate( $plain, $target, false, false );
			$body  = isset( $fields['content'] )
				? $client->translate( array( 'content' => $fields['content'] ), $target, true, false )
				: array();

			foreach ( array( $got, $body ) as $part ) {
				if ( is_wp_error( $part ) ) {
					++ $failed;
					WP_CLI::warning( sprintf( '#%d отклонено: %s', $post->ID, $part->get_error_message() ) );

					continue 2;
				}
			}

			// Токены считаются и за отклонённые записи, поэтому цена за символ
			// выходит скорее завышенной, чем заниженной.
			$chars += array_sum( array_map( 'mb_strlen', $fields ) );

			$rows[] = array(
				'id'     => $post->ID,
				'source' => $fields,
				'target' => array_merge( $got, $body ),
			);
		}

		$spent = microtime( true ) - $started;

		if ( empty( $rows ) ) {
			WP_CLI::error( 'ни одна запись не прошла проверку — сравнивать нечего' );
		}

		// Бренд и имена проектов обязаны остаться латиницей: это единственная
		// часть перевода, которую можно проверить машинно.
		$latin = 0;

		foreach ( $rows as $row ) {
			$source = implode( ' ', $row['source'] );
			$result = implode( ' ', $row['target'] );

			foreach ( array( 'East Property', 'Emaar', 'Damac', 'Azizi' ) as $name ) {
				if ( false !== stripos( $source, $name ) && false === stripos( $result, $name ) ) {
					++ $latin;
					WP_CLI::warning( sprintf( '#%d бренд «%s» потерял латиницу', $row['id'], $name ) );
				}
			}
		}

		$file = rtrim( $out, '/' ) . '/bakeoff-' . $language . '.txt';
		$dump = '';

		foreach ( $rows as $row ) {
			$dump .= sprintf( "===== #%d =====\n", $row['id'] );

			foreach ( $row['source'] as $field => $text ) {
				$dump .= sprintf( "--- %s ---\n%s\n>>>\n%s\n\n", $field, $text, $row['target'][ $field ] ?? '' );
			}
		}

		if ( false === file_put_contents( $file, $dump ) ) {
			WP_CLI::warning( sprintf( 'тексты не записались в %s', $file ) );
		}

		WP_CLI::log( sprintf( 'переведено %d, отклонено %d, бренд потерян %d раз', count( $rows ), $failed, $latin ) );
		WP_CLI::log( sprintf( 'символов исходника %d, времени %.1f с', $chars, $spent ) );

		if ( $client instanceof Gemini_Client ) {
			$usage = $client->usage();
			$rate  = $prices[ $client->model() ] ?? null;

			WP_CLI::log(
				sprintf( 'токенов: вход %d, выход %d, вызовов %d', $usage['in'], $usage['out'], $usage['calls'] )
			);

			if ( $marker && $usage['items'] > 0 ) {
				WP_CLI::log(
					sprintf(
						'маркер конца: проверено ответов %d, забыт %d раз (%.1f%%)',
						$usage['items'],
						$usage['missed'],
						$usage['missed'] / $usage['items'] * 100
					)
				);
				WP_CLI::log(
					0 === $usage['missed']
						? 'ни одного промаха — проверку можно включать'
						: 'есть промахи: включать нельзя, это будет машина ложных отказов'
				);
			}

			if ( null === $rate ) {
				WP_CLI::warning( sprintf( 'цены модели «%s» в таблице нет — стоимость не посчитана', $client->model() ) );
			} elseif ( $chars > 0 ) {
				$cost     = $usage['in'] / 1e6 * $rate[0] + $usage['out'] / 1e6 * $rate[1];
				$per_char = $cost / $chars;
				$average  = $chars / count( $rows );

				WP_CLI::log( sprintf( 'стоимость прогона $%.4f, это $%.3f за миллион символов', $cost, $per_char * 1e6 ) );
				WP_CLI::log(
					sprintf(
						'на 12 000 юнитов по %d символов: $%.2f (пакетным режимом вдвое меньше)',
						(int) $average,
						$per_char * $average * 12000
					)
				);
			}
		}

		WP_CLI::success( sprintf( 'тексты для чтения: %s', $file ) );
	}

	/**
	 * Bring need_translate in line with what the Russian posts actually contain.
	 *
	 * Both importers flag the placeholders they create, but the flag drifts:
	 * records predating the tooling never got one, and a post translated later
	 * by hand keeps a flag it no longer deserves. Either way the backlog lies —
	 * once by hiding work, once by showing work already done.
	 *
	 * The rule is exact rather than a guess, and deliberately ignores the title:
	 * project names like SOBHA "330 Riverside Crescent" stay in Latin script in
	 * the Russian version too, so judging by the title would flag them wrongly.
	 *
	 *   - Russian content byte-identical to the English content -> placeholder,
	 *     the flag belongs there.
	 *   - Russian content carries Cyrillic and differs from English -> really
	 *     translated, the flag is stale.
	 *   - No content on either side -> nothing to judge, left alone. Treating
	 *     these as translated is exactly the bug the first version had.
	 *
	 * Additive by default: missing flags are added, stale ones only reported.
	 * Removing a flag takes work out of somebody's queue, so it is opt-in.
	 *
	 * ## OPTIONS
	 *
	 * [--post-type=<types>]
	 * : Comma-separated post types to walk.
	 * ---
	 * default: property,unit
	 * ---
	 *
	 * [--remove-stale]
	 * : Also drop the flag from posts that are genuinely translated.
	 *
	 * [--dry-run]
	 * : Report what would change and write nothing.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tools flag-untranslated --dry-run
	 *     wp tools flag-untranslated
	 *     wp tools flag-untranslated --remove-stale
	 *     wp tools flag-untranslated --post-type=unit --dry-run
	 *
	 * @param array $args Positional arguments, unused.
	 * @param array $assoc_args Flags.
	 *
	 * @return void
	 */
	public static function flag_untranslated( array $args, array $assoc_args ): void {
		$dry_run      = (bool) Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$remove_stale = (bool) Utils\get_flag_value( $assoc_args, 'remove-stale', false );
		$types        = array_filter(
			array_map( 'trim',
				explode( ',', (string) Utils\get_flag_value( $assoc_args, 'post-type', 'property,unit' ) ) )
		);

		if ( ! function_exists( 'pll_get_post_language' ) ) {
			WP_CLI::error( 'Polylang не активен — сопоставить языки нечем' );
		}

		$totals = array(
			'added'    => 0,
			'stale'    => 0,
			'removed'  => 0,
			'correct'  => 0,
			'unclear'  => 0,
			'no_pair'  => 0,
			'no_text'  => 0,
			'examined' => 0,
		);

		$unclear = array();

		foreach ( $types as $type ) {
			$ids = get_posts(
				array(
					'post_type'        => $type,
					'post_status'      => 'any',
					'posts_per_page'   => - 1,
					'fields'           => 'ids',
					'lang'             => '',
					'suppress_filters' => true,
				)
			);

			foreach ( $ids as $id ) {
				if ( 'ru' !== (string) pll_get_post_language( $id, 'slug' ) ) {
					continue;
				}

				++ $totals['examined'];

				$verdict = self::translation_verdict( (int) $id );

				if ( 'no_pair' === $verdict ) {
					++ $totals['no_pair'];
					continue;
				}

				if ( 'no_text' === $verdict ) {
					++ $totals['no_text'];
					continue;
				}

				$flagged = (bool) get_post_meta( $id, Distress_Units_Importer::NEED_TRANSLATE_META, true );

				if ( 'placeholder' === $verdict && ! $flagged ) {
					++ $totals['added'];

					if ( ! $dry_run ) {
						self::set_translation_flag( (int) $id );
					}

					continue;
				}

				if ( 'translated' === $verdict && $flagged ) {
					++ $totals['stale'];

					if ( $remove_stale && ! $dry_run ) {
						delete_post_meta( $id, Distress_Units_Importer::NEED_TRANSLATE_META );
						++ $totals['removed'];
					}

					continue;
				}

				if ( 'unclear' === $verdict ) {
					++ $totals['unclear'];

					if ( count( $unclear ) < 10 ) {
						$unclear[] = sprintf( '#%d %s', $id, get_the_title( $id ) );
					}

					continue;
				}

				++ $totals['correct'];
			}
		}

		WP_CLI::log( sprintf( 'Типы:                 %s', implode( ', ', $types ) ) );
		WP_CLI::log( sprintf( 'RU-записей осмотрено: %d', $totals['examined'] ) );
		WP_CLI::log( sprintf( 'Флаг уже верен:       %d', $totals['correct'] ) );
		WP_CLI::log( sprintf( 'Флага не хватало:     %d%s',
			$totals['added'],
			$dry_run ? ' (не записано)' : ' — добавлен' ) );
		WP_CLI::log(
			sprintf(
				'Флаг устарел:         %d%s',
				$totals['stale'],
				$remove_stale
					? ( $dry_run ? ' (не записано)' : ' — снят: ' . $totals['removed'] )
					: ' — оставлен, нужен --remove-stale'
			)
		);
		WP_CLI::log( sprintf( 'Без пары на другом языке: %d', $totals['no_pair'] ) );
		WP_CLI::log( sprintf( 'Текста нет ни на одном языке, не тронуты: %d', $totals['no_text'] ) );

		if ( $totals['unclear'] > 0 ) {
			WP_CLI::warning(
				sprintf(
					'не берусь судить у %d записей (текст свой, но без кириллицы) — посмотрите глазами:',
					$totals['unclear']
				)
			);

			foreach ( $unclear as $line ) {
				WP_CLI::log( '  ' . $line );
			}
		}

		if ( $dry_run ) {
			WP_CLI::success( 'dry-run завершён, ничего не записано' );

			return;
		}

		WP_CLI::success( 'готово' );
	}

	/**
	 * Relink units to property in same language
	 *
	 * [--language=<slug>]
	 * : Only units in this Polylang language.
	 *
	 * [--status=<statuses>]
	 * : Comma-separated post statuses to walk.
	 * ---
	 * default: publish,draft,future
	 * ---
	 *
	 * [--limit=<number>]
	 * : Stop after this many fixes. 0 takes them all.
	 * ---
	 * default: 0
	 * ---
	 *
	 * [--report=<path>]
	 * : Write a CSV of every unit considered, fixed or not.
	 *
	 * [--dry-run]
	 * : Report the counts and write nothing.
	 *
	 * [--yes]
	 * : Skip the confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tools relink-units --dry-run
	 *     wp tools relink-units --language=ru --dry-run
	 *     wp tools relink-units --report=/tmp/relink.csv
	 *     wp tools relink-units --language=de --yes
	 *     wp tools relink-units --language=de
	 *
	 * @param array $args Positional arguments, unused.
	 * @param array $assoc_args Flags.
	 *
	 * @return void
	 */
	public static function relink_units( array $args, array $assoc_args ): void {
		global $wpdb;

		$dry_run  = (bool) Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$language = (string) Utils\get_flag_value( $assoc_args, 'language', '' );
		$limit    = max( 0, (int) Utils\get_flag_value( $assoc_args, 'limit', 0 ) );
		$report   = (string) Utils\get_flag_value( $assoc_args, 'report', '' );
		$statuses = array_values(
			array_filter(
				array_map(
					'trim',
					explode( ',', (string) Utils\get_flag_value( $assoc_args, 'status', implode( ',', Unit_Translator::DEFAULT_STATUSES ) ) )
				)
			)
		) ?: Unit_Translator::DEFAULT_STATUSES;

		if ( ! function_exists( 'pll_get_post_translations' ) ) {
			WP_CLI::error( 'Polylang не активен — язык записи определить нечем' );
		}

		$status_in = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
		$params    = array_merge( array( 'unit' ), $statuses );
		$language_clause = '';

		if ( '' !== $language ) {
			$language_clause = 'AND ul.slug = %s';
			$params[]        = $language;
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT u.ID AS unit_id, ul.slug AS unit_lang,
				        p.ID AS project_id, pl.slug AS project_lang
				 FROM {$wpdb->posts} u
				 INNER JOIN {$wpdb->postmeta} pm
				         ON pm.post_id = u.ID AND pm.meta_key = 'property'
				 INNER JOIN {$wpdb->posts} p
				         ON p.ID = CAST(pm.meta_value AS UNSIGNED)
				 INNER JOIN {$wpdb->term_relationships} utr ON utr.object_id = u.ID
				 INNER JOIN {$wpdb->term_taxonomy} utt
				         ON utt.term_taxonomy_id = utr.term_taxonomy_id AND utt.taxonomy = 'language'
				 INNER JOIN {$wpdb->terms} ul ON ul.term_id = utt.term_id
				 INNER JOIN {$wpdb->term_relationships} ptr ON ptr.object_id = p.ID
				 INNER JOIN {$wpdb->term_taxonomy} ptt
				         ON ptt.term_taxonomy_id = ptr.term_taxonomy_id AND ptt.taxonomy = 'language'
				 INNER JOIN {$wpdb->terms} pl ON pl.term_id = ptt.term_id
				 WHERE u.post_type = %s
				   AND u.post_status IN ({$status_in})
				   AND ul.slug <> pl.slug
				   {$language_clause}
				 ORDER BY ul.slug, u.ID",
				$params
			)
		);

		$orphans = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} p
				 WHERE p.post_type = %s AND p.post_status IN ({$status_in})
				   AND NOT EXISTS (
				       SELECT 1 FROM {$wpdb->postmeta} m
				       WHERE m.post_id = p.ID AND m.meta_key = 'property'
				         AND m.meta_value REGEXP '^[0-9]+$' AND m.meta_value <> '0'
				   )",
				array_merge( array( 'unit' ), $statuses )
			)
		);

		if ( empty( $rows ) ) {
			WP_CLI::success(
				sprintf( 'Юнитов с привязкой к чужому языку не нашлось; без привязки вовсе: %d', $orphans )
			);

			return;
		}

		$plan      = array();
		$stuck     = array();
		$pairs     = array();
		$by_target = array();

		foreach ( $rows as $row ) {
			$pair = $row->unit_lang . ' -> ' . $row->project_lang;

			if ( ! isset( $pairs[ $pair ] ) ) {
				$pairs[ $pair ] = array( 'fixable' => 0, 'stuck' => 0 );
			}

			$project_id = (int) $row->project_id;

			if ( ! isset( $by_target[ $project_id ] ) ) {
				$by_target[ $project_id ] = pll_get_post_translations( $project_id );
			}

			$target = (int) ( $by_target[ $project_id ][ $row->unit_lang ] ?? 0 );
			$item   = array(
				'unit_id'      => (int) $row->unit_id,
				'unit_lang'    => (string) $row->unit_lang,
				'project_id'   => $project_id,
				'project_lang' => (string) $row->project_lang,
				'target_id'    => $target,
				'result'       => '',
			);

			if ( $target > 0 && $target !== $project_id ) {
				++ $pairs[ $pair ]['fixable'];
				$plan[] = $item;

				continue;
			}

			++ $pairs[ $pair ]['stuck'];
			$item['result'] = 0 === $target ? 'у проекта нет перевода на этот язык' : 'перевод совпал с исходным проектом';
			$stuck[]        = $item;
		}

		ksort( $pairs );

		WP_CLI::log( 'Юниты, привязанные к проекту другого языка:' );

		foreach ( $pairs as $pair => $counts ) {
			WP_CLI::log(
				sprintf(
					'  %-12s всего %-6d исправимо %-6d нечем %d',
					$pair,
					$counts['fixable'] + $counts['stuck'],
					$counts['fixable'],
					$counts['stuck']
				)
			);
		}

		WP_CLI::log(
			sprintf( 'Итого %d, исправимо %d, нечем %d', count( $rows ), count( $plan ), count( $stuck ) )
		);

		if ( $orphans > 0 ) {
			WP_CLI::log( sprintf( 'Без привязки вовсе: %d — не трогаю, связь придумывать нечем', $orphans ) );
		}

		if ( $limit > 0 && count( $plan ) > $limit ) {
			$plan = array_slice( $plan, 0, $limit );
			WP_CLI::log( sprintf( 'Ограничение --limit: беру первые %d', $limit ) );
		}

		if ( '' !== $report ) {
			self::write_relink_report( $report, array_merge( $plan, $stuck ) );
		}

		if ( $dry_run ) {
			WP_CLI::success( 'dry-run завершён, ничего не записано' );

			return;
		}

		if ( empty( $plan ) ) {
			WP_CLI::warning( 'Исправить нечего: у проектов нет переводов на нужные языки' );

			return;
		}

		WP_CLI::confirm( sprintf( 'Перепривязать %d юнитов?', count( $plan ) ), $assoc_args );

		$bar    = Utils\make_progress_bar( 'Relinking', count( $plan ) );
		$done   = 0;
		$failed = 0;

		foreach ( $plan as $index => $item ) {
			$written = update_post_meta( $item['unit_id'], 'property', (string) $item['target_id'] );

			if ( false === $written ) {
				++ $failed;
				$plan[ $index ]['result'] = 'запись не удалась';
				WP_CLI::warning( sprintf( 'юнит #%d: мета не записалась', $item['unit_id'] ) );
			} else {
				++ $done;
				$plan[ $index ]['result'] = 'перепривязан';
			}

			$bar->tick();
		}

		$bar->finish();

		if ( '' !== $report ) {
			self::write_relink_report( $report, array_merge( $plan, $stuck ) );
		}

		WP_CLI::log( sprintf( 'Перепривязано %d, не удалось %d, осталось нечем %d', $done, $failed, count( $stuck ) ) );

		self::flush_caches();

		WP_CLI::success( 'Готово' );
	}

	/**
	 * Recompute the cached unit counter of every project.
	 *
	 * `units_count` is written lazily, once something asks a project how many
	 * units it has. A project nobody asked about has no row at all, and the
	 * listing reads the stored value rather than counting afresh — so a missing
	 * or stale counter decides what the catalogue shows.
	 *
	 * The count is the one Property::get_units_count() makes: rows of the
	 * `property` meta pointing at this project, whatever their post status or
	 * language. Nothing else is touched.
	 *
	 * ## OPTIONS
	 *
	 * [--language=<slug>]
	 * : Only projects in this Polylang language.
	 *
	 * [--status=<statuses>]
	 * : Comma-separated post statuses to walk.
	 * ---
	 * default: publish,draft,future
	 * ---
	 *
	 * [--limit=<number>]
	 * : Stop after this many writes. 0 takes them all.
	 * ---
	 * default: 0
	 * ---
	 *
	 * [--all]
	 * : Rewrite every project, including those whose counter is already right.
	 *
	 * [--report=<path>]
	 * : Write a CSV of every project that needed a new value.
	 *
	 * [--dry-run]
	 * : Report the counts and write nothing.
	 *
	 * [--yes]
	 * : Skip the confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tools recount-units --dry-run
	 *     wp tools recount-units --dry-run --report=/tmp/counts.csv
	 *     wp tools recount-units --language=en
	 *     wp tools recount-units --yes
	 *
	 * @param array $args Positional arguments, unused.
	 * @param array $assoc_args Flags.
	 *
	 * @return void
	 */
	public static function recount_units( array $args, array $assoc_args ): void {
		global $wpdb;

		$dry_run  = (bool) Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$language = (string) Utils\get_flag_value( $assoc_args, 'language', '' );
		$limit    = max( 0, (int) Utils\get_flag_value( $assoc_args, 'limit', 0 ) );
		$report   = (string) Utils\get_flag_value( $assoc_args, 'report', '' );
		$rewrite  = (bool) Utils\get_flag_value( $assoc_args, 'all', false );
		$statuses = array_values(
			array_filter(
				array_map(
					'trim',
					explode( ',', (string) Utils\get_flag_value( $assoc_args, 'status', implode( ',', Unit_Translator::DEFAULT_STATUSES ) ) )
				)
			)
		) ?: Unit_Translator::DEFAULT_STATUSES;

		$status_in     = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
		$language_join = '';
		// Плейсхолдер языка стоит в SQL раньше типа записи, поэтому и в
		// параметрах он должен идти первым: prepare() связывает их по порядку.
		$params = array();

		if ( '' !== $language ) {
			$language_join = "INNER JOIN {$wpdb->term_relationships} ptr ON ptr.object_id = p.ID
				INNER JOIN {$wpdb->term_taxonomy} ptt
				        ON ptt.term_taxonomy_id = ptr.term_taxonomy_id AND ptt.taxonomy = 'language'
				INNER JOIN {$wpdb->terms} pl ON pl.term_id = ptt.term_id AND pl.slug = %s";
			$params[]      = $language;
		}

		$params = array_merge( $params, array( 'property' ), $statuses );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID,
				        MIN( cnt.meta_value ) AS saved,
				        COUNT( DISTINCT cnt.meta_id ) AS rows_saved,
				        COUNT( DISTINCT exp.meta_id ) AS rows_expired,
				        MAX( COALESCE( live.c, 0 ) ) AS fresh
				 FROM {$wpdb->posts} p
				 {$language_join}
				 LEFT JOIN {$wpdb->postmeta} cnt
				        ON cnt.post_id = p.ID AND cnt.meta_key = 'units_count'
				 LEFT JOIN {$wpdb->postmeta} exp
				        ON exp.post_id = p.ID AND exp.meta_key = 'units_count_expired'
				 LEFT JOIN ( SELECT meta_value AS pid, COUNT(*) c
				             FROM {$wpdb->postmeta}
				             WHERE meta_key = 'property' AND meta_value REGEXP '^[0-9]+$'
				             GROUP BY meta_value ) live ON live.pid = p.ID
				 WHERE p.post_type = %s AND p.post_status IN ({$status_in})
				 GROUP BY p.ID
				 ORDER BY p.ID",
				$params
			)
		);

		if ( empty( $rows ) ) {
			WP_CLI::error( 'Проектов не нашлось' );
		}

		$plan    = array();
		$missing = $wrong = $right = $doubled = 0;

		foreach ( $rows as $row ) {
			$fresh = (int) $row->fresh;
			$saved = null === $row->saved ? null : (int) $row->saved;

			// Дубликаты меты: get_post_meta() берёт первую строку, так что
			// значение зависит от порядка. Такую запись переписываем всегда.
			$twice = (int) $row->rows_saved > 1 || (int) $row->rows_expired > 1;

			if ( $twice ) {
				++ $doubled;
			}

			if ( null === $saved ) {
				++ $missing;
			} elseif ( $saved !== $fresh ) {
				++ $wrong;
			} else {
				++ $right;

				if ( ! $rewrite && ! $twice ) {
					continue;
				}
			}

			$plan[] = array(
				'id'    => (int) $row->ID,
				'saved' => null === $saved ? '' : $saved,
				'fresh' => $fresh,
				'twice' => $twice,
			);
		}

		WP_CLI::log( sprintf( 'Проектов осмотрено: %d', count( $rows ) ) );
		WP_CLI::log( sprintf( '  без счётчика:       %d', $missing ) );
		WP_CLI::log( sprintf( '  значение разошлось: %d', $wrong ) );
		WP_CLI::log( sprintf( '  счётчик верен:      %d%s', $right, $rewrite ? ' (перезапишем, --all)' : ' (пропускаем)' ) );
		WP_CLI::log( sprintf( '  строк меты больше одной: %d', $doubled ) );
		WP_CLI::log( sprintf( 'К записи: %d', count( $plan ) ) );

		if ( $limit > 0 && count( $plan ) > $limit ) {
			$plan = array_slice( $plan, 0, $limit );
			WP_CLI::log( sprintf( 'Ограничение --limit: беру первые %d', $limit ) );
		}

		if ( '' !== $report ) {
			self::write_recount_report( $report, $plan );
		}

		if ( $dry_run ) {
			WP_CLI::success( 'dry-run завершён, ничего не записано' );

			return;
		}

		if ( empty( $plan ) ) {
			WP_CLI::success( 'Все счётчики на месте и верны' );

			return;
		}

		WP_CLI::confirm( sprintf( 'Пересчитать %d проектов?', count( $plan ) ), $assoc_args );

		$bar     = Utils\make_progress_bar( 'Recounting', count( $plan ) );
		$expires = time() + DAY_IN_SECONDS;
		$done    = 0;

		foreach ( $plan as $item ) {
			// update_post_meta переписывает все строки ключа, но лишние
			// оставляет; дубликат переживёт правку и снова разойдётся при
			// первой же частичной записи, поэтому его сносим целиком.
			if ( $item['twice'] ) {
				delete_post_meta( $item['id'], 'units_count' );
				delete_post_meta( $item['id'], 'units_count_expired' );
			}

			update_post_meta( $item['id'], 'units_count', $item['fresh'] );
			update_post_meta( $item['id'], 'units_count_expired', $expires );
			++ $done;
			$bar->tick();
		}

		$bar->finish();

		WP_CLI::log( sprintf( 'Записано счётчиков: %d', $done ) );

		self::flush_caches();

		WP_CLI::success( 'Готово' );
	}

	/**
	 * Write what the recount changed.
	 *
	 * @param string $path  Destination CSV.
	 * @param array  $items Rows to write.
	 *
	 * @return void
	 */
	private static function write_recount_report( string $path, array $items ): void {
		$handle = fopen( $path, 'w' );

		if ( false === $handle ) {
			WP_CLI::warning( sprintf( 'не удалось записать отчёт %s', $path ) );

			return;
		}

		fputcsv( $handle, array( 'property_id', 'saved', 'fresh' ), ',', '"', '' );

		foreach ( $items as $item ) {
			fputcsv( $handle, array( $item['id'], $item['saved'], $item['fresh'] ), ',', '"', '' );
		}

		fclose( $handle );

		WP_CLI::log( sprintf( 'Отчёт: %s', $path ) );
	}


	/**
	 * Write what the relink considered, fixed or not.
	 *
	 * @param string $path  Destination CSV.
	 * @param array  $items Rows to write.
	 *
	 * @return void
	 */
	private static function write_relink_report( string $path, array $items ): void {
		$handle = fopen( $path, 'w' );

		if ( false === $handle ) {
			WP_CLI::warning( sprintf( 'не удалось записать отчёт %s', $path ) );

			return;
		}

		fputcsv( $handle, array( 'unit_id', 'unit_lang', 'old_project', 'old_project_lang', 'new_project', 'result' ), ',', '"', '' );

		foreach ( $items as $item ) {
			fputcsv(
				$handle,
				array(
					$item['unit_id'],
					$item['unit_lang'],
					$item['project_id'],
					$item['project_lang'],
					$item['target_id'] ?: '',
					$item['result'],
				),
				',',
				'"',
				''
			);
		}

		fclose( $handle );

		WP_CLI::log( sprintf( 'Отчёт: %s', $path ) );
	}

	/**
	 * Flag one Russian post, and keep the flag off its English counterpart.
	 *
	 * Polylang is configured to synchronise custom fields — `post_meta` sits in
	 * its sync list — and that synchronisation fires on a plain
	 * update_post_meta(), not only on a full save. Verified by experiment: after
	 * writing the flag on a Russian post the English sibling had it too.
	 *
	 * On the English post the flag is meaningless and actively misleading: it
	 * claims the source text needs translating. So it is stripped right back off.
	 *
	 * The importers avoid this by accident, not by design — they set the flag
	 * before pll_save_post_translations(), while the pair does not exist yet and
	 * there is nothing to sync to.
	 *
	 * @param int $post_id Russian post.
	 *
	 * @return void
	 */
	private static function set_translation_flag( int $post_id ): void {
		update_post_meta( $post_id, Distress_Units_Importer::NEED_TRANSLATE_META, 1 );

		if ( ! function_exists( 'pll_get_post_translations' ) ) {
			return;
		}

		foreach ( pll_get_post_translations( $post_id ) as $lang => $sibling ) {
			if ( 'ru' === $lang || (int) $sibling === $post_id ) {
				continue;
			}

			delete_post_meta( (int) $sibling, Distress_Units_Importer::NEED_TRANSLATE_META );
		}
	}

	/**
	 * Decide what a Russian post actually holds.
	 *
	 * @param int $post_id Russian post.
	 *
	 * @return string One of: placeholder, translated, unclear, no_pair, no_text.
	 */
	private static function translation_verdict( int $post_id ): string {
		if ( ! function_exists( 'pll_get_post_translations' ) ) {
			return 'no_pair';
		}

		$translations = pll_get_post_translations( $post_id );
		$default      = function_exists( 'pll_default_language' ) ? (string) pll_default_language( 'slug' ) : 'en';

		if ( empty( $translations[ $default ] ) ) {
			return 'no_pair';
		}

		$ru = trim( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ) );
		$en = trim( wp_strip_all_tags( (string) get_post_field( 'post_content', $translations[ $default ] ) ) );

		// Текста нет ни на одном языке — судить не о чем, запись не трогаем.
		// Раньше такие попадали в «переведённые» и предлагались к снятию флага:
		// 127 записей на локальной копии, ни одна из них переводом не является.
		if ( '' === $ru && '' === $en ) {
			return 'no_text';
		}

		// Точное совпадение — это копия, оставленная импортёром.
		if ( $ru === $en ) {
			return 'placeholder';
		}

		// Пустой русский текст при заполненном английском — читать нечего.
		if ( '' === $ru ) {
			return 'placeholder';
		}

		if ( preg_match( '~\p{Cyrillic}~u', $ru ) ) {
			return 'translated';
		}

		// Текст свой, но без кириллицы: судить машинно нельзя.
		return 'unclear';
	}

	/**
	 * Move the units a distressuae.com export lists onto this site as distress listings.
	 *
	 * Reads the JSON produced by export-distress-units.php. Every unit is created
	 * with listing_type = distress, owned by the given author, who also becomes
	 * its broker. A project the site does not have is created as a published
	 * stub carrying a noindex robots meta: resolve_property_id() accepts a
	 * project only in `publish`, so under a draft stub the unit would count as
	 * having no project and render without its building. The robots meta is
	 * what keeps the empty page out of the index and the sitemap instead.
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
	 * a project only in publish, and under a draft stub the unit renders with no
	 * building and drops the project from its url.
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
			WP_CLI::log( sprintf( 'Projects to create as %s: %d',
				$project_status,
				count( $preview['new_projects'] ) ) );
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
	 * @param string $path Where to write.
	 * @param array $redirects Old path => new url.
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

		foreach (
			array(
				'created'          => 'Units создано',
				'created_ru'       => 'Русских версий создано (need_translate = 1)',
				'projects_created' => 'Проектов-заготовок создано',
				'media_fetched'    => 'Файлов галереи скачано',
				'media_reused'     => 'Файлов переиспользовано без закачки',
				'terms_assigned'   => 'Привязок location',
				'skipped_existing' => 'Уже было на сайте, пропущено',
				'failed'           => 'Не удалось',
			) as $key => $label
		) {
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
				++ $shown;
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
	 * @param array $args Positional arguments, none are used.
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
	 * Warm the caches of the most visited pages: the home page of every language, the pages it links to and, with --log, the most requested ones.
	 *
	 * Run by scripts/auto-deploy.sh after every deploy.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<number>]
	 * : Warm at most this many pages.
	 * ---
	 * default: 150
	 * ---
	 *
	 * [--concurrency=<number>]
	 * : Requests in flight at once.
	 * ---
	 * default: 3
	 * ---
	 *
	 * [--log=<path>]
	 * : Access log in the combined format. Its most requested pages are warmed right after the home pages.
	 *
	 * [--top=<number>]
	 * : Pages to take from the log.
	 * ---
	 * default: 50
	 * ---
	 *
	 * [--dry-run]
	 * : List the pages and request nothing.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tools warm-cache --dry-run
	 *     wp tools warm-cache
	 *     wp tools warm-cache --log=/var/log/nginx/access.log --top=100
	 *
	 * @param array $args Positional arguments, unused.
	 * @param array $assoc_args Flags.
	 *
	 * @return void
	 */
	public static function warm_cache( array $args, array $assoc_args ): void {
		$limit       = max( 1, (int) Utils\get_flag_value( $assoc_args, 'limit', 150 ) );
		$concurrency = max( 1, min( 10, (int) Utils\get_flag_value( $assoc_args, 'concurrency', 3 ) ) );
		$log         = (string) Utils\get_flag_value( $assoc_args, 'log', '' );

		$top     = max( 0, (int) Utils\get_flag_value( $assoc_args, 'top', 50 ) );
		$dry_run = (bool) Utils\get_flag_value( $assoc_args, 'dry-run', false );

		$warmer = new Cache_Warmer();
		$pages  = $warmer->collect( $limit, $log, $top );

		WP_CLI::log( sprintf( 'Сайт: %s', home_url( '/' ) ) );
		WP_CLI::log( sprintf( 'Страниц к прогреву: %d, из них из журнала: %d',
			count( $pages['urls'] ),
			$pages['from_log'] ) );

		foreach ( $pages['warnings'] as $warning ) {
			WP_CLI::warning( $warning );
		}

		if ( $dry_run ) {
			foreach ( $pages['urls'] as $url ) {
				WP_CLI::log( '  ' . $url );
			}

			WP_CLI::success( 'dry-run: ничего не запрошено' );

			return;
		}

		$results = array();
		$started = microtime( true );

		$warmer->warm(
			$pages['urls'],
			$concurrency,
			static function ( string $url, int $status, float $seconds, string $error ) use ( &$results ): void {
				$results[] = array(
					'url'     => $url,
					'status'  => $status,
					'seconds' => $seconds,
				);

				if ( 200 !== $status || $seconds > 5 ) {
					WP_CLI::log( sprintf( '  %s %5.1f с  %s%s',
						0 === $status ? 'ERR' : $status,
						$seconds,
						$url,
						'' === $error ? '' : ' — ' . $error ) );
				}
			}
		);

		$times = array_column( $results, 'seconds' );
		sort( $times );

		$statuses = array_count_values( array_map( static fn( array $result ): string => (string) $result['status'],
			$results ) );
		ksort( $statuses );

		WP_CLI::log(
			sprintf(
				'Ответы: %s; медиана %.1f с, p90 %.1f с, максимум %.1f с',
				implode( ', ',
					array_map( static fn(
						string $status,
						int $count
					): string => ( '0' === $status ? 'ошибка' : $status ) . ' × ' . $count,
						array_keys( $statuses ),
						$statuses ) ),
				$times[ (int) floor( count( $times ) / 2 ) ] ?? 0,
				$times[ (int) floor( count( $times ) * 0.9 ) ] ?? 0,
				end( $times ) ?: 0
			)
		);

		WP_CLI::success( sprintf( 'прогрето %d страниц за %d с',
			$statuses['200'] ?? 0,
			(int) round( microtime( true ) - $started ) ) );
	}

	/**
	 * Remove the floor from unit titles and slugs: "Unit 1706 on 17 floor" becomes "Unit 1706".
	 *
	 * Every old URL of a unit with a public page keeps answering with a 301 to the
	 * new one; a translation that shared the old slug gets the same new one.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Show what would change and write nothing.
	 *
	 * [--limit=<number>]
	 * : Stop after this many units. Zero means all of them.
	 * ---
	 * default: 0
	 * ---
	 *
	 * [--report=<path>]
	 * : Write every change to this CSV file, old and new URL included.
	 *
	 * [--yes]
	 * : Do not ask for confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tools strip-unit-floors --dry-run --report=/tmp/unit-floors-plan.csv
	 *     wp tools strip-unit-floors --limit=10
	 *     wp tools strip-unit-floors --yes --report=/tmp/unit-floors.csv
	 *
	 * @param array $args       Positional arguments, unused.
	 * @param array $assoc_args Flags.
	 *
	 * @return void
	 */
	public static function strip_unit_floors( array $args, array $assoc_args ): void {
		$dry_run = (bool) Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$limit   = (int) Utils\get_flag_value( $assoc_args, 'limit', 0 );
		$report  = (string) Utils\get_flag_value( $assoc_args, 'report', '' );

		$cleaner = new Unit_Floor_Cleaner();
		$plan    = $cleaner->plan( $limit );
		$items   = $plan['items'];

		self::print_floor_plan( $plan, $cleaner->leftovers( $plan ) );

		if ( empty( $items ) ) {
			WP_CLI::success( 'этажа нет ни в заголовках, ни в слагах — менять нечего' );

			return;
		}

		if ( '' !== $report ) {
			foreach ( $items as $index => $item ) {
				$items[ $index ]['old_url'] = (string) get_permalink( $item['id'] );
			}
		}

		if ( $dry_run ) {
			if ( '' !== $report ) {
				self::write_floor_report( $report, $items );
			}

			WP_CLI::success( 'dry-run завершён, ничего не записано' );

			return;
		}

		WP_CLI::confirm( sprintf( 'Переписать %d юнитов?', count( $items ) ), $assoc_args );

		$bar   = Utils\make_progress_bar( 'Rewriting', count( $items ) );
		$items = $cleaner->apply(
			$items,
			static function () use ( $bar ): void {
				$bar->tick();
			}
		);
		$bar->finish();

		if ( '' !== $report ) {
			self::write_floor_report( $report, $items );
		}

		wp_cache_flush();

		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}

		$failed    = array_filter( $items, static fn( array $item ): bool => isset( $item['error'] ) );
		$drifted   = array_filter( $items, static fn( array $item ): bool => ! isset( $item['error'] ) && ( $item['saved_title'] !== $item['new_title'] || $item['saved_slug'] !== $item['new_slug'] ) );
		$lost      = array_filter( $items, static fn( array $item ): bool => false === ( $item['redirect'] ?? null ) );
		$redirects = count( array_filter( $items, static fn( array $item ): bool => true === ( $item['redirect'] ?? null ) ) );

		foreach ( array_slice( $failed, 0, 20 ) as $item ) {
			WP_CLI::warning( sprintf( '#%d не сохранён: %s', $item['id'], $item['error'] ) );
		}

		foreach ( array_slice( $drifted, 0, 20 ) as $item ) {
			WP_CLI::warning( sprintf( '#%d сохранён не так, как планировалось: «%s» %s', $item['id'], $item['saved_title'], $item['saved_slug'] ) );
		}

		foreach ( array_slice( $lost, 0, 20 ) as $item ) {
			WP_CLI::warning( sprintf( '#%d: старый слаг %s не попал в _wp_old_slug, 301 не будет', $item['id'], $item['old_slug'] ) );
		}

		WP_CLI::log( 'Object cache сброшен.' );

		$message = sprintf(
			'переписано %d из %d юнитов, 301 со старого адреса у %d',
			count( $items ) - count( $failed ),
			count( $items ),
			$redirects
		);

		if ( $failed || $drifted || $lost ) {
			WP_CLI::error( $message . sprintf( '; ошибок %d, расхождений %d, без редиректа %d', count( $failed ), count( $drifted ), count( $lost ) ) );
		}

		WP_CLI::success( $message );
	}

	/**
	 * Print what strip-unit-floors is about to change.
	 *
	 * @param array   $plan      Result of Unit_Floor_Cleaner::plan().
	 * @param array[] $leftovers Units that still mention a floor afterwards.
	 *
	 * @return void
	 */
	private static function print_floor_plan( array $plan, array $leftovers ): void {
		$items = $plan['items'];

		$titles   = count( array_filter( $items, static fn( array $item ): bool => $item['new_title'] !== $item['old_title'] ) );
		$slugs    = count( array_filter( $items, static fn( array $item ): bool => $item['new_slug'] !== $item['old_slug'] ) );
		$follows  = count( array_filter( $items, static fn( array $item ): bool => $item['follows'] > 0 ) );
		$suffixed = count( array_filter( $items, static fn( array $item ): bool => ! empty( $item['suffixed'] ) ) );

		$groups = array();
		foreach ( $items as $item ) {
			$key            = ( '' === $item['lang'] ? '-' : $item['lang'] ) . '/' . $item['status'];
			$groups[ $key ] = ( $groups[ $key ] ?? 0 ) + 1;
		}
		ksort( $groups );

		WP_CLI::log( sprintf( 'Юнитов с этажом в заголовке или слаге: %d', $plan['checked'] ) );
		WP_CLI::log( sprintf( 'К изменению:          %d (заголовков %d, слагов %d)', count( $items ), $titles, $slugs ) );
		WP_CLI::log(
			sprintf(
				'  язык/статус:        %s',
				implode( ', ', array_map( static fn( string $key, int $count ): string => $key . ' ' . $count, array_keys( $groups ), $groups ) )
			)
		);
		WP_CLI::log( sprintf( 'Слаг как у английской версии: %d', $follows ) );
		WP_CLI::log( sprintf( 'Слаг с суффиксом -N (чистый занят живым юнитом или чужим редиректом): %d', $suffixed ) );

		WP_CLI::log( '' );
		WP_CLI::log( 'Что вырезается из заголовков (по всем найденным, цифры как N):' );
		foreach ( $plan['fragments'] as $fragment => $count ) {
			WP_CLI::log( sprintf( '  %5d  %s', $count, $fragment ) );
		}

		WP_CLI::log( 'Что вырезается из слагов:' );
		foreach ( $plan['slug_fragments'] as $fragment => $count ) {
			WP_CLI::log( sprintf( '  %5d  %s', $count, $fragment ) );
		}

		if ( ! empty( $items ) ) {
			WP_CLI::log( '' );
			WP_CLI::log( 'Первые 10:' );
			Utils\format_items(
				'table',
				array_map(
					static fn( array $item ): array => array(
						'id'    => $item['id'],
						'lang'  => $item['lang'],
						'title' => $item['old_title'] . ' → ' . $item['new_title'],
						'slug'  => $item['new_slug'],
					),
					array_slice( $items, 0, 10 )
				),
				array( 'id', 'lang', 'title', 'slug' )
			);
		}

		if ( empty( $leftovers ) ) {
			WP_CLI::log( 'После замены этаж не упоминается ни у одного юнита.' );

			return;
		}

		WP_CLI::warning( sprintf( 'правила не распознали этаж у %d юнитов — они останутся как есть:', count( $leftovers ) ) );
		foreach ( array_slice( $leftovers, 0, 50 ) as $leftover ) {
			WP_CLI::log( sprintf( '  #%d %s | %s', $leftover['id'], $leftover['title'], $leftover['slug'] ) );
		}
	}

	/**
	 * Write the strip-unit-floors changes to a CSV file.
	 *
	 * @param string  $path  Where to write.
	 * @param array[] $items Planned or applied items.
	 *
	 * @return void
	 */
	private static function write_floor_report( string $path, array $items ): void {
		$handle = fopen( $path, 'w' );

		if ( false === $handle ) {
			WP_CLI::warning( sprintf( 'не удалось записать отчёт %s', $path ) );

			return;
		}

		fputcsv( $handle, array( 'id', 'lang', 'status', 'old_title', 'new_title', 'old_slug', 'new_slug', 'old_url', 'new_url', 'redirect', 'error' ), ',', '"', '' );

		foreach ( $items as $item ) {
			$old_url = $item['old_url'] ?? '';
			$new_url = $item['url'] ?? (string) preg_replace( '~/' . preg_quote( $item['old_slug'], '~' ) . '/$~', '/' . $item['new_slug'] . '/', $old_url );

			fputcsv(
				$handle,
				array(
					$item['id'],
					$item['lang'],
					$item['status'],
					$item['old_title'],
					$item['saved_title'] ?? $item['new_title'],
					$item['old_slug'],
					$item['saved_slug'] ?? $item['new_slug'],
					$old_url,
					$new_url,
					null === ( $item['redirect'] ?? null ) ? '' : ( $item['redirect'] ? 'yes' : 'no' ),
					$item['error'] ?? '',
				),
				',',
				'"',
				''
			);
		}

		fclose( $handle );

		WP_CLI::log( sprintf( 'Отчёт: %s (%d строк)', $path, count( $items ) ) );
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

		foreach (
			array(
				'created'             => 'Created',
				'translated'          => 'Russian version added to an existing project',
				'placeholder_ru'      => 'Russian copied from English (need_translate = 1)',
				'skipped'             => 'Already on site, nothing to add',
				'failed'              => 'Failed',
				'without_developer'   => 'Created without a developer',
				'without_coordinates' => 'Created without coordinates',
			) as $key => $label
		) {
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

	/**
	 * Put every location under a country and create the countries themselves.
	 *
	 * The top level of the location tree is the country. Everything that sits
	 * at the top today is a UAE district, so it moves under the default
	 * country; a slug listed in --countries stays where it is.
	 *
	 * ## OPTIONS
	 *
	 * [--countries=<slugs>]
	 * : Comma-separated country slugs, created when missing.
	 * ---
	 * default: uae,saudi-arabia,oman
	 * ---
	 *
	 * [--default=<slug>]
	 * : Country the orphan locations move under.
	 * ---
	 * default: uae
	 * ---
	 *
	 * [--dry-run]
	 * : Only report what would change.
	 *
	 * [--yes]
	 * : Skip the confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tools setup-countries --dry-run --allow-root
	 *     wp tools setup-countries --allow-root
	 *
	 * @param array $args
	 * @param array $assoc_args
	 *
	 * @return void
	 */
	public static function setup_countries( array $args, array $assoc_args ): void {
		$dry_run = (bool) Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$default = sanitize_title( (string) Utils\get_flag_value( $assoc_args, 'default', 'uae' ) );
		$slugs   = array_values(
			array_filter(
				array_map(
					static function ( string $slug ): string {
						return sanitize_title( trim( $slug ) );
					},
					explode( ',', (string) Utils\get_flag_value( $assoc_args, 'countries', 'uae,saudi-arabia,oman' ) )
				)
			)
		);

		if ( '' === $default || ! in_array( $default, $slugs, true ) ) {
			WP_CLI::error( 'Страна по умолчанию должна быть в списке --countries' );
		}

		$countries = array();

		foreach ( $slugs as $slug ) {
			$term = get_term_by( 'slug', $slug, 'location' );

			if ( $term instanceof \WP_Term ) {
				$countries[ $slug ] = (int) $term->term_id;

				if ( ! $dry_run && 'country' !== core_location_type( (int) $term->term_id ) ) {
					update_term_meta( (int) $term->term_id, CORE_LOCATION_TYPE_META, 'country' );
					core_location_flush_type_caches();
				}

				if ( 0 !== (int) $term->parent ) {
					WP_CLI::log( sprintf( 'Страна %s лежит не на верхнем уровне, поднимаю', $slug ) );

					if ( ! $dry_run ) {
						self::set_term_parent( (int) $term->term_id, 0 );
					}
				}

				continue;
			}

			WP_CLI::log( sprintf( 'Создаю страну %s', $slug ) );

			if ( $dry_run ) {
				$countries[ $slug ] = 0;

				continue;
			}

			$created = wp_insert_term( self::country_name( $slug ), 'location', array( 'slug' => $slug, 'parent' => 0 ) );

			if ( is_wp_error( $created ) ) {
				WP_CLI::error( sprintf( '%s: %s', $slug, $created->get_error_message() ) );
			}

			$countries[ $slug ] = (int) $created['term_id'];

			update_term_meta( (int) $created['term_id'], CORE_LOCATION_TYPE_META, 'country' );
			core_location_flush_type_caches();
		}

		// Исключаются и страны из списка, и любые уже помеченные страной: иначе
		// повторный запуск уводил новую страну под страну по умолчанию только
		// потому, что её слаг не назвали в --countries.
		$exclude = array_values( array_unique( array_merge( array_filter( $countries ), core_country_term_ids() ) ) );

		$orphans = get_terms(
			array(
				'taxonomy'   => 'location',
				'parent'     => 0,
				'hide_empty' => false,
				'exclude'    => $exclude,
				'fields'     => 'ids',
			)
		);

		if ( is_wp_error( $orphans ) ) {
			WP_CLI::error( $orphans->get_error_message() );
		}

		$orphans = array_map( 'intval', (array) $orphans );

		WP_CLI::log(
			sprintf(
				'Стран: %d. Локаций без страны: %d',
				count( $countries ),
				count( $orphans )
			)
		);

		if ( empty( $orphans ) ) {
			WP_CLI::success( 'Переносить нечего' );

			return;
		}

		if ( $dry_run ) {
			WP_CLI::success( sprintf( 'Сухой прогон: под %s ушло бы %d локаций', $default, count( $orphans ) ) );

			return;
		}

		WP_CLI::confirm( sprintf( 'Перенести %d локаций под %s?', count( $orphans ), $default ), $assoc_args );

		$parent   = (int) $countries[ $default ];
		$moved    = 0;
		$failed   = array();
		$progress = Utils\make_progress_bar( 'Перенос локаций', count( $orphans ) );

		foreach ( $orphans as $term_id ) {
			if ( self::set_term_parent( $term_id, $parent ) ) {
				++$moved;
			} else {
				$failed[] = sprintf( '%d: запись не изменилась', $term_id );
			}

			$progress->tick();
		}

		$progress->finish();

		foreach ( array_slice( $failed, 0, 20 ) as $error ) {
			WP_CLI::warning( $error );
		}

		WP_CLI::success( sprintf( 'Перенесено %d, ошибок %d', $moved, count( $failed ) ) );
	}

	/**
	 * Move a location term under another one.
	 *
	 * Written straight to the table: wp_update_term() re-derives the slug from
	 * the name whenever the parent changes, and renames terms whose name is a
	 * duplicate.
	 *
	 * @param int $term_id
	 * @param int $parent
	 *
	 * @return bool
	 */
	private static function set_term_parent( int $term_id, int $parent ): bool {
		global $wpdb;

		$updated = $wpdb->update(
			$wpdb->term_taxonomy,
			array( 'parent' => $parent ),
			array( 'term_id' => $term_id, 'taxonomy' => 'location' ),
			array( '%d' ),
			array( '%d', '%s' )
		);

		if ( false === $updated ) {
			return false;
		}

		clean_term_cache( $term_id, 'location' );

		return true;
	}

	/**
	 * Readable name for a country slug.
	 *
	 * @param string $slug
	 *
	 * @return string
	 */
	private static function country_name( string $slug ): string {
		$known = array(
			'uae'          => 'United Arab Emirates',
			'saudi-arabia' => 'Saudi Arabia',
			'oman'         => 'Oman',
		);

		return $known[ $slug ] ?? ucwords( str_replace( '-', ' ', $slug ) );
	}

	/**
	 * Write price_base for every unit and project.
	 *
	 * Listings sort and filter on the base currency, so a Saudi object priced
	 * in riyals does not land among the cheap ones. Grouped by country, so the
	 * currency is known per batch instead of per post.
	 *
	 * ## OPTIONS
	 *
	 * [--post-type=<types>]
	 * : Comma-separated post types.
	 * ---
	 * default: unit,property
	 * ---
	 *
	 * [--dry-run]
	 * : Report the counts and write nothing.
	 *
	 * [--yes]
	 * : Skip the confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tools sync-prices --dry-run --allow-root
	 *
	 * @param array $args
	 * @param array $assoc_args
	 *
	 * @return void
	 */
	public static function sync_prices( array $args, array $assoc_args ): void {
		global $wpdb;

		$dry_run = (bool) Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$types   = array_values(
			array_filter(
				array_map( 'trim', explode( ',', (string) Utils\get_flag_value( $assoc_args, 'post-type', 'unit,property' ) ) )
			)
		);

		if ( empty( $types ) ) {
			WP_CLI::error( 'Нужен хотя бы один тип записи' );
		}

		$type_in = implode( ',', array_fill( 0, count( $types ), '%s' ) );
		$plan    = array();
		$rates   = core_currency_rates();

		foreach ( core_country_slugs() as $country ) {
			$tt_ids = core_country_location_tt_ids( $country );

			if ( empty( $tt_ids ) ) {
				continue;
			}

			$currency = core_country_currency( $country );
			$rate     = (float) ( $rates[ $currency ] ?? 1.0 );
			$in       = implode( ',', $tt_ids );

			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT p.ID, pm.meta_value AS price
					 FROM {$wpdb->posts} p
					 INNER JOIN {$wpdb->term_relationships} tr
					         ON tr.object_id = p.ID AND tr.term_taxonomy_id IN ({$in})
					 INNER JOIN {$wpdb->postmeta} pm
					         ON pm.post_id = p.ID AND pm.meta_key = 'price'
					 WHERE p.post_type IN ({$type_in})",
					$types
				)
			);

			foreach ( (array) $rows as $row ) {
				if ( ! is_numeric( $row->price ) || 0.0 === (float) $row->price ) {
					continue;
				}

				$plan[ (int) $row->ID ] = (int) round( (float) $row->price * $rate );
			}
		}

		// Объект без района страны не имеет, цена уже в базовой валюте.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, pm.meta_value AS price
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm
				         ON pm.post_id = p.ID AND pm.meta_key = 'price'
				 LEFT JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
				 LEFT JOIN {$wpdb->term_taxonomy} tt
				        ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'location'
				 WHERE p.post_type IN ({$type_in})
				 GROUP BY p.ID, pm.meta_value
				 HAVING COUNT( tt.term_taxonomy_id ) = 0",
				$types
			)
		);

		foreach ( (array) $rows as $row ) {
			if ( is_numeric( $row->price ) && 0.0 !== (float) $row->price && ! isset( $plan[ (int) $row->ID ] ) ) {
				$plan[ (int) $row->ID ] = (int) round( (float) $row->price );
			}
		}

		if ( empty( $plan ) ) {
			WP_CLI::error( 'Записей с ценой не нашлось' );
		}

		$stored = array();

		foreach ( array_chunk( array_keys( $plan ), 2000 ) as $chunk ) {
			$ids  = implode( ',', array_map( 'intval', $chunk ) );
			$rows = $wpdb->get_results(
				"SELECT post_id, meta_value, COUNT(*) AS rows_saved
				 FROM {$wpdb->postmeta}
				 WHERE meta_key = 'price_base' AND post_id IN ({$ids})
				 GROUP BY post_id, meta_value"
			);

			foreach ( (array) $rows as $row ) {
				$stored[ (int) $row->post_id ] = array( (int) $row->meta_value, (int) $row->rows_saved );
			}
		}

		$missing = $wrong = $doubled = $right = 0;

		foreach ( $plan as $post_id => $expected ) {
			if ( ! isset( $stored[ $post_id ] ) ) {
				++$missing;

				continue;
			}

			list( $value, $rows_saved ) = $stored[ $post_id ];

			if ( 1 < $rows_saved ) {
				++$doubled;
			} elseif ( $value !== $expected ) {
				++$wrong;
			} else {
				++$right;
				unset( $plan[ $post_id ] );
			}
		}

		WP_CLI::log(
			sprintf(
				'Записей с ценой: %d. Нет price_base: %d, неверных: %d, дублей меты: %d, верных: %d',
				count( $stored ) + $missing,
				$missing,
				$wrong,
				$doubled,
				$right
			)
		);

		if ( empty( $plan ) ) {
			WP_CLI::success( 'Переписывать нечего' );

			return;
		}

		if ( $dry_run ) {
			WP_CLI::success( sprintf( 'Сухой прогон: переписать %d записей', count( $plan ) ) );

			return;
		}

		WP_CLI::confirm( sprintf( 'Переписать price_base у %d записей?', count( $plan ) ), $assoc_args );

		$progress = Utils\make_progress_bar( 'Цены в базовой валюте', count( $plan ) );
		$written  = 0;

		foreach ( array_chunk( $plan, 500, true ) as $chunk ) {
			$ids = implode( ',', array_map( 'intval', array_keys( $chunk ) ) );

			// Уникального индекса по (post_id, meta_key) нет, поэтому строка
			// сначала удаляется: иначе дубли меты только множатся.
			$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = 'price_base' AND post_id IN ({$ids})" );

			$values = array();

			foreach ( $chunk as $post_id => $expected ) {
				$values[] = $wpdb->prepare( '(%d, %s, %s)', $post_id, 'price_base', (string) $expected );
			}

			$wpdb->query( "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode( ',', $values ) );

			foreach ( array_keys( $chunk ) as $post_id ) {
				wp_cache_delete( $post_id, 'post_meta' );
			}

			$written += count( $chunk );
			$progress->tick( count( $chunk ) );
		}

		$progress->finish();

		WP_CLI::success( sprintf( 'Переписано %d записей', $written ) );
	}

	/**
	 * Give every location an explicit type.
	 *
	 * Nothing is inferred from the tree: a root term that is not a known
	 * country is reported rather than promoted, because a district saved
	 * without a parent used to become a country and open routes of its own.
	 *
	 * ## OPTIONS
	 *
	 * [--countries=<slugs>]
	 * : Root slugs to mark as countries.
	 * ---
	 * default: uae,saudi-arabia,oman
	 * ---
	 *
	 * [--dry-run]
	 * : Report the plan and write nothing.
	 *
	 * [--yes]
	 * : Skip the confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tools classify-locations --dry-run --allow-root
	 *     wp tools classify-locations --allow-root
	 *
	 * @param array $args
	 * @param array $assoc_args
	 *
	 * @return void
	 */
	public static function classify_locations( array $args, array $assoc_args ): void {
		$dry_run   = (bool) Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$countries = array_values(
			array_filter(
				array_map(
					static function ( string $slug ): string {
						return sanitize_title( trim( $slug ) );
					},
					explode( ',', (string) Utils\get_flag_value( $assoc_args, 'countries', 'uae,saudi-arabia,oman' ) )
				)
			)
		);

		$terms = get_terms(
			array(
				'taxonomy'   => 'location',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) ) {
			WP_CLI::error( $terms->get_error_message() );
		}

		$plan      = array();
		$ambiguous = array();
		$already   = 0;

		foreach ( $terms as $term ) {
			$term_id = (int) $term->term_id;
			$parent  = (int) $term->parent;
			$current = core_location_type( $term_id );

			if ( '' !== $current ) {
				++$already;

				continue;
			}

			$children = get_term_children( $term_id, 'location' );
			$children = is_wp_error( $children ) ? array() : $children;

			if ( 0 === $parent ) {
				if ( in_array( (string) $term->slug, $countries, true ) ) {
					$plan[ $term_id ] = 'country';

					continue;
				}

				$ambiguous[] = sprintf(
					'%s (#%d): корневой термин вне списка стран, детей %d',
					$term->name,
					$term_id,
					count( $children )
				);

				continue;
			}

			// Район из фида Geniemap: district_id проставляет только импортёр.
			if ( '' !== (string) get_term_meta( $term_id, 'district_id', true ) ) {
				$plan[ $term_id ] = 'area';

				continue;
			}

			if ( empty( $children ) ) {
				$plan[ $term_id ] = 'area';

				continue;
			}

			$ambiguous[] = sprintf(
				'%s (#%d): есть дети (%d), город или район — решить вручную',
				$term->name,
				$term_id,
				count( $children )
			);
		}

		$by_type = array_count_values( $plan );

		WP_CLI::log(
			sprintf(
				'Терминов %d. Уже с типом: %d. К простановке: %d (%s). Неоднозначных: %d',
				count( $terms ),
				$already,
				count( $plan ),
				implode( ', ', array_map( static function ( $type, $count ) {
					return $type . ' ' . $count;
				}, array_keys( $by_type ), $by_type ) ) ?: '-',
				count( $ambiguous )
			)
		);

		foreach ( $ambiguous as $line ) {
			WP_CLI::warning( $line );
		}

		if ( empty( $plan ) ) {
			WP_CLI::success( 'Проставлять нечего' );

			return;
		}

		if ( $dry_run ) {
			WP_CLI::success( sprintf( 'Сухой прогон: проставить тип %d терминам', count( $plan ) ) );

			return;
		}

		WP_CLI::confirm( sprintf( 'Проставить тип %d терминам?', count( $plan ) ), $assoc_args );

		$written = 0;

		foreach ( $plan as $term_id => $type ) {
			$term     = get_term( $term_id, 'location' );
			$parent   = $term instanceof \WP_Term ? (int) $term->parent : 0;
			$conflict = core_location_type_conflict( $type, $parent );

			if ( '' !== $conflict ) {
				WP_CLI::warning( sprintf( '#%d: %s', $term_id, $conflict ) );

				continue;
			}

			update_term_meta( $term_id, CORE_LOCATION_TYPE_META, $type );
			++$written;
		}

		core_location_flush_type_caches();

		WP_CLI::success( sprintf( 'Проставлено %d, неоднозначных %d', $written, count( $ambiguous ) ) );
	}

	/**
	 * Move the single URL switch onto a policy per country.
	 *
	 * The old option decided for the whole site at once. Current behaviour is
	 * carried over as is, so updating the code redirects nothing by itself;
	 * a country is moved to the other shape afterwards, one at a time.
	 *
	 * ## OPTIONS
	 *
	 * [--set=<pairs>]
	 * : Comma-separated country:mode overrides, for example uae:none,oman:prefix.
	 *
	 * [--dry-run]
	 * : Report the plan and write nothing.
	 *
	 * [--yes]
	 * : Skip the confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tools migrate-url-policy --dry-run --allow-root
	 *     wp tools migrate-url-policy --allow-root
	 *     wp tools migrate-url-policy --set=uae:none,oman:prefix --yes --allow-root
	 *
	 * @param array $args
	 * @param array $assoc_args
	 *
	 * @return void
	 */
	public static function migrate_url_policy( array $args, array $assoc_args ): void {
		$dry_run  = (bool) Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$legacy   = (string) get_option( 'core_country_urls', 'legacy' );
		$default  = core_default_country();
		$override = array();

		foreach ( explode( ',', (string) Utils\get_flag_value( $assoc_args, 'set', '' ) ) as $pair ) {
			$parts = array_map( 'trim', explode( ':', $pair ) );

			if ( 2 !== count( $parts ) || ! in_array( $parts[1], array( 'none', 'prefix' ), true ) ) {
				continue;
			}

			$override[ sanitize_title( $parts[0] ) ] = $parts[1];
		}

		$plan = array();

		foreach ( core_country_slugs() as $slug ) {
			$term = get_term_by( 'slug', $slug, 'location' );

			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$stored = (string) get_term_meta( $term->term_id, CORE_COUNTRY_URL_MODE_META, true );

			// Прежний глобальный режим переносится как есть: обновление кода
			// само по себе не должно никого редиректить.
			$wanted = $override[ $slug ]
				?? ( '' !== $stored ? $stored : ( 'country' === $legacy ? 'prefix' : ( $slug === $default ? 'none' : 'prefix' ) ) );

			WP_CLI::log( sprintf( '  %-16s %s -> %s', $slug, $stored ?: '(не задан)', $wanted ) );

			if ( $stored !== $wanted ) {
				$plan[ (int) $term->term_id ] = $wanted;
			}
		}

		WP_CLI::log( sprintf( 'Прежний глобальный переключатель: %s (больше не читается)', $legacy ) );

		if ( empty( $plan ) ) {
			WP_CLI::success( 'Политика уже соответствует' );

			return;
		}

		if ( $dry_run ) {
			WP_CLI::success( sprintf( 'Сухой прогон: изменить политику у %d стран', count( $plan ) ) );

			return;
		}

		WP_CLI::confirm( sprintf( 'Изменить политику адресов у %d стран?', count( $plan ) ), $assoc_args );

		foreach ( $plan as $term_id => $mode ) {
			update_term_meta( $term_id, CORE_COUNTRY_URL_MODE_META, $mode );
		}

		core_location_flush_type_caches();
		flush_rewrite_rules();

		WP_CLI::success( sprintf( 'Изменено %d', count( $plan ) ) );
	}
}
