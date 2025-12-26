<?php
/**
 * Home template
 */

get_header();

if ( 404 === get_query_var( 'pagename' ) || is_404() ) {
    get_template_part( '404' );

    return;
}
?>
    <main>
        <?php
        get_template_part( 'template-parts/sections/index/columns' );
        get_template_part( 'template-parts/sections/index/banner' );
        get_template_part( 'template-parts/sections/index/links' );
        get_template_part( 'template-parts/sections/index/slider' );
        get_template_part( 'template-parts/sections/index/news' );
        get_template_part( 'template-parts/components/catalog', null, array( 'slug' => 'seed-mixes' ) );
        get_template_part( 'template-parts/components/catalog', null, array( 'slug' => 'large' ) );
        get_template_part( 'template-parts/components/catalog', null, array( 'slug' => 'popular' ) );
        get_template_part( 'template-parts/sections/index/cta' );
        get_template_part( 'template-parts/sections/index/slider-2' );
        get_template_part( 'template-parts/sections/index/steps' );
        get_template_part( 'template-parts/sections/index/news-2' );
        ?>
    </main>
<?php
get_footer();