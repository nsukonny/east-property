<?php
/**
 * Gemini API client.
 *
 * @package east-property
 */

namespace Tools;

use WP_Error;

/**
 * Translates batches of text through the Gemini generateContent API.
 *
 * A model is not a translation endpoint: it can drop a tag, swallow a printf
 * placeholder or answer «here is your translation». So every string that comes
 * back is checked against its source, a bad one is asked for once more on its
 * own, and a string that fails twice aborts the chunk instead of being written.
 */
final class Gemini_Client extends Translation_Client {

	/**
	 * Name of the .env entry holding the key.
	 */
	protected const ENV_KEY = 'GEMINI_API_KEY';

	/**
	 * WP_Error code for a missing key.
	 */
	protected const NO_KEY_CODE = 'gemini_no_key';

	/**
	 * Model used when the .env names none.
	 */
	private const MODEL = 'gemini-3.1-flash-lite';

	/**
	 * Seconds to wait for the API.
	 */
	private const TIMEOUT = 180;

	/**
	 * Texts one request carries at most.
	 */
	protected const CHUNK = 20;

	/**
	 * Source characters one request carries at most.
	 *
	 * The cap is on characters, not on strings: a unit description runs to
	 * several thousand characters, and fifty of them in one call would ask the
	 * model for more output than it can return in one answer.
	 */
	private const CHUNK_CHARS = 12000;

	/**
	 * Answers worth asking for again, and how long to wait before each retry.
	 *
	 * Overload and rate limiting are states of the service, not of the
	 * request: the same body succeeds a minute later. A catalogue is a few
	 * hundred calls and a full language run tens of thousands, so one 503
	 * without this would end the whole job.
	 */
	private const RETRY_ON = array( 0, 429, 500, 502, 503, 504 );
	private const BACKOFF = array( 2, 8, 20, 45 );

	/**
	 * Where the domain notes for the prompt live, relative to the theme.
	 */
	private const DOMAIN_FILE = '/core/includes/tools/translation-domain.md';

	/**
	 * Language names the model is asked for, by Polylang slug.
	 */
	private const LANGUAGES = array(
		'en' => 'English',
		'ru' => 'Russian',
		'de' => 'German',
		'fr' => 'French',
		'es' => 'Spanish',
		'it' => 'Italian',
		'pt' => 'Portuguese',
		'nl' => 'Dutch',
		'pl' => 'Polish',
		'uk' => 'Ukrainian',
		'tr' => 'Turkish',
		'he' => 'Hebrew',
		'fa' => 'Persian',
		'ur' => 'Urdu',
		'hi' => 'Hindi',
		'zh' => 'Simplified Chinese',
		'ar' => 'Modern Standard Arabic as written in the United Arab Emirates',
	);

	/**
	 * What this process has spent and seen, for reporting.
	 *
	 * items and missed count answers passed through the terminator check, so
	 * a miss the single retry then fixed is still counted: the question the
	 * check has to answer is how often the model forgets, not how often it
	 * forgets twice.
	 *
	 * @var array<string, int>
	 */
	private array $usage = array( 'in' => 0, 'out' => 0, 'calls' => 0, 'items' => 0, 'missed' => 0 );

	/**
	 * Model asked for by the caller, ahead of the .env.
	 */
	private string $named_model = '';

	/**
	 * Terminator the model is asked to put after every translation.
	 *
	 * A response can finish cleanly with one item cut short inside it: the
	 * array is the right length, finishReason is STOP, tags and placeholders
	 * survive, and a third of a description is gone. Nothing else sees that.
	 * The marker turns the per-response signal into a per-item one.
	 *
	 * It is weak evidence — the model decides for itself that it is done, so
	 * a short paraphrase gets a marker too — and it is off until a run proves
	 * the model never forgets it. One miss in a hundred makes this a machine
	 * for rejecting good work.
	 */
	private const MARKER = '[[end-translate]]';

