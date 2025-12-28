<?php
/**
 * Featured properties section
 */

$properties = $args['properties'] ?? null;

$h2          = $args['h2'] ?? '';
$description = $args['description'] ?? '';
$href        = $args['href'] ?? '#';
$link_text   = $args['link_text'] ?? __( 'See all new properties' );
?>
<section class="properties">
    <div class="container">
        <div class="properties-wrapper">
            <?php
            get_template_part( 'template-parts/components/titles/top-title', null,
                    array(
                            'h2'   => $h2,
                            'desc' => $description,
                            'href' => $href,
                            'link' => $link_text,
                    )
            );
            ?>

            <?php if ( ! empty( $properties ) ) { ?>
                <div class="properties-cards">
                    <?php
                    foreach ( $properties as $property ) {
                        get_template_part( 'template-parts/components/cards/property-card', null,
                                array(
                                        'property' => $property,
                                )
                        );
                    }
                    ?>
                </div>
            <?php } ?>
        </div>
    </div>
</section>