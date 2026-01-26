<?php
/**
 * Top title component
 *
 * @var string $h2 The main title text
 * @var string $desc The description text
 * @var string $href The link URL
 * @var string $link The link text
 */

$h2   = $args['h2'] ?? '';
$desc = $args['desc'] ?? '';
$href = $args['href'] ?? '#';
$link = $args['link'] ?? '';

if ( empty( $h2 ) ) {
    return;
}
?>
<div class="top-title">
    <div class="top-title-info">
        <h2><?php echo esc_html( $h2 ); ?></h2>
        <p><?php echo esc_html( $desc ); ?></p>
    </div>
    <a href="<?php echo esc_url( $href ); ?>">
        <?php echo esc_html( $link ); ?>
        <img src="<?php echo THEME_URL ?>/assets/img/link.svg" width="16" height="16"
             alt="<?php echo esc_html( $link ); ?>">
    </a>
</div>