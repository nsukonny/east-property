<?php
/**
 * Catalog component
 */

$category_slug = $args['slug'] ?? '';
if ( empty( $category_slug ) ) {
	return;
}

$term = get_term_by( 'slug', $category_slug, 'product_cat' );

if ( empty( $term ) ) {
	return;
}

$products = get_posts(
	array(
		'post_type'      => 'product',
		'posts_per_page' => 4,
		'tax_query'      => array(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => $term->slug,
			),
		),
	)
);

if ( empty( $products ) ) {
	return;
}

get_template_part( 'template-parts/sections/index/catalog', null,
	array(
		'term'     => $term,
		'products' => $products,
	)
);
