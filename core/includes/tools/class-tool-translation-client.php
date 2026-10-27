<?php
/**
 * Shared base for the translation providers.
 *
 * @package east-property
 */

namespace Tools;

use WP_Error;

/**
 * Everything a translation provider does that is not provider specific:
 * the paid-for cache, the batching and the order of calls. A provider adds
 * request() and the language code it wants.
 */
abstract class Translation_Client {

	/**
	 * Name of the .env entry holding this provider's key. Providers override it.
	 */
	protected const ENV_KEY = '';

	/**
	 * Texts one request carries. Providers override it.
	 */
	protected const CHUNK = 50;

	/**
	 * WP_Error code for a missing key. Providers override it so a failure
	 * names the provider that is actually configured.
	 */
	protected const NO_KEY_CODE = 'translate_no_key';

	/**
	 * Option holding translations already paid for.
	 */
	protected const CACHE_OPTION = 'ep_deepl_cache';

	/**
	 * Entries the cache is allowed to hold.
	 */
	protected const CACHE_LIMIT = 50000;

	/**
	 * Element standing in for an HTML comment while the API works.
	 */
	protected const MARKER_TAG = 'x-deepl-c';

	/**
	 * Below this length the ratio of a translation to its source means nothing.
	 *
	 * A label legitimately grows several times over: «DLD» spelled out in
	 * Arabic is ten times longer than the acronym. The check is there to catch
	 * a runaway or a truncated answer, and that only shows on real prose.
	 */
	private const RATIO_FROM = 40;

	/**
	 * Translations by target language and source text.
	 *
	 * @var array<string, string>|null
	 */
	private ?array $cache = null;

	/**
	 * Auth key, empty when the project has none configured.
	 *
	 * @var string
	 */
	protected string $key;

	/**
	 * @param string $key Overrides the key from the project .env.
	 */
	public function __construct( string $key = '' ) {
		$this->key = '' !== $key ? $key : self::env( static::ENV_KEY );
	}

	/**
	 * The configured provider.
	 *
	 * @param string $provider Overrides TRANSLATE_PROVIDER from the .env.
	 *
	 * @return self
	 */
	public static function make( string $provider = '' ): self {
		$provider = strtolower( '' !== $provider ? $provider : self::env( 'TRANSLATE_PROVIDER' ) );

		return 'deepl' === $provider ? new DeepL_Client() : new Gemini_Client();
	}

	/**
	 * Value from the project .env, empty when it is not set.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	protected static function env( string $name ): string {
		if ( function_exists( 'core_env' ) ) {
			return core_env( $name );
		}

		return (string) ( $_ENV[ $name ] ?? '' );
	}

	/**
	 * Whether a key is configured.
	 *
	 * @return bool
	 */
	public function has_key(): bool {
		return '' !== $this->key;
	}

