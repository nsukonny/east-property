<?php
/**
 * Template Name: Units Listing Page
 */
get_header( null, array( 'color' => 'sand' ) );

if ( 404 === get_query_var( 'pagename' ) || is_404() ) {
    get_template_part( '404' );

    return;
}
?>
    <main>
        <?php
        get_template_part( 'components/units/filter' );
        get_template_part( 'template-parts/sections/index/about' );
        ?>
    </main>
<?php
get_footer();

