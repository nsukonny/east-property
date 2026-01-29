<?php
/**
 * Property card component
 *
 * @var Entities\Property $property
 */

$labels        = $args['labels'] ?? '';
$image         = $args['image'] ?? '';
$price         = $args['price'] ?? '';
$title         = $args['title'] ?? '';
$property_name = $args['property_name'] ?? '';
$property_url  = $args['property_url'] ?? '#';
$amenities     = $args['amenities'] ?? '';

if ( empty( $title ) || empty( $price ) || empty( $image ) ) {
    return;
}
?>
<div class="property-card">
    <a href="#" class="property-card-img">
        <?php if ( ! empty( $labels[0] ) ) { ?>
            <span class="label <?php echo esc_attr( strtolower( $labels[0]['color'] ) ); ?>"><?php echo esc_html( $labels[0]['name'] ); ?></span>
        <?php } ?>
        <img src="<?php echo esc_url( $image ); ?>" alt="Property image">
    </a>
    <div class="property-card-info">
        <h3><?php echo esc_html( $price ); ?></h3>
        <p><?php echo esc_html( $title ); ?></p>

        <?php if ( ! empty( $property_name ) ) { ?>
            <a href="<?php echo esc_url( $property_url ); ?>"><?php echo esc_html( $property_name ); ?></a>
        <?php } ?>

        <?php if ( ! empty( $amenities ) ) { ?>
            <div class="property-card-items">
                <?php foreach ( $amenities as $amenity ) { ?>
                    <div class="property-card-item">
                        <img src="<?php echo esc_url( $amenity['icon'] ); ?>" width="16" height="16" alt="Vector icon">
                        <span><?php echo esc_html( $amenity['value'] ); ?></span>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>