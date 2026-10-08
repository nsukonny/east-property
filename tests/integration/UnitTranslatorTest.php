<?php
/**
 * Tests of the DeepL translation of units flagged need_translate.
 */

namespace EastProperty\Tests\Integration;

use EastProperty\Tests\TestCase;
use Tools\DeepL_Client;
use Tools\Distress_Units_Importer;
use Tools\Unit_Translator;

/**
 * Selection, target language, atomicity and the flag around Unit_Translator.
 *
 * DeepL is never called: every test answers the HTTP layer itself through
 * pre_http_request.
 */
class UnitTranslatorTest extends TestCase {

	/**
	 * Refuse any request a test did not stub, so none can reach the real API.
	 *
	 * Runs late, so a stub added by a test at the default priority wins.
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
	 * @param string $prefix Marker put in front of each translation.
	 *
	 * @return callable The filter, to hand back to drop_http_stub().
	 */
	private function stub_deepl_success( string $prefix = '[T] ' ): callable {
		$filter = static function ( $pre, $args ) use ( $prefix ) {
			$sent        = json_decode( (string) ( $args['body'] ?? '' ), true );
			$translations = array();

			foreach ( (array) ( $sent['text'] ?? array() ) as $text ) {
				$translations[] = array( 'text' => $prefix . $text );
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
	 * Answer every outgoing request with an error status.
	 *
	 * @param int $code HTTP status to answer with.
	 *
	 * @return callable The filter, to hand back to drop_http_stub().
	 */
	private function stub_deepl_failure( int $code = 456 ): callable {
		$filter = static function () use ( $code ) {
			return array(
				'response' => array(
					'code'    => $code,
					'message' => 'Error',
				),
				'body'     => '{"message":"Quota Exceeded"}',
			);
		};

		add_filter( 'pre_http_request', $filter, 10, 2 );

		return $filter;
	}

	/**
	 * Remove a stub installed earlier.
	 *
	 * @param callable $filter Filter returned by one of the stubs.
	 *
	 * @return void
	 */
	private function drop_http_stub( callable $filter ): void {
		remove_filter( 'pre_http_request', $filter, 10 );
	}

	/**
	 * A flagged Russian post of either supported type, carrying all three texts.
	 *
	 * @param string $post_type unit or property.
	 * @param array  $args      Extra fixture arguments.
	 *
	 * @return int Post ID.
	 */
	private function create_flagged_post( string $post_type = 'unit', array $args = array() ): int {
		$args = array_merge(
			array(
				'title'    => 'Studio in Marina',
				'language' => 'ru',
			),
			$args
		);

		$post_id = 'property' === $post_type
			? $this->create_property( $args )
			: $this->create_unit( $args );

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => '<p>A studio with a <a href="https://example.com">sea view</a>.</p>',
				'post_excerpt' => 'A studio with a sea view.',
			)
		);

		update_post_meta( $post_id, Distress_Units_Importer::NEED_TRANSLATE_META, 1 );

		return $post_id;
	}

	/**
	 * A flagged Russian unit.
	 *
	 * @param array $args Extra create_unit() arguments.
	 *
	 * @return int Unit ID.
	 */
	private function create_flagged_unit( array $args = array() ): int {
		return $this->create_flagged_post( 'unit', $args );
	}

	/**
	 * A translator wired to a stubbed client.
	 *
	 * @return Unit_Translator
	 */
	private function translator(): Unit_Translator {
		return new Unit_Translator( new DeepL_Client( 'test-key:fx' ) );
	}

	/**
	 * find() returns a flagged unit and leaves an unflagged one out.
	 */
	public function test_find_takes_only_flagged_units() {
		$flagged = $this->create_flagged_unit();
		$clean   = $this->create_unit( array( 'language' => 'ru' ) );

		$found = $this->translator()->find( 50 );

		$this->assertContains( $flagged, $found );
		$this->assertNotContains( $clean, $found );
	}

	/**
	 * find() never returns more ids than the limit allows.
	 */
	public function test_find_respects_the_limit() {
		$this->create_flagged_unit( array( 'slug' => 'flagged-one' ) );
		$this->create_flagged_unit( array( 'slug' => 'flagged-two' ) );
		$this->create_flagged_unit( array( 'slug' => 'flagged-three' ) );

		$this->assertCount( 2, $this->translator()->find( 2 ) );
	}

	/**
	 * find() narrows to one language when asked.
	 */
	public function test_find_filters_by_language() {
		$russian = $this->create_flagged_unit();
		$english = $this->create_flagged_unit(
			array(
				'slug'     => 'flagged-english',
				'language' => 'en',
			)
		);

		$found = $this->translator()->find( 50, 0, 'ru' );

		$this->assertContains( $russian, $found );
		$this->assertNotContains( $english, $found );
	}

	/**
	 * The plan reports the post's own language and the matching DeepL target.
	 */
	public function test_plan_reports_the_posts_own_language_as_the_target() {
		$plan = $this->translator()->plan( $this->create_flagged_unit() );

		$this->assertSame( 'ru', $plan['language'] );
		$this->assertSame( 'RU', $plan['target'] );
		$this->assertSame(
			array( 'post:post_title', 'post:post_content', 'post:post_excerpt' ),
			array_keys( $plan['strings'] )
		);
		$this->assertSame( array( 'post_title', 'post_content', 'post_excerpt' ), $plan['summary'] );
	}

	/**
	 * Planning alone writes nothing, which is what the dry run relies on.
	 */
	public function test_plan_changes_nothing() {
		$unit_id = $this->create_flagged_unit();
		$before  = get_post( $unit_id );

		$this->translator()->plan( $unit_id );

		$after = get_post( $unit_id );

		$this->assertSame( $before->post_title, $after->post_title );
		$this->assertSame( $before->post_content, $after->post_content );
		$this->assertSame( $before->post_excerpt, $after->post_excerpt );
		$this->assertSame( '1', (string) get_post_meta( $unit_id, Distress_Units_Importer::NEED_TRANSLATE_META, true ) );
	}

	/**
	 * A unit without the flag is refused unless force is given.
	 */
	public function test_unflagged_unit_is_refused_and_force_accepts_it() {
		$unit_id = $this->create_unit( array( 'language' => 'ru' ) );

		$refused = $this->translator()->plan( $unit_id );

		$this->assertWPError( $refused );
		$this->assertSame( 'unit_not_flagged', $refused->get_error_code() );

		$forced = $this->translator()->plan( $unit_id, true );

		$this->assertIsArray( $forced );
		$this->assertSame( 'RU', $forced['target'] );
	}

	/**
	 * Anything that is not an existing unit is refused by its own error code.
	 */
	public function test_other_post_types_and_missing_posts_are_refused() {
		$article = self::factory()->post->create( array( 'post_type' => 'post' ) );

		$wrong_type = $this->translator()->plan( $article, true );
		$missing    = $this->translator()->plan( 99999999, true );

		$this->assertSame( 'unit_type', $wrong_type->get_error_code() );
		$this->assertSame( 'unit_missing', $missing->get_error_code() );
	}

	/**
	 * A language added in Polylang needs no code change to become translatable.
	 *
	 * The bare code is used when the API accepts it; EN and PT it does not, so
	 * those carry a variant. Anything outside the list is reported as missing.
	 */
	public function test_deepl_target_is_derived_from_the_language_slug() {
		$this->assertSame( 'RU', DeepL_Client::target_language( 'ru' ) );
		$this->assertSame( 'DE', DeepL_Client::target_language( 'de' ) );
		$this->assertSame( 'AR', DeepL_Client::target_language( 'ar' ) );
		$this->assertSame( 'EN-GB', DeepL_Client::target_language( 'en' ) );
		$this->assertSame( 'PT-PT', DeepL_Client::target_language( 'pt' ) );
		$this->assertSame( '', DeepL_Client::target_language( 'zz' ) );
		$this->assertSame( '', DeepL_Client::target_language( '' ) );
	}

	/**
	 * A successful run fills every field and drops the flag.
	 */
	public function test_successful_translation_saves_every_field_and_clears_the_flag() {
		$unit_id = $this->create_flagged_unit();
		$stub    = $this->stub_deepl_success();

		$saved = $this->translator()->translate( $unit_id );

		$this->drop_http_stub( $stub );

		$this->assertIsArray( $saved );

		$post = get_post( $unit_id );

		$this->assertSame( '[T] Studio in Marina', $post->post_title );
		$this->assertStringContainsString( '[T] ', $post->post_content );
		$this->assertStringContainsString( '<a href="https://example.com">sea view</a>', $post->post_content );
		$this->assertSame( '[T] A studio with a sea view.', $post->post_excerpt );
		$this->assertSame( '', (string) get_post_meta( $unit_id, Distress_Units_Importer::NEED_TRANSLATE_META, true ) );
	}

	/**
	 * An API error leaves the unit and its flag exactly as they were.
	 */
	public function test_api_error_changes_nothing_and_keeps_the_flag() {
		$unit_id = $this->create_flagged_unit();
		$before  = get_post( $unit_id );
		$stub    = $this->stub_deepl_failure();

		$result = $this->translator()->translate( $unit_id );

		$this->drop_http_stub( $stub );

		$this->assertWPError( $result );
		$this->assertSame( 'deepl_status', $result->get_error_code() );

		$after = get_post( $unit_id );

		$this->assertSame( $before->post_title, $after->post_title );
		$this->assertSame( $before->post_content, $after->post_content );
		$this->assertSame( $before->post_excerpt, $after->post_excerpt );
		$this->assertSame( '1', (string) get_post_meta( $unit_id, Distress_Units_Importer::NEED_TRANSLATE_META, true ) );
	}

	/**
	 * A response holding fewer translations than were sent saves nothing.
	 */
	public function test_short_response_saves_nothing() {
		$unit_id = $this->create_flagged_unit();
		$before  = get_post( $unit_id );

		$filter = static function () {
			return array(
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'body'     => '{"translations":[{"text":"one only"}]}',
			);
		};

		add_filter( 'pre_http_request', $filter, 10, 2 );

		$result = $this->translator()->translate( $unit_id );

		remove_filter( 'pre_http_request', $filter, 10 );

		$this->assertWPError( $result );
		$this->assertSame( 'deepl_count', $result->get_error_code() );
		$this->assertSame( $before->post_title, get_post( $unit_id )->post_title );
		$this->assertSame( '1', (string) get_post_meta( $unit_id, Distress_Units_Importer::NEED_TRANSLATE_META, true ) );
	}

	/**
	 * Without a key nothing is sent and nothing is written.
	 */
	public function test_missing_key_is_reported_before_anything_is_written() {
		$unit_id    = $this->create_flagged_unit();
		$translator = new Unit_Translator( new DeepL_Client( '' ) );

		$this->assertFalse( $translator->ready() );

		$result = $translator->translate( $unit_id );

		$this->assertWPError( $result );
		$this->assertSame( 'deepl_no_key', $result->get_error_code() );
		$this->assertSame( '1', (string) get_post_meta( $unit_id, Distress_Units_Importer::NEED_TRANSLATE_META, true ) );
	}

	/**
	 * The sibling translation keeps its own copy and its own flag.
	 */
	public function test_sibling_translation_is_left_alone() {
		$english = $this->create_unit(
			array(
				'title'    => 'Studio in Marina',
				'slug'     => 'studio-in-marina',
				'language' => 'en',
			)
		);
		$russian = $this->create_flagged_unit( array( 'slug' => 'studiya-v-marine' ) );

		$this->link_translations(
			array(
				'en' => $english,
				'ru' => $russian,
			)
		);

		$before = get_post( $english );
		$stub   = $this->stub_deepl_success();

		$this->translator()->translate( $russian );

		$this->drop_http_stub( $stub );

		$after = get_post( $english );

		$this->assertSame( $before->post_title, $after->post_title );
		$this->assertSame( $before->post_content, $after->post_content );
		$this->assertSame( $russian, (int) pll_get_post( $english, 'ru' ) );
	}

	/**
	 * Clearing the flag here must not clear it on the sibling translation.
	 *
	 * Polylang syncs post metas through the delete_post_meta hooks, so without
	 * taking need_translate out of pll_copy_post_metas a translated English post
	 * silently drops its Russian sibling out of the backlog.
	 */
	public function test_clearing_the_flag_leaves_the_siblings_flag_alone() {
		$sync_before            = PLL()->options['sync'];
		PLL()->options['sync']  = array( 'post_meta' );

		$english = $this->create_flagged_unit(
			array(
				'slug'     => 'studio-in-marina-en',
				'language' => 'en',
			)
		);
		$russian = $this->create_flagged_unit( array( 'slug' => 'studiya-v-marine-ru' ) );

		$this->link_translations(
			array(
				'en' => $english,
				'ru' => $russian,
			)
		);

		$stub = $this->stub_deepl_success();

		$saved = $this->translator()->translate( $russian );

		$this->drop_http_stub( $stub );

		$russian_flag = (string) get_post_meta( $russian, Distress_Units_Importer::NEED_TRANSLATE_META, true );
		$english_flag = (string) get_post_meta( $english, Distress_Units_Importer::NEED_TRANSLATE_META, true );

		PLL()->options['sync'] = $sync_before;

		$this->assertIsArray( $saved );
		$this->assertSame( '', $russian_flag );
		$this->assertSame( '1', $english_flag );
	}

	/**
	 * A flagged project is found and translated the same way a unit is.
	 */
	public function test_project_is_found_and_translated_like_a_unit() {
		$project = $this->create_flagged_post( 'property', array( 'slug' => 'marina-heights-ru' ) );

		$this->assertContains( $project, $this->translator()->find( 50 ) );

		$plan = $this->translator()->plan( $project );

		$this->assertSame( 'RU', $plan['target'] );

		$stub  = $this->stub_deepl_success();
		$saved = $this->translator()->translate( $project );

		$this->drop_http_stub( $stub );

		$this->assertIsArray( $saved );
		$this->assertSame( '[T] Studio in Marina', get_post( $project )->post_title );
		$this->assertStringContainsString( '[T] ', get_post( $project )->post_content );
		$this->assertSame( '', (string) get_post_meta( $project, Distress_Units_Importer::NEED_TRANSLATE_META, true ) );
	}

	/**
	 * find() narrows to the asked types.
	 */
	public function test_find_filters_by_post_type() {
		$unit    = $this->create_flagged_unit();
		$project = $this->create_flagged_post( 'property', array( 'slug' => 'marina-heights-ru' ) );

		$only_projects = $this->translator()->find( 50, 0, '', array( 'property' ) );

		$this->assertContains( $project, $only_projects );
		$this->assertNotContains( $unit, $only_projects );
	}

	/**
	 * A post already in the default language is refused and keeps its flag.
	 */
	public function test_default_language_post_is_refused_and_left_alone() {
		$english = $this->create_flagged_unit(
			array(
				'slug'     => 'studio-in-marina-en',
				'language' => 'en',
			)
		);

		$refused = $this->translator()->plan( $english, true );

		$this->assertWPError( $refused );
		$this->assertSame( 'post_default_language', $refused->get_error_code() );
		$this->assertSame( '1', (string) get_post_meta( $english, Distress_Units_Importer::NEED_TRANSLATE_META, true ) );
	}

	/**
	 * The limit is not spent on default-language posts, which can only be skipped.
	 */
	public function test_find_leaves_out_the_default_language() {
		$english = $this->create_flagged_unit(
			array(
				'slug'     => 'studio-in-marina-en',
				'language' => 'en',
			)
		);
		$russian = $this->create_flagged_unit( array( 'slug' => 'studiya-v-marine-ru' ) );

		$found = $this->translator()->find( 50 );

		$this->assertContains( $russian, $found );
		$this->assertNotContains( $english, $found );
	}

	/**
	 * A project with amenities and payment plans, as the importer leaves them.
	 *
	 * @param array $args Extra fixture arguments.
	 *
	 * @return int Project ID.
	 */
	private function create_flagged_project_with_fields( array $args = array() ): int {
		$project = $this->create_flagged_post(
			'property',
			array_merge( array( 'slug' => 'marina-heights-ru' ), $args )
		);

		update_post_meta( $project, 'amenities', "Shared Pool\nCovered Parking\nLift" );
		update_post_meta( $project, 'payment_plans', 1 );
		update_post_meta( $project, 'payment_plans_0_id', 18694 );
		update_post_meta( $project, 'payment_plans_0_name', 'Payment Plan 1' );
		update_post_meta( $project, 'payment_plans_0_description', 'No Post Handover' );
		update_post_meta( $project, 'payment_plans_0_downPayment', '20' );
		update_post_meta( $project, 'payment_plans_0_items', 1 );
		update_post_meta( $project, 'payment_plans_0_items_0_id', 110629 );
		update_post_meta( $project, 'payment_plans_0_items_0_name', '50%' );
		update_post_meta( $project, 'payment_plans_0_items_0_description', 'On booking' );
		update_post_meta( $project, 'payment_plans_0_items_0_paymentPlanId', '18694' );

		return $project;
	}

	/**
	 * Amenities are translated line by line and the list keeps its shape.
	 */
	public function test_amenities_are_translated_line_by_line() {
		$project = $this->create_flagged_project_with_fields();
		$stub    = $this->stub_deepl_success();

		$saved = $this->translator()->translate( $project );

		$this->drop_http_stub( $stub );

		$this->assertContains( 'amenities', $saved );
		$this->assertSame(
			"[T] Shared Pool\n[T] Covered Parking\n[T] Lift",
			(string) get_post_meta( $project, 'amenities', true )
		);
	}

	/**
	 * Only name and description of the payment plans are touched.
	 */
	public function test_payment_plans_keep_their_structure() {
		$project = $this->create_flagged_project_with_fields();
		$stub    = $this->stub_deepl_success();

		$saved = $this->translator()->translate( $project );

		$this->drop_http_stub( $stub );

		$this->assertContains( 'payment_plans', $saved );
		$this->assertSame( '[T] Payment Plan 1', (string) get_post_meta( $project, 'payment_plans_0_name', true ) );
		$this->assertSame( '[T] No Post Handover', (string) get_post_meta( $project, 'payment_plans_0_description', true ) );
		$this->assertSame( '[T] On booking', (string) get_post_meta( $project, 'payment_plans_0_items_0_description', true ) );

		// Строки без букв и служебные поля остаются как были.
		$this->assertSame( '50%', (string) get_post_meta( $project, 'payment_plans_0_items_0_name', true ) );
		$this->assertSame( '18694', (string) get_post_meta( $project, 'payment_plans_0_id', true ) );
		$this->assertSame( '20', (string) get_post_meta( $project, 'payment_plans_0_downPayment', true ) );
		$this->assertSame( '18694', (string) get_post_meta( $project, 'payment_plans_0_items_0_paymentPlanId', true ) );
		$this->assertSame( '1', (string) get_post_meta( $project, 'payment_plans', true ) );
	}

	/**
	 * The sibling keeps its own field values, which Polylang would otherwise sync.
	 */
	public function test_sibling_keeps_its_own_field_values() {
		$sync_before           = PLL()->options['sync'];
		PLL()->options['sync'] = array( 'post_meta' );

		$english = $this->create_flagged_post(
			'property',
			array(
				'slug'     => 'marina-heights-en',
				'language' => 'en',
			)
		);
		$russian = $this->create_flagged_project_with_fields();

		update_post_meta( $english, 'amenities', "Shared Pool\nCovered Parking\nLift" );
		update_post_meta( $english, 'payment_plans_0_name', 'Payment Plan 1' );

		$this->link_translations(
			array(
				'en' => $english,
				'ru' => $russian,
			)
		);

		$stub = $this->stub_deepl_success();

		$this->translator()->translate( $russian );

		$this->drop_http_stub( $stub );

		$english_amenities = (string) get_post_meta( $english, 'amenities', true );
		$english_plan      = (string) get_post_meta( $english, 'payment_plans_0_name', true );

		PLL()->options['sync'] = $sync_before;

		$this->assertSame( "Shared Pool\nCovered Parking\nLift", $english_amenities );
		$this->assertSame( 'Payment Plan 1', $english_plan );
	}

	/**
	 * A string translated once is not sent again.
	 */
	public function test_repeated_strings_are_served_from_the_cache() {
		$calls  = 0;
		$filter = static function ( $pre, $args ) use ( &$calls ) {
			++ $calls;

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

		$first  = $this->create_flagged_project_with_fields();
		$second = $this->create_flagged_project_with_fields( array( 'slug' => 'marina-heights-two-ru' ) );

		$this->translator()->translate( $first );
		$after_first = $calls;

		$this->translator()->translate( $second );

		remove_filter( 'pre_http_request', $filter, 10 );

		$this->assertGreaterThan( 0, $after_first );
		$this->assertSame( $after_first, $calls, 'второй объект с теми же строками не должен давать новых запросов' );
		$this->assertSame( '[T] Payment Plan 1', (string) get_post_meta( $second, 'payment_plans_0_name', true ) );
	}

	/**
	 * A unit with no text of its own is reported without any request going out.
	 *
	 * The columns are emptied directly: wp_update_post() refuses a post whose
	 * title, content and excerpt are all blank.
	 */
	public function test_unit_without_text_is_reported_and_nothing_is_sent() {
		global $wpdb;

		$unit_id = $this->create_unit( array( 'language' => 'ru' ) );

		$wpdb->update(
			$wpdb->posts,
			array(
				'post_title'   => '',
				'post_content' => '',
				'post_excerpt' => '',
			),
			array( 'ID' => $unit_id )
		);
		clean_post_cache( $unit_id );

		update_post_meta( $unit_id, Distress_Units_Importer::NEED_TRANSLATE_META, 1 );

		$called = false;
		$filter = static function () use ( &$called ) {
			$called = true;

			return new \WP_Error( 'test_unexpected_request', 'запрос не должен был уйти' );
		};

		add_filter( 'pre_http_request', $filter, 10, 2 );

		$result = $this->translator()->translate( $unit_id );

		remove_filter( 'pre_http_request', $filter, 10 );

		$this->assertFalse( $called );
		$this->assertWPError( $result );
		$this->assertSame( 'unit_no_text', $result->get_error_code() );
	}
}
