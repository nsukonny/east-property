<?php
/**
 * Navigation menus
 */


if ( ! has_nav_menu( 'header_menu' ) ) {
    return;
}

?>
<nav id="header-nav" class="header-nav">
    <ul class="menu">
        <li class="menu-item mobile">
            <img src="<?php echo THEME_URL; ?>/assets/img/logo.svg" alt="">
        </li>
        <?php
        wp_nav_menu(
                array(
                        'theme_location' => 'header_menu',
                        'container'      => false,
                        'menu_class'     => 'menu',

                )
        );
        ?>
        <li class="menu-item mobile">
            <img src="<?php echo THEME_URL; ?>/assets/img/hero-image.png" alt="">
        </li>
    </ul>
</nav>