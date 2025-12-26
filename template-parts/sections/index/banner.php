<?php
/**
 * Banner section template part
 */

$banner = get_field( 'banner', 'option' );
//TODO finish it.
?>
<section class="banner">
    <?php if ( ! empty( $banner['seo_title'] ) ) { ?>
        <h2 class="sr-only"><?php echo esc_html( $banner['seo_title'] ); ?></h2>
    <?php } ?>

    <picture>
        <source media="(max-width: 768px)" srcset="<?php echo THEME_URL; ?>/assets/img/banner-mob.jpg">
        <img class="banner-bg" src="<?php echo THEME_URL; ?>/assets/img/banner.jpg" alt="Баннер">
    </picture>
    <div class="banner-wrapper">
        <div class="banner-info">
            <div class="banner-title">
                <span>Бестселлер</span>
                <h3>
                    <em>Новые</em>
                    релизы
                </h3>
            </div>
            <div class="texts">
                <p>
                    Amnesia Lemon — это сорт, созданный совместными усилиями селекционеров Barneys Farm и Soma,
                    выигравшей Кубок Каннабиса в 2004 году.
                </p>
            </div>
        </div>
    </div>
</section>