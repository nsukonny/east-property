<?php
/**
 * Off-plan property mortgage section for mortgage calculator
 */

$developers = array(
	array( 'name' => 'Emaar', 'img' => 'emaar.png' ),
	array( 'name' => 'Dubai Properties', 'img' => 'dubai-prop.png' ),
	array( 'name' => 'Nakheel', 'img' => 'nakheel.png' ),
	array( 'name' => 'Majid Al Futtaim', 'img' => 'majid.png' ),
	array( 'name' => 'Sobha', 'img' => 'sobha.png' ),
	array( 'name' => 'Aldar', 'img' => 'aldar.png' ),
	array( 'name' => 'Ellington Properties', 'img' => 'ellington.png' ),
	array( 'name' => 'Wasl', 'img' => 'wasl.png' ),
	array( 'name' => 'Damac', 'img' => 'damac.png' ),
	array( 'name' => 'Omniyat', 'img' => 'omniyat.png' ),
	array( 'name' => 'Binghatti', 'img' => 'binghatti.png' ),
);
?>
<section class="mortgage-offplan-section">
	<div class="container">
		<div class="mortgage-offplan-wrapper">
			<div class="mortgage-offplan-grid">
				<div class="mortgage-offplan-info">
					<h2 class="mortgage-offplan-title">
						<?php _e( 'Mortgage on an off-plan property', 'east-property' ); ?>
					</h2>

					<div class="mortgage-offplan-text">
						<p>
							<?php
							printf(
								/* translators: %s: 50% complete */
								__( 'An off-plan mortgage becomes available once the building is more than %s, the buyer has paid over 50%% of the price, and the developer is accredited by the bank.', 'east-property' ),
								'<b>' . __( '50% complete', 'east-property' ) . '</b>'
							);
							?>
						</p>
						<p>
							<?php _e( 'Before that stage, buyers use the developer’s payment plan.', 'east-property' ); ?>
						</p>
					</div>

					<div class="mortgage-offplan-btn">
						<?php
						get_template_part(
							'core/components/ui/button',
							null,
							array(
								'class' => 'button sm gray',
								'text'  => __( 'Learn more', 'east-property' ),
								'link'  => core_home_url( '/off-plan/' ),
							)
						);
						?>
					</div>
				</div>

				<div class="mortgage-offplan-logos">
					<div class="offplan-logos-grid">
						<?php foreach ( $developers as $dev ) : ?>
							<div class="offplan-logo-item">
								<img src="<?php echo esc_url( THEME_URL . '/assets/img/' . $dev['img'] ); ?>"
								     alt="<?php echo esc_attr( $dev['name'] ); ?>"
								     loading="lazy">
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