	/**
	 * Whether this instance asks for the terminator.
	 */
	private bool $marker = false;

	/**
	 * Ask the model to terminate every translation, and check that it did.
	 *
	 * @param bool $on
	 *
	 * @return void
	 */
	public function use_marker( bool $on ): void {
		$this->marker = $on;
	}

	/**
	 * Talk to another model than the .env names.
	 *
	 * @param string $model Model id.
	 *
	 * @return void
	 */
	public function use_model( string $model ): void {
		$this->named_model = $model;
	}

	/**
	 * Language name for a Polylang slug.
	 *
	 * @param string $slug Polylang language slug.
	 *
	 * @return string Empty when the project has no name for it.
	 */
	public function target_for( string $slug ): string {
		return self::LANGUAGES[ strtolower( $slug ) ] ?? '';
	}

	/**
	 * Tokens billed so far in this process.
	 *
	 * @return array<string, int>
	 */
	public function usage(): array {
		return $this->usage;
	}

	/**
	 * Model this instance talks to.
	 *
	 * @return string
	 */
	public function model(): string {
		if ( '' !== $this->named_model ) {
			return $this->named_model;
		}

		$named = self::env( 'GEMINI_MODEL' );

		return '' !== $named ? $named : self::MODEL;
	}

	/**
	 * Keep this provider's answers apart from another provider's.
	 *
	 * The prompt is part of what produced an answer, so it belongs in the key
	 * next to the model. Without it, sharpening a domain note or a rule looks
	 * like it changed nothing: every string is served from the cache as it was
	 * translated under the old wording.
	 *
	 * @return string
	 */
	protected function cache_scope(): string {
		return 'gemini:' . $this->model() . ':' . substr( md5( $this->instruction( '' ) ), 0, 8 );
	}

	/**
	 * Split the pending strings by character budget.
	 *
	 * @param array $pending
	 *
	 * @return array<int, array>
	 */
	protected function chunks( array $pending ): array {
		$chunks = array();
		$batch  = array();
		$size   = 0;

		foreach ( $pending as $key => $text ) {
			$length = mb_strlen( (string) $text );

			if ( ! empty( $batch ) && ( $size + $length > self::CHUNK_CHARS || count( $batch ) >= self::CHUNK ) ) {
				$chunks[] = $batch;
				$batch    = array();
				$size     = 0;
			}

			$batch[ $key ] = $text;
			$size         += $length;
		}

		if ( ! empty( $batch ) ) {
			$chunks[] = $batch;
		}

		return $chunks;
	}

	/**
	 * One chunk, with a second try for whatever came back wrong.
	 *
	 * @param array  $texts    Strings keyed by caller.
	 * @param string $target   Target language name.
	 * @param bool   $html     Whether the strings carry markup.
	 * @param array  $options  Unused; kept for the shared signature.
	 * @param array  $contexts What a string means, under the same keys as $texts.
	 *
	 * @return array|WP_Error
	 */
	protected function request( array $texts, string $target, bool $html, array $options = array(), array $contexts = array() ) {
		$comments = array();
		$sources  = $texts;

		if ( $html ) {
			foreach ( $texts as $key => $text ) {
				$texts[ $key ] = self::mask_comments( (string) $text, $comments[ $key ] );
			}
		}

		$answer = $this->attempt( $texts, $target, $contexts );

		if ( is_wp_error( $answer ) ) {
			return $answer;
		}

		$faults = $this->take_markers( $answer ) + $this->faults( $texts, $answer );

		if ( ! empty( $faults ) ) {
			$retry = $this->attempt(
				array_intersect_key( $texts, $faults ),
				$target,
				array_intersect_key( $contexts, $faults )
			);

			if ( is_wp_error( $retry ) ) {
				return $retry;
			}

			$missed = $this->take_markers( $retry );
			$answer = array_merge( $answer, $retry );
			$faults = $missed + $this->faults( $texts, $answer );
		}

		if ( ! empty( $faults ) ) {
			$key = (string) array_key_first( $faults );

			return new WP_Error(
				'gemini_integrity',
				sprintf( 'перевод поля %s не прошёл проверку дважды: %s', $key, $faults[ $key ] )
			);
		}

		$result = array();

		foreach ( $sources as $key => $source ) {
			$text = (string) ( $answer[ $key ] ?? '' );

			if ( ! empty( $comments[ $key ] ) ) {
				$text = self::unmask_comments( $text, $comments[ $key ] );

				if ( null === $text ) {
					return new WP_Error(
						'gemini_markers',
						sprintf( 'модель потеряла служебные метки поля %s', $key )
					);
				}
			}

			$result[ $key ] = $text;
		}

		return $result;
	}

