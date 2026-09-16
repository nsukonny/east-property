<?php

/**
 * Load template for component.
 *
 * If fine not exists in theme/template-parts, we load it from theme/core/template-parts
 *
 * @param string $template_path The slug name for the generic template.
 * @param array $args The arguments to pass to the template.
 *
 * @return void
 */
function get_component_template( string $template_path, array $args = array() ): void {
	static $resolved = array();

	if ( ! isset( $resolved[ $template_path ] ) ) {
		$candidate = 'template-parts/' . $template_path;
		if ( ! locate_template( $candidate . '.php' ) ) {
			$candidate = 'core/' . $candidate;
			if ( ! locate_template( $candidate . '.php' ) ) {
				$candidate = '';
			}
		}

		$resolved[ $template_path ] = $candidate;
	}

	if ( '' === $resolved[ $template_path ] ) {
		return;
	}

	get_template_part(
		$resolved[ $template_path ],
		null,
		$args
	);
}

/**
 * Reset transient on request reset cache in W3 Total cache
 */
function ep_flush_all_transients(): void {
	global $wpdb;

	$wpdb->query(
		"DELETE FROM {$wpdb->options}
		WHERE option_name LIKE '\_transient\_%'
		   OR option_name LIKE '\_transient\_timeout\_%'
		   OR option_name LIKE '\_site\_transient\_%'
		   OR option_name LIKE '\_site\_transient\_timeout\_%'"
	);

	wp_cache_flush();
}

add_action( 'w3tc_flush_all', 'ep_flush_all_transients', 9999 );

/**
 * Disable wp-admin access for any instead administrator
 */
function restrict_broker_admin_access(): void {
	if ( ! is_user_logged_in() ) {
		return;
	}

	if ( wp_doing_ajax() ) {
		return;
	}

	$user = wp_get_current_user();
	if ( empty( $user->roles ) || ! in_array( 'administrator', (array) $user->roles, true ) ) {
		wp_safe_redirect( core_get_account_page_url() );
		exit;
	}
}

add_action( 'admin_init', 'restrict_broker_admin_access' );

function restrict_wp_login(): void {
	if ( str_contains( $_SERVER['REQUEST_URI'] ?? '', 'wp-login.php' ) ) {
		wp_safe_redirect( core_get_account_page_url() );
		exit;
	}
}

add_action( 'init', 'restrict_wp_login' );

$current_user = wp_get_current_user();
if ( ! $current_user || ! in_array( 'administrator', (array) $current_user->roles, true ) ) {
	add_filter( 'show_admin_bar', '__return_false' );
}

/**
 * Wrapper for home_url. Support polylang.
 *
 * @param string $path
 *
 * @return string
 */
function core_home_url( string $path = '' ): string {
	if ( function_exists( 'pll_home_url' ) ) {
		$path = ltrim( $path, '/' );

		return pll_home_url() . $path;
	}

	return home_url( $path );
}

/**
 * Keep the account area out of every shared cache.
 *
 * The account template serves personal broker data to signed-in visitors and a
 * form carrying a fresh nonce to everyone else, so a copy held by a proxy, a CDN
 * or another visitor's browser is either a leak or an expired nonce that breaks
 * logging in. Matching on the resolved template rather than on a slug keeps this
 * true for every translation of the page.
 *
 * @param string $template Template chosen by the template loader.
 *
 * @return string
 */
function core_never_cache_account( string $template ): string {
	if ( 'page-account.php' !== basename( $template ) ) {
		return $template;
	}

	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}

	nocache_headers();

	return $template;
}

add_filter( 'template_include', 'core_never_cache_account' );

/**
 * Run a callback once the response has been sent.
 *
 * Under PHP-FPM the connection is closed first with fastcgi_finish_request(), so
 * the visitor does not wait for the work. Without it (Apache mod_php on the local
 * stand) the callback simply runs at the end of the request.
 *
 * @param callable $callback Work to run.
 *
 * @return void
 */
