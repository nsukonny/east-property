<?php
/**
 * Archive template for Properties (CPT: properties)
 */

get_header( null, array( 'color' => 'sand' ) );
?>
    <main>
        <?php
        get_template_part( 'components/properties/filter' );
        get_template_part( 'template-parts/sections/index/about' );
        ?>
    </main>
<?php
get_footer();

