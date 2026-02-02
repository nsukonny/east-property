<?php
/**
 * Broker contact panel component
 */

?>
<div class="contact-panel" id="broker-panel">
    <div class="contact-panel-inner">
        <div class="contact-panel-left">
            <p>
                <?php _e( 'Contact broker to learn more and get special offers.' ); ?>
            </p>
        </div>
        <div class="contact-panel-right">
            <?php
            get_template_part( 'components/ui/button', null,
                    array(
                            'class' => 'gray sm',
                            'text'  => __( 'Save' ),
                            'src'   => THEME_URL . '/assets/img/bookmark.svg',
                            'alt'   => __( 'Save' ),
                    )
            );

            get_template_part( 'components/ui/button', null,
                    array(
                            'class' => 'orange sm',
                            'text'  => __( 'Contact broker' ),
                            'modal' => 'broker-modal',
                    )
            );
            ?>
        </div>
    </div>
</div>