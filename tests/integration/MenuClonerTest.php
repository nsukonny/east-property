<?php
/**
 * Tests of copying navigation menus into another language.
 */

namespace EastProperty\Tests\Integration;

use EastProperty\Tests\TestCase;
use Tools\Menu_Cloner;

/**
 * What a copy points at, what it leaves out, and what a second run does.
 */
class MenuClonerTest extends TestCase {

	/**
	 * @var int[]
	 */
	private array $menus = array();

	public function tear_down() {
		foreach ( $this->menus as $menu_id ) {
			wp_delete_nav_menu( $menu_id );
		}

		$this->menus = array();

		parent::tear_down();
	}

	/**
	 * A menu in the default language, assigned to a theme location.
	 *
	 * @param string $location Theme location.
	 * @param array  $items    Arguments for wp_update_nav_menu_item().
	 *
	 * @return int
	 */
	private function source_menu( string $location, array $items ): int {
		$menu_id = (int) wp_create_nav_menu( 'Source ' . $location . ' ' . wp_rand() );

		$this->menus[] = $menu_id;

		foreach ( $items as $item ) {
			wp_update_nav_menu_item( $menu_id, 0, $item + array( 'menu-item-status' => 'publish' ) );
		}

		Menu_Cloner::assign( $location, 'en', $menu_id );

		return $menu_id;
	}

	/**
	 * A page with a Russian translation.
	 *
	 * @return array{0:int,1:int}
	 */
	private function translated_page(): array {
		$english = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$russian = self::factory()->post->create( array( 'post_type' => 'page' ) );

		pll_set_post_language( $english, 'en' );
		pll_set_post_language( $russian, 'ru' );
		$this->link_translations(
			array(
				'en' => $english,
				'ru' => $russian,
			)
		);

		return array( $english, $russian );
	}

	/**
	 * A copied item points at the translation, not at the source.
	 */
	public function test_copy_points_items_at_the_translation() {
		list( $english, $russian ) = $this->translated_page();

		$source = $this->source_menu(
			'header_menu',
			array(
				array(
					'menu-item-type'      => 'post_type',
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $english,
				),
			)
		);

		$result = ( new Menu_Cloner() )->clone_menu( $source, 'ru' );

		$this->assertFalse( is_wp_error( $result ) );
		$this->assertSame( 'create', $result['action'] );
		$this->assertSame( 1, $result['created'] );

		$this->menus[] = (int) $result['menu_id'];

		$items = wp_get_nav_menu_items( (int) $result['menu_id'] );

		$this->assertCount( 1, $items );
		$this->assertSame( $russian, (int) $items[0]->object_id );
		$this->assertSame( 'page', $items[0]->object );
	}

	/**
	 * An item whose target has no translation is left out and reported.
	 */
	public function test_item_without_translation_is_left_out() {
		list( $english ) = $this->translated_page();

		$lonely = self::factory()->post->create( array( 'post_type' => 'page' ) );
		pll_set_post_language( $lonely, 'en' );

		$source = $this->source_menu(
			'header_menu',
			array(
				array(
					'menu-item-type'      => 'post_type',
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $english,
				),
				array(
					'menu-item-type'      => 'post_type',
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $lonely,
				),
			)
		);

		$result = ( new Menu_Cloner() )->clone_menu( $source, 'ru' );

		$this->menus[] = (int) $result['menu_id'];

		$this->assertSame( 1, $result['created'] );
		$this->assertCount( 1, $result['skipped'] );
		$this->assertStringContainsString( 'нет перевода', $result['skipped'][0]['reason'] );
		$this->assertStringContainsString( (string) $lonely, $result['skipped'][0]['reason'] );
	}

	/**
	 * A link of this site keeps its path and query under the language prefix.
	 */
	public function test_custom_link_gets_the_language_prefix() {
		$this->assertSame(
			home_url( '/ru/off-plan/?beds=studio,1' ),
			Menu_Cloner::localize_url( home_url( '/off-plan/?beds=studio,1' ), 'ru' )
		);

		$this->assertSame(
			'https://example.com/elsewhere/',
			Menu_Cloner::localize_url( 'https://example.com/elsewhere/', 'ru' )
		);

		$this->assertSame( '', Menu_Cloner::localize_url( '', 'ru' ) );
	}

	/**
	 * A term of a taxonomy Polylang does not translate is kept as it is.
	 */
	public function test_shared_taxonomy_term_is_kept() {
		$this->assertFalse( pll_is_translated_taxonomy( 'location' ) );

		$created = wp_insert_term( 'Dubai Hills ' . wp_rand(), 'location' );
		$term    = (int) $created['term_id'];

		$source = $this->source_menu(
			'footer_menu_popular',
			array(
				array(
					'menu-item-type'      => 'taxonomy',
					'menu-item-object'    => 'location',
					'menu-item-object-id' => $term,
				),
			)
		);

		$result = ( new Menu_Cloner() )->clone_menu( $source, 'ru' );

		$this->menus[] = (int) $result['menu_id'];

		$items = wp_get_nav_menu_items( (int) $result['menu_id'] );

		$this->assertCount( 1, $items );
		$this->assertSame( $term, (int) $items[0]->object_id );
	}

	/**
	 * A second run leaves the copy alone and does not make another one.
	 */
	public function test_second_run_keeps_the_existing_copy() {
		list( $english ) = $this->translated_page();

		$source = $this->source_menu(
			'header_menu',
			array(
				array(
					'menu-item-type'      => 'post_type',
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $english,
				),
			)
		);

		$cloner = new Menu_Cloner();
		$first  = $cloner->clone_menu( $source, 'ru' );

		$this->menus[] = (int) $first['menu_id'];

		$before = count( wp_get_nav_menus() );
		$again  = $cloner->clone_menu( $source, 'ru' );

		$this->assertSame( 'none', $again['action'] );
		$this->assertSame( (int) $first['menu_id'], (int) $again['menu_id'] );
		$this->assertCount( $before, wp_get_nav_menus() );

		$forced = $cloner->clone_menu( $source, 'ru', true );

		$this->assertSame( 'update', $forced['action'] );
		$this->assertSame( (int) $first['menu_id'], (int) $forced['menu_id'] );
		$this->assertCount( $before, wp_get_nav_menus() );
	}

	/**
	 * The copy is reported for every location the default language fills.
	 */
	public function test_plan_covers_every_assigned_location() {
		list( $english ) = $this->translated_page();

		$this->source_menu(
			'header_menu',
			array(
				array(
					'menu-item-type'      => 'post_type',
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $english,
				),
			)
		);

		$plan = ( new Menu_Cloner() )->plan( 'ru' );

		$this->assertFalse( is_wp_error( $plan ) );

		$locations = wp_list_pluck( $plan, 'location' );

		$this->assertContains( 'header_menu', $locations );

		$this->assertTrue( is_wp_error( ( new Menu_Cloner() )->plan( 'en' ) ) );
	}
}
