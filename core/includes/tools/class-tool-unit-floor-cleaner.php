<?php
/**
 * Tool for removing the floor from unit titles and slugs.
 *
 * @package east-property
 */

namespace Tools;

/**
 * Rewrites "Unit 1706 on 17 floor" to "Unit 1706" and the slug to match.
 *
 * Every old URL keeps answering with a 301: the old slug goes to _wp_old_slug,
 * which wp_old_slug_redirect() reads and core_old_slug_redirect_language() keeps
 * in the requested language. WordPress records it for published units by itself,
 * the tool adds it for the other statuses that have a public page (archived).
 */
final class Unit_Floor_Cleaner {

	/**
	 * Floor level spelled out: "high", "mid/high", "ground".
	 */
	private const LEVEL = '(?:ground|mezzanine|podium|mid|middle|low|lower|high|higher|top|upper)(?:\s*/\s*(?:mid|middle|low|high|top))?';

	/**
	 * What may stand between the preposition and "floor": "17", "4th", "P2", "G", a level.
	 */
	private const TOKEN = '(?:\d+(?:st|nd|rd|th)?|[a-z]{1,2}\d{1,2}|g|gf|lg|ug|m|' . self::LEVEL . ')';

	/**
	 * Floor phrases in English and Russian titles.
	 */
	private const PHRASE = '(?:(?:on|in|at|в|на)\s+(?:the\s+|a\s+)?(?:' . self::TOKEN . '[\s-]*)?floors?'
		. '|' . self::LEVEL . '[\s-]+floors?'
		. '|(?:на\s+)?(?:высок|средн|низк|верхн|последн)\p{L}*\s+этаж\p{L}*'
		. '|(?:на\s+)?\d+(?:-?\p{L}{1,2})?\s+этаж\p{L}*)';

	/**
	 * Separators around a phrase: comma, dashes, pipe.
	 */
	private const SEPARATOR = '\s*[,—–|]\s*|\s+-\s+';

	/**
	 * Floor phrase in a title together with its surroundings.
	 */
	private const TITLE_PATTERN = '~(?<lead>^|' . self::SEPARATOR . '|\s+)(?<phrase>' . self::PHRASE . ')(?![\p{L}\d])(?!-\p{L})(?<trail>' . self::SEPARATOR . '|\s+|$)~iu';

	/**
	 * Floor level inside a slug.
	 */
	private const SLUG_LEVEL = '(?:ground|mezzanine|podium|mid|middle|low|lower|high|higher|top|upper)(?:-(?:mid|middle|low|high|top))?';

	/**
	 * Floor segment of a slug, with the numeric suffix WordPress appended after it.
	 */
	private const SLUG_PATTERN = '~(?:^|-)(?:(?:on|in|at)-(?:the-|a-)?(?:(?:\d+(?:st|nd|rd|th)?|[a-z]{1,2}\d{1,2}|g|gf|lg|ug|m|' . self::SLUG_LEVEL . ')-)?floors?|' . self::SLUG_LEVEL . '-floors?)(?:-\d{1,2}$)?(?=-|$)~';

	/**
	 * Statuses whose slug WordPress keeps unique.
	 */
	private const UNIQUE_SLUG_STATUSES = array( 'publish', 'private', 'future', 'archived' );

	/**
	 * Removed title fragments, normalised, with counts.
	 *
	 * @var array<string, int>
	 */
	private array $title_fragments = array();

	/**
	 * Removed slug fragments, normalised, with counts.
	 *
	 * @var array<string, int>
	 */
	private array $slug_fragments = array();

	/**
	 * Old slug => units that answer it with a 301.
	 *
	 * @var array<string, int[]>
	 */
	private array $old_slugs = array();

	/**
	 * Slugs handed out during this run, by language.
	 *
	 * @var array<string, array<string, bool>>
	 */
	private array $claimed = array();

