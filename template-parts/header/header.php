<?php
/**
 * Header template
 */
?>
<header class="header">
    <div class="container">
        <div class="header-wrapper">
            <div class="header-top">
                <a href="/" class="header-logo">
                    <img src="<?php echo THEME_URL; ?>/assets/img/logo.svg" width="101" height="50"
                         alt="Логотип Dutch Seeds">
                </a>
                <div class="header-inner">
                    <form class="header-search">
                        <label for="search-input" class="sr-only"><?php _e( 'Поиск' ); ?></label>
                        <input id="search-input" type="search" name="s" placeholder="<?php _e( 'Search' ); ?>">
                        <button type="button">
                            <img src="<?php echo THEME_URL; ?>/assets/img/search.svg" width="28" height="28" alt="">
                        </button>
                    </form>

                    <div class="header-actions">
                        <button class="header-action m">
                            <img src="<?php echo THEME_URL; ?>/assets/img/search-gray.svg" width="24" height="24"
                                 alt="Векторная иконка">
                        </button>
                        <button class="header-action color">
                            <img src="<?php echo THEME_URL; ?>/assets/img/shopping-cart.svg" width="24" height="24"
                                 alt="Векторная иконка">
                        </button>
                        <button class="header-action" id="lang">
                            <span>EN</span>
                        </button>
                    </div>
                    <button class="header-burger" type="button" aria-label="<?php _e( 'Open Menu' ); ?>"
                            aria-expanded="false"
                            aria-controls="header-nav">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>
            <div class="header-bottom">
                <?php get_template_part( 'template-parts/header/nav' ); ?>
            </div>
        </div>
    </div>

</header>