	/**
	 * Translate several strings into one language.
	 *
	 * Strings already translated once are served from the option cache, and
	 * strings without a single letter are handed back untouched: the project
	 * data is full of values like "10%" repeated thousands of times.
	 *
	 * Only strings the caller marks reusable go through the cache. A title or a
	 * description belongs to one post and would never be asked for again, so
	 * keeping it would grow the stored option with every post translated and
	 * make every later write rewrite all of it.
	 *
	 * A chunk that came back before a later one failed is already paid for, so
	 * the cache is written on the way out of an error too.
	 *
	 * @param array  $texts    Strings keyed by caller; the keys come back on the result.
	 * @param string $target   Target language, in whatever form the provider takes.
	 * @param bool   $html     Whether the strings carry markup.
	 * @param bool   $reusable Whether these strings repeat across records.
	 * @param array  $options  Provider specific request fields.
	 *
	 * @return array|WP_Error Translations under the original keys.
	 */
	public function translate( array $texts, string $target, bool $html = false, bool $reusable = true, array $options = array() ) {
		$variant = $this->variant( $options );
		$result  = array();
		$pending = array();

		foreach ( $texts as $key => $text ) {
			$text = (string) $text;

			if ( '' === trim( $text ) ) {
				continue;
			}

			if ( ! preg_match( '~\p{L}~u', $text ) ) {
				$result[ $key ] = $text;
				continue;
			}

			$cached = $reusable ? $this->cached( $target, $text, $variant ) : null;

			if ( null !== $cached ) {
				$result[ $key ] = $cached;
				continue;
			}

			$pending[ $key ] = $text;
		}

		if ( empty( $pending ) ) {
			return $result;
		}

		if ( ! $this->has_key() ) {
			return new WP_Error( static::NO_KEY_CODE, static::ENV_KEY . ' не задан в .env' );
		}

		$added = false;

		foreach ( $this->chunks( $pending ) as $chunk ) {
			$answer = $this->request( $chunk, $target, $html, $options );

			if ( is_wp_error( $answer ) ) {
				if ( $added ) {
					$this->flush_cache();
				}

				return $answer;
			}

			foreach ( $answer as $key => $text ) {
				$result[ $key ] = $text;

				if ( $reusable ) {
					$this->remember( $target, $pending[ $key ], $text, $variant );
					$added = true;
				}
			}
		}

		if ( $added ) {
			$this->flush_cache();
		}

		return $result;
	}

	/**
	 * Split the pending strings into requests.
	 *
	 * @param array $pending
	 *
	 * @return array<int, array>
	 */
	protected function chunks( array $pending ): array {
		return array_chunk( $pending, static::CHUNK, true );
	}

	/**
	 * One call to the API.
	 *
	 * @param array  $texts   Strings keyed by caller.
	 * @param string $target  Target language.
	 * @param bool   $html    Whether the strings carry markup.
	 * @param array  $options Provider specific request fields.
	 *
	 * @return array|WP_Error
	 */
	abstract protected function request( array $texts, string $target, bool $html, array $options = array() );

	/**
	 * Provider language code for a Polylang slug, empty when there is none.
	 *
	 * @param string $slug Polylang language slug.
	 *
	 * @return string
	 */
	abstract public function target_for( string $slug ): string;

	/**
	 * Replace HTML comments with placeholder elements.
	 *
	 * DeepL rejects a string holding four comments or more — «text without
	 * parent» — and WordPress block markup carries two per block, so any post
	 * of two blocks would fail. A model, for its part, is glad to translate
	 * what is inside them. Either way the comments hold block attributes the
	 * editor parses, and must come back byte for byte.
	 *
	 * @param string $text
	 * @param array  $comments Filled with the comments taken out, by index.
	 *
	 * @return string
	 */
	protected static function mask_comments( string $text, ?array &$comments ): string {
		$comments = array();
		$index    = 0;

		return (string) preg_replace_callback(
			'~<!--[\s\S]*?-->~',
			static function ( array $match ) use ( &$comments, &$index ): string {
				$comments[ $index ] = $match[0];

				return sprintf( '<%1$s i="%2$d"></%1$s>', static::MARKER_TAG, $index++ );
			},
			$text
		);
	}

	/**
	 * Put the comments back where the placeholders stand.
	 *
	 * @param string $text
	 * @param array  $comments Comments taken out by mask_comments().
	 *
	 * @return string|null Null when a placeholder did not come back.
	 */
	protected static function unmask_comments( string $text, array $comments ): ?string {
		$seen    = array();
		$pattern = sprintf( '~<%1$s i="(\d+)"\s*></%1$s>~', static::MARKER_TAG );

		$text = (string) preg_replace_callback(
			$pattern,
			static function ( array $match ) use ( $comments, &$seen ): string {
				$index          = (int) $match[1];
				$seen[ $index ] = true;

				return (string) ( $comments[ $index ] ?? '' );
			},
			$text
		);

		return count( $seen ) === count( $comments ) ? $text : null;
	}

