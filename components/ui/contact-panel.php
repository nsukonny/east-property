<div class="contact-panel">
    <div class="contact-panel-inner">
        <div class="contact-panel-left">
            <p>
                <?php _e( 'Contact our agent to learn more and get special offers.' ); ?>
            </p>
        </div>
        <div class="contact-panel-right">
            <?php
            get_template_part( 'components/ui/button', null,
                    array(
                            'class' => 'gray sm bookmark',
                            'text'  => __( 'Save' ),
                            'src'   => THEME_URL . '/assets/img/bookmark.svg',
                    )
            );

            get_template_part( 'components/ui/button', null,
                    array(
                            'class' => 'orange sm request-quote',
                            'text'  => __( 'Request a quote' ),
                            'src'   => THEME_URL . '/assets/img/call.svg',
                            'modal' => 'quote-modal',
                    )
            );
            ?>
        </div>
    </div>
</div>