	/**
	 * Remove floor phrases from a title.
	 *
	 * @param string $title Title as stored.
	 *
	 * @return string The same string when it has no floor phrase.
	 */
	public function strip_title( string $title ): string {
		$result = $title;

		for ( $pass = 0; $pass < 5; $pass++ ) {
			$next = preg_replace_callback( self::TITLE_PATTERN, array( $this, 'replace_title_phrase' ), $result );

			if ( null === $next || $next === $result ) {
				break;
			}

			$result = $next;
		}

		if ( $result === $title ) {
			return $title;
		}

		$result = (string) preg_replace( '/\s{2,}/u', ' ', $result );

		return (string) preg_replace( '/^[\s,—–|-]+|[\s,—–|-]+$/u', '', $result );
	}

	/**
	 * Remove the floor segment from a slug.
	 *
	 * @param string $slug Slug as stored.
	 *
	 * @return string The same string when it has no floor segment.
	 */
	public function strip_slug( string $slug ): string {
		$result = preg_replace_callback(
			self::SLUG_PATTERN,
			function ( array $match ): string {
				$this->count( $this->slug_fragments, ltrim( $match[0], '-' ) );

				return '';
			},
			$slug
		);

		if ( null === $result || $result === $slug ) {
			return $slug;
		}

		$result = trim( (string) preg_replace( '/-{2,}/', '-', $result ), '-' );

		return '' === $result ? $slug : $result;
	}

	/**
	 * Find the units to rewrite and settle their new titles and slugs.
	 *
	 * Default-language units go first, each followed by its translations, so a
	 * translation that shared the old slug gets the same new one.
	 *
	 * @param int $limit Stop after this many units, zero for all.
	 *
	 * @return array{items: array, checked: int, fragments: array, slug_fragments: array}
	 */
	public function plan( int $limit = 0 ): array {
		$this->title_fragments = array();
		$this->slug_fragments  = array();
		$this->claimed         = array();
		$this->old_slugs       = $this->load_old_slugs();

		$default = function_exists( 'pll_default_language' ) ? (string) pll_default_language( 'slug' ) : '';
		$rows    = $this->query_candidates();
		$items   = array();

		foreach ( $rows as $row ) {
			$new_title = $this->strip_title( (string) $row->post_title );
			$new_slug  = $this->strip_slug( (string) $row->post_name );

			$items[ (int) $row->ID ] = array(
				'id'           => (int) $row->ID,
				'lang'         => function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( (int) $row->ID, 'slug' ) : '',
				'translations' => function_exists( 'pll_get_post_translations' ) ? array_map( 'intval', pll_get_post_translations( (int) $row->ID ) ) : array(),
				'status'       => (string) $row->post_status,
				'parent'       => (int) $row->post_parent,
				'old_title'    => (string) $row->post_title,
				'new_title'    => $new_title,
				'old_slug'     => (string) $row->post_name,
				'new_slug'     => $new_slug,
				'follows'      => 0,
				'suffixed'     => false,
			);
		}

		$items = array_filter(
			$items,
			static fn( array $item ): bool => $item['new_title'] !== $item['old_title'] || $item['new_slug'] !== $item['old_slug']
		);

		$ordered = $this->order_by_translation_group( $items, $default );

		if ( $limit > 0 ) {
			$ordered = array_slice( $ordered, 0, $limit );
		}

		$settled = array();

		foreach ( $ordered as $index => $item ) {
			if ( $item['new_slug'] === $item['old_slug'] ) {
				continue;
			}

			$source  = (int) ( $item['translations'][ $default ] ?? 0 );
			$desired = $item['new_slug'];

			if ( $source > 0 && $source !== $item['id'] && isset( $settled[ $source ] ) && $settled[ $source ]['old'] === $item['old_slug'] ) {
				$desired         = $settled[ $source ]['new'];
				$item['follows'] = $source;
			}

			$item['new_slug'] = $this->free_slug( $desired, $item );
			$item['suffixed'] = $item['new_slug'] !== $desired;

			$settled[ $item['id'] ]                               = array(
				'old' => $item['old_slug'],
				'new' => $item['new_slug'],
			);
			$this->claimed[ $item['lang'] ][ $item['new_slug'] ] = true;

			$ordered[ $index ] = $item;
		}

		arsort( $this->title_fragments );
		arsort( $this->slug_fragments );

		return array(
			'items'          => $ordered,
			'checked'        => count( $rows ),
			'fragments'      => $this->title_fragments,
			'slug_fragments' => $this->slug_fragments,
		);
	}

