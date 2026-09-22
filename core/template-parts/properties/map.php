<?php

/**
 * Default template for map
 */

$mode        = $args['mode'] ?? 'list'; //single or list
$property    = $args['property'] ?? array();
$property_id = $property['ID'] ?? '';
$is_single   = 'single' === $mode;

$show_sidebar = filter_var( $args['show_sidebar'] ?? false, FILTER_VALIDATE_BOOLEAN );
$properties   = $args['properties'] ?? array();

if ( empty( $properties['items'] ) && $property ) {
	$properties = array( 'items' => array( $property ) );
}

$class             = $args['class'] ?? '';
$search_by_address = $args['search_by_address'] ?? false;

/*
 * The listing hands over its own page of projects, the map tab hands over the
 * whole matching set: the map has no pagination of its own.
 */
$map_items      = $args['map_properties'] ?? ( $properties['items'] ?? array() );
$map_properties = get_map_properties_json( $map_items, ! $is_single );

$latitude  = $property['latitude'] ?? '';
$longitude = $property['longitude'] ?? '';
?>
<script>
	const filterPropertiesJson = <?php echo $map_properties; ?>;
</script>
<div class="render-map <?php echo esc_attr( $class ); ?>">
	<div class="map js-map-instance"
		 data-map-mode="<?php echo esc_attr( $mode ); ?>"
		 data-property-id="<?php echo esc_attr( $property_id ); ?>"
		 data-show-sidebar="<?php echo $show_sidebar ? 'true' : 'false'; ?>"
		 data-single-geo-marker="<?php echo THEME_URL; ?>/assets/img/geo.svg"
		<?php if ( '' !== $latitude && '' !== $longitude ) { ?>
			data-latitude="<?php echo esc_attr( $latitude ); ?>"
			data-longitude="<?php echo esc_attr( $longitude ); ?>"
		<?php } ?>
	>
		<?php if ( $search_by_address ) { ?>
			<div class="map-address-search">
				<div class="js-map-address"></div>

				<input
						type="hidden"
						name="address"
						class="js-map-address-value"
						value=""
				>
			</div>
		<?php } ?>

		<div class="map-container js-map-container"></div>

		<?php if ( $show_sidebar ) { ?>
			<aside class="map-sidebar is-hidden js-map-sidebar">
				<div class="aside-map-header">
					<div class="map-handle-wrapper">
						<span class="map-sidebar-handle"></span>
					</div>
					<button class="sidebar-close js-map-sidebar-close">
						<img src="<?php echo THEME_URL; ?>/assets/img/close.svg" width="32" height="32" alt="Close">
					</button>
				</div>

				<div class="map-sidebar-content">
					<?php get_component_template( 'ui/loader' ); ?>
					<div class="sidebar-card-target js-sidebar-card-target"></div>
				</div>
				<div class="a-link">
					<a href="#" class="button orange sm full-width" target="_blank">
						<?php _e( 'Explore Project', 'east-property' ); ?>
					</a>
				</div>
			</aside>
		<?php } ?>
	</div>
</div>