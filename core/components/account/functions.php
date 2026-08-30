<?php
/**
 * Account component functions.
 */

use Entities\Estate_User;
use Entities\Property;
use Entities\Unit;

/**
 * Get account page URL.
 *
 * @param array $args Query args.
 *
 * @return string
 */
function core_get_account_page_url( array $args = array() ): string {
	$page = get_page_by_path( 'account' );
	$url  = $page ? get_permalink( $page ) : core_home_url( '/account/' );

	if ( ! empty( $args ) ) {
		$url = add_query_arg( $args, $url );
	}

	return $url;
}

/**
 * Validate redirect URL.
 *
 * @param string $redirect_url Requested redirect URL.
 *
 * @return string
 */
function core_validate_account_redirect_url( string $redirect_url = '' ): string {
	$redirect_url = trim( $redirect_url );

	return wp_validate_redirect( $redirect_url, core_get_account_page_url() );
}

/**
 * Encode messages for redirect query args.
 *
 * @param array $messages Messages list.
 *
 * @return string
 */
function core_encode_account_messages( array $messages ): string {
	$messages = array_filter( array_map( 'sanitize_text_field', $messages ) );

	return rawurlencode( wp_json_encode( array_values( $messages ) ) );
}

/**
 * Redirect back to account page.
 *
 * @param string $action Active page action.
 * @param array $errors Error messages.
 * @param array $notices Success messages.
 * @param array $args Additional query args.
 *
 * @return void
 */
function core_account_redirect(
	string $action,
	array $errors = array(),
	array $notices = array(),
	array $args = array()
): void {
	$query_args = array_merge(
		array(
			'action' => $action,
		),
		$args
	);

	if ( ! empty( $errors ) ) {
		$query_args['status'] = 'error';
		$query_args['error']  = core_encode_account_messages( $errors );
	}

	if ( ! empty( $notices ) ) {
		$query_args['status'] = 'error';
		$query_args['notice'] = core_encode_account_messages( $notices );
	}

	wp_safe_redirect( core_get_account_page_url( $query_args ) );
	exit;
}

/**
 * Get active account tab.
 *
 * @return string
 */
function core_get_account_active_tab(): string {
	$allowed_tabs = array( 'login', 'register', 'units', 'add_unit', 'add_property', 'account', 'projects' );
	$default_tab  = is_user_logged_in() ? 'units' : 'login';
	$tab          = sanitize_key( $_GET['tab'] ?? $default_tab );

	if ( ! in_array( $tab, $allowed_tabs, true ) ) {
		return $default_tab;
	}

	if ( ! is_user_logged_in() && in_array( $tab, array( 'units', 'add_unit', 'account' ), true ) ) {
		return 'login';
	}

	return $tab;
}

/**
 * Get available unit type choices.
 *
 * @return array
 */
function core_get_unit_type_choices(): array {
	$field = function_exists( 'get_field_object' ) ? get_field_object( 'field_694ea57c4ae1f' ) : null;

	if ( ! empty( $field['choices'] ) && is_array( $field['choices'] ) ) {
		return $field['choices'];
	}

	return array(
		'apartment' => __( 'Apartments', 'east-property' ),
		'villa'     => __( 'Villas', 'east-property' ),
		'townhouse' => __( 'Townhouses', 'east-property' ),
		'penthouse' => __( 'Penthouses', 'east-property' ),
		'house'     => __( 'Houses', 'east-property' ),
		'office'    => __( 'Offices', 'east-property' ),
	);
}

/**
 * Get units created by current user.
 *
 * @param int $limit Items per page.
 *
 * @return array
 */
