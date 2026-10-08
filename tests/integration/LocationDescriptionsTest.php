<?php
/**
 * Tests of the per-language descriptions of location terms.
 */

namespace EastProperty\Tests\Integration;

use EastProperty\Tests\TestCase;
use Tools\DeepL_Client;
use Tools\Location_Translator;

/**
 * Storage, admin save path, language-aware output and the DeepL backfill.
 */
class LocationDescriptionsTest extends TestCase {

	/**
	 * Refuse any request a test did not stub, so none can reach the real API.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		add_filter(
			'pre_http_request',
			static function ( $pre ) {
				return false === $pre
					? new \WP_Error( 'test_http_blocked', 'тест не подменил HTTP-слой' )
					: $pre;
			},
			99,
			2
		);
	}

	/**
	 * Answer every outgoing request with a DeepL body echoing the sent texts.
	 *
	 * @return callable The filter, to hand back to drop_http_stub().
	 */
	private function stub_deepl_success(): callable {
		$filter = static function ( $pre, $args ) {
			$sent         = json_decode( (string) ( $args['body'] ?? '' ), true );
			$translations = array();

			foreach ( (array) ( $sent['text'] ?? array() ) as $text ) {
				$translations[] = array( 'text' => '[T] ' . $text );
			}

			return array(
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'body'     => wp_json_encode( array( 'translations' => $translations ) ),
			);
		};

		add_filter( 'pre_http_request', $filter, 10, 2 );

		return $filter;
	}

	/**
	 * Remove a stub installed earlier.
	 *
	 * @param callable $filter
	 *
	 * @return void
	 */
	private function drop_http_stub( callable $filter ): void {
		remove_filter( 'pre_http_request', $filter, 10 );
	}

	/**
	 * A location carrying a description in the default language.
	 *
	 * @param string $slug
	 * @param string $description
	 *
	 * @return int Term id.
	 */
	private function create_location_with_description( string $slug, string $description = 'A district by the sea.' ): int {
		$this->create_location( $slug );

		$term = get_term_by( 'slug', $slug, 'location' );

		wp_update_term( $term->term_id, 'location', array( 'description' => $description ) );

		return (int) $term->term_id;
	}

	/**
	 * A translator wired to a stubbed client.
	 *
	 * @return Location_Translator
	 */
	private function translator(): Location_Translator {
		return new Location_Translator( new DeepL_Client( 'test-key:fx' ) );
	}

	/**
	 * Only languages other than the default get a variant.
	 */
	public function test_only_extra_languages_get_a_variant() {
		$this->assertSame( array( 'ru' ), core_location_languages() );
		$this->assertSame( 'description_ru', core_location_description_key( 'ru' ) );
	}

	/**
	 * The default language reads the term's own field, the other its meta.
	 */
	public function test_description_follows_the_language() {
		$term = $this->create_location_with_description( 'palm-jumeirah' );

		update_term_meta( $term, 'description_ru', 'Район у моря.' );

		$this->assertSame( 'A district by the sea.', core_location_description( $term, 'en' ) );
		$this->assertSame( 'Район у моря.', core_location_description( $term, 'ru' ) );
	}

	/**
	 * A language without its own text shows nothing rather than the English one.
	 */
	public function test_missing_variant_shows_nothing() {
		$term = $this->create_location_with_description( 'business-bay' );

		$this->assertSame( 'A district by the sea.', core_location_description( $term, 'en' ) );
		$this->assertSame( '', core_location_description( $term, 'ru' ) );
	}

	/**
	 * Without a term there is nothing to read.
	 */
	public function test_empty_input_is_tolerated() {
		$this->assertSame( '', core_location_description( '' ) );
		$this->assertSame( '', core_location_description( 999999 ) );
	}

