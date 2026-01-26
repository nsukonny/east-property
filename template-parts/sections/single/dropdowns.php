<?php
/**
 * Display Unit prices
 *
 * @var \Entities\Unit $unit
 */

$units = $args['units'] ?? null;
if ( ! $units ) {
    return;
}

$beds  = $args['beds'] ?? 1;
$baths = $args['min_baths'] . ' - ' . $args['max_baths'];
if ( $args['min_baths'] === $args['max_baths'] || ( empty( $args['min_baths'] ) || empty( $args['max_baths'] ) ) ) {
    $baths = $args['min_baths'];
}

$area = $args['min_area'] . ' - ' . $args['max_area'];
if ( $args['min_area'] === $args['max_area'] || ( empty( $args['min_area'] ) || empty( $args['max_area'] ) ) ) {
    $area = $args['min_area'];
}

$price = sprintf( '%s %s', __( 'AED' ), number_format( (float) $args['price'], 0, '.', ',' ) );
?>
<div class="dropdown info-dropdown">
    <button class="dropdown-button" type="button" aria-haspopup="true" aria-expanded="false">
        <span class="dropdown-arrow">
            <img src="<?php echo THEME_URL; ?>/assets/img/arrow-down.svg" width="16" height="16" alt="vector arrow">
        </span>
        <span class="dropdown-row">
            <?php if ( ! empty( $beds ) ) { ?>
                <span class="button-row-item">
                <img class="button-row-ico" src="<?php echo THEME_URL; ?>/assets/img/bed.svg" width="16" height="16"
                     alt="">
                <span class="button-row-text"><?php echo esc_html( $beds ); ?><?php echo ' ' . __( 'Bed' ); ?></span>
            </span>
            <?php } ?>

            <?php if ( ! empty( $baths ) ) { ?>
                <span class="button-row-item">
                <img class="button-row-ico" src="<?php echo THEME_URL; ?>/assets/img/bath.svg" width="16" height="16"
                     alt="">
                <span class="button-row-text"><?php echo esc_html( $baths ); ?><?php echo ' ' . __( 'Baths' ); ?></span>
            </span>
            <?php } ?>

            <?php if ( ! empty( $area ) ) { ?>
                <span class="button-row-item">
                <img class="button-row-ico" src="<?php echo THEME_URL; ?>/assets/img/meters.svg" width="16" height="16"
                     alt="">
                <span class="button-row-text"><?php echo esc_html( $area ); ?><?php echo ' ' . __( 'sqft' ); ?></span>
            </span>
            <?php } ?>

             <span class="button-row-item">
                <span class="button-row-price"><?php _e( 'from' ); ?> <em><?php echo esc_html( $price ); ?></em></span>
            </span>
        </span>
    </button>
    <div class="dropdown-content">
        <div class="dropdown-inner">
            <?php foreach ( $units as $unit ) { ?>
                <div class="dropdown-content-row">
                    <div class="dropdown-content-col">
                        <img src="<?php echo THEME_URL; ?>/assets/img/bed.svg" width="16" height="16" alt="">
                        <span class="button-row-text"><?php echo esc_html( $unit->get_beds() ); ?><?php echo ' ' . __( 'Bed' ); ?></span>
                    </div>
                    <div class="dropdown-content-col">
                        <img src="<?php echo THEME_URL; ?>/assets/img/bath.svg" width="16" height="16" alt="">
                        <span class="button-row-text"><?php echo esc_html( $unit->get_baths() ); ?><?php echo ' ' . __( 'Baths' ); ?></span>
                    </div>
                    <div class="dropdown-content-col">
                        <img src="<?php echo THEME_URL; ?>/assets/img/meters.svg" width="16" height="16" alt="">
                        <span class="button-row-text"><?php echo esc_html( $unit->get_area() ); ?><?php echo ' ' . __( 'sqft' ); ?></span>
                    </div>
                    <div class="dropdown-content-col image-col">
                        <div data-modal-open="plan-modal">
                            <img src="<?php echo esc_url( $unit->get_thumb() ); ?>" width="851" height="575"
                                 alt="image">
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>