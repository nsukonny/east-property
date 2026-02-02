<?php
/**
 * Breadcrumb component
 *
 * @var array $args
 */

if ( is_front_page() ) {
    return;
}

if ( is_archive() ) {
    $current_page_title = post_type_archive_title( '', false );
} else {
    $current_page_title = get_the_title();
}

?>
<nav class="woocommerce-breadcrumb" aria-label="Breadcrumb">
    <a href="<?php echo home_url( '/' ); ?>" class="breadcrumb-link"><?php _e( 'Home' ); ?></a>
    <span class="delimiter">/</span>
    <span class="breadcrumb-current"><?php echo esc_html( $current_page_title ); ?></span>
</nav>