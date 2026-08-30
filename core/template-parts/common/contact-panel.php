<?php
$variant      = $args['variant'] ?? 'default';
$title        = $args['title'] ?? '';
$description  = $args['description'] ?? '';
$phone        = $args['phone'] ?? '';
$phone_clean  = $phone ? preg_replace( '/[^0-9+]/', '', $phone ) : '';
$button_text  = $args['button_text'] ?? '';
$button_modal = $args['modal'] ?? 'broker-modal';
$button_link  = $args['link'] ?? '';
$button_class = $args['button_class'] ?? 'orange sm';
?>
<div class="contact-panel<?php echo ( 'mortgage' === $variant ) ? ' mortgage-panel' : ''; ?>" id="broker-panel">
	<div class="contact-panel-inner">
		<div class="contact-panel-left">
			<?php if ( 'mortgage' === $variant ) : ?>
				<?php if ( ! empty( $title ) ) : ?>
					<h4 class="contact-panel-title"><?php echo esc_html( $title ); ?></h4>
				<?php endif; ?>
				<p>
					<?php echo esc_html( ! empty( $description ) ? $description : __( 'Free consultation and pre-approval. A DDA specialist will contact you and advise on the next step.', 'east-property' ) ); ?>
				</p>
			<?php else : ?>
				<p>
					<?php _e( 'Contact broker to learn more and get special offers.', 'east-property' ); ?>
				</p>
			<?php endif; ?>
		</div>
		<div class="contact-panel-right">
			<?php
			//TODO Add save button to bookmark
			//			get_template_part( 'core/components/ui/button', null,
			//				array(
			//					'class' => 'gray sm',
			//					'text'  => __( 'Save' ),
			//					'src'   => THEME_URL . '/assets/img/bookmark.svg',
			//					'alt'   => __( 'Save' ),
			//				)
			//			);

			if ( 'mortgage' === $variant ) {
				if ( ! empty( $phone ) ) {
					?>
					<a href="tel:<?php echo esc_attr( $phone_clean ); ?>" class="contact-panel-phone">
						<span class="flag-icon">🇦🇪</span>
						<span><?php echo esc_html( $phone ); ?></span>
					</a>
					<?php
				}

				get_template_part(
					'core/components/ui/button',
					null,
					array(
						'class' => $button_class,
						'text'  => ! empty( $button_text ) ? $button_text : __( 'Get pre-approved', 'east-property' ),
						'modal' => $button_modal,
						'link'  => $button_link,
					)
				);
			} else {
				get_template_part(
					'core/components/ui/button',
					null,
					array(
						'class' => 'orange sm',
						'text'  => __( 'Contact broker', 'east-property' ),
						'modal' => 'broker-modal',
					)
				);
			}
			?>
		</div>
	</div>
</div>