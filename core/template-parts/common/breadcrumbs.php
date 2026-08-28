<?php
/**
 * Breadcrumbs template part
 */

$links = $args['links'] ?? array();
$type  = $args['type'] ?? '';

if ( empty( $links ) ) {
	return;
}
?>
<nav class="woocommerce-breadcrumb <?php echo esc_attr( $type ); ?>" aria-label="Breadcrumb">
	<a href="<?php echo core_home_url( '/' ); ?>" class="breadcrumb-link"><?php _e( 'Home' , 'east-property' ); ?></a>

	<?php foreach ( $links as $link ) { ?>
		<span class="delimiter">/</span>
		<?php if ( ! empty( $link['url'] ) ) { ?>
			<a href="<?php echo esc_url( $link['url'] ); ?>"
			   class="breadcrumb-link"><?php echo esc_html( $link['title'] ) ?></a>
		<?php } else { ?>
			<span class="breadcrumb-current"><?php echo esc_html( $link['title'] ); ?></span>
		<?php } ?>
	<?php } ?>
</nav>