function core_after_response( callable $callback ): void {
	static $queue = null;

	if ( null === $queue ) {
		$queue = array();

		add_action(
			'shutdown',
			static function () use ( &$queue ) {
				if ( empty( $queue ) ) {
					return;
				}

				if ( function_exists( 'fastcgi_finish_request' ) ) {
					fastcgi_finish_request();
				}

				$jobs  = $queue;
				$queue = array();

				foreach ( $jobs as $job ) {
					$job();
				}
			},
			PHP_INT_MAX
		);
	}

	$queue[] = $callback;
}

/**
 * A cached value that is refreshed without making a visitor wait for it.
 *
 * Replaces the get_transient() / set_transient() pairs with the same keys and
 * lifetimes. A stored value is out of date when its lifetime has run out or when
 * the catalogue changed after it was built (core_cache_mark_stale()). Either
 * way it is still returned, and the new one is built after the response and
 * stored for the next request, instead of a visitor waiting for the rebuild.
 * With nothing stored at all the value is built on the spot, as before.
 *
 * Options mirror the checks each call site used to make:
 *  - respect_dev: do not read in dev mode (the "! IS_DEV ?" checks);
 *  - keep_empty:  an empty stored value is a hit (the "false !==" checks) rather
 *                 than a miss (the "! empty()" checks);
 *  - keep_expired: true by default - the value is kept for a second lifetime so
 *                 it can be served while it rebuilds. Pass false for keys built
 *                 from request parameters (listings, maps): there is one per URL
 *                 crawled, so a second lifetime doubles how many sit in memory.
 *                 An expired value is then gone and built on the spot; a value
 *                 outdated by a catalogue change is still served and rebuilt
 *                 after the response.
 *
 * @param string $key Transient key.
 * @param callable $build Builds the value.
 * @param int $ttl Lifetime in seconds.
 * @param array $options See above.
 * @param bool|null $hit Set to true when the value came from storage.
 *
 * @return mixed
 */
function core_cache_remember( string $key, callable $build, int $ttl, array $options = array(), ?bool &$hit = null ) {
	$hit   = false;
	$entry = ( ! empty( $options['respect_dev'] ) && IS_DEV ) ? false : get_transient( $key );

	// A warm-up request rebuilds whatever is out of date before it answers.
	if ( core_cache_warming() && is_array( $entry ) && core_cache_is_outdated( $entry ) ) {
		$entry = false;
	}

	$keep_expired = $options['keep_expired'] ?? true;

	// Kept for one lifetime only: past it the value is gone, even if still stored.
	if ( ! $keep_expired && is_array( $entry ) && (int) ( $entry['expires'] ?? 0 ) <= time() ) {
		$entry = false;
	}

	if (
		is_array( $entry )
		&& isset( $entry['core_cache'], $entry['expires'] )
		&& array_key_exists( 'value', $entry )
		&& ( ! empty( $options['keep_empty'] ) || ! empty( $entry['value'] ) )
	) {
		$hit = true;

		/*
		 * Out of date because the catalogue changed and a warm-up is already
		 * queued: that run rebuilds it, so this visitor only gets the stored
		 * value. Otherwise the rebuild happens after this response.
		 */
		$changed = ( $entry['generation'] ?? '' ) !== core_cache_generation();

		if ( core_cache_is_outdated( $entry ) && ! ( $changed && wp_next_scheduled( 'core_cache_warm' ) ) ) {
			core_after_response(
				static function () use ( $key, $build, $ttl, $keep_expired ) {
					core_cache_rebuild( $key, $build, $ttl, $keep_expired );
				}
			);
		}

		return $entry['value'];
	}

	$generation = core_cache_generation();
	$value      = $build();
	core_cache_store( $key, $value, $ttl, $generation, $keep_expired );

	return $value;
}

