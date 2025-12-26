<footer class="footer">
    <div class="container">
        <div class="footer-wrapper">
            <div class="footer-top">
                <div class="footer-top-inner">
                    <div class="footer-top-left">
                        <a class="footer-logo" href="/">
                            <img src="<?php echo THEME_URL; ?>/assets/img/logo-white.png" alt="">
                        </a>
                        <p>
                            В современном мире, где технологии развиваются быстрее, чем успевает адаптироваться человек,
                            грань
                            между реальностью и виртуальностью становится всё менее заметной. Каждое нажатие клавиши,
                            каждое
                            движение мыши
                        </p>
                    </div>
                    <div class="footer-top-right">
                        <?php
                        get_template_part( 'template-parts/footer/nav', null,
                                array(
                                        'title'               => __( 'Навигация' ),
                                        'menu_theme_location' => 'footer_menu'
                                )
                        );

                        get_template_part( 'template-parts/footer/nav', null,
                                array(
                                        'title'               => __( 'Правовая информация' ),
                                        'menu_theme_location' => 'footer_menu_politics'
                                )
                        );
                        ?>
                    </div>
                </div>
                <div class="footer-images">
                    <img src="<?php echo THEME_URL; ?>/assets/img/mastercard.svg" width="56" height="32" alt="иконка">
                    <img src="<?php echo THEME_URL; ?>/assets/img/visa.svg" width="56" height="32" alt="иконка">
                </div>
            </div>
            <div class="footer-bottom">
                <div class="copyright">
                    © 2026 Dutch Seeds.
                </div>

                <div class="footer-bottom-links">
                    <a href="t.me/dutch_seeds" target="_blank" rel="noopener noreferrer">
                        <?php _e( 'Мы в Telegram' ); ?>
                    </a>
                </div>
            </div>

        </div>
    </div>
</footer>
</div><!-- .wrapper -->

<?php
wp_footer();