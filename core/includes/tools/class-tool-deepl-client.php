<?php
/**
 * DeepL API client.
 *
 * @package east-property
 */

namespace Tools;

use WP_Error;

/**
 * Translates batches of text through the DeepL v2 API.
 */
final class DeepL_Client {

	/**
	 * Seconds to wait for the API.
	 */
	private const TIMEOUT = 25;

	/**
	 * Texts the API accepts in one request.
	 */
	private const CHUNK = 50;

	/**
	 * Option holding translations already paid for.
	 */
	private const CACHE_OPTION = 'ep_deepl_cache';

	/**
	 * Entries the cache is allowed to hold.
	 */
	private const CACHE_LIMIT = 50000;

	/**
	 * Translations by target language and source text.
	 *
	 * @var array<string, string>|null
	 */
	private ?array $cache = null;

	/**
	 * Target codes the API accepts, read from /v2/languages on 08.10.2026.
	 */
	private const TARGETS = 'AF AN AR AS AY AZ BA BE BG BN BR BS CA CS CY DA DE DE-CH DE-DE EL EN-GB EN-US EO ES'
		. ' ES-419 ES-ES ET EU FA FI FR FR-CA FR-FR GA GL GN GU HA HE HI HR HT HU HY ID IG IS IT JA JV KA KK KO'
		. ' KY LA LB LN LT LV MG MI MK ML MN MR MS MT MY NB NE NL OC OM PA PL PS PT-BR PT-PT QU RO RU SA SK SL SQ'
		. ' SR ST SU SV SW TA TE TG TH TK TL TN TR TS TT UK UR UZ VI WO XH YI ZH ZH-HANS ZH-HANT ZU';

	/**
	 * Languages whose bare code the API does not accept as a target.
	 */
	private const TARGET_VARIANTS = array(
		'en' => 'EN-GB',
		'pt' => 'PT-PT',
	);

	/**
	 * Auth key, empty when the project has none configured.
	 *
	 * @var string
	 */
	private string $key;

	/**
	 * @param string $key Overrides DEEPL_API_KEY from the project .env.
	 */
	public function __construct( string $key = '' ) {
		$this->key = '' !== $key
			? $key
			: ( function_exists( 'core_env' ) ? core_env( 'DEEPL_API_KEY' ) : (string) ( $_ENV['DEEPL_API_KEY'] ?? '' ) );
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
	 * DeepL target language for a Polylang slug.
	 *
	 * @param string $slug Polylang language slug.
	 *
	 * @return string Empty when DeepL has no counterpart.
	 */
	public static function target_language( string $slug ): string {
		$slug = strtolower( $slug );

		if ( isset( self::TARGET_VARIANTS[ $slug ] ) ) {
			return self::TARGET_VARIANTS[ $slug ];
		}

		$code = strtoupper( $slug );

		return in_array( $code, explode( ' ', self::TARGETS ), true ) ? $code : '';
	}

	/**
	 * Translate several strings into one language.
	 *
	 * Strings already translated once are served from the option cache, and
	 * strings without a single letter are handed back untouched: the project
	 * data is full of values like "10%" repeated thousands of times.
	 *
	 * @param array  $texts  Strings keyed by caller; the keys come back on the result.
	 * @param string $target DeepL target language.
	 * @param bool   $html   Whether the strings carry markup.
	 *
	 * @return array|WP_Error Translations under the original keys.
	 */
	public function translate( array $texts, string $target, bool $html = false ) {
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

			$cached = $this->cached( $target, $text );

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
			return new WP_Error( 'deepl_no_key', 'DEEPL_API_KEY не задан в .env' );
		}

		$added = false;

		foreach ( array_chunk( $pending, self::CHUNK, true ) as $chunk ) {
			$answer = $this->request( $chunk, $target, $html );

			if ( is_wp_error( $answer ) ) {
				return $answer;
			}

			foreach ( $answer as $key => $text ) {
				$result[ $key ] = $text;
				$this->remember( $target, $pending[ $key ], $text );
				$added = true;
			}
		}

		if ( $added ) {
			$this->flush_cache();
		}

		return $result;
	}

