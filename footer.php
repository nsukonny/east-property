</main>
<?php

/**
 * All list of modals
 * 'modals' => array(
 * 'image-modal',
 * //'desc-modal',
 * 'quote-modal',
 * 'create-modal',
 * 'signin-modal',
 * 'forgot-modal',
 * 'contact-manager-modal',
 * 'broker-modal',
 * 'boost-modal',
 * 'boost-info-modal',
 * 'map-coords-picker',
 * )
 * **/

$args['modals'] = $args['modals'] ?? array();
if ( ! is_user_logged_in() ) {
	$args['modals'][] = 'create-modal';
	$args['modals'][] = 'signin-modal';
	$args['modals'][] = 'forgot-modal';
}

get_template_part(
	'core/components/common/footer',
	null,
	$args
);
?>
</body>
</html>