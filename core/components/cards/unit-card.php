<?php

/**
 * Unit card component
 *
 * @var array $unit
 */

use Entities\Unit;

$unit = $args['unit'] ?? null;
if ( empty( $unit ) ) {
	return;
}

$template = $args['template'] ?? 'unit-card';

$is_author = 0 !== $unit['author_id'] && (int) $unit['author_id'] === get_current_user_id();
$is_draft  = 'draft' === $unit['post_status'];

get_component_template(
	'cards/' . $template,
	array(
		'unit_id' => $unit['ID'],
		'url' => $unit['url'] ?? '',
		'labels' => Unit::build_labels( $unit ),
		'image' => $unit['gallery'][0]['sizes']['featured-card'] ?? '',
		'price' => $unit['price'] ? get_price_html( $unit['price'] ) : '',
		'original_price' => $unit['original_price'] ? get_price_html( $unit['original_price'] ) : '',
		'discount' => $unit['discount'] ?? null,
		'gallery' => $unit['gallery'] ?? array(),
		'title' => $unit['title'] ?? '',
		'property_name' => $unit['property_name'] ?? '',
		'property_url' => $unit['property_url'] ?? '',
		'developer_name' => $unit['developer_name'] ?? '',
		'amenities' => Unit::build_amenities( $unit ),
		'edit_link' => $is_author ? core_home_url( '/account?action=edit_unit&id=' . $unit['ID'] ) : '',
		'is_can_boost' => $is_author && ! $is_draft,
		'is_favorite' => Unit::is_favorite( (int) $unit['ID'] ),
		'broker' => ! empty( $unit['author_id'] ) ? new \Entities\Estate_User( $unit['author_id'] ) : null,
	)
);
