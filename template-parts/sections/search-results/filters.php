<?php
/**
 * Search results filters section
 */
$h2         = $args['h2'] ?? '';
$properties = $args['properties'] ?? array();
$units      = $args['units'] ?? array();
?>
<section class="results-filters">
    <h2 class="sr-only"><?php echo esc_html( $h2 ); ?></h2>
    <div class="container">
        <?php
        get_template_part( 'components/filters/result-filters', null,
                array(
                        'post_type'   => ! empty( $properties ) ? 'properties' : 'unit',
                        'total_found' => ! empty( $properties ) ? count( $properties ) : count( $units ),
                )
        );
        ?>
    </div>
</section>