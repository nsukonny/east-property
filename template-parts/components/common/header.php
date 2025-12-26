<?php
/**
 * Header template
 */

$color = $args['color'] ?? '';
?>
<header class="header <?php echo esc_attr( $color ); ?>">
    <div class="container">
        <div class="header-wrapper">
            <a href="/" class="header-logo">
                <img src="<?php echo THEME_URL; ?>/assets/img/logo.svg" width="132" height="50" alt="Vector logotype">
            </a>
            <?php get_template_part( 'template-parts/components/common/nav' ); ?>
            <div class="header-actions">
                <?php if ( ! is_user_logged_in() ) { ?>
                    <button class="header-signin" type="button" data-modal-open="signin-modal">
                        Sign in
                    </button>
                    <?php
                    get_template_part( 'template-parts/components/ui/button', null,
                            array(
                                    'class'      => 'black sm header-login',
                                    'text'       => __( 'Create Account' ),
                                    'src'        => THEME_URL . '/assets/img/user.svg',
                                    'data_modal' => 'create-modal',
                            )
                    );
                    ?>
                <?php } else { ?>
                    <?php
                    $current_user = wp_get_current_user();
                    get_template_part( 'template-parts/components/ui/button', null,
                            array(
                                    'class' => 'black sm header-login',
                                    'text'  => $current_user->display_name,
                                    'src'   => THEME_URL . '/assets/img/user.svg',
                                    'link'  => home_url( 'my-account' ),
                            )
                    );
                    ?>
                <?php } ?>
                <button class="burger-button" type="button" aria-label="<?php _e( 'Open menu' ); ?>"
                        aria-expanded="false"
                        aria-controls="header-nav">
                    <span class="line"></span>
                </button>
            </div>
        </div>
        <div class="header-overlay" data-header-overlay></div>
    </div>
</header>
