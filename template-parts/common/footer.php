<footer class="footer">
	<div class="container">
		<div class="footer-wrapper">
			<div class="footer-inner">
				<div class="footer-info">
					<?php get_template_part( 'core/components/common/subscribe-form/subscribe-form' ); ?>
					<a class="footer-logo" href="/">
						<img class="footer-logo" src="<?php echo THEME_URL; ?>/assets/img/logo.svg" width="132"
						     height="50" alt="Vector logotype">
					</a>
					<div class="footer-block">
						<span>Address</span>
						<address>
							Sheikh Zayed Road, Building 25 <br>
							Al Quoz 3 <br>
							Dubai
						</address>
					</div>
					<div class="footer-block">
						<span>Live Support?</span>
						<a href="mailto:support@eastproperty.com">
							support@eastproperty.com
						</a>
					</div>
				</div>
				<div class="footer-menu">
					<nav>
						<?php
						if ( has_nav_menu( 'footer_menu_popular' ) ) {
							?>
							<ul class="menu">
								<li class="menu-item">
									<span><?php _e( 'Popular Search' ); ?></span>
								</li>
								<?php
								wp_nav_menu( array(
									'theme_location' => 'footer_menu_popular',
									'container'      => false,
									'items_wrap'     => '%3$s',
									'depth'          => 1,
									'fallback_cb'    => false,
								) );
								?>
							</ul>
						<?php } ?>

						<?php
						if ( has_nav_menu( 'footer_menu_discovery' ) ) {
							?>
							<ul class="menu">
								<li class="menu-item">
									<span><?php _e( 'Discovery' ); ?></span>
								</li>
								<?php
								wp_nav_menu( array(
									'theme_location' => 'footer_menu_discovery',
									'container'      => false,
									'items_wrap'     => '%3$s',
									'depth'          => 1,
									'fallback_cb'    => false,
								) );
								?>
							</ul>
						<?php } ?>
					</nav>
					<nav>
						<?php
						if ( has_nav_menu( 'footer_menu_quick_links' ) ) {
							?>
							<ul class="menu">
								<li class="menu-item">
									<span><?php _e( 'Quick Links' ); ?></span>
								</li>
								<?php
								wp_nav_menu( array(
									'theme_location' => 'footer_menu_quick_links',
									'container'      => false,
									'items_wrap'     => '%3$s',
									'depth'          => 1,
									'fallback_cb'    => false,
								) );
								?>
							</ul>
						<?php } ?>
					</nav>
				</div>
			</div>
			<div class="copyright">
				<span><?php esc_html_e( '© East Property – All rights reserved' ); ?></span>
			</div>
		</div>
	</div>
</footer>
<?php get_template_part( 'core/components/common/notifications/notifications' ); ?>