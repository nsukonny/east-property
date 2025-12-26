<?php
/**
 * Product card component
 */
$post = $args['product'] ?? null;
if ( empty( $post ) ) {
    return;
}

$product = wc_get_product( $post );
if ( empty( $product ) ) {
    return;
}

$stickers    = get_field( 'stickers', $post->ID );
$brand_terms = wp_get_post_terms( $post->ID, 'product_brand' );
$brand_name  = $brand_terms[0]->name ?? '';
$price       = $product->get_price();
$old_price   = $product->get_regular_price();
?>
<div class="card" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
     data-price-old="<?php echo esc_attr( $old_price ); ?>" data-price-new="<?php echo esc_attr( $price ); ?>">
    <div class="card-inner">
        <a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="card-img">
            <?php echo $product->get_image(); ?>
        </a>
        <div class="card-info">
            <div class="card-info-top">
                <?php if ( ! empty( $stickers ) ) { ?>
                    <div class="card-labels">
                        <?php foreach ( $stickers as $sticker ) { ?>
                            <span class="color-label <?php echo esc_attr( $sticker['value'] ); ?>">
                                <?php echo esc_html( $sticker['label'] ); ?>
                            </span>
                        <?php } ?>
                    </div>
                <?php } ?>
                <div class="card-rate">
                    <img src="<?php echo THEME_URL; ?>/assets/img/star.svg" width="23" height="23"
                         alt="Векторная звезда рейтинга">
                    <span>4.8/5</span>
                </div>
            </div>
            <div class="card-desc">
                <div class="card-title">
                    <h5>
                        <em><?php echo esc_html( $product->get_title() ); ?></em>
                        <?php echo esc_html( $brand_name ); ?>
                    </h5>
                </div>
                <div class="card-stat">
                    <div class="card-row">
                        <div class="card-col">
                            <div class="card-col-item">
                                <img src="<?php echo THEME_URL; ?>/assets/img/thc.svg" width="20" height="20"
                                     alt="Векторная иконка">
                                <span>30-33%</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-row">
                        <div class="card-col-item">
                            <div class="card-col-type">
                                <div class="left">
                                    <span>Тип семян:</span>
                                    <div class="types">
                                        <span class="type active">A</span>/
                                        <span class="type">F</span>/
                                        <span class="type">R</span>
                                    </div>
                                </div>
                                <div class="right">
                                    <img src="<?php echo THEME_URL; ?>/assets/img/height.svg" width="20" height="20"
                                         alt="Векторная иконка">
                                    <span>70см</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-row">
                        <div class="card-col-item">
                            <img src="<?php echo THEME_URL; ?>/assets/img/insa.svg" width="20" height="20"
                                 alt="Векторная иконка">
                            <span>Преимущественно сатива</span>
                        </div>
                    </div>
                    <div class="card-row">
                        <div class="card-col-item">
                            <img src="<?php echo THEME_URL; ?>/assets/img/sand-watch.svg" width="20" height="20"
                                 alt="Векторная иконка">
                            <span>Bruce Banner x Lemon OG</span>
                        </div>
                    </div>
                </div>
                <div class="card-price">
                    <?php if ( $old_price > $price ) { ?>
                        <span class="old-price"><?php echo esc_html( $old_price ); ?> р</span>
                    <?php } ?>

                    <span class="new-price"><?php echo esc_html( $price ); ?> р</span>
                </div>
                <div class="card-nums">
                    <button class="button borderless square active">1</button>
                    <button class="button borderless square">2</button>
                    <button class="button borderless square">3</button>
                </div>
                <div class="card-bottom">
                    <div class="card-counter">
                        <button class="decr"><span></span></button>
                        <span class="card-counter-value">1</span>
                        <button class="incr"><span></span></button>
                    </div>
                    <button class="button filled add">
                        Корзина
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>