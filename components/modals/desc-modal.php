<?php
/**
 * Modal: Description Modal
 */

$title = get_the_title();
$desc  = get_the_content();

if ( empty( $title ) || empty( $desc ) ) {
    return;
}
?>
<div class="modal-wrapper desc-modal" data-modal-id="desc-modal">
    <div class="modal">
        <div class="modal-info">
            <div class="modal-title">
                <h3><?php echo esc_html( $title ); ?></h3>
                <button class="modal-close" data-modal-close aria-label="Close">
                    <img src="<?php echo THEME_URL; ?>/assets/img/close.svg" width="24" height="24" alt="Close icon">
                </button>
            </div>
            <div class="modal-desc">
                <div class="modal-desc-content">
                    <p><?php echo apply_filters( 'the_content', $desc ); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>