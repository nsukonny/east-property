<?php
/**
 * Template Name: My Account
 */
get_header();
?>
    <main>
        Кабинет пользователя
        <?php
        get_template_part( 'template-parts/sections/main-menu/hero' );
        get_template_part( 'template-parts/sections/main-menu/tabs' );
        ?>
    </main>
<?php
get_footer();

