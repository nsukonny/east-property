<?php
/**
 * Large card template
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
<a href="<?php echo esc_url( $url ); ?>" class="large-card">
    <div class="large-card-inner">
        <div class="large-card-left">
            <div class="large-card-images">
                <div class="large-card-images-left">
                    <?php if ( ! empty( $first_image ) ) { ?>
                        <img src="<?php echo esc_url( $first_image['sizes']['large'] ); ?>" alt="Image">
                    <?php } ?>
                </div>
                <?php if ( ! empty( $gallery ) ) { ?>
                    <div class="large-card-images-right">
                        <?php foreach ( $gallery as $image ) { ?>
                            <img src="<?php echo esc_url( $image['sizes']['medium'] ); ?>" alt="Image">
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>
        <div class="large-card-right">
            <div class="large-card-top">
                <div class="large-card-top-left">
                    <div class="large-card-top-title">
                        <span class="subtext"><?php _e( 'from' ); ?></span>
                        <span class="large-card-price">
                            <?php echo esc_html( $price ); ?>
                        </span>
                    </div>
                    <span class="large-card-dev">
                        <?php echo esc_html( $title ); ?>
                    </span>
                </div>
                <?php if ( ! empty( $labels ) ) { ?>
                    <div class="large-card-top-right">
                        <div class="large-card-labels">
                            <?php foreach ( $labels as $label ) { ?>
                                <div class="label <?php echo esc_attr( $label['color'] ); ?>">
                                    <?php echo strtoupper( esc_html( $label['name'] ) ); ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <div class="large-card-bottom">
                <div class="large-card-info">
                    <?php if ( ! empty( $amenities ) ) { ?>
                        <div class="large-card-info-top">
                            <div class="large-card-info-items">
                                <?php foreach ( $amenities as $amenity ) { ?>
                                    <span>
                                        <img src="<?php echo esc_url( $amenity['icon'] ); ?>" width="16" height="16"
                                             alt="<?php _e( 'Vector icon' ); ?>">
                                        <?php echo esc_html( $amenity['value'] ); ?>
                                    </span>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="large-card-info-bottom">
                        <?php if ( ! empty( $location ) ) { ?>
                            <p><?php echo esc_html( $location ); ?></p>
                        <?php } ?>
                        <div class="view-details">
                            <?php _e( 'View details' ); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</a>