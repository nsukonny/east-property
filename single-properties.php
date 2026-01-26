<?php
/**
 * Single template for Properties (CPT: properties)
 */

use Entities\Property;

get_header( null, array( 'color' => 'white' ) );
?>
    <main>
        <?php
        while ( have_posts() ) {
            the_post();

            $property = new Property( get_the_ID() );
            get_template_part( 'template-parts/sections/single/items', null, array( 'property' => $property ) );
        }
        ?>
    </main>
<?php
get_footer();

