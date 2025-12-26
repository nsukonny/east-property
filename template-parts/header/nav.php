<?php
/**
 * Navigation menus
 */
?>
<nav class="header-nav" id="header-nav">
    <?php
    wp_nav_menu(
            array(
                    'theme_location' => 'header_menu',
                    'container'      => 'ul',
                    'menu_class'     => 'menu',

            )
    );
    ?>
</nav>
<nav class="header-nav-mob">
    <?php
    wp_nav_menu(
            array(
                    'theme_location' => 'header_menu_mobile',
                    'container'      => 'ul',
                    'menu_class'     => 'menu',

            )
    );
    ?>
</nav>