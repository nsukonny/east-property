<?php

$text     = $args['text'] ?? '';
$position = in_array( $args['position'] ?? 'top', array( 'top', 'bottom', 'left', 'right' ), true ) ? ( $args['position'] ?? 'top' ) : 'top';
$extra    = $args['class'] ?? '';
$classes  = trim( 'tooltip ' . $position . ' ' . $extra );
?>
<?php if ( ! empty( $text ) ) : ?>
<span class="<?php echo esc_attr( $classes ); ?>" role="tooltip">
	<button type="button" class="tooltip-btn" aria-label="<?php esc_attr_e( 'More info', 'east-property' ); ?>" aria-expanded="false">
		<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/info.svg' ); ?>" alt="" class="tooltip-icon" width="16" height="16" aria-hidden="true">
	</button>
	<span class="tooltip-bubble" role="status">
		<span class="tooltip-text"><?php echo esc_html( $text ); ?></span>
		<span class="tooltip-arrow" aria-hidden="true"></span>
	</span>
</span>
<?php endif; ?>