	/**
	 * The edit screen prints a textarea per extra language.
	 */
	public function test_edit_screen_prints_a_field_per_language() {
		$term = get_term( $this->create_location_with_description( 'dubai-marina' ), 'location' );

		ob_start();
		do_action( 'location_edit_form_fields', $term, 'location' );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'name="description_ru"', $html );
		$this->assertStringContainsString( 'core_location_descriptions_nonce', $html );
	}

	/**
	 * The save path needs the nonce and strips markup.
	 */
	public function test_save_requires_the_nonce_and_stores_plain_text() {
		$term = $this->create_location_with_description( 'jvc' );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$_POST = array( 'description_ru' => 'Подделка' );
		core_location_save_descriptions( $term );

		$this->assertSame( '', (string) get_term_meta( $term, 'description_ru', true ) );

		$_POST = array(
			'core_location_descriptions_nonce' => wp_create_nonce( 'core_location_descriptions' ),
			'description_ru'                   => "Район <strong>у моря</strong>.<script>alert(1)</script>",
		);
		core_location_save_descriptions( $term );

		$saved = (string) get_term_meta( $term, 'description_ru', true );

		$this->assertStringNotContainsString( '<strong>', $saved );
		$this->assertStringNotContainsString( '<script', $saved );
		$this->assertStringContainsString( 'Район', $saved );

		$_POST = array(
			'core_location_descriptions_nonce' => wp_create_nonce( 'core_location_descriptions' ),
			'description_ru'                   => '   ',
		);
		core_location_save_descriptions( $term );

		$this->assertSame( '', (string) get_term_meta( $term, 'description_ru', true ) );

		$_POST = array();
	}

	/**
	 * Only locations with a source and without the language are picked.
	 */
	public function test_find_takes_locations_missing_the_language() {
		$missing = $this->create_location_with_description( 'palm-jumeirah' );
		$filled  = $this->create_location_with_description( 'business-bay' );
		$empty   = $this->create_location_with_description( 'jvc', '' );

		update_term_meta( $filled, 'description_ru', 'Уже есть.' );

		$found = $this->translator()->find( 'ru', 50 );

		$this->assertContains( $missing, $found );
		$this->assertNotContains( $filled, $found );
		$this->assertNotContains( $empty, $found );
	}

	/**
	 * A filled location is refused unless force is given.
	 */
	public function test_filled_location_is_refused_and_force_accepts_it() {
		$term = $this->create_location_with_description( 'palm-jumeirah' );

		update_term_meta( $term, 'description_ru', 'Уже есть.' );

		$refused = $this->translator()->plan( $term, 'ru' );

		$this->assertWPError( $refused );
		$this->assertSame( 'location_filled', $refused->get_error_code() );
		$this->assertIsArray( $this->translator()->plan( $term, 'ru', true ) );
	}

	/**
	 * A language outside the list is refused before anything is sent.
	 */
	public function test_unknown_language_is_refused() {
		$term   = $this->create_location_with_description( 'palm-jumeirah' );
		$result = $this->translator()->plan( $term, 'de' );

		$this->assertWPError( $result );
		$this->assertSame( 'location_language', $result->get_error_code() );
	}

	/**
	 * A successful run stores the variant and leaves the source alone.
	 */
	public function test_translation_is_stored_next_to_the_source() {
		$term = $this->create_location_with_description( 'palm-jumeirah' );
		$stub = $this->stub_deepl_success();

		$written = $this->translator()->translate( $term, 'ru' );

		$this->drop_http_stub( $stub );

		$this->assertIsInt( $written );
		$this->assertSame( '[T] A district by the sea.', (string) get_term_meta( $term, 'description_ru', true ) );
		$this->assertSame( 'A district by the sea.', (string) get_term( $term, 'location' )->description );
	}

	/**
	 * An API error writes nothing.
	 */
	public function test_api_error_writes_nothing() {
		$term   = $this->create_location_with_description( 'palm-jumeirah' );
		$filter = static function () {
			return array(
				'response' => array(
					'code'    => 456,
					'message' => 'Error',
				),
				'body'     => '{"message":"Quota Exceeded"}',
			);
		};

		add_filter( 'pre_http_request', $filter, 10, 2 );

		$result = $this->translator()->translate( $term, 'ru' );

		remove_filter( 'pre_http_request', $filter, 10 );

		$this->assertWPError( $result );
		$this->assertSame( '', (string) get_term_meta( $term, 'description_ru', true ) );
	}
}