	/**
	 * Units that still mention a floor once the plan is applied.
	 *
	 * @param array $plan Result of plan().
	 *
	 * @return array[] id, title, slug.
	 */
	public function leftovers( array $plan ): array {
		$planned   = array_column( $plan['items'], null, 'id' );
		$leftovers = array();

		foreach ( $this->query_candidates() as $row ) {
			$title = $planned[ (int) $row->ID ]['new_title'] ?? (string) $row->post_title;
			$slug  = $planned[ (int) $row->ID ]['new_slug'] ?? (string) $row->post_name;

			if ( preg_match( '~floor|этаж~iu', $title . ' ' . $slug ) ) {
				$leftovers[] = array(
					'id'    => (int) $row->ID,
					'title' => $title,
					'slug'  => $slug,
				);
			}
		}

		return $leftovers;
	}

	/**
	 * Write the planned titles and slugs.
	 *
	 * Kses and the pll_save_post handlers are lifted for the run: otherwise
	 * wp_update_post() filters the content again and Polylang copies metas and
	 * dates onto the translations, dropping need_translate from them.
	 *
	 * @param array         $items       Items from plan().
	 * @param callable|null $on_progress Called after every unit.
	 *
	 * @return array Items with saved_title, saved_slug, redirect, url and error.
	 */
	public function apply( array $items, ?callable $on_progress = null ): array {
		global $wp_filter;

		$pll_save_post = $wp_filter['pll_save_post'] ?? null;
		unset( $wp_filter['pll_save_post'] );

		kses_remove_filters();

		foreach ( $items as $index => $item ) {
			$postarr = array(
				'ID'        => $item['id'],
				'edit_date' => true,
			);

			if ( $item['new_title'] !== $item['old_title'] ) {
				$postarr['post_title'] = $item['new_title'];
			}

			if ( $item['new_slug'] !== $item['old_slug'] ) {
				$postarr['post_name'] = $item['new_slug'];
			}

			$result = wp_update_post( wp_slash( $postarr ), true );

			if ( is_wp_error( $result ) ) {
				$items[ $index ]['error'] = $result->get_error_message();
			} else {
				$saved = get_post( $item['id'] );

				$items[ $index ]['saved_title'] = (string) $saved->post_title;
				$items[ $index ]['saved_slug']  = (string) $saved->post_name;
				$items[ $index ]['url']         = (string) get_permalink( $saved );
				$items[ $index ]['redirect']    = $this->keep_old_slug( $saved, $item['old_slug'] );
			}

			if ( null !== $on_progress ) {
				$on_progress();
			}
		}

		kses_init();

		if ( null !== $pll_save_post ) {
			$wp_filter['pll_save_post'] = $pll_save_post;
		}

		return $items;
	}

	/**
	 * Make sure the old URL of a unit with a public page answers with a 301.
	 *
	 * @param \WP_Post $saved    Unit as saved.
	 * @param string   $old_slug Slug before the run.
	 *
	 * @return bool|null Whether the old slug redirects, null when the unit has no public page or kept its slug.
	 */
	private function keep_old_slug( \WP_Post $saved, string $old_slug ): ?bool {
		if ( $saved->post_name === $old_slug || ! is_post_status_viewable( $saved->post_status ) ) {
			return null;
		}

		$old_slugs = get_post_meta( $saved->ID, '_wp_old_slug' );

		if ( ! in_array( $old_slug, $old_slugs, true ) && 'publish' !== $saved->post_status ) {
			add_post_meta( $saved->ID, '_wp_old_slug', $old_slug );
			$old_slugs[] = $old_slug;
		}

		return in_array( $old_slug, $old_slugs, true );
	}

	/**
	 * Decide what replaces one floor phrase, so the separators stay readable.
	 *
	 * @param array $match Named groups lead, phrase, trail.
	 *
	 * @return string
	 */
	private function replace_title_phrase( array $match ): string {
		$this->count(
			$this->title_fragments,
			trim( (string) preg_replace( '/\s+/u', ' ', mb_strtolower( trim( $match['lead'] ) . ' ' . $match['phrase'] . ' ' . trim( $match['trail'] ) ) ) )
		);

		if ( '' === $match['lead'] || '' === $match['trail'] ) {
			return '';
		}

		if ( '' !== trim( $match['lead'] ) ) {
			return $match['lead'];
		}

		if ( '' !== trim( $match['trail'] ) ) {
			return $match['trail'];
		}

		return ' ';
	}

