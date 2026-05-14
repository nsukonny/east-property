<?php

use Entities\Broker;

$color = $args['color'] ?? '';
?>
<header class="header <?php echo esc_attr( $color ); ?>">
	<div class="container">
		<div class="header-wrapper">
			<a href="/" class="header-logo">
				<img src="<?php echo THEME_URL; ?>/assets/img/logo.svg" width="132" height="50" alt="Vector logotype">
			</a>
			<?php get_template_part( 'core/components/common/nav' ); ?>
			<div class="header-actions">
				<?php if ( ! is_user_logged_in() ) { ?>
					<button class="header-signin" type="button" data-modal-open="signin-modal">
						<?php esc_html_e( 'Sign in' ); ?>
					</button>
					<button class="button black sm header-login" data-modal-open="create-modal">
						<img src="<?php echo esc_url( THEME_URL ); ?>/assets/img/user.svg"
						     width="16" height="16" alt="<?php esc_html_e( 'Create' ); ?>">
						<?php esc_html_e( 'Create Account' ); ?>
					</button>
				<?php } else {
					$current_user   = wp_get_current_user();
					$current_broker = new Broker( $current_user );
					$boost_points   = $current_broker->get_boost_points();
					if ( 250 > $boost_points ) {
						$boost_points .= ' / ' . __( 'Top up' );
					}
					?>
					<a href="<?php echo home_url( 'account' ); ?>" class="button sm orange green boost-points">
						<img src="<?php echo esc_url( THEME_URL ); ?>/assets/img/star.svg"
						     alt="<?php esc_html_e( 'Boost Points' ); ?>">
						<span class="count"><?php echo esc_attr( $boost_points ); ?></span>
					</a>
					<a class="button black sm header-login" href="<?php echo esc_url( home_url( 'account' ) ); ?>">
						<img src="<?php echo esc_url( THEME_URL ); ?>/assets/img/user.svg" width="16" height="16"
						     alt="">
						<?php echo esc_html( $current_user->display_name ); ?>
					</a>
				<?php } ?>
				<button class="burger-button" type="button" aria-label="<?php _e( 'Open menu' ); ?>"
				        aria-expanded="false"
				        aria-controls="header-nav">
					<span class="line"></span>
				</button>
			</div>
		</div>
		<div class="header-overlay" data-header-overlay></div>
	</div>
</header>
