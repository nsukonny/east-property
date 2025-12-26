<?php
/**
 * Display list of brands and category buttons
 */

$brands = get_terms(
        array(
                'taxonomy'   => 'product_brand',
                'orderby'    => 'name',
                'order'      => 'ASC',
                'hide_empty' => false, //TODO hide after run
        )
);

$categories = get_terms(
        array(
                'taxonomy'   => 'product_cat',
                'orderby'    => 'name',
                'order'      => 'ASC',
                'hide_empty' => false, //TODO hide after run
                'parent'     => 0,
        )
);

if ( empty( $brands ) && empty( $categories ) ) {
    return;
}

$seo_title = get_field( 'seo_h1_title', 'option' ) ?? null;
$welcome   = get_field( 'home_welcome', 'option' ) ?? array();
?>
<section class="columns">
    <?php if ( ! empty( $seo_title ) ) { ?>
        <h1 class="sr-only"><?php echo esc_html( $seo_title ); ?></h1>
    <?php } ?>
    <div class="columns-title">
        <?php if ( ! empty( $welcome['title'] ) ) { ?>
            <h2><?php echo esc_html( $welcome['title'] ); ?></h2>
        <?php } ?>

        <?php if ( ! empty( $welcome['description'] ) ) { ?>
            <h3><?php echo esc_html( $welcome['description'] ); ?></h3>
        <?php } ?>
    </div>
    <div class="container">
        <div class="columns-inner">
            <?php if ( ! empty( $brands ) ) { ?>
                <div class="columns-items">
                    <?php foreach ( $brands as $brand ) { ?>
                        <a href="<?php echo esc_url( get_term_link( $brand ) ); ?>">
                            <?php echo esc_html( $brand->name ); ?>
                        </a>
                    <?php } ?>
                </div>
            <?php } ?>

            <?php if ( ! empty( $categories ) ) { ?>
                <div class="columns-buttons">
                    <?php foreach ( $categories as $category ) { ?>
                        <a href="<?php echo esc_url( get_term_link( $category ) ); ?>"
                           class="button borderless column-button"
                        >
                            <?php echo esc_html( $category->name ); ?>
                        </a>
                    <?php } ?>
                    <a href="#" class="button borderless column-button">
                        <?php _e( 'Все бренды' ); ?>
                    </a>
                </div>
            <?php } ?>
        </div>

    </div>
</section>