/**
 * Store a value for core_cache_remember().
 *
 * The transient itself lives for two lifetimes, so an expired value is still
 * there to be served while the next one is built - unless $keep_expired is
 * false, see core_cache_remember().
 *
 * @param string $key Transient key.
 * @param mixed $value Value.
 * @param int $ttl Lifetime in seconds.
 * @param string $generation Catalogue generation the value was built from.
 * @param bool $keep_expired Keep the value for a second lifetime.
 *
 * @return void
 */
function core_cache_store( string $key, $value, int $ttl, string $generation, bool $keep_expired = true ): void {
	set_transient(
		$key,
		array(
			'core_cache' => 1,
			'expires'    => time() + $ttl,
			'generation' => $generation,
			'value'      => $value,
		),
		$keep_expired ? 2 * $ttl : $ttl
	);
}

/**
 * Rebuild one expired value, at most once at a time per key.
 *
 * Concurrent requests that find the same expired value keep serving it rather
 * than all rebuilding it together.
 *
 * @param string $key Transient key.
 * @param callable $build Builds the value.
 * @param int $ttl Lifetime in seconds.
 * @param bool $keep_expired Keep the value for a second lifetime.
 *
 * @return void
 */
function core_cache_rebuild( string $key, callable $build, int $ttl, bool $keep_expired = true ): void {
	$lock     = 'core_cache_lock_' . md5( $key );
	$external = wp_using_ext_object_cache();

	if ( $external ) {
		if ( ! wp_cache_add( $lock, 1, 'core_cache', 5 * MINUTE_IN_SECONDS ) ) {
			return;
		}
	} elseif ( ! add_option( $lock, time(), '', false ) ) {
		if ( (int) get_option( $lock ) > time() - 5 * MINUTE_IN_SECONDS ) {
			return;
		}

		update_option( $lock, time(), false );
	}

	try {
		// Read before building: a change that lands mid-build leaves this value
		// marked with the older generation, so it is rebuilt once more.
		$generation = core_cache_generation();
		core_cache_store( $key, $build(), $ttl, $generation, $keep_expired );
	} finally {
		if ( $external ) {
			wp_cache_delete( $lock, 'core_cache' );
		} else {
			delete_option( $lock );
		}
	}
}

/**
 * SQL for a delivery_date meta value in one comparable form, Ymd.
 *
 * The date picker and the account form store 20270630, the Geniemap feed stored
 * 2027-06-30 as it came (4 117 of 5 047 projects on the 2026-09-13 dump). As
 * strings the two do not compare: '2027-06-30' sorts below '20270101', so the
 * year filters put more than a thousand projects on the wrong side. Dropping
 * the dashes makes both one format; compare against Ymd values.
 *
 * @param string $alias Alias of the postmeta join holding delivery_date.
 *
 * @return string
 */
function core_sql_delivery_date( string $alias ): string {
	return "REPLACE( {$alias}.meta_value, '-', '' )";
}

/**
 * Load posts, their terms and their meta for many ids in a few queries.
 *
 * Nothing is read differently afterwards: the same functions return the same
 * values, they just find them in the object cache instead of asking the
 * database one post at a time.
 *
 * @param int[] $ids Post ids.
 *
 * @return void
 */
function core_prime_posts( array $ids ): void {
	$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );

	if ( empty( $ids ) ) {
		return;
	}

	_prime_post_caches( $ids, true, true );

	// Posts that were already cached are skipped above; their meta may not be.
	update_meta_cache( 'post', $ids );
}

/**
 * Warm the object cache for everything a page of listing cards reads.
 *
 * A unit card reads the unit, its project and the project's developer, and a
 * project card reads the project and its developer. Each of those used to load
 * its own post, terms and meta on first use - about seventy queries for twenty
 * cards. Loading them level by level takes a handful.
 *
 * @param array $entities Unit or Property entities.
 *
 * @return void
 */
