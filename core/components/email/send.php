<?php
/**
 * Send email notification
 */

$email    = $args['email'] ?? '';
$subject  = $args['subject'] ?? '';
$content  = $args['content'] ?? '';
$template = $args['template'] ?? 'base';

if ( empty( $email ) || empty( $subject ) || empty( $content ) ) {
	return;
}

ob_start();
get_component_template(
	'email/' . $template,
	array(
		'subject'   => $subject,
		'content'   => $content,
		'site_name' => PROJECT_NAME,
		'domain'    => $_SERVER['HTTP_HOST'] ?? core_home_url(),
	)
);
$html = ob_get_clean();
if ( empty( $html ) ) {
	return;
}

$headers = array(
	'Content-Type: text/html; charset=UTF-8',
);

wp_mail( $email, $subject, $html, $headers );
