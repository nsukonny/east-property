<?php
/**
 * Pagination component
 */

$total_items = $args['total_items'] ?? 0;
if ( 0 >= $total_items ) {
	return;
}

$current_page   = pagination_get_current_page();
$elements_count = $args['elements_count'] ?? 7;
$per_page       = $args['items_per_page'] ?? PROPERTIES_PER_PAGE ?? 20;
$total_pages    = ceil( $total_items / $per_page );

$from = 1 < $current_page ? max( 1, $current_page - floor( $elements_count / 2 ) ) : $current_page;
if ( 0 >= $from ) {
	$from = 1;
}
$to = $from + $elements_count - 1;
if ( $to > $total_pages ) {
	$to   = $total_pages;
	$from = max( 1, $to - $elements_count + 1 );
}

if ( 1 >= $to ) {
	return;
}

$uri        = $_REQUEST['current_href'] ?? $_SERVER['REQUEST_URI'] ?? '';
$parsed_uri = parse_url( $uri );
$path       = $parsed_uri['path'] ?? '';
$query      = $parsed_uri['query'] ?? '';
parse_str( $query, $query_args );

$prev_page      = $current_page - 1;
$next_page      = $current_page + 1;
$pages          = array();
$sliced_path    = explode( '/page-' . $current_page, $path );
$sliced_path[0] = rtrim( $sliced_path[0], '/' );
$current_url    = core_home_url( $sliced_path[0] ?? '' );
for ( $i = $from; $i <= $to; $i++ ) {
	$page_link = add_query_arg( $query_args, core_home_url( $sliced_path[0] . '/page-' . $i . '/' ) );
	if ( 1 === $i ) {
		$page_link = add_query_arg( $query_args, core_home_url( $sliced_path[0] ) );
	}

	$pages[] = array(
		'number'  => $i,
		'link'    => esc_url( $page_link ),
		'is_prev' => (int) $i === $prev_page,
		'is_next' => (int) $i === $next_page,
	);
}

get_component_template(
	'common/pagination',
	array(
		'current_page' => $current_page,
		'pages'        => $pages,
	)
);
