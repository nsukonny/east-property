<?php
/**
 * Button component template
 */

$class = $args['class'] ?? '';
$src   = $args['src'] ?? '';
$alt   = $args['alt'] ?? '';
$text  = $args['text'] ?? '';
$url   = $args['link'] ?? '';
?>

<?php if ( ! empty( $url ) ) { ?>
    <a class="button <?php echo esc_attr( $class ); ?>" href="<?php echo esc_url( $url ); ?>">
        <?php if ( ! empty( $src ) ) { ?>
            <img src="<?php echo esc_attr( $src ); ?>" width="16" height="16" alt="<?php echo esc_attr( $alt ); ?>">
        <?php } ?>
        <?php echo esc_html( $text ); ?>
    </a>
<?php } else { ?>
    <button class="button <?php echo esc_attr( $class ); ?>">
        <?php if ( ! empty( $src ) ) { ?>
            <img src="<?php echo esc_attr( $src ); ?>" width="16" height="16" alt="<?php echo esc_attr( $alt ); ?>">
        <?php } ?>
        <?php echo esc_html( $text ); ?>
    </button>
<?php } ?>