	/**
	 * Why a translation cannot be trusted, empty when it can.
	 *
	 * Catches the three ways a generated answer goes wrong without looking
	 * wrong: markup altered, printf placeholders dropped, and an answer that
	 * ran away or stopped short of the source.
	 *
	 * @param string $source
	 * @param string $translation
	 *
	 * @return string
	 */
	protected static function fault( string $source, string $translation ): string {
		if ( '' === trim( $translation ) ) {
			return 'пустой ответ';
		}

		$tags = static function ( string $text ): array {
			preg_match_all( '~<\s*/?\s*([a-z0-9-]+)~i', $text, $found );
			$list = array_map( 'strtolower', $found[1] );
			sort( $list );

			return $list;
		};

		if ( $tags( $source ) !== $tags( $translation ) ) {
			return 'набор тегов не совпал';
		}

		$holders = static function ( string $text ): array {
			preg_match_all( '~%(?:\d+\$)?[sd]|%%~', $text, $found );
			$list = $found[0];
			sort( $list );

			return $list;
		};

		if ( $holders( $source ) !== $holders( $translation ) ) {
			return 'плейсхолдеры не совпали';
		}

		$length = mb_strlen( $source );
		$ratio  = mb_strlen( $translation ) / max( 1, $length );

		if ( $length > self::RATIO_FROM && ( $ratio < 0.3 || $ratio > 3.5 ) ) {
			return sprintf( 'длина изменилась в %.1f раза', $ratio );
		}

		return '';
	}

	/**
	 * Translation paid for earlier, if any.
	 *
	 * @param string $target
	 * @param string $text
	 * @param string $variant
	 *
	 * @return string|null
	 */
	private function cached( string $target, string $text, string $variant = '' ): ?string {
		if ( null === $this->cache ) {
			$stored      = get_option( self::CACHE_OPTION, array() );
			$this->cache = is_array( $stored ) ? $stored : array();
		}

		$key = self::cache_key( $target, $text, $variant );

		return isset( $this->cache[ $key ] ) ? (string) $this->cache[ $key ] : null;
	}

	/**
	 * Keep a translation for the next run.
	 *
	 * @param string $target
	 * @param string $source
	 * @param string $translation
	 * @param string $variant
	 *
	 * @return void
	 */
	private function remember( string $target, string $source, string $translation, string $variant = '' ): void {
		if ( null === $this->cache || count( $this->cache ) >= self::CACHE_LIMIT ) {
			return;
		}

		$this->cache[ self::cache_key( $target, $source, $variant ) ] = $translation;
	}

	/**
	 * Write the cache back.
	 *
	 * @return void
	 */
	private function flush_cache(): void {
		if ( null !== $this->cache ) {
			update_option( self::CACHE_OPTION, $this->cache, false );
		}
	}

	/**
	 * Cache key of a source string in a language.
	 *
	 * @param string $target
	 * @param string $text
	 * @param string $variant
	 *
	 * @return string
	 */
	private static function cache_key( string $target, string $text, string $variant = '' ): string {
		return md5( $target . "\0" . ( '' === $variant ? '' : $variant . "\0" ) . $text );
	}

	/**
	 * Cache discriminator for a set of request options.
	 *
	 * The options change what comes back, so a call carrying them must not be
	 * served an answer stored by a call without them. A call with no options
	 * and no scope keeps the key it used before, and the entries paid for stay
	 * usable.
	 *
	 * @param array $options
	 *
	 * @return string
	 */
	protected function variant( array $options ): string {
		$options = array_filter( $options, static fn( $value ): bool => '' !== (string) $value );
		$scope   = $this->cache_scope();

		if ( '' !== $scope ) {
			$options['~scope'] = $scope;
		}

		if ( empty( $options ) ) {
			return '';
		}

		ksort( $options );

		return md5( (string) wp_json_encode( $options ) );
	}

	/**
	 * What separates this provider's cache entries from another's.
	 *
	 * @return string
	 */
	protected function cache_scope(): string {
		return '';
	}
}
