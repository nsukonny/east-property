<?php
/**
 * Add Broker user role
 */
function register_broker_role(): void {
	$editor = get_role( 'editor' );
	$caps   = $editor ? $editor->capabilities : array();

	if ( empty( $caps ) ) {
		$caps = array(
			'read'         => true,
			'edit_posts'   => true,
			'upload_files' => true,
		);
	}

	add_role( 'broker', __( 'Broker', 'east-property' ), $caps );

	$broker = get_role( 'broker' );
	if ( $broker ) {
		$broker->add_cap( 'read' );
		$broker->add_cap( 'edit_posts' );
		$broker->add_cap( 'upload_files' );
		$broker->remove_cap( 'delete_pages' );
	}
}

function remove_broker_role(): void {
	remove_role( 'broker' );
}

add_action( 'after_switch_theme', 'register_broker_role' );

add_action( 'switch_theme', 'remove_broker_role' );