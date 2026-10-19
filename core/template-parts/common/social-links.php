<?php
/**
 * Social links of the footer.
 *
 * Nothing is printed when the current language has no link filled in.
 */

$links = function_exists( 'core_social_links' ) ? core_social_links() : array();

if ( empty( $links ) ) {
	return;
}
?>
<div class="footer-social">
	<span class="footer-social-title"><?php esc_html_e( 'Follow us on social media', 'east-property' ); ?></span>
	<ul class="footer-social-list">
		<?php foreach ( $links as $link ) { ?>
			<li class="footer-social-item">
				<a class="footer-social-link footer-social-link--<?php echo esc_attr( $link['key'] ); ?>"
				   href="<?php echo esc_url( $link['url'] ); ?>"
				   target="_blank"
				   rel="noopener nofollow"
				   aria-label="<?php echo esc_attr( $link['label'] ); ?>"></a>
			</li>
		<?php } ?>
	</ul>
</div>
