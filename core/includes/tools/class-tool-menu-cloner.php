<?php
/**
 * Copies navigation menus into another language.
 *
 * @package east-property
 */

namespace Tools;

use WP_Error;
use WP_Term;

/**
 * Builds a language's menus from the ones the default language uses.
 */
final class Menu_Cloner {

	/**
	 * Term meta holding the menu a copy was made from.
	 */
	private const SOURCE_META = 'ep_menu_source';

	/**
	 * Term meta holding the language a copy was made for.
	 */
	private const LANGUAGE_META = 'ep_menu_language';

	/**
	 * Polylang slug of the language menus are copied from.
	 *
	 * @return string
	 */
	public static function default_language(): string {
		return function_exists( 'pll_default_language' )
			? (string) pll_default_language( 'slug' )
			: 'en';
	}

	/**
	 * Theme key Polylang files its menu assignments under.
	 *
	 * @return string
	 */
	public static function theme(): string {
		return get_stylesheet();
	}

	/**
	 * Menu assigned to each theme location in a language.
	 *
	 * @param string $language Polylang slug.
	 *
	 * @return array<string, int> Location to menu id.
	 */
	public static function assignments( string $language ): array {
		$option = (array) get_option( 'polylang', array() );
		$stored = $option['nav_menus'][ self::theme() ] ?? array();
		$result = array();

		foreach ( (array) $stored as $location => $by_language ) {
			$menu_id = (int) ( $by_language[ $language ] ?? 0 );

			if ( 0 < $menu_id ) {
				$result[ (string) $location ] = $menu_id;
			}
		}

		return $result;
	}

	/**
	 * Point a theme location at a menu for one language.
	 *
	 * @param string $location Theme location.
	 * @param string $language Polylang slug.
	 * @param int    $menu_id  Menu to assign.
	 *
	 * @return void
	 */
	public static function assign( string $location, string $language, int $menu_id ): void {
		$option = (array) get_option( 'polylang', array() );

		$option['nav_menus'][ self::theme() ][ $location ][ $language ] = $menu_id;

		update_option( 'polylang', $option );
	}

	/**
	 * Copy Polylang already holds for a source menu in a language.
	 *
	 * @param int    $source_id Menu copied from.
	 * @param string $language  Polylang slug.
	 *
	 * @return WP_Term|null
	 */
	public static function existing_copy( int $source_id, string $language ): ?WP_Term {
		$menus = wp_get_nav_menus();

		foreach ( $menus as $menu ) {
			$from = (int) get_term_meta( $menu->term_id, self::SOURCE_META, true );
			$lang = (string) get_term_meta( $menu->term_id, self::LANGUAGE_META, true );

			if ( $from === $source_id && $lang === $language ) {
				return $menu;
			}
		}

		return null;
	}

	/**
	 * Name a copy is created under.
	 *
	 * Follows the naming the site already uses for Russian and German:
	 * the source name with the language appended.
	 *
	 * @param WP_Term $source   Menu copied from.
	 * @param string  $language Polylang slug.
	 *
	 * @return string
	 */
	public static function copy_name( WP_Term $source, string $language ): string {
		return $source->name . ' ' . strtoupper( $language );
	}

	/**
	 * What a run would do, without writing anything.
	 *
	 * @param string $language Polylang slug.
	 *
	 * @return array|WP_Error One entry per theme location.
	 */
	public function plan( string $language ) {
		$default = self::default_language();

		if ( $language === $default ) {
			return new WP_Error(
				'ep_menu_same_language',
				sprintf( '«%s» — язык-источник, копировать его в себя нечего', $language )
			);
		}

		$sources = self::assignments( $default );

		if ( empty( $sources ) ) {
			return new WP_Error(
				'ep_menu_no_source',
				sprintf( 'у языка «%s» нет меню ни на одном месте темы', $default )
			);
		}

		$plan = array();

		foreach ( $sources as $location => $source_id ) {
			$source = wp_get_nav_menu_object( $source_id );

			if ( ! $source instanceof WP_Term ) {
				continue;
			}

			$copy  = self::existing_copy( $source_id, $language );
			$items = $this->map_items( $source_id, $language );

			$plan[] = array(
				'location' => $location,
				'source'   => $source,
				'copy'     => $copy,
				'assigned' => (int) ( self::assignments( $language )[ $location ] ?? 0 ),
				'mapped'   => $items['mapped'],
				'skipped'  => $items['skipped'],
			);
		}

		return $plan;
	}

	/**
	 * Resolve every item of a menu against the target language.
	 *
	 * A page or a project becomes its translation; a term of a taxonomy that
	 * Polylang does not translate stays the shared term; a custom link keeps
	 * its path under the language prefix. An item whose target has no
	 * translation is reported instead of being pointed at another language.
	 *
	 * @param int    $source_id Menu read from.
	 * @param string $language  Polylang slug.
	 *
	 * @return array Keys mapped, skipped.
	 */
	public function map_items( int $source_id, string $language ): array {
		$items   = wp_get_nav_menu_items( $source_id, array( 'update_post_term_cache' => false ) );
		$mapped  = array();
		$skipped = array();

		foreach ( (array) $items as $item ) {
			$resolved = $this->map_item( $item, $language );

			if ( null === $resolved ) {
				$skipped[] = array(
					'id'     => (int) $item->ID,
					'title'  => (string) $item->title,
					'type'   => (string) $item->type,
					'object' => (string) $item->object,
					'reason' => sprintf( 'нет перевода %s #%d', $item->object, (int) $item->object_id ),
				);

				continue;
			}

			$mapped[] = $resolved;
		}

		return array(
			'mapped'  => $mapped,
			'skipped' => $skipped,
		);
	}

