<?php
/**
 * Tool for warming the caches of the most visited pages.
 *
 * @package east-property
 */

namespace Tools;

use WpOrg\Requests\Requests;
use WpOrg\Requests\Response;

/**
 * Requests the pages visitors land on most, so their caches are built before visitors arrive.
 */
final class Cache_Warmer {

	/**
	 * User agent of the warm-up requests, which also keeps them out of the log counts.
	 */
	public const USER_AGENT = 'EastProperty-CacheWarmer/1.0';

	/**
	 * Path prefixes that are not pages a visitor lands on, checked without the language prefix.
	 */
	private const SKIP_PATHS = array( '/wp-admin', '/wp-login', '/wp-json', '/wp-content', '/wp-includes', '/xmlrpc.php', '/feed', '/account', '/cdn-cgi' );

	/**
	 * Query arguments that act rather than show a page.
	 */
	private const SKIP_QUERY = '~(^|&)(run_import|action|logout|_wpnonce|preview|replytocom)=~i';

	/**
	 * Extensions of files that are not pages.
	 */
	private const SKIP_EXTENSIONS = '~\.(pdf|jpe?g|png|gif|webp|avif|svg|ico|css|js|map|xml|txt|zip|mp4|woff2?)$~i';

	/**
	 * How much of the end of the access log is read.
	 */
	private const LOG_TAIL_BYTES = 20 * MB_IN_BYTES;

	/**
	 * Home page of every language, the most requested pages of the log, then what the home pages link to.
	 *
	 * @param int    $limit Maximum number of pages.
	 * @param string $log   Access log in the combined format, empty to skip it.
	 * @param int    $top   Pages to take from the log.
	 *
	 * @return array{urls: string[], from_log: int, warnings: string[]}
	 */
	public function collect( int $limit, string $log = '', int $top = 50 ): array {
		$warnings = array();
		$homes    = $this->home_urls();
		$logged   = $this->log_urls( $log, $top, $warnings );
		$urls     = array_merge( $homes, $logged );

		foreach ( $homes as $home ) {
			$urls = array_merge( $urls, $this->linked_urls( $home, $warnings ) );
		}

		return array(
			'urls'     => array_slice( array_values( array_unique( $urls ) ), 0, $limit ),
			'from_log' => count( $logged ),
			'warnings' => $warnings,
		);
	}

	/**
	 * Request the pages, a batch of `$concurrency` at a time.
	 *
	 * @param string[] $urls        Pages to request.
	 * @param int      $concurrency Requests in flight at once.
	 * @param callable $on_result   Receives the URL, the final status (0 on failure), the seconds taken and the error.
	 *
	 * @return void
	 */
	public function warm( array $urls, int $concurrency, callable $on_result ): void {
		foreach ( array_chunk( $urls, max( 1, $concurrency ) ) as $batch ) {
			$started  = microtime( true );
			$requests = array();

			foreach ( $batch as $url ) {
				$requests[ $url ] = array(
					'url'  => $url,
					'type' => Requests::GET,
				);
			}

			Requests::request_multiple(
				$requests,
				array(
					'useragent'       => self::USER_AGENT,
					'timeout'         => 90,
					'connect_timeout' => 10,
					'redirects'       => 5,
					'complete'        => static function ( $response, $url ) use ( $started, $on_result ): void {
						$seconds = microtime( true ) - $started;

						if ( $response instanceof Response ) {
							$on_result( (string) $url, (int) $response->status_code, $seconds, '' );

							return;
						}

						$on_result( (string) $url, 0, $seconds, $response instanceof \Throwable ? $response->getMessage() : 'no response' );
					},
				)
			);
		}
	}

	/**
	 * Home page of every language.
	 *
	 * @return string[]
	 */
	private function home_urls(): array {
		if ( ! function_exists( 'pll_languages_list' ) || ! function_exists( 'pll_home_url' ) ) {
			return array( home_url( '/' ) );
		}

		$urls = array();

		foreach ( (array) pll_languages_list() as $language ) {
			$urls[] = (string) pll_home_url( (string) $language );
		}

		return array() === $urls ? array( home_url( '/' ) ) : $urls;
	}

