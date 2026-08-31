<?php
/**
 * Navigation menus
 */

use Entities\Estate_User;

global $current_user;

if ( ! has_nav_menu( 'header_menu' ) ) {
	return;
}

if ( empty( $current_user ) || 0 === $current_user->ID ) {
	$current_user = null;
}

$estate_user = $current_user ? new Estate_User( $current_user ) : null;
$logout_url  = wp_logout_url( core_get_account_page_url() );
$switcher    = function_exists( 'core_get_language_switcher' ) ? core_get_language_switcher() : array();
?>
<nav id="header-nav" class="header-nav">
	<div class="header-nav-drawer">
		<div class="header-nav-header mobile">
			<a href="/" class="header-nav-logo">
				<img src="<?php echo THEME_URL; ?>/assets/img/logo.svg" width="120" height="45" alt="East Property">
			</a>
			<button class="header-nav-close" type="button" aria-label="<?php _e( 'Close menu', 'east-property' ); ?>">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
			</button>
		</div>

		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'header_menu',
				'container'      => false,
				'menu_class'     => 'header-menu-list',
				'fallback_cb'    => false,
			)
		);
		?>

		<?php if ( ! empty( $switcher ) ) : ?>
			<div class="header-nav-languages mobile">
				<ul class="language-switcher">
					<?php foreach ( $switcher as $language ) { ?>
						<a lang="<?php echo esc_attr( $language['locale'] ); ?>"
						   hreflang="<?php echo esc_attr( $language['locale'] ); ?>"
						   href="<?php echo esc_url( $language['url'] ); ?>">
							<?php require THEME_PATH . '/assets/img/lang/earth.svg'; ?>
							<span><?php echo esc_html( $language['label'] ); ?></span>
						</a>
					<?php } ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php if ( null !== $estate_user ) : ?>
			<div class="header-nav-footer mobile">
				<a class="button link logout" href="<?php echo esc_url( $logout_url ); ?>">
					<?php esc_html_e( 'Logout' , 'east-property' ); ?>
				</a>
			</div>
		<?php else : ?>
			<div class="header-nav-auth mobile">
				<button class="button black sm full-width" type="button" data-modal-open="signin-modal">
					<?php esc_html_e( 'Sign in', 'east-property' ); ?>
				</button>
			</div>
		<?php endif; ?>

		<div class="header-nav-bottom-img mobile">
			<img src="<?php echo THEME_URL; ?>/assets/img/hero-image.png" alt="" loading="lazy">
		</div>
	</div>
</nav>