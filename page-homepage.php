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
        get_template_part( 'template-parts/sections/index/categories' );
        get_template_part( 'core/components/lists/featured' );
        get_template_part( 'template-parts/sections/index/about' );

        $property_posts = get_posts( array(
                'post_type'      => 'property',
                'posts_per_page' => - 1,
                'status'         => 'publish',
        ) );
        $properties     = array();
        foreach ( $property_posts as $property_post ) {
            $properties[] = new Property( $property_post );
        }
        get_template_part( 'core/components/properties/map', null,
                array(
                        'properties'   => $properties,
                        'show_sidebar' => true,
                )
        );
        ?>
    </main>
<?php
get_footer();

