<?php
/**
 * Single template for Properties (CPT: properties)
 */

use Entities\Unit;

get_header( null, array( 'color' => 'white' ) );
?>
    <main>
        <?php
        while ( have_posts() ) {
            the_post();

            $unit = new Unit( get_the_ID() );
            get_template_part( 'template-parts/sections/unit/items', null, array( 'unit' => $unit ) );
            get_template_part( 'template-parts/sections/unit/properties', null, array( 'unit' => $unit ) );
        }
        ?>
    </main>
<?php
get_footer();

