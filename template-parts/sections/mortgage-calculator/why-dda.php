<?php

$why_items = array(
	array(
		'title' => __( 'LTV / AECB / DBR Expertise', 'east-property' ),
		'desc'  => __( 'In-house mortgage advisory department', 'east-property' ),
	),
	array(
		'title' => __( 'Direct Negotiations with Banks', 'east-property' ),
		'desc'  => __( 'Tailored terms based on your profile', 'east-property' ),
	),
	array(
		'title' => __( 'Free of Charge', 'east-property' ),
		'desc'  => __( 'Free consultation and pre-approval', 'east-property' ),
	),
	array(
		'title' => __( 'RU / EN / AR', 'east-property' ),
		'desc'  => __( 'Response within 15 minutes', 'east-property' ),
	),
	array(
		'title' => __( 'We Work with Non-Residents', 'east-property' ),
		'desc'  => __( 'Full end-to-end support for purchases from abroad', 'east-property' ),
	),
	array(
		'title' => __( 'Islamic Financing', 'east-property' ),
		'desc'  => __( 'Murabaha · Ijarah · Musharakah → separate page', 'east-property' ),
	),
);
?>
<section class="mortgage-why-section">
	<div class="container">
		<div class="mortgage-why-wrapper">
			<h2 class="mortgage-why-title">
				<?php _e( 'Why DDA', 'east-property' ); ?>
			</h2>

			<div class="mortgage-why-grid">
				<?php foreach ( $why_items as $item ) : ?>
					<div class="mortgage-why-item">
						<h3 class="why-item-title">
							<?php echo esc_html( $item['title'] ); ?>
						</h3>
						<p class="why-item-desc">
							<?php echo esc_html( $item['desc'] ); ?>
						</p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
