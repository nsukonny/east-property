<?php
/**
 * District archive: /areas/{slug}/
 *
 * Without this template WordPress falls through to archive.php, which redirects
 * every archive to /404 — so all ~154 district URLs answered 302 → /404 while
 * still being listed in the sitemap.
 *
 * The page is the projects listing constrained to one district. get_properties()
 * reads the filter from $_REQUEST['location'] by term slug, and default_filters
 * carries the district into the AJAX filter so it survives further filtering.
 */

$location = get_queried_object();
if ( ! $location instanceof WP_Term ) {
	get_header( null, array( 'color' => 'sand' ) );
	get_template_part( '404' );
	get_footer();

	return;
}

// The listing reads the district from the request, the same way /projects/?location= does.
$_REQUEST['location'] = $location->slug;
$_GET['location']     = $location->slug;

get_header( null, array( 'color' => 'sand' ) );

get_template_part(
	'core/components/properties/filter',
	null,
	array(
		'search_by' => false,
		'h2' => sprintf(
		/* translators: %s is a district name. */
			__( 'Projects in %s', 'east-property' ),
			$location->name
		),
		'default_filters' => array(
			'location' => $location->slug,
		),
	)
);

get_template_part( 'template-parts/sections/index/about' );

get_footer();
