<?php
/**
 * Button component template
 */

$class  = $args['class'] ?? '';
$src    = $args['src'] ?? '';
$alt    = $args['alt'] ?? '';
$text   = $args['text'] ?? '';
$url    = $args['link'] ?? '';
$modal  = $args['modal'] ?? false;
$target = $args['target'] ?? '';
$rel    = $args['rel'] ?? '';
?>

<?php if ( ! empty( $url ) ) { ?>
    <a class="button <?php echo esc_attr( $class ); ?>" href="<?php echo esc_url( $url ); ?>"
            <?php if ( ! empty( $target ) ) {
                echo 'target="' . $target . '"';
            } ?>
            <?php if ( ! empty( $rel ) ) {
                echo 'rel="' . $rel . '"';
            } ?>
    >
        <?php if ( ! empty( $src ) ) { ?>
            <img src="<?php echo esc_attr( $src ); ?>" width="16" height="16" alt="<?php echo esc_attr( $alt ); ?>">
        <?php } ?>
        <?php echo esc_html( $text ); ?>
    </a>
<?php } else { ?>
    <button class="button <?php echo esc_attr( $class ); ?>"
            <?php if ( ! empty( $modal ) ) { ?>data-modal-open="<?php echo esc_attr( $modal ); ?>"<?php } ?>>
        <?php if ( ! empty( $src ) ) { ?>
            <img src="<?php echo esc_attr( $src ); ?>" width="16" height="16" alt="<?php echo esc_attr( $alt ); ?>">
        <?php } ?>
        <?php echo esc_html( $text ); ?>
    </button>
<?php } ?>