	/**
	 * Which answers cannot be trusted, and why.
	 *
	 * @param array $sources
	 * @param array $answer
	 *
	 * @return array<string, string>
	 */
	/**
	 * Strip the terminator off each answer, naming the ones that lack it.
	 *
	 * Runs before the other checks, or the marker itself would count towards
	 * the length and the tag set. A key named here goes through the same
	 * single retry as any other fault.
	 *
	 * @param array $answer Translations by key, stripped in place.
	 *
	 * @return array Reasons by key, empty when the marker is off.
	 */
	private function take_markers( array &$answer ): array {
		if ( ! $this->marker ) {
			return array();
		}

		$missing = array();

		$this->usage['items'] += count( $answer );

		foreach ( $answer as $key => $text ) {
			$text = rtrim( (string) $text );

			if ( ! str_ends_with( $text, self::MARKER ) ) {
				$missing[ $key ] = str_contains( $text, self::MARKER )
					? 'маркер конца не в конце'
					: 'маркер конца не поставлен';

				continue;
			}

			$text = rtrim( substr( $text, 0, -strlen( self::MARKER ) ) );

			if ( str_contains( $text, self::MARKER ) ) {
				$missing[ $key ] = 'маркер конца встречается не один раз';

				continue;
			}

			$answer[ $key ] = $text;
		}

		$this->usage['missed'] += count( $missing );

		return $missing;
	}

	/**
	 * What came back wrong in this answer, by key.
	 *
	 * @param array $sources Strings that were sent.
	 * @param array $answer  Strings that came back.
	 *
	 * @return array
	 */
	private function faults( array $sources, array $answer ): array {
		$faults = array();

		foreach ( $sources as $key => $source ) {
			$fault = ! isset( $answer[ $key ] )
				? 'строка не вернулась'
				: self::fault( (string) $source, (string) $answer[ $key ] );

			if ( '' !== $fault ) {
				$faults[ $key ] = $fault;
			}
		}

		return $faults;
	}

