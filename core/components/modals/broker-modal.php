<?php

use Entities\Estate_User;

if ( ! empty( $post ) || 'unit' === $post->post_type ) {
	$unit   = new \Entities\Unit( $post->ID );
	$broker = $unit->get_broker();
} else {
	$broker_wp_user = Estate_User::get_default_broker();
	$broker         = new Estate_User( $broker_wp_user );
}

$whatsapp_text = __( 'Hello, I am interested in property', 'east-property' );
if ( isset( $unit ) ) {
	$whatsapp_text .= ' - ' . $unit->get_url();
}
$whats_app_link = $broker?->get_whatsapp( '', $whatsapp_text ) ?: WHATS_APP_LINK;
$phone          = $broker?->get_phone( '', true ) ?: PROJECT_PHONE;
?>
<div class="modal-wrapper broker-modal" data-modal-id="broker-modal">
	<div class="modal">
		<div class="modal-info">
			<div class="modal-title">
				<h3>
					<?php _e( 'Contact broker', 'east-property' ); ?>
				</h3>
				<button class="modal-close" data-modal-close aria-label="Close">
					<img src="<?php echo THEME_URL; ?>/assets/img/close.svg" width="24" height="24" alt="Close icon">
				</button>
			</div>
			<div class="modal-info-inner">
				<p>
					<?php _e( 'You can get more information about the available properties, special deals and prices.',
						'east-property' ); ?>
				</p>
				<ul>
					<li>
						<img src="<?php echo THEME_URL; ?>/assets/img/key.svg" width="16" height="16" alt="vector icon">
						<?php _e( 'We work directly with brokers', 'east-property' ); ?>
					</li>
					<li>
						<img src="<?php echo THEME_URL; ?>/assets/img/secure.svg" width="16" height="16"
						     alt="vector icon">
						<?php _e( 'We facilitate the entire process for you', 'east-property' ); ?>
					</li>
					<li>
						<img src="<?php echo THEME_URL; ?>/assets/img/money.svg" width="16" height="16"
						     alt="vector icon">
						<?php _e( 'We don’t add anything on top of the price', 'east-property' ); ?>
					</li>
				</ul>
			</div>
			<div class="modal-links">
				<?php if ( ! empty( $phone ) ) { ?>
					<a href="tel:<?php echo esc_attr( $phone ); ?>" id="bm_phone" target="_blank"
					   rel="noopener noreferrer"
					   class="button sm orange">
						<img src="<?php echo THEME_URL; ?>/assets/img/phone.svg" width="16" height="16"
						     alt="vector link">
						<?php _e( 'Phone', 'east-property' ); ?>
					</a>
				<?php } ?>

				<?php if ( ! empty( $whats_app_link ) ) { ?>
					<a href="<?php echo $whats_app_link; ?>" id="bm_whatsapp" target="_blank" rel="noopener noreferrer"
					   class="button sm orange">
						<img src="<?php echo THEME_URL; ?>/assets/img/call.svg" width="16" height="16"
						     alt="vector link">
						<?php _e( 'WhatsApp', 'east-property' ); ?>
					</a>
				<?php } ?>
			</div>
		</div>
	</div>
</div>