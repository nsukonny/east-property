<?php
/**
 * Hero section template
 */

$hero             = get_field( 'hero_section', 'option' );
$h1               = ! empty( $hero['title'] ) ? $hero['title'] : get_bloginfo( 'name' );
$properties_count = wp_count_posts( 'properties' )->publish;
$daily_sales      = round( $properties_count / 5 );
?>
<section class="hero">
    <div class="container">
        <div class="hero-wrapper">
            <div class="hero-left">
                <h1><?php echo $h1; ?></h1>
                <?php get_template_part( 'components/filters/search-tabs' ); ?>
                <div class="hero-items">
                    <div class="hero-item">
                        <span>
                            <?php echo esc_attr( $properties_count ); ?>+
                        </span>
                        <p>
                            <?php _e( 'Properties for sale' ); ?>
                        </p>
                    </div>
                    <div class="hero-item">
                        <span>
                           <?php echo esc_attr( $daily_sales ); ?>
                        </span>
                        <p>
                            <?php _e( 'Average sales daily' ); ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="hero-right">
                <div class="hero-img">
                    <img src="<?php echo THEME_URL; ?>/assets/img/hero-image.png" width="655" height="418" alt="">
                </div>
            </div>
        </div>
    </div>
</section>