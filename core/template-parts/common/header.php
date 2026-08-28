<?php

use Entities\Estate_User;

global $current_user;

$color       = $args['color'] ?? '';
$estate_user = new Estate_User( $current_user );
$lang        = null;
if ( function_exists( 'pll_current_language' ) ) {
	$lang = pll_current_language();
}
$all_languages = get_all_languages();
?>
<header class="header <?php echo esc_attr( $color ); ?>">
	<div class="container">
		<div class="header-wrapper">
			<a href="/" class="header-logo">
				<img src="<?php echo THEME_URL; ?>/assets/img/logo.svg" width="132" height="50"
				     alt="<?php esc_html_e( 'Vector logotype', 'east-property' ); ?>">
			</a>
			<?php get_template_part( 'core/components/common/nav' ); ?>
			<div class="header-actions">
				<?php if ( function_exists( 'pll_the_languages' ) ) : ?>
					<ul class="language-switcher">
						<?php foreach ( $all_languages as $language ) {
							if ( $language['slug'] === $lang ) {
								continue;
							}

							$label = 'ru' === $language['slug'] ? 'РУ' : strtoupper( $language['slug'] );
							?>
							<a lang="<?php echo esc_attr( $language['locale'] ); ?>"
							   hreflang="<?php echo esc_attr( $language['locale'] ); ?>"
							   href="<?php echo esc_url( $language['url'] ); ?>">
								<?php require THEME_PATH . '/assets/img/lang/earth.svg'; ?>
								<span><?php echo esc_html( $label ); ?></span>
							</a>
						<?php } ?>
					</ul>
				<?php endif; ?>

				<?php if ( ! is_user_logged_in() ) { ?>
					<button class="header-signin" type="button" data-modal-open="signin-modal">
						<?php esc_html_e( 'Sign in', 'east-property' ); ?>
					</button>
					<button class="button black sm header-login" data-modal-open="create-modal">
						<img src="<?php echo esc_url( THEME_URL ); ?>/assets/img/user.svg"
						     width="16" height="16" alt="<?php esc_html_e( 'Create', 'east-property' ); ?>">
						<?php esc_html_e( 'Create Account', 'east-property' ); ?>
					</button>
				<?php } else {
					if ( $estate_user->is_broker() ) {
						$boost_points = $estate_user->get_boost_points();
						if ( 250 > $boost_points ) {
							$boost_points .= ' / ' . __( 'Top up', 'east-property' );
						}
						?>
						<a href="<?php echo pll_home_url(); ?>account" class="button sm orange green boost-points">
							<img src="<?php echo esc_url( THEME_URL ); ?>/assets/img/star.svg"
							     alt="<?php esc_html_e( 'Boost Points', 'east-property' ); ?>">
							<span class="count"><?php echo esc_attr( $boost_points ); ?></span>
						</a>
					<?php } ?>
					<a class="button black sm header-login" href="<?php echo pll_home_url(); ?>account">
						<img src="<?php echo esc_url( THEME_URL ); ?>/assets/img/user.svg" width="16" height="16"
						     alt="">
						<?php echo esc_html( $current_user->display_name ); ?>
					</a>
				<?php } ?>
				<button class="burger-button" type="button" aria-label="<?php _e( 'Open menu', 'east-property' ); ?>"
				        aria-expanded="false"
				        aria-controls="header-nav">
					<span class="line"></span>
				</button>
			</div>
		</div>
		<div class="header-overlay" data-header-overlay></div>
	</div>
</header>
