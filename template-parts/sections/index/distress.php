<?php
/**
 * Distress banner section
 */

$link = core_home_url( '/distress/' );
?>
<section class="distress-banner">
    <div class="container">
        <div class="distress-banner-inner">
            <picture class="distress-banner-bg">
                <source media="(min-width: 768px)" width="2155" height="730"
                        srcset="<?php echo esc_url( THEME_URL . '/assets/img/distress/desktop.webp' ); ?>">
                <img src="<?php echo esc_url( THEME_URL . '/assets/img/distress/mobile.webp' ); ?>"
                     width="941" height="1672" loading="lazy" decoding="async"
                     alt="<?php esc_attr_e( 'Distress deals in Dubai', 'east-property' ); ?>">
            </picture>

            <div class="distress-banner-content is-desktop">
                <span class="distress-banner-eyebrow"><?php esc_html_e( 'DISTRESS DEALS', 'east-property' ); ?></span>
                <h2><?php esc_html_e( 'Own Dubai for Less', 'east-property' ); ?></h2>
                <p><?php esc_html_e( 'Premium properties from motivated sellers at below-market prices.', 'east-property' ); ?></p>
                <p><?php esc_html_e( 'Limited opportunities. The best deals move fast.', 'east-property' ); ?></p>
                <a class="distress-banner-cta" href="<?php echo esc_url( $link ); ?>">
                    <?php esc_html_e( 'Explore Distress Deals', 'east-property' ); ?>
                    <span aria-hidden="true">&rarr;</span>
                </a>
            </div>

            <div class="distress-banner-content is-mobile">
                <h2><?php esc_html_e( 'Your Dubai Home. For Less.', 'east-property' ); ?></h2>
                <p><?php esc_html_e( 'Exclusive properties at below-market prices.', 'east-property' ); ?></p>
                <p><?php esc_html_e( 'Move fast — the best deals don’t wait.', 'east-property' ); ?></p>
                <a class="distress-banner-cta" href="<?php echo esc_url( $link ); ?>">
                    <?php esc_html_e( 'View Deals', 'east-property' ); ?>
                    <span aria-hidden="true">&rarr;</span>
                </a>
            </div>
        </div>
    </div>
</section>
