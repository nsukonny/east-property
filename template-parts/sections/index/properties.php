<?php
/**
 * Featured properties section
 */

$properties = $args['properties'] ?? null;

if ( empty( $properties ) ) {
    return;
}

$first_property = reset( $properties );
$segment        = $first_property->get_segment();
?>
<section class="properties">
    <div class="container">
        <div class="properties-wrapper">
            <?php
            get_template_part( 'template-parts/components/titles/top-title', null,
                    array(
                            'h2'   => __( 'Featured new projects in the UAE' ),
                            'desc' => __( 'Aliquam lacinia diam quis lacus euismod' ),
                            'href' => ! empty( $segment['id'] ) ? get_term_link( (int) $segment['id'] ) : '#',
                            'link' => __( 'See all new properties' ),
                    )
            );
            ?>
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
        </div>
    </div>
</section>