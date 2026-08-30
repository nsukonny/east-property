<?php

/**
 * Default template for map
 */

$mode              = $args['mode'] ?? 'list'; //single or list
$property          = $args['property'] ?? array();
$property_id       = $property ? $property->get_id() : '';
$show_sidebar      = $args['show_sidebar'] ?? 'false';
$properties        = $args['properties'] ?? array( 'items' => array( $property ) );
$class             = $args['class'] ?? '';
$search_by_address = $args['search_by_address'] ?? false;
$map_properties    = get_map_properties_json( $properties['items'] ?? array(), true );
?>
<script>
	const filterPropertiesJson = <?php echo $map_properties; ?>;
</script>
<div class="render-map <?php echo esc_attr( $class ); ?>">
	<div class="map js-map-instance"
		data-map-mode="<?php echo esc_attr( $mode ); ?>"
		data-property-id="<?php echo esc_attr( $property_id ); ?>"
		data-show-sidebar="<?php echo esc_attr( $show_sidebar ); ?>"
		data-single-geo-marker="<?php echo THEME_URL; ?>/assets/img/geo.svg"
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
	</div>
</div>