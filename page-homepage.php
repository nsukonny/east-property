<?php
/**
 * Template Name: Homepage
 */

use Entities\Property;

get_header( null, array( 'color' => 'sand' ) );

if ( 404 === get_query_var( 'pagename' ) || is_404() ) {
	get_template_part( '404' );

	return;
}
?>
    <main>
		<?php
		get_template_part( 'template-parts/sections/index/hero' );
		get_template_part( 'core/components/sections/explore-by-districts' );
		get_template_part( 'core/components/sections/explore-by-beds' );
		get_template_part( 'core/components/units/featured', null, array( 'limit' => 3 ) );
		get_template_part( 'template-parts/sections/index/about' );

		$property_posts = get_posts(
			array(
				'post_type'      => 'property',
				'posts_per_page' => - 1,
				'status'         => 'publish',
			)
		);

		$properties = array();
		foreach ( $property_posts as $property_post ) {
			$property    = new Property( $property_post );
			$units_count = $property->get_units_count();
			if ( 0 >= $units_count || 30 < $units_count ) {
				continue;
			}

			$properties[] = $property;
		}
		get_template_part(
			'core/components/properties/map',
			null,
			array(
				'properties'   => array( 'items' => $properties ),
				'show_sidebar' => true,
			)
		);
		?>
    </main>
<?php
get_footer();

