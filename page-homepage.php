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

		$properties = get_properties_by_count_of_units();
		get_template_part(
			'core/components/properties/map',
			null,
			array(
				'properties' => array(
					'items' => $properties,
				),
				'show_sidebar' => true,
			)
		);
		?>
	</main>
<?php
get_footer();

