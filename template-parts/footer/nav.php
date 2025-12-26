<?php
/**
 * Footer menus
 */

$menu_location = $args['menu_theme_location'] ?? 'footer_menu';

if ( ! has_nav_menu( $menu_location ) ) {
    return;
}

$title = $args['title'] ?? __( 'Навигация' );
?>
<nav class="footer-nav">
    <span><?php echo esc_html( $title ); ?></span>
    <div class="footer-nav-inner">
        <?php
        wp_nav_menu(
                array(
                        'theme_location' => $menu_location,
                        'container'      => 'ul',
                        'menu_class'     => 'menu',

                )
        );
        ?>
        <?php
        wp_nav_menu(
                array(
                        'theme_location' => $menu_location,
                        'container'      => 'ul',
                        'menu_class'     => 'menu',

                )
        );
        ?>
    </div>
</nav>