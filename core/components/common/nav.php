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
?>
<nav id="header-nav" class="header-nav">
	<ul class="menu">
		<li class="menu-item mobile">
			<img src="<?php echo THEME_URL; ?>/assets/img/logo.svg" alt="">
		</li>
		<li class="menu-item">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'header_menu',
					'container'      => false,
					'menu_class'     => '',
				)
			);
			?>
		</li>
		<?php
		if ( null !== $estate_user ) {
			?>
			<li class="menu-item mobile">
				<a class="button link logout"
				   href="<?php echo esc_url( $logout_url ); ?>">
					<?php esc_html_e( 'Logout' , 'east-property' ); ?>
				</a>
			</li>
			<?php
		}
		?>
	</ul>
	<ul class="menu">
		<li class="menu-item mobile stick-bottom">
			<img src="<?php echo THEME_URL; ?>/assets/img/hero-image.png" alt="">
		</li>
	</ul>
</nav>