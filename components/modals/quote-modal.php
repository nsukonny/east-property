<?php
/**
 * Quote request modal
 *
 * @var WP_User $broker
 */

use Entities\Developer;

$brokers = get_users(
        array(
                'role'    => 'broker',
                'orderby' => 'user_nicename',
                'order'   => 'ASC',
                'number'  => 1,
        )
);

if ( empty( $brokers ) ) {
    return;
}

global $post;

$broker         = array_shift( $brokers );
$whats_app_link = get_user_meta( $broker->ID, 'whats_app', true );
$property       = new \Entities\Property( $post );
$developer      = $property->get_developer();
?>
<div class="modal-wrapper quote-modal" data-modal-id="quote-modal">
    <div class="modal">
        <div class="modal-info">
            <div class="modal-title">
                <h3>
                    <?php _e( 'Request a quote' ); ?>
                </h3>
                <button class="modal-close" data-modal-close aria-label="Close">
                    <img src="<?php echo THEME_URL; ?>/assets/img/close.svg" width="24" height="24" alt="Close icon">
                </button>
            </div>
            <div class="modal-info-inner">
                <p>
                    <?php _e( 'You can get more information about the available properties, special deals and prices.' ); ?>
                </p>
                <ul>
                    <li>
                        <img src="<?php echo THEME_URL; ?>/assets/img/key.svg" width="16" height="16" alt="vector icon">
                        <?php _e( 'We work directly with builders' ); ?>
                    </li>
                    <li>
                        <img src="<?php echo THEME_URL; ?>/assets/img/secure.svg" width="16" height="16"
                             alt="vector icon">
                        <?php _e( 'We facilitate the entire process for you We facilitate the entire process for you' ); ?>
                    </li>
                    <li>
                        <img src="<?php echo THEME_URL; ?>/assets/img/money.svg" width="16" height="16"
                             alt="vector icon">
                        <?php _e( 'We don’t add anything on top of the price' ); ?>
                    </li>
                </ul>
            </div>
            <div class="modal-links">
                <?php if ( ! empty( $developer['url'] ) ) { ?>
                    <a href="<?php echo esc_url( $developer['url'] ); ?>" target="_blank" rel="noopener noreferrer"
                       class="button sm gray">
                        <img src="<?php echo THEME_URL; ?>/assets/img/link.svg" width="16" height="16"
                             alt="vector link">
                        <?php _e( 'Developer website' ); ?>
                    </a>
                <?php } ?>

                <?php if ( ! empty( $whats_app_link ) ) { ?>
                    <a href="<?php echo esc_url( $whats_app_link ); ?>" target="_blank" rel="noopener noreferrer"
                       class="button sm orange">
                        <img src="<?php echo THEME_URL; ?>/assets/img/call.svg" width="16" height="16"
                             alt="vector link">
                        <?php _e( 'Contact us' ); ?>
                    </a>
                <?php } ?>
            </div>
        </div>
    </div>
</div>