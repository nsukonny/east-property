<?php
/**
 * Home template
 */

get_header( null, array( 'color' => 'sand' ) );

if ( 404 === get_query_var( 'pagename' ) || is_404() ) {
    echo '404 on index';
    //get_template_part( '404' );

    return;
}
?>
    <main>
        <?php
        get_template_part( 'template-parts/sections/index/hero' );
        get_template_part( 'template-parts/sections/index/categories' );
        get_template_part( 'components/lists/featured' );
        get_template_part( 'template-parts/sections/index/about' );
        get_template_part( 'template-parts/sections/index/map' );
        ?>
    </main>
<?php
get_footer();