<?php
$tags = get_terms(
        array(
                'taxonomy'   => 'product_tag',
                'orderby'    => 'name',
                'order'      => 'ASC',
                'hide_empty' => false, //TODO hide after run
        )
);

if ( empty( $tags ) ) {
    return;
}

$tags_data = get_field( 'tags', 'option' );


$limit = $tags_data['limit'] ?? 20;
$limit = (int) $limit;
?>
<section class="links">
    <?php if ( ! empty( $tags_data['seo_title'] ) ) { ?>
        <h2 class="sr-only"><?php echo esc_html( $tags_data['seo_title'] ); ?></h2>
    <?php } ?>
    <div class="container">
        <div class="links-items">
            <?php foreach ( $tags as $tag ) { ?>
                <a class="link borderless" href="<?php echo esc_url( get_term_link( $tag ) ); ?>">
                    <?php echo esc_html( $tag->name ); ?>
                </a>
                <?php
                $limit --;

                if ( 0 > $limit ) {
                    break;
                }
            } ?>
        </div>
        <?php if ( 20 < count( $tags ) ) { ?>
            <div class="view-all">
                <a href="#"><?php _e( 'Посмотреть все теги' ); ?></a>
            </div>
        <?php } ?>
    </div>
</section>