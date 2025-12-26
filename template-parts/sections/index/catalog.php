<?php
/**
 * Template for catalog section
 *
 * @var WP_Term $term
 * @var WP_Post[] $products
 */

$term     = $args['term'] ?? null;
$products = $args['products'] ?? null;

if ( empty( $term ) || empty( $products ) ) {
    return;
}
?>
<section class="catalog">
    <h2 class="sr-only">Скрытый сео заголовок</h2>
    <div class="container">
        <div class="catalog-wrapper">
            <div class="catalog-top">
                <div class="catalog-top-title">
                    <h3><?php echo esc_html( $term->name ); ?></h3>
                </div>
                <a href="<?php echo esc_url( get_term_link( $term ) ); ?>">
                    <?php _e( 'Смотреть все' ); ?>
                </a>
            </div>

            <div class="catalog-items">
                <?php
                foreach ( $products as $product ) {
                    get_template_part( 'template-parts/components/card', null,
                            array(
                                    'product' => $product,
                            )
                    );
                }
                ?>
            </div>
        </div>
    </div>
</section>