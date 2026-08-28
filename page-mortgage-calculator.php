<?php
/**
 * Template Name: Mortgage Calculator
 */

get_header( null, array( 'color' => 'white' ) );

if ( 404 === get_query_var( 'pagename' ) || is_404() ) {
	get_template_part( '404' );

	return;
}

get_template_part( 'template-parts/sections/mortgage-calculator/hero' );
get_template_part( 'template-parts/sections/mortgage-calculator/calculator' );
get_template_part( 'template-parts/sections/mortgage-calculator/entry-costs' );
get_template_part( 'template-parts/sections/mortgage-calculator/matched-units' );
get_template_part( 'template-parts/sections/mortgage-calculator/why-dda' );
get_template_part( 'template-parts/sections/mortgage-calculator/faq' );

get_footer();