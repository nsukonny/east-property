<?php
/**
 * Common functions
 */

/**
 * Build an array of links for breadcrumbs
 *
 * @return array
 */
function get_core_breadcrumbs_links(): array {
	global $unit, $property, $term, $post;

	$links = array();

	if ( ! empty( $term ) ) {
		$location           = get_term_by( 'slug', $term, 'location' );
		$links[]            = array(
			'url'   => core_home_url( '/off-plan/' ),
			'title' => __( 'Properties', 'east-property' ),
		); //TODO Get off plan or secondary from Unit
		$current_page_title = $location->name;
	}

	if ( ! empty( $post ) && in_array( $post->post_type, array( 'developers', 'page' ), true ) ) {
		$current_page_title = get_the_title();
	}

	if ( null !== $unit ) {
		if ( 'off-plan' === $unit->get_listing_type() ) {
			$links[] = array(
				'url'   => core_home_url( '/off-plan/' ),
				'title' => __( 'Off Plan Properties', 'east-property' ),
			);
		} elseif ( 'secondary' === $unit->get_listing_type() ) {
			$links[] = array(
				'url'   => core_home_url( '/secondary/' ),
				'title' => __( 'Secondary Listings', 'east-property' ),
			);
		} elseif ( 'distress' === $unit->get_listing_type() ) {
			$links[] = array(
				'url'   => core_home_url( '/distress/' ),
				'title' => __( 'Distress Deals', 'east-property' ),
			);
		}

		$property = $unit->get_property();
		if ( ! empty( $property ) ) {
			$links[] = array(
				'url'   => $property->get_url(),
				'title' => $property->get_title(),
			);
		}
		$current_page_title = $unit->get_title();
	}

	if ( null === $unit && null !== $property ) {
		$links[] = array(
			'url'   => core_home_url( '/projects/' ),
			'title' => __( 'Projects in UAE', 'east-property' ),
		);

		$current_page_title = $property->get_title();
	}

	$links[] = array(
		'url'   => null,
		'title' => $current_page_title,
	);

	return $links;
}

/**
 * Parse URl and get current page number
 *
 * @return int
 */
function pagination_get_current_page(): int {
	$uri          = $_REQUEST['current_href'] ?? $_SERVER['REQUEST_URI'] ?? '';
	$current_page = 1;
	$path         = parse_url( $uri, PHP_URL_PATH );

	if ( preg_match( '~/(?:page/|page-)(\d+)(?:/)?$~', rtrim( $path, '/' ), $m ) ) {
		$current_page = (int) $m[1];
	}

	return $current_page;
}

/**
 * Read a setting from the project .env
 *
 * The file is loaded once per request, so this also works under WP-CLI, where
 * no wp_head runs.
 *
 * @param string $key
 * @param string $fallback
 *
 * @return string
 */
function core_env( string $key, string $fallback = '' ): string {
	static $loaded = false;

	if ( ! $loaded ) {
		$root     = dirname( THEME_PATH, 3 );
		$autoload = $root . '/vendor/autoload.php';

		if ( file_exists( $autoload ) ) {
			require_once $autoload;
		}

		if ( class_exists( \Dotenv\Dotenv::class ) ) {
			\Dotenv\Dotenv::createImmutable( $root )->safeLoad();
		}

		$loaded = true;
	}

	return isset( $_ENV[ $key ] ) ? (string) $_ENV[ $key ] : $fallback;
}

/**
 * Add custom JS configs to global scripts
 *
 * @return void
 */
function custom_head_scripts(): void {
	?>
	<script>
		window.MAP_CONFIG = {
			apiKey: '<?php echo core_env( 'GOOGLE_MAPS_KEY' ); ?>',
			mapId: '<?php echo core_env( 'GOOGLE_MAPS_MAP_ID' ); ?>',
		};
	</script>
	<?php
}

add_action( 'wp_head', 'custom_head_scripts', 5 );