function core_get_current_user_units( int $limit = PROPERTIES_PER_PAGE ): array {
	if ( ! is_user_logged_in() ) {
		return array(
			'items' => array(),
			'total' => 0,
		);
	}

	$current_page = pagination_get_current_page();
	$current_page = $current_page > 0 ? $current_page : 1;

	$query = new WP_Query(
		array(
			'post_type'      => 'unit',
			'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'posts_per_page' => $limit,
			'paged'          => $current_page,
			'author'         => get_current_user_id(),
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	$items = array();
	foreach ( $query->posts as $unit_post ) {
		$items[] = new Unit( $unit_post );
	}

	return array(
		'items' => $items,
		'total' => (int) $query->found_posts,
	);
}

/**
 * Get favorite units
 *
 * @param int $limit Items per page.
 *
 * @return array
 */
function core_get_current_user_favorite_units( int $limit = PROPERTIES_PER_PAGE ): array {
	if ( ! is_user_logged_in() ) {
		return array(
			'items' => array(),
			'total' => 0,
		);
	}

	$current_page = pagination_get_current_page();
	$current_page = $current_page > 0 ? $current_page : 1;

	$favorite_unit_ids = get_user_meta( get_current_user_id(), 'favorite_units', true );
	if ( empty( $favorite_unit_ids ) ) {
		return array(
			'items' => array(),
			'total' => 0,
		);
	}

	$items = array();
	foreach ( $favorite_unit_ids as $unit_id ) {
		$items[] = new Unit( $unit_id );
	}

	return array(
		'items' => $items,
		'total' => (int) count( $favorite_unit_ids ),
	);
}

/**
 * Get properties created by current user.
 *
 * @param int $limit
 *
 * @return array
 */
function core_get_current_user_properties( int $limit = PROPERTIES_PER_PAGE ): array {
	if ( ! is_user_logged_in() ) {
		return array(
			'items' => array(),
			'total' => 0,
		);
	}

	$current_page = pagination_get_current_page();
	$current_page = $current_page > 0 ? $current_page : 1;

	$query = new WP_Query(
		array(
			'post_type'      => 'property',
			'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'posts_per_page' => $limit,
			'paged'          => $current_page,
			'author'         => get_current_user_id(),
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	$items = array();
	foreach ( $query->posts as $property_post ) {
		$items[] = new Property( $property_post );
	}

	return array(
		'items' => $items,
		'total' => (int) $query->found_posts,
	);
}

/**
 * Build unique username from provided base.
 *
 * @param string $base Username base.
 *
 * @return string
 */
function core_build_unique_username( string $base ): string {
	$base = sanitize_user( $base, true );
	if ( empty( $base ) ) {
		$base = 'user';
	}

	$username = $base;
	$index    = 1;
	while ( username_exists( $username ) ) {
		++ $index;
		$username = $base . $index;
	}

	return $username;
}

/**
 * Resolve user login value from username or email.
 *
 * @param string $user_login Username or email.
 *
 * @return string
 */
function core_resolve_user_login( string $user_login ): string {
	if ( is_email( $user_login ) ) {
		$user = get_user_by( 'email', $user_login );
		if ( $user ) {
			return (string) $user->user_login;
		}
	}

	return $user_login;
}

/**
 * Handle frontend login.
 *
 * @return void
 */
function core_handle_account_login(): void {
	$nonce = $_POST['core_account_login_nonce'] ?? '';
	if ( ! wp_verify_nonce( $nonce, 'core_account_login' ) ) {
		show_notify_error( 'account', 'security_error' );
	}

	$user_login = sanitize_text_field( wp_unslash( $_POST['log'] ?? '' ) );
	$password   = (string) wp_unslash( $_POST['pwd'] ?? '' );
	$remember   = ! empty( $_POST['rememberme'] );

	if ( empty( $user_login ) || empty( $password ) ) {
		show_notify_error( 'account', 'credentials_empty' );
	}

	$user = wp_signon(
		array(
			'user_login'    => core_resolve_user_login( $user_login ),
			'user_password' => $password,
			'remember'      => $remember,
		),
		is_ssl()
	);

	if ( is_wp_error( $user ) ) {
		show_notify_error( 'account', 'error', $user->get_error_message() );
	}

	$redirect_to = core_validate_account_redirect_url( sanitize_text_field( wp_unslash( $_POST['redirect_to'] ?? '' ) ) );

	wp_safe_redirect( $redirect_to );
	exit;
}

add_action( 'admin_post_nopriv_core_account_login', 'core_handle_account_login' );
add_action( 'admin_post_core_account_login', 'core_handle_account_login' );

/**
 * Logout user from account
 *
 * @return void
 */
function init_logout(): void {
	if ( isset( $_GET['logout'] ) ) {
		wp_logout();
		wp_safe_redirect( core_get_account_page_url() );
		exit;
	}
}

add_action( 'init', 'init_logout' );

/**
 * Reset password for user
 *
 * @return void
 */
function reset_user_password_handler(): void {
	$nonce = $_POST['_wpnonce'] ?? '';
	if ( ! wp_verify_nonce( $nonce, 'upd_account_passwd' ) ) {
		show_notify_error( 'account', 'security_error' );
	}

	$user_id = $_POST['user_id'] ? (int) $_POST['user_id'] : 0;
	$key     = $_POST['key'] ?? '';
	if ( empty( $user_id ) || empty( $key ) ) {
		show_notify_error( 'account', 'expired_reset_key' );
	}

	$user = get_user_by( 'id', $user_id );
	if ( ! $user ) {
		show_notify_error( 'account', 'expired_reset_key' );
	}

	$valid_key = check_password_reset_key( $key, $user->user_login );
	if ( is_wp_error( $valid_key ) ) {
		show_notify_error( 'account', 'expired_reset_key' );
	}

	$password        = (string) wp_unslash( $_POST['password'] ?? '' );
	$repeat_password = (string) wp_unslash( $_POST['repeat_password'] ?? '' );

	$reset_link_path = 'reset-password/?key=' . $key . '&login=' . $user->user_login;
	if ( empty( $password ) || strlen( $password ) < 8 ) {
		show_notify_error( $reset_link_path, 'password_length' );
	}

	if ( $password !== $repeat_password ) {
		show_notify_error( $reset_link_path, 'password_mismatch' );
	}

	reset_password( $user, $password );

	show_notify_success( 'account', 'password_updated' );
}

add_action( 'admin_post_nopriv_reset_user_password', 'reset_user_password_handler' );
add_action( 'admin_post_reset_user_password', 'reset_user_password_handler' );

/**
 * Handle frontend registration.
 *
 * @return void
 */
function core_handle_account_register(): void {
	$nonce = $_POST['core_account_register_nonce'] ?? '';
	if ( ! wp_verify_nonce( $nonce, 'core_account_register' ) ) {
		show_notify_error( 'account?tab=register', 'security_error' );
	}

	$email         = sanitize_email( wp_unslash( $_POST['user_email'] ?? '' ) );
	$password      = (string) wp_unslash( $_POST['user_password'] ?? '' );
	$first_name    = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
	$phone_number  = sanitize_text_field( wp_unslash( $_POST['phone_number'] ?? '' ) );
	$agency_id     = absint( $_POST['agency_id'] ?? 0 );
	$user_role     = sanitize_text_field( wp_unslash( $_POST['user_role'] ?? '' ) );
	$allowed_roles = get_existing_user_roles();
	if ( ! isset( $allowed_roles[ $user_role ] ) ) {
		$user_role = array_key_first( $allowed_roles );
	}

	if ( empty( $email ) || ! is_email( $email ) || strlen( $password ) < 8 ) {
		show_notify_error( 'account?tab=register', 'required_fields' );
	}

	if ( email_exists( $email ) ) {
		show_notify_error( 'account?tab=register', 'email_exists' );
	}

	$user_login   = core_build_unique_username( current( explode( '@', $email ) ) );
	$display_name = trim( $first_name );
	if ( empty( $display_name ) ) {
		$display_name = $user_login;
	}

	$user_id = wp_insert_user(
		array(
			'user_login'   => $user_login,
			'user_pass'    => $password,
			'user_email'   => $email,
			'first_name'   => $first_name,
			'display_name' => $display_name,
			'role'         => $user_role,
		)
	);

	if ( is_wp_error( $user_id ) ) {
		show_notify_error( 'account?tab=register', 'error', $user_id->get_error_message() );
	}

	wp_set_current_user( (int) $user_id );
	wp_set_auth_cookie( (int) $user_id );

	$estate_user = new Estate_User( $user_id );
	$estate_user->update_phones( $phone_number );

	if ( 'broker' === $user_role ) {
		$estate_user->register_broker();
		$estate_user->update_whatsapp( $phone_number );

		update_field( 'agency', $agency_id, 'user_' . $user_id );
	}

	$redirect_to = $_POST['redirect_to'] ?? 'account';
	$user        = get_user_by( 'id', $user_id );

	send_email_verification_token( $user );

	show_notify_success( $redirect_to, 'profile_created' );
}

add_action( 'admin_post_nopriv_core_account_register', 'core_handle_account_register' );
add_action( 'admin_post_core_account_register', 'core_handle_account_register' );

/**
 * Give every translation the slug of the default language.
 *
 * A title in Cyrillic turns into a percent encoded slug, so a Russian unit ends
 * up at /ru/property/.../%d0%ba%d0%b2%d0%b0.... Reusing the English slug keeps
 * the URL readable and identical across locales.
 *
 * Call after pll_save_post_translations(): the language has to be set before the
 * slug, or wp_unique_post_slug() treats the other translation as a clash and
 * appends -2.
 *
 * @param array $translations Language slug => post ID.
 *
 * @return void
 */
function core_sync_translation_slugs( array $translations ): void {
	if ( count( $translations ) < 2 || ! function_exists( 'pll_default_language' ) ) {
		return;
	}

	$default = (string) pll_default_language( 'slug' );
	if ( '' === $default || empty( $translations[ $default ] ) ) {
		return;
	}

	$source_id   = (int) $translations[ $default ];
	$source_post = get_post( $source_id );
	if ( ! $source_post ) {
		return;
	}

	// A draft has no slug yet: WordPress derives one only on publish, and a title
	// in Cyrillic becomes a percent encoded slug at that point. Settle it now from
	// the default language title so the URL never carries encoded characters.
	$slug = $source_post->post_name;
	if ( '' === $slug ) {
		$slug = wp_unique_post_slug(
			sanitize_title( $source_post->post_title ),
			$source_id,
			$source_post->post_status,
			$source_post->post_type,
			$source_post->post_parent
		);
	}

	if ( '' === $slug ) {
		return;
	}

	foreach ( $translations as $post_id ) {
		$post_id = (int) $post_id;
		$post    = get_post( $post_id );
		if ( ! $post || $post->post_name === $slug ) {
			continue;
		}

		// The language is already assigned, so wp_unique_post_slug() lets the
		// translations share one slug instead of appending -2.
		wp_update_post(
			array(
				'ID'        => $post_id,
				'post_name' => $slug,
			)
		);
	}
}

/**
 * Handle frontend unit creation.
 *
 * @return void
 */
function account_create_unit(): void {
	if ( ! isset( $_POST['action'] ) || 'create_unit' !== $_POST['action'] ) {
		return;
	}

	$current_user = wp_get_current_user();
	if ( ! $current_user || ! $current_user->exists() ) {
		return;
	}
	//get user roles
	$current_user_roles = $current_user->roles ?? array();
	if ( ! in_array( 'broker', $current_user_roles, true )
	     && ! in_array( 'administrator', $current_user_roles, true ) ) {
		show_notify_error( 'account', 'permission_denied' );
	}

	$nonce = $_POST['_wpnonce'] ?? '';
	if ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) || ! wp_verify_nonce(
			$nonce,
			'account_create_unit_nonce'
		) ) {
		show_notify_error( 'account', 'security_error' );
	}

	$final_image_selection_json = $_POST['final_image_selection'] ?? '';

	$languages         = get_all_languages();
	$errors            = array();
	$unit_translations = array();
	foreach ( $languages as $language ) {
		if ( empty( $_POST[ $language['slug'] ] ) ) {
			continue;
		}

		$unit_id = update_unit_translation( $language, $_POST[ $language['slug'] ] );
		if ( is_wp_error( $unit_id ) ) {
			core_account_redirect( 'add_unit', array( $unit_id->get_error_message() ) );
		}

		$property_id           = absint( $_POST[ $language['slug'] ]['property_id'] ?? 0 );
		$final_image_selection = ! empty( $final_image_selection_json ) ? json_decode(
			wp_unslash( $final_image_selection_json ),
			true
		) : array();
		$gallery_thumbs_ids    = array();
		if ( ! empty( $final_image_selection['fullOrder'] ) ) {
			$gallery_thumbs_ids = array_merge(
				$gallery_thumbs_ids,
				array_map( 'absint', $final_image_selection['fullOrder'] )
			);
		}

		if ( ! empty( $_POST['user_thumbnails_ids'] ) ) {
			$exploded_ids = explode( ',', $_POST['user_thumbnails_ids'] );
			foreach ( $exploded_ids as $user_thumbnails_id ) {
				$user_thumbnails_id = absint( $user_thumbnails_id );
				if ( ! in_array( $user_thumbnails_id, $gallery_thumbs_ids, true ) ) {
					$gallery_thumbs_ids[] = $user_thumbnails_id;
				}
			}
		}

		$gallery_thumbs_ids = array_unique( $gallery_thumbs_ids );
		$gallery_thumbs_ids = array_values( array_filter( $gallery_thumbs_ids, fn( $value ) => $value !== 0 ) );

		if ( empty( $gallery_thumbs_ids ) ) {
			$property           = new Property( $property_id );
			$gallery_thumbs_ids = $property->get_random_gallery_ids();
		}
		update_field( 'gallery', $gallery_thumbs_ids, $unit_id );

		if ( ! isset( $location_terms ) ) {
			$location_terms = wp_get_post_terms( $property_id, 'location', array( 'fields' => 'ids' ) );
		}

		if ( ! is_wp_error( $location_terms ) && ! empty( $location_terms ) ) {
			wp_set_post_terms( $unit_id, $location_terms, 'location', false );
		}

		delete_post_meta( $unit_id, 'auto_approve_errors' );
		delete_post_meta( $unit_id, 'is_wait_user_actions' );

		$unit_translations[ $language['slug'] ] = $unit_id;
	}

	if ( count( $unit_translations ) > 1 ) {
		pll_save_post_translations( $unit_translations );
	}

	core_sync_translation_slugs( $unit_translations );

	core_flush_listing_caches();

//	if ( ! empty( $errors ) ) {
//		show_notify_error( 'account?action=add_unit', $errors );
//	}

	show_notify_success( 'account', 'property_added' );
}

/**
 * Insert or update unit translation for the given language.
 *
 * @param mixed $language
 * @param array $unit_data
 *
 * @return int|WP_Error
 */
function update_unit_translation( mixed $language, array $unit_data ): int|WP_Error {
	$title       = sanitize_text_field( wp_unslash( $unit_data['unit_title'] ?? '' ) );
	$property_id = absint( $_POST[ $language['slug'] ]['property_id'] ?? 0 );
	$price       = absint( $unit_data['price'] ?? 0 );
	$area_size   = round( abs( (float) ( $unit_data['area'] ?? 0 ) ), 2 );
	$unit_type   = sanitize_text_field( wp_unslash( $unit_data['unit_type'] ?? '' ) );

	$description  = wp_kses_post( wp_unslash( $unit_data['description'] ?? '' ) );
	$listing_type = core_sanitize_listing_type(
		sanitize_text_field( wp_unslash( $unit_data['listing_type'] ?? '' ) )
	);

	// The original price and the discount off it only make sense for a distress deal.
	$original_price = 'distress' === $listing_type ? absint( $unit_data['original_price'] ?? 0 ) : 0;
	$discount       = 0;
	if ( 'distress' === $listing_type ) {
		if ( 0 >= $price || $original_price <= $price ) {
			return new WP_Error(
				'invalid_original_price',
				__( 'A distress listing needs an original price higher than the asking price.', 'east-property' )
			);
		}

		// Keep in sync with discountPercent() in submit-unit.js.
		$discount = (int) round( ( ( $original_price - $price ) / $original_price ) * 100 );
	}

	$price_per_sqft = absint( $unit_data['price_per_square'] ?? 0 );
	$bedrooms       = max( 0, (int) ( $unit_data['bedrooms'] ?? 0 ) );
	$bathrooms      = max( 0, (int) ( $unit_data['bathrooms'] ?? 0 ) );
//	$allowed_unit_types = core_get_unit_type_choices();

//	$errors = array();
//	if ( empty( $title ) ) {
//		$errors[] = __( 'Unit title is required.', 'east-property' );
//	}
//
//	if ( empty( $property_post ) || 'property' !== $property_post->post_type ) {
//		$errors[] = __( 'Please select a valid property.', 'east-property' );
//	}
//
//	if ( $price <= 0 ) {
//		$errors[] = __( 'Price must be greater than zero.', 'east-property' );
//	}
//
//	if ( $area_size <= 0 ) {
//		$errors[] = __( 'Area size must be greater than zero.', 'east-property' );
//	}
//
//	if ( ! empty( $unit_type ) && ! isset( $allowed_unit_types[ $unit_type ] ) ) {
//		$errors[] = __( 'Selected unit type is invalid.', 'east-property' );
//	}
//
//	if ( ! empty( $errors ) ) {
//		return $errors;
//	}

	if ( ! empty( $unit_data['unit_id'] ) ) {
		$unit_id = absint( $unit_data['unit_id'] );
		$unit    = new Unit( $unit_id );
		if ( ! $unit->exists() ) {
			core_account_redirect( 'add_unit', array( __( 'Unit not found.', 'east-property' ) ) );
		}

		wp_update_post(
			array(
				'ID'           => $unit_id,
				'post_title'   => $title,
				'post_content' => wp_slash( $description ),
				'post_excerpt' => wp_trim_words( wp_strip_all_tags( $description ), 30, '...' ),
				'post_status'  => apply_filters( 'core_frontend_unit_status', 'draft' ),
			)
		);
	} else {
		$unit_id = wp_insert_post(
			array(
				'post_type'    => 'unit',
				'post_status'  => apply_filters( 'core_frontend_unit_status', 'draft' ),
				'post_title'   => $title,
				'post_content' => wp_slash( $description ),
				'post_excerpt' => wp_trim_words( wp_strip_all_tags( $description ), 30, '...' ),
				'post_author'  => get_current_user_id(),
			),
			true
		);

		if ( function_exists( 'pll_set_post_language' ) ) {
			pll_set_post_language( $unit_id, $language['slug'] );
		}
	}

	if ( is_wp_error( $unit_id ) ) {
		return $unit_id;
	}

	$property_translations = pll_get_post_translations( $property_id );
	if ( isset( $property_translations[ $language['slug'] ] ) ) {
		update_field( 'property', $property_id, $unit_id );
	}

	update_field( 'broker', get_current_user_id(), $unit_id );
	update_field( 'listing_type', $listing_type, $unit_id );
	update_field( 'unit_type', $unit_type, $unit_id );
	update_field( 'price', $price, $unit_id );
	update_field( 'original_price', $original_price, $unit_id );
	update_field( 'discount', $discount, $unit_id );
	update_field( 'price_per_square_foot', $price_per_sqft, $unit_id );
	update_field( 'bedrooms', $bedrooms, $unit_id );
	update_field( 'bathrooms', $bathrooms, $unit_id );
	update_field( 'area_size', $area_size, $unit_id );

	return $unit_id;
}

add_action( 'init', 'account_create_unit' );

/**
 * Validate one language's worth of submitted property data.
 *
 * Shared fields repeat in every language, so the caller deduplicates. Errors on
 * the two per-language fields name the language they belong to.
 *
 * @param array $property_data Submitted values for one language.
 * @param array $language Language definition from get_all_languages().
 * @param bool $name_language Whether to point at the language in per-language errors.
 *
 * @return array List of error messages.
 */
function core_validate_property_translation(
	array $property_data,
	array $language,
	bool $name_language = false
): array {
	$errors   = array();
	$suffix   = $name_language ? ' (' . $language['name'] . ')' : '';
	$required = array(
		'title'       => __( 'Property title is required.', 'east-property' ) . $suffix,
		'description' => __( 'Property description is required.', 'east-property' ) . $suffix,
	);

	foreach ( $required as $field => $message ) {
		if ( '' === trim( (string) ( $property_data[ $field ] ?? '' ) ) ) {
			$errors[] = $message;
		}
	}

	$shared = array(
		'latitude'       => __( 'Latitude is required.', 'east-property' ),
		'longitude'      => __( 'Longitude is required.', 'east-property' ),
		'location_id'    => __( 'Please select a valid location.', 'east-property' ),
		'ownership_type' => __( 'Please select a valid ownership type.', 'east-property' ),
		'property_types' => __( 'Please select a valid property type.', 'east-property' ),
	);

	foreach ( $shared as $field => $message ) {
		if ( '' === trim( (string) ( $property_data[ $field ] ?? '' ) ) ) {
			$errors[] = $message;
		}
	}

	return $errors;
}

/**
 * Insert or update the property translation for the given language.
 *
 * @param mixed $language Language definition from get_all_languages().
 * @param array $property_data Submitted values for that language.
 *
 * @return int|WP_Error
 */
function update_property_translation( mixed $language, array $property_data ): int|WP_Error {
	$title          = sanitize_text_field( wp_unslash( $property_data['title'] ?? '' ) );
	$description    = wp_kses_post( wp_unslash( $property_data['description'] ?? '' ) );
	$latitude       = sanitize_text_field( wp_unslash( $property_data['latitude'] ?? '' ) );
	$longitude      = sanitize_text_field( wp_unslash( $property_data['longitude'] ?? '' ) );
	$developer_id   = absint( $property_data['developer_id'] ?? 0 );
	$ownership_type = sanitize_text_field( wp_unslash( $property_data['ownership_type'] ?? '' ) );
	$property_types = sanitize_text_field( wp_unslash( $property_data['property_types'] ?? '' ) );
	$delivery_date  = sanitize_text_field( wp_unslash( $property_data['delivery_date'] ?? '' ) );
	$is_completed   = ! empty( $property_data['is_completed'] );

	if ( $is_completed ) {
		$delivery_date = '2000-01-01';
	}

	if ( ! empty( $property_data['property_id'] ) ) {
		$property_id = absint( $property_data['property_id'] );
		$property    = new Property( $property_id );
		if ( ! $property->exists() ) {
			return new WP_Error( 'property_not_found', __( 'Property not found.', 'east-property' ) );
		}

		wp_update_post(
			array(
				'ID'           => $property_id,
				'post_title'   => $title,
				'post_content' => wp_slash( $description ),
				'post_excerpt' => wp_trim_words( wp_strip_all_tags( $description ), 30, '...' ),
			)
		);
	} else {
		$property_id = wp_insert_post(
			array(
				'post_type'    => 'property',
				'post_status'  => apply_filters( 'core_frontend_property_status', 'draft' ),
				'post_title'   => $title,
				'post_content' => wp_slash( $description ),
				'post_excerpt' => wp_trim_words( wp_strip_all_tags( $description ), 30, '...' ),
				'post_author'  => get_current_user_id(),
			),
			true
		);

		if ( is_wp_error( $property_id ) ) {
			return $property_id;
		}

		if ( function_exists( 'pll_set_post_language' ) ) {
			pll_set_post_language( $property_id, $language['slug'] );
		}
	}

	if ( is_wp_error( $property_id ) ) {
		return $property_id;
	}

	update_field( 'latitude', $latitude, $property_id );
	update_field( 'longitude', $longitude, $property_id );
	update_field( 'developer_rel', $developer_id, $property_id );
	update_field( 'ownership_type', $ownership_type, $property_id );
	update_field( 'delivery_date', $delivery_date, $property_id );
	update_field( 'property_type', explode( ',', $property_types ), $property_id );

	return $property_id;
}

/**
 * Create or update new property project
 *
 * @return void
 */
function core_handle_account_create_property(): void {
	if ( ! isset( $_POST['action'] ) || 'core_account_create_property' !== $_POST['action'] ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		core_account_redirect( 'login', array( __( 'Please sign in to add a property.', 'east-property' ) ) );
	}

	if ( ! current_user_can( 'edit_posts' ) ) {
		core_account_redirect( 'account',
			array( __( 'Your account does not have permission to add property.', 'east-property' ) ) );
	}

	$nonce = $_POST['_wpnonce'] ?? '';
	if ( ! wp_verify_nonce( $nonce, 'account_create_property_nonce' ) ) {
		core_account_redirect( 'add_property',
			array( __( 'Security check failed. Please try again.', 'east-property' ) ) );
	}

	$languages     = get_all_languages();
	$name_language = 1 < count( $languages );

	// Validate every language before writing anything, so a mistake in one tab
	// does not leave half of the translations saved.
	$errors = array();
	foreach ( $languages as $language ) {
		if ( empty( $_POST[ $language['slug'] ] ) ) {
			continue;
		}

		$errors = array_merge(
			$errors,
			core_validate_property_translation( $_POST[ $language['slug'] ], $language, $name_language )
		);
	}

	if ( ! empty( $errors ) ) {
		show_notify_error( 'account?action=add_property', array_values( array_unique( $errors ) ) );
	}

	$final_image_selection_json = $_POST['final_image_selection'] ?? '';
	$property_translations      = array();
	$location_id                = 0;

	foreach ( $languages as $language ) {
		if ( empty( $_POST[ $language['slug'] ] ) ) {
			continue;
		}

		$property_data = $_POST[ $language['slug'] ];
		$property_id   = update_property_translation( $language, $property_data );
		if ( is_wp_error( $property_id ) ) {
			core_account_redirect( 'add_property', array( $property_id->get_error_message() ) );
		}

		// The gallery is picked once for the whole form, not per language.
		$final_image_selection = ! empty( $final_image_selection_json ) ? json_decode(
			wp_unslash( $final_image_selection_json ),
			true
		) : array();
		$gallery_thumbs_ids    = array();
		if ( ! empty( $final_image_selection['fullOrder'] ) ) {
			$gallery_thumbs_ids = array_map( 'absint', $final_image_selection['fullOrder'] );
		}

		if ( ! empty( $_POST['user_thumbnails_ids'] ) ) {
			foreach ( explode( ',', $_POST['user_thumbnails_ids'] ) as $user_thumbnails_id ) {
				$user_thumbnails_id = absint( $user_thumbnails_id );
				if ( ! in_array( $user_thumbnails_id, $gallery_thumbs_ids, true ) ) {
					$gallery_thumbs_ids[] = $user_thumbnails_id;
				}
			}
		}

		$gallery_thumbs_ids = array_values( array_filter( array_unique( $gallery_thumbs_ids ) ) );
		update_field( 'gallery', $gallery_thumbs_ids, $property_id );

		// The location taxonomy is not translated, so every language carries the
		// same term.
		$location_id = absint( $property_data['location_id'] ?? 0 );
		if ( $location_id > 0 ) {
			wp_set_post_terms( $property_id, array( $location_id ), 'location', false );
		}

		$property_translations[ $language['slug'] ] = $property_id;
	}

	if ( count( $property_translations ) > 1 && function_exists( 'pll_save_post_translations' ) ) {
		pll_save_post_translations( $property_translations );
	}

	core_sync_translation_slugs( $property_translations );

	// The old code hashed $_REQUEST of this POST, which never matches the hash a
	// listing page builds from the visitor's filters, so nothing was ever dropped.
	core_flush_listing_caches();

	show_notify_success( 'account?tab=projects', 'property_added' );
}

add_action( 'init', 'core_handle_account_create_property' );

/**
 * Create or update new property project
 *
 * @return void
 */
function account_update_profile(): void {
	if ( ! isset( $_POST['action'] ) || 'account_update_profile' !== $_POST['action'] ) {
		return;
	}

	$nonce = $_POST['_wpnonce'] ?? '';
	if ( ! is_user_logged_in() || ! wp_verify_nonce( $nonce, 'update_profile' ) ) {
		show_notify_error( 'account?tab=account', 'security_error' );
	}

	$user_display_name = sanitize_text_field( wp_unslash( $_POST['display_name'] ?? '' ) );
	$agency_id         = absint( $_POST['agency_id'] ?? 0 );
	$phones            = array_map( 'sanitize_text_field', wp_unslash( $_POST['phone'] ?? array() ) );
	$whatsapps         = array_map( 'sanitize_text_field', wp_unslash( $_POST['whatsapp'] ?? array() ) );

	$current_user_id = get_current_user_id();
	$updated         = wp_update_user(
		array(
			'ID'           => $current_user_id,
			'display_name' => $user_display_name,
			'first_name'   => $user_display_name,
			//'user_email'   => $email, //TODO For change email, we need approve it by links from email
		)
	);

	if ( is_wp_error( $updated ) ) {
		show_notify_error( 'account', 'error_on_update' );
	}

	$estate_user = new Estate_User( $current_user_id );
	$estate_user->update_phones( $phones );
	$estate_user->update_whatsapp( $whatsapps );

	update_field( 'agency', $agency_id, 'user_' . $current_user_id );

	$current_avatar_id = $_POST['current_avatar_id'] ?? 0;
	if ( empty( $current_avatar_id ) || 0 === (int) $current_avatar_id ) {
		//upload new avatar from file
		if ( ! empty( $_FILES['avatar'] ) && ! empty( $_FILES['avatar']['tmp_name'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';

			$attachment_id = media_handle_upload( 'avatar', 0 );

			if ( is_wp_error( $attachment_id ) ) {
				show_notify_error( 'account', 'error_on_avatar_upload' );
			} else {
				update_field( 'avatar', $attachment_id, 'user_' . $current_user_id );
			}
		}
	}

	show_notify_success( 'account?tab=account', 'profile_updated' );
}

add_action( 'init', 'account_update_profile' );

/**
 * Add pretty pagination for account page.
 *
 * @return void
 */
function core_register_account_pagination_rewrite(): void {
	add_rewrite_rule(
		'^account/page-([0-9]+)/?$',
		'index.php?pagename=account&cur_page=$matches[1]',
		'top'
	);
}

add_action( 'init', 'core_register_account_pagination_rewrite' );

/**
 * Get presets lists for property.
 *
 * @return void
 */
function ajax_get_property_presets(): void {
	//check_ajax_referer( 'get_filtered_properties' );

	$property_id = absint( $_POST['property_id'] ?? 0 );
	if ( empty( $property_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Property ID is required.', 'east-property' ) ) );
	}

	$property = new Property( $property_id );
	if ( ! $property->exists() ) {
		wp_send_json_error( array( 'message' => __( 'Property not found.', 'east-property' ) ) );
	}

	$html            = '';
	$property_images = $property->get_gallery();
	if ( ! empty( $property_images ) ) {
		ob_start();
		foreach ( $property_images as $property_image ) {
			?>
			<div class="uploader-item"
			     data-id="<?php echo esc_attr( $property_image['ID'] ); ?>"
			     data-url="<?php echo esc_url( $property_image['sizes']['large'] ); ?>">
				<img src="<?php echo esc_url( $property_image['sizes']['thumbnail'] ); ?>"
				     alt="<?php esc_html_e( 'Property image', 'east-property' ); ?>">
			</div>
			<?php
		}
		$html = ob_get_clean();
	}

	wp_send_json_success( array( 'html' => $html ) );
}

add_action( 'wp_ajax_get_property_presets', 'ajax_get_property_presets' );

/**
 * Upload user images to the library
 *
 * @return void
 */
function ajax_upload_user_images(): void {
	if ( empty( $_FILES ) ) {
		wp_send_json_error(
			array(
				'message' => 'No file uploaded',
			),
			400
		);
	}

	$file_key = array_key_first( $_FILES );

	if ( ! $file_key || empty( $_FILES[ $file_key ] ) ) {
		wp_send_json_error(
			array(
				'message' => 'Invalid upload payload',
			),
			400
		);
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$attachment_id = media_handle_upload( $file_key, 0 );

	if ( is_wp_error( $attachment_id ) ) {
		wp_send_json_error(
			array(
				'message' => $attachment_id->get_error_message(),
			),
			400
		);
	}

	wp_send_json_success(
		array(
			'attachment_id' => (int) $attachment_id,
			'url'           => wp_get_attachment_url( $attachment_id ),
		)
	);
}

add_action( 'wp_ajax_upload_user_images', 'ajax_upload_user_images' );

/**
 * Remove images from the library by attachment id
 *
 * @return void
 */
function ajax_remove_user_images() {
	$attachment_id = isset( $_POST['attachment_id'] ) ? (int) $_POST['attachment_id'] : 0;

	if ( ! $attachment_id ) {
		wp_send_json_error(
			array(
				'message' => '',
			),
			400
		);
	}

	$current_user_id    = get_current_user_id();
	$attachment_user_id = get_post_field( 'post_author', $attachment_id );
	if ( (int) $current_user_id !== (int) $attachment_user_id ) {
		wp_send_json_error(
			array(
				'message' => 'You do not have permission to delete this attachment',
			),
			403
		);
	}

	$deleted = wp_delete_attachment( $attachment_id, true );

	if ( ! $deleted ) {
		wp_send_json_error(
			array(
				'message' => 'Failed to delete attachment',
			),
			400
		);
	}

	wp_send_json_success(
		array(
			'message' => 'Deleted',
		)
	);
}

add_action( 'wp_ajax_remove_user_images', 'ajax_remove_user_images' );

/**
 * Approve draft units by wp cron
 *
 * @return void
 */
add_action(
	'autoapprove_draft_units',
	static function () {
		$draft_unit_ids = get_posts(
			array(
				'post_type'      => 'unit',
				'post_status'    => 'draft',
				'posts_per_page' => 20,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		if ( empty( $draft_unit_ids ) ) {
			return;
		}

		foreach ( $draft_unit_ids as $draft_unit_id ) {
			$unit = new Unit( $draft_unit_id );
			if ( ! $unit->exists() ) {
				continue;
			}

			$unit->auto_approve();
		}
	}
);

if ( isset( $_GET['autoapprove_draft_units'] ) ) {
	add_action(
		'init',
		static function () {
			do_action( 'autoapprove_draft_units' );
		}
	);
}

/**
 * Approve draft properties by wp cron
 *
 * @return void
 */
add_action(
	'autoapprove_draft_properties',
	static function () {
		$draft_properties_ids = get_posts(
			array(
				'post_type'      => 'property',
				'post_status'    => 'draft',
				'posts_per_page' => 20,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		if ( empty( $draft_properties_ids ) ) {
			return;
		}

		foreach ( $draft_properties_ids as $draft_properties_id ) {
			wp_update_post(
				array(
					'ID'          => $draft_properties_id,
					'post_status' => 'publish',
				)
			);
		}
	}
);

/**
 * Call WP Cron every 3 minutes for approve units
 */
add_action(
	'init',
	static function () {
		if ( ! wp_next_scheduled( 'autoapprove_draft_units' ) ) {
			wp_schedule_event( time(), 'every_3_minutes', 'autoapprove_draft_units' );
		}

		if ( ! wp_next_scheduled( 'autoapprove_draft_properties' ) ) {
			wp_schedule_event( time(), 'every_3_minutes', 'autoapprove_draft_properties' );
		}
	}
);

add_filter(
	'cron_schedules',
	static function ( $schedules ) {
		$schedules['every_3_minutes'] = array(
			'interval' => 3 * MINUTE_IN_SECONDS,
			'display'  => 'Every 3 minutes',
		);

		return $schedules;
	}
);

const BOOST_PLANS = array(
	1 => array(
		'title'  => '1 day - 250 boost points',
		'days'   => 1,
		'points' => 250,
	),
	3 => array(
		'title'  => '3 days - 500 boost points',
		'days'   => 3,
		'points' => 500,
	),
	7 => array(
		'title'  => '1 week on top - 1000 boost points',
		'days'   => 7,
		'points' => 1000,
	),
);

/**
 * Boost unit in the search results
 *
 * @return void
 */
function ajax_boost_property(): void {
	//check_ajax_referer( 'boost_property_nonce' );

	$unit_id = absint( $_POST['unit_id'] ?? 0 );
	if ( empty( $unit_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Property ID is required.', 'east-property' ) ) );
	}

	$unit = new Unit( $unit_id );
	if ( ! $unit->exists() ) {
		wp_send_json_error( array( 'message' => __( 'Property not found.', 'east-property' ) ) );
	}

	$default_boost_plan_key = array_key_first( BOOST_PLANS );
	$boost_plan_key         = $_POST['boost_plan'] ?? $default_boost_plan_key;

	$current_user_id = get_current_user_id();
	$is_boosted      = $unit->boost( $boost_plan_key, $current_user_id );
	if ( ! $is_boosted ) {
		wp_send_json_error( array( 'message' => __( 'Not enough boost points for boost plan.', 'east-property' ) ) );
	}

	wp_send_json_success( array( 'message' => __( 'Property boosted successfully.', 'east-property' ) ) );
}

add_action( 'wp_ajax_boost_property', 'ajax_boost_property' );

/**
 * Get user boost points
 *
 * @return void
 */
function ajax_get_boost_points(): void {
	check_ajax_referer( 'get_filtered_properties' );

	$current_user = wp_get_current_user();
	$broker       = new Estate_User( $current_user );

	if ( ! $broker->exists() || ! $broker->is_broker() ) {
		wp_send_json_error( array( 'message' => __( 'Broker not found.', 'east-property' ) ) );
	}

	wp_send_json_success( array( 'boost_points' => $broker->get_boost_points() ) );
}

add_action( 'wp_ajax_get_boost_points', 'ajax_get_boost_points' );

/**
 * Remove unit
 *
 * @return void
 */
function delete_unit(): void {
	if ( ! isset( $_GET['action'] ) || 'delete_unit' !== $_GET['action'] ) {
		return;
	}

	$unit_id = absint( $_GET['unit_id'] ?? 0 );
	if ( empty( $unit_id ) ) {
		show_notify_error( 'account', 'security_error' );
	}

	$unit = new Unit( $unit_id );
	if ( ! $unit->exists() ) {
		show_notify_error( 'account', 'security_error' );
	}

	if ( $unit->delete() ) {
		show_notify_success( 'account', 'unit_deleted' );
	}

	show_notify_error( 'account', 'security_error' );
}

add_action( 'init', 'delete_unit' );

/**
 * Get list of user roles
 *
 * @return array
 */
function get_existing_user_roles(): array {
	return array(
		'client' => __( 'Client', 'east-property' ),
		'broker' => __( 'Broker', 'east-property' ),
	);
}

/**
 * Add or remove unit from favorite list of this user
 *
 * @return void
 */
function ajax_toggle_favorite(): void {
	//check_ajax_referer( 'get_filtered_properties' );
	$unit_id = absint( $_POST['unit_id'] ?? 0 );
	if ( empty( $unit_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Property ID is required.', 'east-property' ) ) );
	}

	global $current_user;
	if ( ! $current_user || ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'YOu are not authenticated.', 'east-property' ) ) );
	}

	$favorites = get_user_meta( $current_user->ID, 'favorite_units', true );
	if ( ! is_array( $favorites ) ) {
		$favorites = array();
	}

	$is_favorite = false;
	if ( ! in_array( $unit_id, $favorites, true ) ) {
		$favorites[] = $unit_id;
		$is_favorite = true;
	} else {
		foreach ( $favorites as $key => $favorite ) {
			if ( (int) $favorite === (int) $unit_id ) {
				unset( $favorites[ $key ] );
			}
		}
	}

	update_user_meta( $current_user->ID, 'favorite_units', $favorites );
	wp_send_json_success( array( 'is_favorite' => $is_favorite ) );
}


/**
 * Send for user an email with verification token
 *
 * @param WP_User|false $user
 *
 * @return void
 */
function send_email_verification_token( WP_User|false $user ): void {
	if ( ! $user ) {
		return;
	}

	$verification_token = get_password_reset_key( $user );
	if ( is_wp_error( $verification_token ) ) {
		return;
	}

	$verify_email_link = core_home_url( '/account/?verify_email_token=' . $verification_token );

	$html = __( 'Hello ', 'east-property' ) . $user->display_name . '.';
	$html .= '<br><br>';
	$html .= __( 'Thank you for registering with', 'east-property' ) . ' ' . PROJECT_NAME . '.';
	$html .= '<br><br>';
	$html .= __( 'To complete your registration and activate your account, please verify your email address by clicking the link below:',
		'east-property' );
	$html .= '<br><br>';
	$html .= '<a href="' . $verify_email_link . '">' . __( 'Verify email address', 'east-property' ) . '</a>';
	$html .= '<br><br>';
	$html .= __( 'or copy and paste the following link into your browser:', 'east-property' );
	$html .= '<br><b>' . preg_replace( '~^https?://~i', '', $verify_email_link ) . '</b><br>';

	if ( IS_DEV ) {
		error_log( '--- Verification email link for user ' . $user->user_email . ': ' . $verify_email_link );
	}

	get_template_part(
		'core/components/email/send',
		null,
		array(
			'email'   => $user->user_email,
			'subject' => __( 'Verify your email address', 'east-property' ),
			'content' => $html,
		)
	);
}

add_action( 'wp_ajax_toggle_favorite', 'ajax_toggle_favorite' );

/**
 * Add or remove unit from favorite list of this user
 *
 * @return void
 */
function ajax_toggle_favorite_unauthenticated(): void {
	wp_send_json_error(
		array(
			'message' => __(
				'Sign in to save this property to your favorites.',
				'east-property'
			),
		)
	);
}

add_action( 'wp_ajax_nopriv_toggle_favorite', 'ajax_toggle_favorite_unauthenticated' );

/**
 * Resend verification email
 *
 * @return void
 */
function ajax_resend_verification_email(): void {
	check_ajax_referer( 'get_filtered_properties' );

	global $current_user;

	if ( ! $current_user || ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'east-property' ) ) );
	}

	send_email_verification_token( $current_user );

	wp_send_json_success( array( 'message' => __( 'Verification email resent. Check your email.', 'east-property' ) ) );
}

add_action( 'wp_ajax_resend_verification_email', 'ajax_resend_verification_email' );

/**
 * Check verification email token and mark email as verified
 *
 * @return void
 */
function check_verification_email(): void {
	global $current_user;
	if ( ! $current_user || ! is_user_logged_in() ) {
		show_notify_error( 'account', 'security_error' );

		return;
	}

	$token = sanitize_text_field( $_GET['verify_email_token'] );
	if ( empty( $token ) || is_wp_error( check_password_reset_key( $token, $current_user->user_login ) ) ) {
		show_notify_error( 'account', 'invalid_verification_token' );

		return;
	}

	$user = new Estate_User( $current_user->ID );
	$user->mark_email_as_verified();

	show_notify_success( 'account', 'email_verified' );
}

if ( ! empty( $_GET['verify_email_token'] ) ) {
	add_action( 'init', 'check_verification_email' );
}

/**
 * Check this ad is distress or not
 *
 * @return void
 */
function ajax_verify_distress(): void {
	//check_ajax_referer( 'get_filtered_properties' );
	if ( 5 <= (int) $_REQUEST['discount'] ) {
		wp_send_json_success();
	}

	include_once WPMU_PLUGIN_DIR . '/distress/below_market.php';

	$path = GENIEMAP_SQLITE_PATH;
	if ( ! file_exists( $path ) ) {
		wp_send_json_success();
	}

	$pdo = new PDO( 'sqlite:' . $path );
	$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	$stmt = $pdo->prepare( 'SELECT project_id, raw_json FROM project_units ORDER BY project_id ASC LIMIT :limit OFFSET :offset' );
	$stmt->bindValue( ':limit', 700, PDO::PARAM_INT );
	$stmt->bindValue( ':offset', 0, PDO::PARAM_INT );
	$stmt->execute();
	$rows = $stmt->fetchAll( PDO::FETCH_ASSOC );

	$units_rows = array();
	foreach ( $rows as $row ) {
		$units = gm_decode_json( $row['raw_json'] );
		if ( empty( $units ) || ! is_array( $units ) ) {
			continue;
		}

		foreach ( $units as $unit ) {
			$units_rows[] = array(
				'id'           => $unit['id'] ?? null,
				'property_id'  => $unit['projectId'] ?? null,
				'price'        => $unit['price'] ?? null,
				'area_m2'      => $unit['square'] ?? null,
				'rooms'        => isset( $unit['layout']['name'] ) ? ( 'Studio' === $unit['layout']['name'] ? 0 : (int) $unit['layout']['name'] ) : null,
				'district'     => $row['districtId'] ?? null,
				'floor'        => $unit['floor'] ?? null,
				'floors_total' => null,
				'condition'    => null,
				'listing_date' => isset( $unit['createdAt'] ) ? date( 'Y-m-d', strtotime( $unit['createdAt'] ) ) : null,
				// 'price_history' => json_encode( [ array( 'price' => 100000 ), array( 'price' => $unit['price'] ?? null ) ] ), // mock price history
			);
		}
	}

	//Place out $_REQUEST data to rows as another data
	/**
	 * in $_REQUEST we have
	 * Array
	 * (
	 * [action] => verify_distress
	 * [unit_id] =>
	 * [_wpnonce] => 084d180abf
	 * [_wp_http_referer] => /account/?action=add_unit
	 * [unit_title] =>
	 * [price] =>
	 * [price_per_square] => 0
	 * [original_price] =>
	 * [discount] => 0
	 * [area] =>
	 * [property_id] => 9015
	 * [unit_type] => apartment
	 * [bedrooms] =>
	 * [bathrooms] =>
	 * [description] =>
	 * [user_thumbnails_ids] =>
	 * [_ajax_nonce] => 362e78bbae
	 * )
	 */

	$property      = new Property( absint( $_REQUEST['property_id'] ?? 0 ) );
	$delivery_date = $property && $property->exists() ? date(
		'Y-m-d',
		strtotime( $property->get_delivery_date() )
	) : null;

	$units_rows[] = array(
		'id'           => 1,
		'property_id'  => null,
		'price'        => $_REQUEST['price'] ?? null,
		'area_m2'      => $_REQUEST['area'] ?? null,
		'rooms'        => $_REQUEST['bedrooms'] ?? null,
		'district'     => null,
		'floor'        => null,
		'floors_total' => null,
		'condition'    => null,
		'listing_date' => $delivery_date,
	);

	$distress_units     = BelowMarketDetector::detect( $units_rows );
	$below_market_score = 0;
	foreach ( $distress_units as $distress_unit ) {
		$below_market_score = $distress_unit['below_market_score'];
		if ( 1 === (int) $distress_unit['id'] && 0 < $distress_unit['below_market_score'] ) {
			wp_send_json_success();
		}
	}

	wp_send_json_error(
		array(
			'message' => __(
							 'This property is not a distress deal. Below Market Score : ',
							 'east-property'
						 ) . $below_market_score,
		)
	);
}

add_action( 'wp_ajax_verify_distress', 'ajax_verify_distress' );

/**
 * Change from defal logout url to account/?logout=1
 *
 * @param string $logout_url
 *
 * @return string
 */
function change_logout_url( string $logout_url ): string {
	return add_query_arg( array( 'logout' => 1 ), core_get_account_page_url() );
}

add_filter( 'logout_url', 'change_logout_url' );