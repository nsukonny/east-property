</main>
<?php
get_template_part( 'core/components/common/footer',
        null,
        array(
                'modals' => array(
                        'image-modal',
                        'desc-modal',
                        'quote-modal',
                        'create-modal',
                        'signin-modal',
                        'forgot-modal',
                        'broker-modal',
                ),
        )
);
?>
</body>
</html>