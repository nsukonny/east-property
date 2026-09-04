<?php

/**
 * Default template for map
 */

$mode        = $args['mode'] ?? 'list'; //single or list
$property    = $args['property'] ?? array();
$property_id = $property ? $property->get_id() : '';
$is_single   = 'single' === $mode;

/*
 * Accepts false, 'false' and everything in between: callers pass a boolean while
 * the old default here was the string 'false', and the script only ever treated
 * the literal 'false' as off — so a sidebar switched off in PHP stayed on.
 */
$show_sidebar = filter_var( $args['show_sidebar'] ?? false, FILTER_VALIDATE_BOOLEAN );

/*
 * Falls back on an empty list, not only on a missing one: the component wrapper
 * always forwards a 'properties' key and hands over an empty array when the
 * caller passed none, so the null coalescing fallback never fired and a single
 * project's map was built from nothing.
 */
$properties = $args['properties'] ?? array();

if ( empty( $properties['items'] ) && $property ) {
	$properties = array( 'items' => array( $property ) );
}

$class             = $args['class'] ?? '';
$search_by_address = $args['search_by_address'] ?? false;

/*
 * Skipping projects without available units keeps pointless markers off the
 * listing map, but on a project's own page that project is the only marker
 * there is — dropping it left the map centred on the default coordinates.
 */
$map_properties = get_map_properties_json( $properties['items'] ?? array(), ! $is_single );

/*
 * Written into the markup, not only into the script: the coordinates are what
 * says where the project is, and a crawler that runs no JavaScript — every AI
 * crawler among them — would otherwise find no location on the page at all.
 * The script reads them too, so a single map no longer depends on the JSON.
 */
$latitude  = $property && method_exists( $property, 'get_latitude' ) ? (string) $property->get_latitude() : '';
$longitude = $property && method_exists( $property, 'get_longitude' ) ? (string) $property->get_longitude() : '';
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