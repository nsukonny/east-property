<?php
/**
 * Property card component
 *
 * @var Entities\Property $property
 */

$property = $args['property'] ?? null;
if ( $property === null || ! $property->exists() ) {
    return;
}

$apartments_params = $property->get_apartments_params( 3 );
$labels            = $property->get_labels();
?>
<a href="<?php echo esc_url( $property->get_url() ); ?>" class="property-card">
    <div class="property-card-img">
        <?php if ( ! empty( $labels[0] ) ) { ?>
            <span class="label <?php echo esc_attr( strtolower( $labels[0]['color'] ) ); ?>"><?php echo esc_html( $labels[0]['name'] ); ?></span>
        <?php } ?>
        <img src="<?php echo esc_url( $property->get_thumb() ); ?>" width="370" height="240" alt="Property image">
    </div>
    <div class="property-card-info">
        <h3><?php echo esc_html( $property->get_price_html() ); ?></h3>
        <p><?php echo esc_html( $property->get_title() ); ?></p>

        <?php if ( ! empty( $apartments_params ) ) { ?>
            <div class="property-card-items">
                <?php foreach ( $apartments_params as $params ) { ?>
                    <div class="property-card-item">
                        <img src="<?php echo esc_url( $params['icon'] ); ?>" width="16" height="16" alt="Vector icon">
                        <span><?php echo esc_html( $params['value'] ); ?></span>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</a>