<?php
/**
 * Gallery Modal Template
 */

$gallery = $args['gallery'] ?? null;
if ( empty( $gallery ) ) {
	return;
}

get_component_template( 'modals/gallery-modal', array( 'gallery' => $gallery ) );
