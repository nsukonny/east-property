<?php
/**
 * Small card template
 *
 * @var array $args
 */

$title     = $args['title'] ?: '';
$price     = $args['price'] ?: '';
$location  = $args['location'] ?: '';
$gallery   = $args['gallery'] ?: '';
$labels    = $args['labels'] ?: '';
$amenities = $args['amenities'] ?: '';
$url       = $args['url'] ?: '#';

if ( empty( $title ) || empty( $price ) || empty( $gallery ) ) {
    return;
}

$first_image = $gallery[0];
unset( $gallery[0] );
?>
<div class="small-card">
    <div class="small-card-inner">
        <div class="small-card-left">
            <div class="small-card-img">
                <?php if ( ! empty( $labels[0] ) ) { ?>
                    <span class="small-card-date"><?php echo esc_html( $labels[0]['name'] ); ?></span>
                <?php } ?>

                <?php if ( ! empty( $first_image ) ) { ?>
                    <img src="<?php echo esc_url( $first_image['sizes']['large'] ); ?>" alt="Image">
                <?php } ?>
            </div>
        </div>
        <div class="small-card-right">
            <div class="small-card-title">
                <span><?php echo esc_html( $price ); ?></span>
                <p><?php echo esc_html( $title ); ?></p>
            </div>
            <?php if ( ! empty( $amenities ) ) { ?>
                <div class="small-card-items">
                    <?php foreach ( $amenities as $amenity ) { ?>
                        <span>
                            <img src="<?php echo esc_url( $amenity['icon'] ); ?>" width="16" height="16"
                                 alt="<?php _e( 'Vector icon' ); ?>">
                            <?php echo esc_html( $amenity['value'] ); ?>
                        </span>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </div>
</div>