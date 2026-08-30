<?php
//Ask user for cookies

?>
<div class="cookie-notice" id="cookie-notice" style="display: none;">
	<div class="cookie-notice__content">
		<div class="cookie-notice__text">
			<strong><?php esc_html_e( 'We use cookies' , 'east-property' ); ?></strong>
			<p>
				<?php esc_html_e( 'We use cookies to improve your browsing experience, analyze site traffic,
								and personalize content. By clicking “Accept”, you agree to our use of cookies.' , 'east-property' ); ?>
			</p>
		</div>

		<div class="cookie-notice__actions">
			<button type="button" class="cookie-btn cookie-btn--secondary" id="cookie-decline">
				<?php esc_html_e( 'Decline' , 'east-property' ); ?>
			</button>
			<button type="button" class="cookie-btn cookie-btn--primary" id="cookie-accept">
				<?php esc_html_e( 'Accept' , 'east-property' ); ?>
			</button>
		</div>
	</div>
</div>