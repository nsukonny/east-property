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
final class DeepL_Client extends Translation_Client {

	/**
	 * Name of the .env entry holding the key.
	 */
	protected const ENV_KEY = 'DEEPL_API_KEY';

	/**
	 * WP_Error code for a missing key.
	 */
	protected const NO_KEY_CODE = 'deepl_no_key';

	/**
	 * Seconds to wait for the API.
	 */
	private const TIMEOUT = 25;

	/**
	 * Texts the API accepts in one request.
	 */
	protected const CHUNK = 50;

	/**
	 * Request fields the caller may add to a translation call.
	 *
	 * Without source_lang the API guesses, and on a one-word label it guesses
	 * wrong: "Bed" came back as «the world». model_type picks the newer engine,
	 * context tells it what kind of text this is without translating it.
	 */
	private const EXTRA_FIELDS = array( 'source_lang', 'context', 'model_type', 'glossary_id' );

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
		'zh' => 'ZH-HANS',
	);

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
	 * DeepL target language for a Polylang slug.
	 *
	 * @param string $slug Polylang language slug.
	 *
	 * @return string
	 */
	public function target_for( string $slug ): string {
		return self::target_language( $slug );
	}

	/**
	 * One call to the API.
	 *
	 * Sent as JSON because the form encoding WordPress produces for a nested
	 * array is text[0]=…, which the API does not accept.
	 *
	 * @param array  $texts   Strings keyed by caller.
	 * @param string $target  DeepL target language.
	 * @param bool   $html    Whether the strings carry markup.
	 * @param array  $options Extra API fields, sent only when filled.
	 *
	 * @return array|WP_Error
	 */
	protected function request( array $texts, string $target, bool $html, array $options = array() ) {
		$keys     = array_keys( $texts );
		$comments = array();

		if ( $html ) {
			foreach ( $texts as $key => $text ) {
				$texts[ $key ] = self::mask_comments( (string) $text, $comments[ $key ] );
			}
		}

		$body = array(
			'target_lang' => $target,
			'text'        => array_values( array_map( 'strval', $texts ) ),
		);

		foreach ( self::EXTRA_FIELDS as $field ) {
			if ( ! empty( $options[ $field ] ) ) {
				$body[ $field ] = (string) $options[ $field ];
			}
		}

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

			$key = $keys[ $index ];

			if ( isset( $comments[ $key ] ) && ! empty( $comments[ $key ] ) ) {
				$text = self::unmask_comments( $text, $comments[ $key ] );

				if ( null === $text ) {
					return new WP_Error(
						'deepl_markers',
						sprintf( 'DeepL потерял служебные метки поля %s', $key )
					);
				}
			}

			$result[ $key ] = $text;
		}

		return $result;
	}

	/**
	 * Endpoint for the configured key.
	 *
	 * Free keys end with :fx and are served by a different host.
	 *
	 * @return string
	 */
	private function endpoint(): string {
		$override = self::env( 'DEEPL_API_URL' );

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