	/**
	 * Pages of this site a page links to.
	 *
	 * @param string   $page     Page to read.
	 * @param string[] $warnings Collected warnings.
	 *
	 * @return string[]
	 */
	private function linked_urls( string $page, array &$warnings ): array {
		$response = wp_remote_get(
			$page,
			array(
				'timeout'    => 90,
				'user-agent' => self::USER_AGENT,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$warnings[] = sprintf( 'не удалось прочитать ссылки со страницы %s', $page );

			return array();
		}

		preg_match_all( '~href=(["\'])(.*?)\1~i', (string) wp_remote_retrieve_body( $response ), $matches );

		return array_values( array_filter( array_map( array( $this, 'normalize' ), $matches[2] ) ) );
	}

	/**
	 * Most requested pages at the end of an access log in the combined format.
	 *
	 * @param string   $log      Log path, empty to skip it.
	 * @param int      $top      Pages to return.
	 * @param string[] $warnings Collected warnings.
	 *
	 * @return string[]
	 */
	private function log_urls( string $log, int $top, array &$warnings ): array {
		if ( '' === $log || 0 >= $top ) {
			return array();
		}

		$handle = is_readable( $log ) ? fopen( $log, 'rb' ) : false;

		if ( false === $handle ) {
			$warnings[] = sprintf( 'журнал %s не читается, беру только главные страницы и их ссылки', $log );

			return array();
		}

		$size = (int) filesize( $log );

		if ( $size > self::LOG_TAIL_BYTES ) {
			fseek( $handle, $size - self::LOG_TAIL_BYTES );
			fgets( $handle );
		}

		$counts = array();

		while ( false !== ( $line = fgets( $handle ) ) ) {
			if ( ! preg_match( '~"GET (\S+) HTTP/[\d.]+" 200 \d+ "[^"]*" "([^"]*)"~', $line, $match ) || str_starts_with( $match[2], self::USER_AGENT ) ) {
				continue;
			}

			$url = $this->normalize( $match[1] );

			if ( '' !== $url ) {
				$counts[ $url ] = ( $counts[ $url ] ?? 0 ) + 1;
			}
		}

		fclose( $handle );
		arsort( $counts );

		return array_slice( array_keys( $counts ), 0, $top );
	}

	/**
	 * Absolute URL of a page of this site, or an empty string for anything that is not a page to warm.
	 *
	 * @param string $href Link or requested path.
	 *
	 * @return string
	 */
	private function normalize( string $href ): string {
		$home = wp_parse_url( home_url( '/' ) );
		$href = html_entity_decode( trim( $href ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$root = ( $home['scheme'] ?? 'https' ) . '://' . ( $home['host'] ?? '' ) . ( isset( $home['port'] ) ? ':' . $home['port'] : '' );

		if ( str_starts_with( $href, '/' ) && ! str_starts_with( $href, '//' ) ) {
			$href = $root . $href;
		}

		$parts = wp_parse_url( $href );

		if ( empty( $parts['host'] ) || $parts['host'] !== ( $home['host'] ?? '' ) ) {
			return '';
		}

		$path  = $parts['path'] ?? '/';
		$query = $parts['query'] ?? '';
		$bare  = (string) preg_replace( '~^/[a-z]{2}(?=/)~', '', $path );

		foreach ( self::SKIP_PATHS as $prefix ) {
			if ( str_starts_with( $bare, $prefix ) ) {
				return '';
			}
		}

		if ( preg_match( self::SKIP_EXTENSIONS, $path ) || preg_match( self::SKIP_QUERY, $query ) ) {
			return '';
		}

		return $root . trailingslashit( $path ) . ( '' === $query ? '' : '?' . $query );
	}
}
