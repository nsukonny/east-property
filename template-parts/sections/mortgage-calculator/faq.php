<?php

$faq_items = array(
	array(
		'question' => __('Can a non-resident get a mortgage in Dubai?', 'east-property'),
		'answer' => __('Yes. Non-residents can get a mortgage with a down payment from 40% and verified income from 10,000 USD per month.', 'east-property'),
		'opened' => true,
	),
	array(
		'question' => __('Is life insurance mandatory?', 'east-property'),
		'answer' => __('Yes, UAE banks require life insurance as a mandatory condition for securing a mortgage loan.', 'east-property'),
		'opened' => false,
	),
	array(
		'question' => __('Can I get a mortgage on an off-plan property?', 'east-property'),
		'answer' => __('Yes, mortgages are available for completed as well as off-plan projects from authorized developers with up to 50% financing.', 'east-property'),
		'opened' => false,
	),
	array(
		'question' => __('How long does the whole process take?', 'east-property'),
		'answer' => __('The entire process typically takes between 30 and 100 days from the initial application to the key handover and deed registration.', 'east-property'),
		'opened' => false,
	),
	array(
		'question' => __('What is pre-approval and how long is it valid?', 'east-property'),
		'answer' => __('Pre-approval is an initial assessment from a bank confirming the loan amount you qualify for. It is usually valid for 60 to 90 days.', 'east-property'),
		'opened' => false,
	),
	array(
		'question' => __('What costs are there besides the down payment?', 'east-property'),
		'answer' => __('Additional costs include the DLD fee (4% + 580 AED), agency commission (2%), bank processing fee (up to 1%), property valuation, and mortgage registration.', 'east-property'),
		'opened' => false,
	),
);
?>
<section class="mortgage-faq-section">
	<div class="container">
		<div class="mortgage-faq-wrapper">
			<h2 class="mortgage-faq-title">
				<?php _e('Questions and answers', 'east-property'); ?>
			</h2>

			<div class="mortgage-faq-list">
				<?php foreach ( $faq_items as $item ) : ?>
					<div class="dropdown mortgage-faq-item<?php echo ! empty( $item['opened'] ) ? ' opened' : ''; ?>">
						<button class="dropdown-button faq-question-btn" type="button" aria-expanded="<?php echo ! empty( $item['opened'] ) ? 'true' : 'false'; ?>">
							<span class="faq-question-text">
								<?php echo esc_html( $item['question'] ); ?>
							</span>
							<span class="faq-toggle-icon" aria-hidden="true"></span>
						</button>
						<div class="dropdown-content">
							<div class="dropdown-inner">
								<p><?php echo esc_html( $item['answer'] ); ?></p>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>