<?php

/**
 * Add Client user role
 */
function register_client_role(): void {
	add_role( 'client', __( 'Client' , 'east-property' ) );

	$client = get_role( 'client' );
	if ( $client ) {
		$client->add_cap( 'read' );
		$client->add_cap( 'edit_posts' );
		$client->add_cap( 'upload_files' );
		$client->remove_cap( 'delete_pages' );
	}
}

function remove_client_role(): void {
	remove_role( 'client' );
}

add_action( 'after_switch_theme', 'register_client_role' );

add_action( 'switch_theme', 'remove_client_role' );