	/**
	 * One call to the API.
	 *
	 * The strings go as a JSON array and the answer is pinned to a JSON array
	 * of the same length by responseSchema: asked in prose, a model renumbers,
	 * merges or prefaces the list, and the keys stop lining up.
	 *
	 * The model also over-escapes a slash inside the JSON it writes, so a
	 * closing tag comes back as `<\/span>` and the markup check rejects a
	 * translation that is otherwise right. The sequence never occurs in the
	 * catalogue or in any post, so it is undone here rather than guarded
	 * against downstream.
	 *
	 * @param array  $texts    Strings keyed by caller.
	 * @param string $target   Target language name.
	 * @param array  $contexts What a string means, under the same keys as $texts.
	 *
	 * @return array|WP_Error
	 */
	private function attempt( array $texts, string $target, array $contexts = array() ) {
		if ( empty( $texts ) ) {
			return array();
		}

		$keys   = array_keys( $texts );
		$values = array_values( array_map( 'strval', $texts ) );
		$budget = (int) ( mb_strlen( implode( '', $values ) ) / 2 ) + 1024;
		$items  = $values;

		foreach ( $keys as $index => $key ) {
			$context = (string) ( $contexts[ $key ] ?? '' );

			if ( '' !== $context ) {
				$items[ $index ] = array(
					'text'    => $values[ $index ],
					'context' => $context,
				);
			}
		}

		$body = array(
			'systemInstruction' => array(
				'parts' => array( array( 'text' => $this->instruction( $target ) ) ),
			),
			'contents'          => array(
				array(
					'role'  => 'user',
					'parts' => array(
						array(
							'text' => sprintf(
								"%d items follow. Answer with exactly %d translations.\n%s",
								count( $values ),
								count( $values ),
								(string) wp_json_encode( $items, JSON_UNESCAPED_UNICODE )
							),
						),
					),
				),
			),
			'generationConfig'  => array(
				'temperature'      => 0,
				'responseMimeType' => 'application/json',
				'responseSchema'   => array(
					'type'  => 'ARRAY',
					'items' => array( 'type' => 'STRING' ),
				),
				'maxOutputTokens'  => $budget,
			),
		);

		$url = sprintf(
			'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
			$this->model()
		);

		$args = array(
			'timeout' => self::TIMEOUT,
			'headers' => array(
				'x-goog-api-key' => $this->key,
				'Content-Type'   => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
		);

		$code     = 0;
		$raw      = '';
		$attempts = count( self::BACKOFF ) + 1;

		for ( $try = 0; $try < $attempts; $try++ ) {
			$response = wp_remote_post( $url, $args );

			if ( is_wp_error( $response ) ) {
				$code = 0;
				$raw  = $response->get_error_message();
			} else {
				$code = (int) wp_remote_retrieve_response_code( $response );
				$raw  = (string) wp_remote_retrieve_body( $response );
			}

			if ( 200 === $code ) {
				break;
			}

			$wait = self::retry_after( $raw );

			if ( ! in_array( $code, self::RETRY_ON, true ) || $try === $attempts - 1 || $wait > array_sum( self::BACKOFF ) ) {
				return 0 === $code
					? new WP_Error( 'gemini_http', $raw )
					: new WP_Error( 'gemini_status', self::status_message( $code, $raw ) );
			}

			sleep( max( self::BACKOFF[ $try ], $wait ) );
		}

		$decoded = json_decode( $raw, true );

		if ( ! is_array( $decoded ) ) {
			return new WP_Error( 'gemini_body', 'ответ Gemini не разобран' );
		}

		++ $this->usage['calls'];
		$this->usage['in']  += (int) ( $decoded['usageMetadata']['promptTokenCount'] ?? 0 );
		$this->usage['out'] += (int) ( $decoded['usageMetadata']['candidatesTokenCount'] ?? 0 );

		$reason = (string) ( $decoded['candidates'][0]['finishReason'] ?? '' );

		if ( '' !== $reason && 'STOP' !== $reason ) {
			return new WP_Error(
				'gemini_finish',
				sprintf( 'модель оборвала ответ: %s', $reason )
			);
		}

		$text = (string) ( $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '' );
		$list = json_decode( $text, true );

		if ( ! is_array( $list ) ) {
			return new WP_Error( 'gemini_shape', 'модель вернула не массив строк' );
		}

		if ( count( $list ) !== count( $keys ) ) {
			return new WP_Error(
				'gemini_count',
				sprintf( 'модель вернула %d переводов вместо %d', count( $list ), count( $keys ) )
			);
		}

		$clean = array_map(
			static fn( $text ): string => str_replace( '\\/', '/', (string) $text ),
			$list
		);

		return array_combine( $keys, $clean );
	}

	/**
	 * The system prompt for one target language.
	 *
	 * @param string $target Target language name.
	 *
	 * @return string
	 */
	private function instruction( string $target ): string {
		$rules = array(
			sprintf( 'You translate Dubai real estate website content from English into %s.', $target ),
			'Input is a JSON array. Return a JSON array of translations, same length, same order.',
			'An item is either a string to translate, or an object {"text", "context"} where context says what the text means on this site. The context is a hint about meaning, not a reason to leave the text alone: translate the text exactly as you would any other item, pick the sense the context points at, and never put the context itself in the answer.',
			'Translate every item independently. Never merge, split, reorder, drop or add items.',
			'One item often holds several lines or paragraphs. Line breaks inside an item are part of it and never separate items: such an item is still one string in the answer, with its line breaks kept.',
			'Output the translation only. No notes, no quotes around it, no explanation.',
			'Reproduce all HTML tags, attributes and their order exactly. Translate only the text between tags.',
			'Reproduce printf placeholders (%s, %d, %1$s, %%) exactly as they appear, including their count.',
			'Keep numbers, prices, currency codes, areas, dates and URLs unchanged.',
			'Leave proper names in Latin script: project, developer, community and company names, and the brand East Property.',
			'An item that is already in the target language is returned unchanged.',
			'Everything else must be rendered in the target language, even where English is common in that market and even for industry jargon. An item may stay in English only if it is a brand, a person, a project, a developer, a place, or one of the acronyms the domain notes keep as they are.',
		);

		if ( $this->marker ) {
			$rules[] = sprintf(
				'Close each item with %s. One item gets one terminator however many lines, paragraphs or headings it holds: it belongs after the last line of the whole item, never after a line, a sentence or a block inside it, and nothing may follow it.',
				self::MARKER
			);
		}

		$domain = $this->domain();

		if ( '' !== $domain ) {
			$rules[] = "Domain notes. These describe what the English means on this site; follow them:\n" . $domain;
		}

		return implode( "\n", $rules );
	}

	/**
	 * Domain notes for the prompt, empty when the file is absent.
	 *
	 * @return string
	 */
	private function domain(): string {
		static $notes = null;

		if ( null === $notes ) {
			$path  = get_stylesheet_directory() . self::DOMAIN_FILE;
			$notes = is_readable( $path ) ? trim( (string) file_get_contents( $path ) ) : '';
		}

		return $notes;
	}

	/**
	 * Readable reason for a non-200 answer.
	 *
	 * @param int    $code HTTP status.
	 * @param string $raw  Response body.
	 *
	 * @return string
	 */
	/**
	 * Seconds the API itself asks us to wait, 0 when it does not say.
	 *
	 * A 429 is two different things. Too many requests a second clears in
	 * seconds; a day's quota spent on the free tier comes back with a
	 * retryDelay of eighteen hours, and sitting through the backoff five times
	 * over only wastes a minute before failing anyway.
	 *
	 * @param string $raw Response body.
	 *
	 * @return int
	 */
	private static function retry_after( string $raw ): int {
		$decoded = json_decode( $raw, true );

		foreach ( (array) ( $decoded['error']['details'] ?? array() ) as $detail ) {
			if ( ! is_array( $detail ) || ! str_ends_with( (string) ( $detail['@type'] ?? '' ), 'RetryInfo' ) ) {
				continue;
			}

			if ( preg_match( '~^(\d+(?:\.\d+)?)s$~', (string) ( $detail['retryDelay'] ?? '' ), $found ) ) {
				return (int) ceil( (float) $found[1] );
			}
		}

		return 0;
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
			404 => 'модель не найдена (404)',
			429 => 'лимит запросов исчерпан (429)',
			500 => 'ошибка на стороне Google (500)',
			503 => 'модель перегружена (503)',
		);

		$message = $known[ $code ] ?? sprintf( 'HTTP %d', $code );
		$decoded = json_decode( $raw, true );

		if ( is_array( $decoded ) && ! empty( $decoded['error']['message'] ) ) {
			$message .= ': ' . sanitize_text_field( (string) $decoded['error']['message'] );
		}

		return $message;
	}
}
