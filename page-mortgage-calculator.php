<?php
/**
 * Template Name: Mortgage Calculator
 */

get_header(null, array('color' => 'white'));

if (404 === get_query_var('pagename') || is_404()) {
	get_template_part('404');

	return;
}

get_template_part('template-parts/sections/mortgage-calculator/hero');
get_template_part('template-parts/sections/mortgage-calculator/calculator');
get_template_part('template-parts/sections/mortgage-calculator/entry-costs');
get_template_part('template-parts/sections/mortgage-calculator/matched-units');
get_template_part('template-parts/sections/mortgage-calculator/off-plan');
get_template_part('template-parts/sections/mortgage-calculator/why-dda');
get_template_part('template-parts/sections/mortgage-calculator/faq');

get_template_part(
	'core/components/ui/contact-panel',
	null,
	array(
		'variant'     => 'mortgage',
		'title'       => __( 'Ready to find out the exact terms for your deal?', 'east-property' ),
		'description' => __( 'Free consultation and pre-approval. A DDA specialist will contact you and advise on the next step.', 'east-property' ),
		'phone'       => '+971 56 680 9684',
		'button_text' => __( 'Get pre-approved', 'east-property' ),
		'modal'       => 'broker-modal',
	)
);

get_footer();