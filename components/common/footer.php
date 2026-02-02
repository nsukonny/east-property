<?php
/**
 * Footer template part
 */
?>
    <footer class="footer">
        <div class="container">
            <div class="footer-wrapper">
                <div class="footer-inner">
                    <div class="footer-info">
                        <div class="subscribe">
                            <label for="subs">
                                <span><?php _e( 'Keep Yourself Up to Date' ); ?></span>
                                <input type="email" id="subs" placeholder="<?php _e( 'Your email address' ); ?>">
                            </label>
                            <?php
                            get_template_part( 'components/ui/button', null,
                                    array(
                                            'class' => 'orange xl',
                                            'text'  => __( 'Subscribe' ),
                                            'src'   => THEME_URL . '/assets/img/bell.svg',
                                            'alt'   => __( 'Subscribe icon' ),
                                    )
                            );
                            ?>
                        </div>
                        <a class="footer-logo" href="/">
                            <img class="footer-logo" src="<?php echo THEME_URL; ?>/assets/img/logo.svg" width="132"
                                 height="50" alt="Vector logotype">
                        </a>
                        <div class="footer-block">
                            <span>Address</span>
                            <address>
                                Sheikh Zayed Road, Building 25 <br>
                                Al Quoz 3 <br>
                                Dubai
                            </address>
                        </div>
                        <div class="footer-block">
                            <span>Live Support?</span>
                            <a href="mailto:info@eastpropoerty.com">
                                info@eastpropoerty.com
                            </a>
                        </div>
                    </div>
                    <div class="footer-menu">
                        <nav>
                            <?php
                            if ( has_nav_menu( 'footer_menu_popular' ) ) {
                                ?>
                                <ul class="menu">
                                    <li class="menu-item">
                                        <span><?php _e( 'Popular Search' ); ?></span>
                                    </li>
                                    <?php
                                    wp_nav_menu( array(
                                            'theme_location' => 'footer_menu_popular',
                                            'container'      => false,
                                            'items_wrap'     => '%3$s',
                                            'depth'          => 1,
                                            'fallback_cb'    => false,
                                    ) );
                                    ?>
                                </ul>
                            <?php } ?>

                            <?php
                            if ( has_nav_menu( 'footer_menu_discovery' ) ) {
                                ?>
                                <ul class="menu">
                                    <li class="menu-item">
                                        <span><?php _e( 'Discovery' ); ?></span>
                                    </li>
                                    <?php
                                    wp_nav_menu( array(
                                            'theme_location' => 'footer_menu_discovery',
                                            'container'      => false,
                                            'items_wrap'     => '%3$s',
                                            'depth'          => 1,
                                            'fallback_cb'    => false,
                                    ) );
                                    ?>
                                </ul>
                            <?php } ?>
                        </nav>
                        <nav>
                            <?php
                            if ( has_nav_menu( 'footer_menu_quick_links' ) ) {
                                ?>
                                <ul class="menu">
                                    <li class="menu-item">
                                        <span><?php _e( 'Quick Links' ); ?></span>
                                    </li>
                                    <?php
                                    wp_nav_menu( array(
                                            'theme_location' => 'footer_menu_quick_links',
                                            'container'      => false,
                                            'items_wrap'     => '%3$s',
                                            'depth'          => 1,
                                            'fallback_cb'    => false,
                                    ) );
                                    ?>
                                </ul>
                            <?php } ?>
                        </nav>
                    </div>
                </div>
                <div class="copyright">
                    <span>© East Property – All rights reserved</span>
                </div>
            </div>
        </div>
    </footer>
<?php
get_template_part( 'components/modals/image-modal' );
get_template_part( 'components/modals/quote-modal' );
get_template_part( 'components/modals/create-modal' );
get_template_part( 'components/modals/signin-modal' );
get_template_part( 'components/modals/forgot-modal' );
get_template_part( 'components/modals/desc-modal' );
get_template_part( 'components/modals/broker-modal' );

wp_footer();