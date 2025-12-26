<?php
/**
 * Slider section on the homepage
 */

$new_items = get_field( 'new_items', 'option' ); //TODO Replace it with sale_items

$categories = get_terms(
        array(
                'taxonomy'   => 'product_cat',
                'orderby'    => 'name',
                'order'      => 'ASC',
                'hide_empty' => true,
                'parent'     => 0,
                'exclude'    => array(
                        get_term_by( 'slug', 'sale', 'product_cat' )->term_id //TODO move to setting
                )
        )
);

if ( empty( $categories ) ) {
    return;
}

$products = get_posts(
        array(
                'post_type'      => 'product',
                'posts_per_page' => - 1,
                'tax_query'      => array(
                        array(
                                'taxonomy' => 'product_cat',
                                'field'    => 'slug',
                                'terms'    => 'sale', //TODO move to setting
                        ),
                ),
        )
);
?>
<section class="cards-slider">
    <h2 class="sr-only">Заголовок для скринридеров</h2>
    <div class="container">
        <div class="cards-slider-wrapper">
            <div class="cards-slider-content">
                <div class="cards-slider-actions">
                    <h4>Товары на акции</h4>
                    <div class="slider-actions-buttons">
                        <div class="tab-buttons">
                            <?php foreach ( $categories as $key => $category ) { ?>
                                <button class="button borderless <?php if ( 0 === $key ) { ?>active<?php } ?>"
                                        data-tab="<?php echo esc_attr( $category->slug ); ?>">
                                    <?php echo esc_html( $category->name ); ?>
                                </button>
                            <?php } ?>
                        </div>
                        <div class="swiper-navigation">
                            <button class="swiper-prev">
                                <img src="<?php echo THEME_URL; ?>/assets/img/slide-arrow.svg" width="20" height="20"
                                     alt="Векторная стрелка">
                            </button>
                            <button class="swiper-next">
                                <img src="<?php echo THEME_URL; ?>/assets/img/slide-arrow.svg" width="20" height="20"
                                     alt="Векторная стрелка">
                            </button>
                        </div>
                    </div>
                </div>
                <div class="tabs-content-wrapper">
                    <?php
                    get_template_part( 'template-parts/components/cta-card', null,
                            array(
                                    'class'       => 'mob',
                                    'src'         => $new_items['ad_image'] ?? '',
                                    'title'       => $new_items['ad_title'] ?? '',
                                    'description' => $new_items['ad_description'] ?? '',
                                    'link'        => $new_items['ad_link'] ?? '#',
                            )
                    );
                    ?>
                    <div class="tabs-content">
                        <?php foreach ( $categories as $key => $category ) { ?>
                            <div class="tab-content <?php if ( 0 === $key ) { ?>active<?php } ?>"
                                 data-tab="<?php echo esc_attr( $category->slug ); ?>">
                                <div class="swiper swiper-cards">
                                    <div class="swiper-wrapper">
                                        <?php foreach ( $products as $product ) {
                                            $product_terms = get_the_terms( $product->ID, 'product_cat' );

                                            if ( empty( $product_terms ) || is_wp_error( $product_terms ) ) {
                                                continue;
                                            }

                                            $term_ids = array_map( 'intval', wp_list_pluck( $product_terms, 'term_id' ) );

                                            if ( ! in_array( (int) $category->term_id, $term_ids, true ) ) {
                                                continue;
                                            }
                                            ?>
                                            <div class="swiper-slide">
                                                <?php
                                                get_template_part( 'template-parts/components/card', null,
                                                        array(
                                                                'product' => $product,
                                                        )
                                                );
                                                ?>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>