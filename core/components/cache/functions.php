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
	$request_params = implode(
		'',
		array_map(
			fn( $key ) => $_REQUEST[ $key ] ?? '',
			$approved_request_vars
		)
	);

	$request_params .= wp_json_encode( $args );
	$request_params .= wp_cache_get_last_changed( $group );

	return md5( $key . '_' . $request_params );
}
