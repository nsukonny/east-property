<?php
/**
 * Template Name: Seed Banks
 */
get_header();

echo '<pre>---prd-' . print_r( 'seedbanks', true ) . '</pre>';
wp_die();
?>
    <main>
        <?php
        if (!empty($_GET['group'])) {
            get_template_part('template-parts/sections/menu-single/hero', null,
                    array(
                            'group_id' => (int)$_GET['group'],
                    )
            );
        } else {
            get_template_part('template-parts/sections/main-menu/hero');
            get_template_part('template-parts/sections/main-menu/tabs');
        }
        ?>
    </main>
<?php
get_footer();