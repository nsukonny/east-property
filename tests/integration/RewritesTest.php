<?php
/**
 * Regression tests of the public unit, projects list and district URLs.
 */

namespace EastProperty\Tests\Integration;

use EastProperty\Tests\StoppedResponse;
use EastProperty\Tests\TestCase;

/**
 * /property/{project}/{unit}/, the projects list and the district pages in both languages: what resolves, what redirects and what answers 404.
 *
 * Requests go through WP::main() and the template_redirect handlers; a handler that would send a status or a redirect and exit is stopped and read instead.
 */
class RewritesTest extends TestCase {

	/**
	 * Unit IDs by language.
	 *
	 * @var array<string, int>
	 */
	private array $units = array();

	/**
	 * Create an English and a Russian unit in translated projects and rebuild the rules.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$project_en = $this->create_property( array( 'slug' => 'marina-heights' ) );
		$project_ru = $this->create_property(
			array(
				'slug'     => 'marina-heights-ru',
				'language' => 'ru',
			)
		);

		$this->link_translations(
			array(
				'en' => $project_en,
				'ru' => $project_ru,
			)
		);

		$this->units = array(
			'en' => $this->create_unit(
				array(
					'slug'     => 'sea-view-studio',
					'property' => $project_en,
				)
			),
			'ru' => $this->create_unit(
				array(
					'slug'     => 'studiya-s-vidom',
					'language' => 'ru',
					'property' => $project_ru,
				)
			),
		);

		flush_rewrite_rules( false );
	}

	/**
	 * The unit URL resolves the unit and renders it.
	 */
	public function test_unit_url_resolves_the_unit() {
		$response = $this->request( 'en', '/property/marina-heights/sea-view-studio/' );

		$this->assertTrue( is_singular( 'unit' ) );
		$this->assertSame( $this->units['en'], get_queried_object_id() );
		$this->assertSame( 200, $response->status );
	}

	/**
	 * Any segment after the unit slug answers 404.
	 */
	public function test_unit_url_with_an_extra_segment_answers_404() {
		$response = $this->request( 'en', '/property/marina-heights/sea-view-studio/random-extra-segment/' );

		$this->assertSame( 404, $response->status );
		$this->assertTrue( is_404() );
		$this->assertFalse( is_singular( 'unit' ) );
	}

	/**
	 * The Russian unit URL resolves the Russian unit.
	 */
	public function test_russian_unit_url_resolves_the_russian_unit() {
		$response = $this->request( 'ru', '/ru/property/marina-heights-ru/studiya-s-vidom/' );

		$this->assertTrue( is_singular( 'unit' ) );
		$this->assertSame( $this->units['ru'], get_queried_object_id() );
		$this->assertSame( 200, $response->status );
	}

	/**
	 * Any segment after the Russian unit slug answers 404.
	 */
	public function test_russian_unit_url_with_an_extra_segment_answers_404() {
		$response = $this->request( 'ru', '/ru/property/marina-heights-ru/studiya-s-vidom/random-extra-segment/' );

		$this->assertSame( 404, $response->status );
		$this->assertTrue( is_404() );
	}

	/**
	 * A unit requested under another project, or by its old /properties/ URL, answers 301 to its own URL.
	 */
	public function test_unit_under_a_wrong_project_redirects_to_its_url() {
		$wrong_project = $this->request( 'en', '/property/another-project/sea-view-studio/' );
		$old_url       = $this->request( 'en', '/properties/dubai-marina/marina-heights/sea-view-studio/' );
		$wrong_ru      = $this->request( 'ru', '/ru/property/another-project/studiya-s-vidom/' );

		$this->assertSame( 301, $wrong_project->status );
		$this->assertSame( home_url( '/property/marina-heights/sea-view-studio/' ), $wrong_project->location );
		$this->assertSame( 301, $old_url->status );
		$this->assertSame( home_url( '/property/marina-heights/sea-view-studio/' ), $old_url->location );
		$this->assertSame( 301, $wrong_ru->status );
		$this->assertSame( home_url( '/ru/property/marina-heights-ru/studiya-s-vidom/' ), $wrong_ru->location );
	}

