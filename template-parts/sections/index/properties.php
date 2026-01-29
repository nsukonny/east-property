<?php
/**
 * Featured properties section
 */

$units = $args['units'] ?? null;

$h2            = $args['h2'] ?? '';
$description   = $args['description'] ?? '';
$href          = $args['href'] ?? '#';
$link_text     = $args['link_text'] ?? __( 'See all new properties' );
$card_template = $args['card_template'] ?? 'unit-card';
?>
<section class="properties">
    <div class="container">
        <div class="properties-wrapper">
            <?php
            get_template_part( 'components/titles/top-title', null,
                    array(
                            'h2'   => $h2,
                            'desc' => $description,
                            'href' => $href,
                            'link' => $link_text,
                    )
            );
            ?>

            <?php if ( ! empty( $units ) ) { ?>
                <div class="properties-cards">
                    <?php
                    foreach ( $units as $unit ) {
                        get_template_part( 'components/cards/unit-card', null,
                                array(
                                        'unit'     => $unit,
                                        'template' => $card_template,
                                )
                        );
                    }
                    ?>
                </div>
            <?php } ?>
        </div>
    </div>
</section>