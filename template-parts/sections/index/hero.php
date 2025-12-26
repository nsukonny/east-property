<?php
/**
 * Hero section template
 */

$hero = get_field( 'hero_section', 'option' );
$h1   = ! empty( $hero['title'] ) ? $hero['title'] : get_bloginfo( 'name' );
?>
<section class="hero">
    <div class="container">
        <div class="hero-wrapper">
            <div class="hero-left">
                <h1><?php echo $h1; ?></h1>
                <?php get_template_part( 'template-parts/components/ui/search-tabs', null,
                        array(
                                'class' => 'hero-tabs',
                        )
                ); ?>
                <div class="hero-items">
                    <div class="hero-item">
                        <span>
                            230+
                        </span>
                        <p>
                            <?php _e( 'Properties for sale' ); ?>
                        </p>
                    </div>
                    <div class="hero-item">
                        <span>
                           10
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