	/**
	 * A unit without a project lives at /property/{unit}/.
	 */
	public function test_unit_without_a_project_resolves_by_its_slug() {
		$orphan   = $this->create_unit( array( 'slug' => 'orphan-unit' ) );
		$response = $this->request( 'en', '/property/orphan-unit/' );

		$this->assertSame( $orphan, get_queried_object_id() );
		$this->assertSame( 200, $response->status );
	}

	/**
	 * A district URL resolves the district and the page it names, under /ru/ as without a prefix.
	 */
	public function test_district_urls_resolve_the_district_in_both_languages() {
		$location = get_term_by( 'slug', $this->create_location( 'dubai-marina' ), 'location' );
		$requests = array(
			array( 'en', '/areas/dubai-marina/', 1 ),
			array( 'en', '/projects/dubai-marina/', 1 ),
			array( 'en', '/projects/dubai-marina/page-2/', 2 ),
			array( 'ru', '/ru/areas/dubai-marina/', 1 ),
			array( 'ru', '/ru/projects/dubai-marina/', 1 ),
			array( 'ru', '/ru/projects/dubai-marina/page-2/', 2 ),
		);

		foreach ( $requests as list( $language, $path, $page ) ) {
			$response = $this->request( $language, $path );

			$this->assertTrue( is_tax( 'location' ), $path );
			$this->assertSame( $location->term_id, get_queried_object_id(), $path );
			$this->assertSame( $page, pagination_get_current_page(), $path );
			$this->assertSame( 200, $response->status, $path );
		}
	}

	/**
	 * Page two of the projects list resolves the projects page of the requested language.
	 */
	public function test_projects_list_page_two_resolves_the_page_of_the_language() {
		if ( ! isset( PLL()->share_post_slug ) ) {
			$this->markTestSkipped( 'Translations share the projects page slug only with Polylang Pro.' );
		}

		$pages = array();

		foreach ( array( 'en', 'ru' ) as $language ) {
			$this->switch_language( $language );

			$pages[ $language ] = self::factory()->post->create( array( 'post_type' => 'page' ) );

			pll_set_post_language( $pages[ $language ], $language );
			wp_update_post(
				array(
					'ID'        => $pages[ $language ],
					'post_name' => 'projects',
				)
			);
		}

		$this->link_translations( $pages );

		foreach ( array( 'en' => '/projects/page-2/', 'ru' => '/ru/projects/page-2/' ) as $language => $path ) {
			$response = $this->request( $language, $path );

			$this->assertSame( $pages[ $language ], get_queried_object_id(), $path );
			$this->assertSame( 2, pagination_get_current_page(), $path );
			$this->assertSame( 200, $response->status, $path );
		}
	}

	/**
	 * Run a front-end request in a language up to the template, as Polylang does for a request under that language.
	 *
	 * @param string $language Language the request is served in.
	 * @param string $path     Requested path.
	 *
	 * @return StoppedResponse Status and redirect target; 200 when nothing stopped the request.
	 */
	private function request( string $language, string $path ): StoppedResponse {
		$this->switch_language( $language );
		$this->go_to( home_url( $path ) );

		$status   = static function ( $header, $code ) {
			throw new StoppedResponse( (int) $code );
		};
		$redirect = static function ( $location, $code ) {
			throw new StoppedResponse( (int) $code, $location );
		};

		add_filter( 'status_header', $status, 10, 2 );
		add_filter( 'wp_redirect', $redirect, 10, 2 );

		try {
			do_action( 'template_redirect' );
		} catch ( StoppedResponse $response ) {
			return $response;
		} finally {
			remove_filter( 'status_header', $status );
			remove_filter( 'wp_redirect', $redirect );
		}

		return new StoppedResponse( 200 );
	}
}
