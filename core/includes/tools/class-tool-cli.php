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
		WP_CLI::add_command( 'tools warm-cache', array( __CLASS__, 'warm_cache' ) );
		WP_CLI::add_command( 'tools strip-unit-floors', array( __CLASS__, 'strip_unit_floors' ) );
		WP_CLI::add_command( 'tools translate-units', array( __CLASS__, 'translate_units' ) );
		WP_CLI::add_command( 'tools translate-locations', array( __CLASS__, 'translate_locations' ) );
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
	 * Fill units and projects flagged need_translate with DeepL output.
	 *
	 * The flag sits on posts an importer created by copying the other language,
	 * so the target is the post's own Polylang language. Posts already in the
	 * default language are skipped, flag untouched: their text is the source.
	 * Nothing is written until every field of a post came back translated.
	 *
	 * ## OPTIONS
	 *
	 * [--post-type=<types>]
	 * : Comma-separated types to walk.
	 * ---
	 * default: unit,property
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
	 * : Only units in this Polylang language.
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

		// Названный юнит переводим независимо от флага.
		$force = $force || $post_id > 0;

		if ( ! function_exists( 'pll_get_post_language' ) ) {
			WP_CLI::error( 'Polylang не активен — язык поста определить нечем' );
		}

		$translator = new Unit_Translator();

		if ( ! $dry_run && ! $translator->ready() ) {
			WP_CLI::error( 'DEEPL_API_KEY не задан в .env — переводить нечем' );
		}

		$ids = $post_id > 0
			? array( $post_id )
			: $translator->find( $limit, $offset, $language, $types );

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

			$plan = $translator->plan( (int) $id, $force );

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

			$saved = $translator->translate( (int) $id, $force );

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
}
