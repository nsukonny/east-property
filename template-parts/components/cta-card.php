<?php
/**
 * Call to action card component
 *
 * @param string $class Additional classes.
 */
$class       = $args['class'] ?? '';
$image_src   = $args['image_src'] ?? THEME_URL . '/assets/img/cta1.jpg';
$title       = $args['title'] ?? '';
$description = $args['description'] ?? '';
$link        = $args['link'] ?? '#';

if ( empty( $title ) ) {
    return;
}
?>
<div class="cta-card <?php echo esc_attr( $class ); ?>">
    <div class="cta-card-img">
        <img src="<?php echo esc_url( $image_src ); ?>" alt="Картинка">
    </div>
    <div class="cta-card-info">
        <div class="cta-card-title">
            <?php echo esc_html( $title ); ?>
        </div>
        <p>
            <?php echo esc_html( $description ); ?>
        </p>
        <a href="<?php echo esc_url( $link ); ?>">
            <?php _e( 'Показать все' ); ?>
        </a>
    </div>
</div>