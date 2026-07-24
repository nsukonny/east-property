<?php
/**
 * Archive template for Properties (CPT: properties)
 */

get_header( null, array( 'color' => 'sand' ) );

get_template_part( 'core/components/properties/filter' );
get_template_part( 'template-parts/sections/index/about' );

get_footer();

