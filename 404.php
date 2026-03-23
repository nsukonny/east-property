<?php

get_header();

?>
    <section class="single-404">
        <div class="container">
            <img src="<?php echo THEME_URL; ?>/assets/img/404.jpg" alt="404">
            <div class="text">
                <h1>404</h1>
                <p><?php _e( 'Page not found' ); ?></p>
                <a href="<?php echo esc_url( home_url( '/properties' ) ) ?>"
                   class="button orange xl"><?php _e( 'Go to properties' ); ?></a>
            </div>
        </div>
    </section>
<?php

get_footer();
