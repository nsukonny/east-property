<?php
/**
 * Property card component
 *
 * @var \MessiaTheme\Entities\Property $property
 */

$property = $args['property'] ?? null;
if ( $property === null || ! $property->exists() ) {
    return;
}

$amenities = $property->get_amenities( 3 );
?>
<a href="<?php echo esc_url( $property->get_url() ); ?>" class="property-card">
    <div class="property-card-img">
        <span class="label"><?php echo esc_html( $property->get_delivery_date() ); ?></span>
        <img src="<?php echo esc_url( $property->get_thumb() ); ?>" width="370" height="240" alt="Property image">
    </div>
    <div class="property-card-info">
        <h3><?php echo esc_html( $property->get_price_html() ); ?></h3>
        <p><?php echo esc_html( $property->get_title() ); ?></p>

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
</a>