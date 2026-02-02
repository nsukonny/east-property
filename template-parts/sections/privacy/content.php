<?php
/**
 * Text page content
 */

$title       = $args['title'] ?? '';
$description = $args['description'] ?? '';
?>
<section class="privacy">
    <div class="container">
        <div class="privacy-wrapper">
            <h1><?php echo esc_html( $title ); ?></h1>

            <?php echo apply_filters( 'the_content', $description ); ?>

        </div>
    </div>
</section>