function core_prime_listing( array $entities ): void {
	$ids = array();
	foreach ( $entities as $entity ) {
		if ( is_object( $entity ) && method_exists( $entity, 'get_id' ) ) {
			$ids[] = (int) $entity->get_id();
		}
	}

	if ( empty( $ids ) ) {
		return;
	}

	core_prime_posts( $ids );

	// The projects the units belong to; for projects this finds nothing.
	$projects = array();
	foreach ( $ids as $id ) {
		$projects[] = (int) get_post_meta( $id, 'property', true );
	}
	core_prime_posts( $projects );

	// The developers of the listed projects and of the units' projects.
	$developers = array();
	foreach ( array_merge( $ids, $projects ) as $id ) {
		if ( $id > 0 ) {
			$developers[] = (int) get_post_meta( $id, 'developer_rel', true );
		}
	}
	core_prime_posts( $developers );
}

/**
 * First stored value of one meta key for many posts, in one query.
 *
 * The first row by meta_id, which is the row get_post_meta( $id, $key, true )
 * returns.
 *
 * @param int[] $post_ids Post ids.
 * @param string $meta_key Meta key.
 *
 * @return array<int, string> Post id => value; posts without the key are absent.
 */
function core_first_meta_values( array $post_ids, string $meta_key ): array {
	global $wpdb;

	$post_ids = array_values( array_unique( array_filter( array_map( 'intval', $post_ids ) ) ) );

	if ( empty( $post_ids ) ) {
		return array();
	}

	$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
	$rows         = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT post_id, meta_value FROM {$wpdb->postmeta}
				WHERE meta_key = %s AND post_id IN ($placeholders)
				ORDER BY meta_id ASC",
			array_merge( array( $meta_key ), $post_ids )
		)
	);

	$values = array();
	foreach ( $rows as $row ) {
		$post_id = (int) $row->post_id;
		if ( ! isset( $values[ $post_id ] ) ) {
			$values[ $post_id ] = (string) $row->meta_value;
		}
	}

	return $values;
}

/**
 * The current catalogue generation.
 *
 * Changes whenever a project, unit, developer or location changes; cached values
 * built from an older generation are served once more and rebuilt.
 *
 * @return string
 */
function core_cache_generation(): string {
	return (string) get_option( 'core_cache_generation', '' );
}

/**
 * Flag every cached catalogue value as out of date.
 *
 * The flag is set once, at the end of the request, however many posts or fields
 * the request changed - an import or a batch approval moves it one time.
 *
 * @return void
 */
function core_cache_mark_stale(): void {
	static $queued = false;

	if ( $queued ) {
		return;
	}

	$queued = true;

	add_action(
		'shutdown',
		static function () {
			update_option( 'core_cache_generation', uniqid( '', true ), true );

			// One warm-up a minute at most: every change until it runs rides along.
			if ( ! wp_next_scheduled( 'core_cache_warm' ) ) {
				wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'core_cache_warm' );
			}
		},
		0
	);
}

/**
 * Post types whose changes show up in cached listings, filters and the map.
 *
 * @return string[]
 */
function core_cache_watched_post_types(): array {
	return array( 'property', 'unit', 'developers' );
}

add_action(
	'save_post',
	static function ( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( $post instanceof WP_Post && in_array( $post->post_type, core_cache_watched_post_types(), true ) ) {
			core_cache_mark_stale();
		}
	},
	10,
	2
);

add_action(
	'deleted_post',
	static function ( $post_id, $post = null ) {
		if ( $post instanceof WP_Post && in_array( $post->post_type, core_cache_watched_post_types(), true ) ) {
			core_cache_mark_stale();
		}
	},
	10,
	2
);

/*
 * Fields are often written after save_post has fired - update_field() calls in
 * the account forms and the importers - so field changes flag the cache too.
 * Keys starting with an underscore are ACF references and WordPress internals
 * that accompany a real field, and the units counter is written by the cache's
 * own readers; neither says the catalogue changed.
 */
foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $core_cache_meta_hook ) {
	add_action(
		$core_cache_meta_hook,
		static function ( $meta_id, $object_id, $meta_key ) {
			if ( '' === (string) $meta_key || '_' === $meta_key[0] || in_array( $meta_key,
					array( 'units_count', 'units_count_expired' ),
					true ) ) {
				return;
			}

			if ( in_array( get_post_type( (int) $object_id ), core_cache_watched_post_types(), true ) ) {
				core_cache_mark_stale();
			}
		},
		10,
		3
	);
}
unset( $core_cache_meta_hook );

add_action(
	'set_object_terms',
	static function ( $object_id ) {
		if ( in_array( get_post_type( (int) $object_id ), core_cache_watched_post_types(), true ) ) {
			core_cache_mark_stale();
		}
	}
);

add_action(
	'edited_term',
	static function ( $term_id, $tt_id, $taxonomy ) {
		if ( 'location' === $taxonomy ) {
			core_cache_mark_stale();
		}
	},
	10,
	3
);

/**
 * Whether a stored cache entry is past its lifetime or its catalogue generation.
 *
 * @param array $entry Entry as stored by core_cache_store().
 *
 * @return bool
 */
function core_cache_is_outdated( array $entry ): bool {
	return (int) ( $entry['expires'] ?? 0 ) <= time()
	       || ( $entry['generation'] ?? '' ) !== core_cache_generation();
}

/*
 * The warm-up token is taken out of the request before anything reads it: the
 * listing caches are keyed by the request's parameters, and a warm-up has to
 * fill the same keys a visitor uses.
 */
if ( isset( $_GET['core_cache_warm'] ) ) {
	$GLOBALS['core_cache_warm_token'] = (string) wp_unslash( $_GET['core_cache_warm'] );
	unset( $_GET['core_cache_warm'], $_REQUEST['core_cache_warm'] );
}

/**
 * Whether this request is a warm-up sent by core_cache_warm.
 *
 * @return bool
 */
function core_cache_warming(): bool {
	static $warming = null;

	if ( null === $warming ) {
		$token   = (string) ( $GLOBALS['core_cache_warm_token'] ?? '' );
		$warming = '' !== $token && hash_equals( wp_hash( 'core_cache_warm' ), $token );
	}

	return $warming;
}

/**
 * Pages a warm-up requests: the homepage and the main listings in every language.
 *
 * @return string[]
 */
function core_cache_warm_urls(): array {
	$languages = function_exists( 'pll_languages_list' ) ? (array) pll_languages_list() : array( '' );
	$urls      = array();

	foreach ( $languages as $language ) {
		$urls[] = ( '' !== $language && function_exists( 'pll_home_url' ) ) ? pll_home_url( $language ) : home_url( '/' );

		foreach ( array( 'projects', 'off-plan', 'secondary', 'distress' ) as $path ) {
			$page = get_page_by_path( $path );

			if ( ! $page ) {
				continue;
			}

			$page_id = ( '' !== $language && function_exists( 'pll_get_post' ) ) ? (int) pll_get_post( $page->ID,
				$language ) : (int) $page->ID;

			if ( $page_id > 0 ) {
				$urls[] = get_permalink( $page_id );
			}
		}
	}

	return array_values( array_unique( array_filter( (array) apply_filters( 'core_cache_warm_urls', $urls ) ) ) );
}

/*
 * The warm-up itself: one pass over the key pages right after a batch of
 * changes, each request rebuilding what it reads before it answers. Visitors
 * keep getting the previous data until then.
 */
add_action(
	'core_cache_warm',
	static function () {
		$token = wp_hash( 'core_cache_warm' );

		foreach ( core_cache_warm_urls() as $url ) {
			wp_remote_get(
				add_query_arg( 'core_cache_warm', $token, $url ),
				array(
					'timeout'     => 60,
					'redirection' => 0,
					'sslverify'   => false,
					'headers'     => array( 'Cache-Control' => 'no-cache' ),
				)
			);
		}
	}
);
