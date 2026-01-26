<?php
/**
 * Thumbs Slider Template
 */
$gallery = $args['gallery'] ?? array();
if ( empty( $gallery ) ) {
    return;
}
?>
<div class="swiper main-swiper">
    <div class="swiper-wrapper">
        <?php foreach ( $gallery as $image ) { ?>
            <div class="swiper-slide"><img src="<?php echo esc_url( $image['url'] ); ?>" alt=""></div>
        <?php } ?>
    </div>
    <button class="swiper-prev"><img src="<?php echo THEME_URL; ?>/assets/img/swiper-arr.svg" width="16"
                                     height="16" alt="<?php _e( 'Prev' ); ?>"></button>
    <button class="swiper-next"><img src="<?php echo THEME_URL; ?>/assets/img/swiper-arr.svg" width="16"
                                     height="16" alt="<?php _e( 'Next' ); ?>"></button>
</div>
<div class="swiper thumbs-swiper-container">
    <div class="swiper-wrapper">
        <?php foreach ( $gallery as $image ) { ?>
            <div class="swiper-slide"><img src="<?php echo esc_url( $image['sizes']['large'] ); ?>" alt=""></div>
        <?php } ?>
    </div>
</div>