	/**
	 * First free variant of a slug: not live in the language, not handed out in
	 * this run and not an old slug that already redirects to another unit.
	 *
	 * @param string $desired Slug without the floor.
	 * @param array  $item    Planned item.
	 *
	 * @return string
	 */
	private function free_slug( string $desired, array $item ): string {
		if ( ! in_array( $item['status'], self::UNIQUE_SLUG_STATUSES, true ) ) {
			return $desired;
		}

		$slug = $desired;

		for ( $suffix = 2; $suffix < 1000; $suffix++ ) {
			$taken = isset( $this->claimed[ $item['lang'] ][ $slug ] )
				|| wp_unique_post_slug( $slug, $item['id'], $item['status'], 'unit', $item['parent'] ) !== $slug
				|| $this->redirects_elsewhere( $slug, $item );

			if ( ! $taken ) {
				return $slug;
			}

			$slug = $desired . '-' . $suffix;
		}

		return $slug;
	}

	/**
	 * Whether a slug is an old slug of a unit outside this item's translation group.
	 *
	 * @param string $slug Candidate slug.
	 * @param array  $item Planned item.
	 *
	 * @return bool
	 */
	private function redirects_elsewhere( string $slug, array $item ): bool {
		if ( empty( $this->old_slugs[ $slug ] ) ) {
			return false;
		}

		$own = array_merge( array( $item['id'] ), array_values( $item['translations'] ) );

		return array() !== array_diff( $this->old_slugs[ $slug ], $own );
	}

	/**
	 * Put default-language units first, each followed by its translations.
	 *
	 * @param array  $items   Items keyed by id.
	 * @param string $default Default language slug.
	 *
	 * @return array[]
	 */
	private function order_by_translation_group( array $items, string $default ): array {
		$ordered = array();

		foreach ( $items as $id => $item ) {
			if ( isset( $ordered[ $id ] ) || ( '' !== $default && $item['lang'] !== $default ) ) {
				continue;
			}

			$ordered[ $id ] = $item;

			foreach ( $item['translations'] as $translation ) {
				if ( isset( $items[ $translation ] ) && ! isset( $ordered[ $translation ] ) ) {
					$ordered[ $translation ] = $items[ $translation ];
				}
			}
		}

		return array_values( $ordered + $items );
	}

	/**
	 * Units whose title or slug mentions a floor.
	 *
	 * @return object[] ID, post_title, post_name, post_status, post_parent.
	 */
	private function query_candidates(): array {
		global $wpdb;

		return (array) $wpdb->get_results(
			"SELECT ID, post_title, post_name, post_status, post_parent
			FROM {$wpdb->posts}
			WHERE post_type = 'unit'
				AND post_status NOT IN ( 'trash', 'auto-draft', 'inherit' )
				AND ( post_title LIKE '%floor%' OR post_title LIKE '%этаж%' OR post_name LIKE '%floor%' )
			ORDER BY ID"
		);
	}

	/**
	 * Old slugs of all units.
	 *
	 * @return array<string, int[]>
	 */
	private function load_old_slugs(): array {
		global $wpdb;

		$rows = (array) $wpdb->get_results(
			"SELECT pm.meta_value, pm.post_id
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = '_wp_old_slug'
				AND p.post_type = 'unit'"
		);

		$old_slugs = array();

		foreach ( $rows as $row ) {
			$old_slugs[ (string) $row->meta_value ][] = (int) $row->post_id;
		}

		return $old_slugs;
	}

	/**
	 * Count a removed fragment with its digits folded, so "on 17 floor" and "on 3 floor" add up.
	 *
	 * @param array  $counter  Counter to add to.
	 * @param string $fragment Removed text.
	 *
	 * @return void
	 */
	private function count( array &$counter, string $fragment ): void {
		$key = (string) preg_replace( '/\d+/', 'N', $fragment );

		$counter[ $key ] = ( $counter[ $key ] ?? 0 ) + 1;
	}
}
