<?php

/**
 * Footer template part
 */
$modals = $args['modals'] ?? array();

get_template_part( 'core/components/common/cookies/cookies' );

get_component_template( 'common/footer' );

if ( ! empty( $modals ) && is_array( $modals ) ) {
	foreach ( $modals as $modal ) {
		get_template_part( 'core/components/modals/' . $modal );
	}
}

wp_footer();