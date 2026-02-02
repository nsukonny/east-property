<?php
/**
 * Template Name: Text Page
 */
get_header( null, array( 'color' => 'white' ) );
?>
    <main>
        <?php
        get_template_part( 'template-parts/sections/privacy/content', null,
                array(
                        'title'       => get_the_title(),
                        'description' => get_the_content(),
                )
        );
        ?>
    </main>
<?php
get_footer();

