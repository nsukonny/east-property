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

$beds = $args['beds'] ?? 1;

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
            <?php foreach ( $units as $unit ) {
                $unit_baths       = $unit->get_baths();
                $unit_beds        = $unit->get_beds();
                $unit_area        = $unit->get_area();
                $unit_floor_plans = $unit->get_floor_plan();
                ?>
                <div class="dropdown-content-row">
                    <?php if ( ! empty( $unit_beds ) ) { ?>
                        <div class="dropdown-content-col">
                            <img src="<?php echo THEME_URL; ?>/assets/img/bed.svg" width="16" height="16" alt="">
                            <span class="button-row-text"><?php echo esc_html( $unit->get_beds() ); ?><?php echo ' ' . __( 'Bed' ); ?></span>
                        </div>
                    <?php } ?>

                    <?php if ( ! empty( $unit_baths ) ) { ?>
                        <div class="dropdown-content-col">
                            <img src="<?php echo THEME_URL; ?>/assets/img/bath.svg" width="16" height="16" alt="">
                            <span class="button-row-text"><?php echo esc_html( $unit_baths ); ?><?php echo ' ' . __( 'Baths' ); ?></span>
                        </div>
                    <?php } ?>

                    <?php if ( ! empty( $unit_area ) ) { ?>
                        <div class="dropdown-content-col">
                            <img src="<?php echo THEME_URL; ?>/assets/img/meters.svg" width="16" height="16" alt="">
                            <span class="button-row-text"><?php echo esc_html( $unit->get_area() ); ?><?php echo ' ' . __( 'sqft' ); ?></span>
                        </div>
                    <?php } ?>

                    <?php if ( ! empty( $unit_floor_plans ) ) { ?>
                        <div class="dropdown-content-col image-col">
                            <?php foreach ( $unit_floor_plans as $unit_floor_plan ) { ?>
                                <div data-modal-open="plan-modal">
                                    <img src="<?php echo esc_url( $unit_floor_plan['layout']['sizes']['large'] ); ?>"
                                         alt="<?php echo esc_attr( $unit_floor_plan['layout']['name'] ); ?>">
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </div>
</div>