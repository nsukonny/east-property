<?php

/**
 * Generate key for cache by request params
 *
 * @param string $key
 * @param string $group //Group of cache for fast reset by group
 * @param array $approved_request_vars
 * @param array $args
 *
 * @return string
 */
function core_generate_cache_key(
	string $key,
	string $group,
	array $approved_request_vars,
	array $args = array()
): string {
	$request_params = '';
	if ( ! empty( $approved_request_vars ) ) {
		foreach ( $approved_request_vars as $value ) {
			if ( empty( $_REQUEST[ $value ] ) ) {
				continue;
			}

			$request_params .= $value . '=' . $_REQUEST[ $value ];
		}
	}
	
	$request_params .= wp_json_encode( $args );
	$request_params .= wp_cache_get_last_changed( $group );

	return md5( $key . '_' . $request_params );
}
