<?php
/**
 * Card for unit
 *
 * @var array $args
 */

$title          = $args['title'] ?? '';
$price          = $args['price'] ?? '';
$original_price = $args['original_price'] ?? '';
$discount       = $args['discount'] ?? '';
$location       = $args['location'] ?? '';
$labels         = $args['labels'] ?? '';
$image          = $args['image'] ?? '';
$amenities      = $args['amenities'] ?? '';
$url            = $args['url'] ?? '#';
$property_name  = $args['property_name'] ?? '';
$property_url   = $args['property_url'] ?? '#';
$developer_name = $args['developer_name'] ?? '';

if ( empty( $title ) || empty( $price ) ) {
	return;
}

// unit-card.php only passes an original price for a distress deal, so it doubles
// as the flag for the below-market card styling.
$card_classes = 'property-card' . ( ! empty( $original_price ) ? ' is-distress' : '' );

if ( ! empty( $amenities ) ) {
	foreach ( $amenities as $am_key => $amenity ) {
		if ( '0 ' . __( 'Beds' , 'east-property' ) === $amenity['value'] ) {
			$amenities[ $am_key ]['value'] = __( 'Studio' , 'east-property' );
		}
	}
}
?>
<a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $card_classes ); ?>">
	<div class="property-card-img">
		<?php if ( ! empty( $labels[0] ) ) { ?>
			<span class="label <?php echo esc_attr( strtolower( $labels[0]['color'] ) ); ?>"><?php echo esc_html( $labels[0]['name'] ); ?></span>
		<?php } ?>
		<img src="<?php echo esc_url( $image ); ?>" width="370" height="240" alt="Property image">
	</div>
	<div class="property-card-info">
		<div class="price-title">
			<h3><?php echo esc_html( $price ); ?></h3>
			<?php if ( ! empty( $discount ) ) { ?>
				<div class="discount">Discount:<span><?php echo esc_html( $discount ); ?>%</span></div>
			<?php } ?>
		</div>
		
		<?php if ( ! empty( $original_price ) ) { ?>
			<span class="original-price">
			<?php
			esc_html_e( 'Original Price:' , 'east-property' );
			echo '&nbsp;' . esc_html( $original_price );
			?>
		</span>
		<?php } ?>

		<p><?php echo esc_html( $title ); ?></p>

		<span class="real-estate"><?php echo esc_html( $property_name ); ?></span>

		<?php if ( ! empty( $amenities ) ) { ?>
			<div class="property-card-items">
				<?php foreach ( $amenities as $amenity ) { ?>
					<div class="property-card-item">
						<img src="<?php echo esc_url( $amenity['icon'] ); ?>" width="16" height="16"
						     alt="<?php echo esc_html( $amenity['value'] ); ?>">
						<span><?php echo esc_html( $amenity['value'] ); ?></span>
					</div>
				<?php } ?>
			</div>
		<?php } ?>
	</div>
</a>