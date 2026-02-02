<?php
/**
 * Single template for Properties (CPT: properties)
 */

use Entities\Unit;

get_header( null, array( 'color' => 'sand' ) );
?>
    <main>
        <?php
        while ( have_posts() ) {
            the_post();

            $developer = new Developer( get_the_ID() );
            get_template_part( 'template-parts/sections/properties/filters', null, array( 'develper' => $developer ) );
            get_template_part( 'template-parts/sections/developer/tabs', null, array( 'developer' => $developer ) );
            get_template_part( 'template-parts/sections/index/about' );
        }
        ?>
    </main>
<?php
get_footer();

