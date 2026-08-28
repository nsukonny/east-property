<?php
/**
 * Component for check if user authorized and show account page. If user not authorized show login form
 */

use Entities\Estate_User;
use Entities\Property;
use Entities\Unit;

global $post, $current_user;

if ( ! empty( $post ) && 'reset-password' === $post->post_name ) {
	$key   = $_GET['key'] ?? '';
	$login = $_GET['login'] ?? '';

	if ( empty( $key ) || empty( $login ) ) {
		show_notify_error( 'account', 'expired_reset_key' );
	}

	$user = get_user_by( 'login', $login );
	if ( ! $user ) {
		show_notify_error( 'account', 'expired_reset_key' );
	}

	$valid_key = check_password_reset_key( $key, $login );
	if ( is_wp_error( $valid_key ) ) {
		show_notify_error( 'account', 'expired_reset_key' );
	}

	get_component_template(
		'account/reset-password',
		array(
			'user' => $user,
			'key'  => $key,
		),
	);

	return;
}

if ( ! is_user_logged_in() ) {
	$agencies_posts = get_posts(
		array(
			'post_type'      => 'agency',
			'posts_per_page' => 10,
		)
	);

	$user_roles = get_existing_user_roles();

	get_component_template(
		'account/login-form',
		array(
			'agencies_posts' => $agencies_posts,
			'user_roles'     => $user_roles,
		),
	);

	return;
}

$estate_user = new Estate_User( $current_user );
if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'add_unit', 'edit_unit' ) ) ) {
	$properties = get_properties( 5000, true );
	if ( ! empty( $properties['items'] ) ) {
		$properties = $properties['items'];
	}

	$unit_type_field = function_exists( 'get_field_object' ) ? get_field_object( 'field_694ea57c4ae1f' ) : null;

	$unit_type_choices = ! empty( $unit_type_field['choices'] ) && is_array( $unit_type_field['choices'] )
		? $unit_type_field['choices']
		: array();

	get_component_template(
		'account/edit-unit',
		array(
			'unit'              => isset( $_GET['id'] ) ? new Unit( $_GET['id'] ) : null,
			'properties'        => $properties,
			'unit_type_choices' => $unit_type_choices,
		)
	);

	return;
}

if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'add_property', 'edit_property' ) ) ) {
	$property_type_options  = function_exists( 'get_field_object' ) ? get_field_object( 'field_694ea826a1147' )['choices'] : null;
	$ownership_type_options = function_exists( 'get_field_object' ) ? get_field_object( 'field_694ea884a114c' )['choices'] : null;
	$developers             = get_posts(
		array(
			'post_type'      => 'developers',
			'posts_per_page' => - 1,
		)
	);

	get_component_template(
		'account/edit-property',
		array(
			'property'               => isset( $_GET['id'] ) ? new Property( $_GET['id'] ) : null,
			'locations'              => get_terms( 'location' ),
			'developers'             => $developers ?: array(),
			'property_type_options'  => $property_type_options,
			'ownership_type_options' => $ownership_type_options,
		)
	);

	return;
}

$agencies_posts = get_posts(
	array(
		'post_type'      => 'agency',
		'posts_per_page' => 10,
	)
);

if ( $estate_user->is_broker() || $estate_user->is_admin() ) {
	get_component_template(
		'account/profile',
		array(
			'agencies_posts'  => $agencies_posts,
			'user_units'      => core_get_current_user_units(),
			'user_properties' => core_get_current_user_properties(),
			'favourites'      => core_get_current_user_favorite_units(),
		)
	);

	return;
}

$user_favorite_units = get_user_meta( $current_user->ID, 'favorite_units', true );
get_component_template(
	'account/profile-client',
	array(
		'user_units' => core_get_current_user_favorite_units(),
	)
);