	/**
	 * One call to the API.
	 *
	 * Sent as JSON because the form encoding WordPress produces for a nested
	 * array is text[0]=…, which the API does not accept.
	 *
	 * @param array  $texts  Strings keyed by caller.
	 * @param string $target DeepL target language.
	 * @param bool   $html   Whether the strings carry markup.
	 *
	 * @return array|WP_Error
	 */
	private function request( array $texts, string $target, bool $html ) {
		$keys = array_keys( $texts );
		$body = array(
			'target_lang' => $target,
			'text'        => array_values( array_map( 'strval', $texts ) ),
		);

		if ( $html ) {
			$body['tag_handling'] = 'html';
		}

		$response = wp_remote_post(
			$this->endpoint(),
			array(
				'timeout' => self::TIMEOUT,
				'headers' => array(
					'Authorization' => 'DeepL-Auth-Key ' . $this->key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'deepl_http', $response->get_error_message() );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = (string) wp_remote_retrieve_body( $response );

		if ( 200 !== $code ) {
			return new WP_Error( 'deepl_status', self::status_message( $code, $raw ) );
		}

		$decoded = json_decode( $raw, true );

		if ( ! is_array( $decoded ) || empty( $decoded['translations'] ) || ! is_array( $decoded['translations'] ) ) {
			return new WP_Error( 'deepl_body', 'ответ DeepL не разобран' );
		}

		$translations = array_values( $decoded['translations'] );

		if ( count( $translations ) !== count( $keys ) ) {
			return new WP_Error(
				'deepl_count',
				sprintf( 'DeepL вернул %d переводов вместо %d', count( $translations ), count( $keys ) )
			);
		}

		$result = array();

		foreach ( $translations as $index => $item ) {
			$text = is_array( $item ) && isset( $item['text'] ) ? (string) $item['text'] : '';

			if ( '' === trim( $text ) ) {
				return new WP_Error(
					'deepl_empty',
					sprintf( 'DeepL вернул пустой перевод поля %s', $keys[ $index ] )
				);
			}

			$result[ $keys[ $index ] ] = $text;
		}

		return $result;
	}

	/**
	 * Translation paid for earlier, if any.
	 *
	 * @param string $target
	 * @param string $text
	 *
	 * @return string|null
	 */
	private function cached( string $target, string $text ): ?string {
		if ( null === $this->cache ) {
			$stored      = get_option( self::CACHE_OPTION, array() );
			$this->cache = is_array( $stored ) ? $stored : array();
		}

		$key = self::cache_key( $target, $text );

		return isset( $this->cache[ $key ] ) ? (string) $this->cache[ $key ] : null;
	}

	/**
	 * Keep a translation for the next run.
	 *
	 * @param string $target
	 * @param string $source
	 * @param string $translation
	 *
	 * @return void
	 */
	private function remember( string $target, string $source, string $translation ): void {
		if ( null === $this->cache || count( $this->cache ) >= self::CACHE_LIMIT ) {
			return;
		}

		$this->cache[ self::cache_key( $target, $source ) ] = $translation;
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
	 *
	 * @return string
	 */
	private static function cache_key( string $target, string $text ): string {
		return md5( $target . "\0" . $text );
	}

	/**
	 * Endpoint for the configured key.
	 *
	 * Free keys end with :fx and are served by a different host.
	 *
	 * @return string
	 */
	private function endpoint(): string {
		$override = function_exists( 'core_env' ) ? core_env( 'DEEPL_API_URL' ) : '';

		if ( '' !== $override ) {
			return $override;
		}

		return str_ends_with( $this->key, ':fx' )
			? 'https://api-free.deepl.com/v2/translate'
			: 'https://api.deepl.com/v2/translate';
	}

	/**
	 * Readable reason for a non-200 answer.
	 *
	 * @param int    $code HTTP status.
	 * @param string $raw  Response body.
	 *
	 * @return string
	 */
	private static function status_message( int $code, string $raw ): string {
		$known = array(
			400 => 'запрос отклонён (400)',
			403 => 'ключ не принят (403)',
			404 => 'неверный endpoint (404)',
			413 => 'текст слишком большой (413)',
			429 => 'слишком частые запросы (429)',
			456 => 'квота исчерпана (456)',
		);

		$message = $known[ $code ] ?? sprintf( 'HTTP %d', $code );
		$decoded = json_decode( $raw, true );

		if ( is_array( $decoded ) && ! empty( $decoded['message'] ) ) {
			$message .= ': ' . sanitize_text_field( (string) $decoded['message'] );
		}

		return $message;
	}
}