	/**
	 * One item resolved against the target language.
	 *
	 * @param object $item     Item of the source menu.
	 * @param string $language Polylang slug.
	 *
	 * @return array|null Null when the target has no version in that language.
	 */
	private function map_item( $item, string $language ): ?array {
		$resolved = array(
			'source_id' => (int) $item->ID,
			'parent'    => (int) $item->menu_item_parent,
			'type'      => (string) $item->type,
			'object'    => (string) $item->object,
			'object_id' => (int) $item->object_id,
			'url'       => (string) $item->url,
			'title'     => (string) get_post_field( 'post_title', (int) $item->ID ),
			'attr'      => (string) $item->post_excerpt,
			'target'    => (string) $item->target,
			'classes'   => array_filter( (array) $item->classes ),
			'xfn'       => (string) $item->xfn,
			'position'  => (int) $item->menu_order,
		);

		if ( 'post_type' === $item->type ) {
			$translated = function_exists( 'pll_get_post' )
				? (int) pll_get_post( (int) $item->object_id, $language )
				: 0;

			if ( 0 >= $translated ) {
				return null;
			}

			$resolved['object_id'] = $translated;
			$resolved['url']       = '';

			return $resolved;
		}

		if ( 'taxonomy' === $item->type ) {
			$translated = function_exists( 'pll_is_translated_taxonomy' )
				&& pll_is_translated_taxonomy( (string) $item->object )
				? (int) pll_get_term( (int) $item->object_id, $language )
				: (int) $item->object_id;

			if ( 0 >= $translated ) {
				return null;
			}

			$resolved['object_id'] = $translated;
			$resolved['url']       = '';

			return $resolved;
		}

		$resolved['url'] = self::localize_url( (string) $item->url, $language );

		return $resolved;
	}

	/**
	 * Put a URL of this site under the prefix of a language.
	 *
	 * Only the site's own links are touched, and only their prefix: the path
	 * and the query are what the editor chose, filters included.
	 *
	 * @param string $url      Address from the source item.
	 * @param string $language Polylang slug.
	 *
	 * @return string
	 */
	public static function localize_url( string $url, string $language ): string {
		if ( '' === $url || ! function_exists( 'pll_home_url' ) ) {
			return $url;
		}

		$home = home_url( '/' );

		if ( 0 !== strpos( $url, $home ) ) {
			return $url;
		}

		return rtrim( (string) pll_home_url( $language ), '/' ) . '/' . substr( $url, strlen( $home ) );
	}

	/**
	 * Create, or refresh, the copy of one menu.
	 *
	 * @param int    $source_id Menu copied from.
	 * @param string $language  Polylang slug.
	 * @param bool   $force     Rebuild the items of a copy that already exists.
	 * @param bool   $inherit   Drop custom labels so they follow the translation.
	 *
	 * @return array|WP_Error Keys action, menu_id, created, skipped.
	 */
	public function clone_menu( int $source_id, string $language, bool $force = false, bool $inherit = false ) {
		$source = wp_get_nav_menu_object( $source_id );

		if ( ! $source instanceof WP_Term ) {
			return new WP_Error( 'ep_menu_missing', sprintf( 'меню #%d не существует', $source_id ) );
		}

		$copy   = self::existing_copy( $source_id, $language );
		$action = null === $copy ? 'create' : 'update';

		if ( 'update' === $action && ! $force ) {
			return array(
				'action'  => 'none',
				'menu_id' => (int) $copy->term_id,
				'created' => 0,
				'skipped' => array(),
			);
		}

		if ( null === $copy ) {
			$menu_id = wp_create_nav_menu( self::copy_name( $source, $language ) );

			if ( is_wp_error( $menu_id ) ) {
				return $menu_id;
			}

			$menu_id = (int) $menu_id;

			update_term_meta( $menu_id, self::SOURCE_META, $source_id );
			update_term_meta( $menu_id, self::LANGUAGE_META, $language );
		} else {
			$menu_id = (int) $copy->term_id;

			foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $existing ) {
				wp_delete_post( (int) $existing->ID, true );
			}
		}

		$items   = $this->map_items( $source_id, $language );
		$created = array();

		foreach ( $items['mapped'] as $item ) {
			$title = $item['title'];

			if ( $inherit && in_array( $item['type'], array( 'post_type', 'taxonomy' ), true ) ) {
				$title = '';
			}

			$new_id = wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'      => $title,
					'menu-item-url'        => $item['url'],
					'menu-item-object'     => $item['object'],
					'menu-item-object-id'  => $item['object_id'],
					'menu-item-type'       => $item['type'],
					'menu-item-attr-title' => $item['attr'],
					'menu-item-target'     => $item['target'],
					'menu-item-classes'    => implode( ' ', $item['classes'] ),
					'menu-item-xfn'        => $item['xfn'],
					'menu-item-position'   => $item['position'],
					'menu-item-status'     => 'publish',
				)
			);

			if ( is_wp_error( $new_id ) ) {
				continue;
			}

			$created[ $item['source_id'] ] = (int) $new_id;
		}

		// Вторым проходом, потому что родитель может идти в списке после ребёнка.
		foreach ( $items['mapped'] as $item ) {
			if ( 0 === $item['parent'] || empty( $created[ $item['source_id'] ] ) ) {
				continue;
			}

			$parent = (int) ( $created[ $item['parent'] ] ?? 0 );

			if ( 0 < $parent ) {
				update_post_meta( $created[ $item['source_id'] ], '_menu_item_menu_item_parent', $parent );
			}
		}

		return array(
			'action'  => $action,
			'menu_id' => $menu_id,
			'created' => count( $created ),
			'skipped' => $items['skipped'],
		);
	}
}
