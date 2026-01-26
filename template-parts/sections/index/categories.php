<?php
/**
 * Categories section template
 */

$properties = get_posts(
        array(
                'post_type'      => 'properties',
                'posts_per_page' => - 1,
                'post_status'    => 'publish',
        )
);

$apartments_count = count( $properties );
$houses_count     = 0;
$offices_count    = 0;
$villas_count     = 0;
?>
<section class="categories">
    <div class="container">
        <div class="categories-wrapper">
            <?php
            get_template_part( 'components/titles/top-title', null,
                    array(
                            'h2'   => __( 'Explore new properties' ),
                            'desc' => __( 'Quick picks for you in UAE' ),
                            'href' => home_url( '/properties/' ),
                            'link' => __( 'All Types' ),
                    )
            );
            ?>
            <div class="categories-cards">
                <a href="<?php echo esc_url( home_url( '/properties/?type=apartments' ) ); ?>" class="category-card">
                    <img class="category-card-bg" src="<?php echo THEME_URL; ?>/assets/img/c1.png" width="270"
                         height="270" alt="Category image">
                    <div class="category-card-desc">
                        <h3>
                            <?php _e( 'Apartments' ); ?>
                        </h3>
                        <p><?php echo esc_attr( $apartments_count ); ?>
                            <?php _e( 'Properties' ); ?>
                        </p>
                    </div>
                </a>
                <a href="<?php echo esc_url( home_url( '/properties/?type=houses' ) ); ?>" class="category-card">
                    <img class="category-card-bg" src="<?php echo THEME_URL; ?>/assets/img/c2.png" width="270"
                         height="270" alt="Category image">
                    <div class="category-card-desc">
                        <h3>
                            <?php _e( 'Family homes' ); ?>
                        </h3>
                        <p>
                            <?php echo esc_attr( $houses_count ); ?>
                            <?php _e( 'Properties' ); ?>
                        </p>
                    </div>
                </a>
                <a href="<?php echo esc_url( home_url( '/properties/?type=offices' ) ); ?>" class="category-card">
                    <img class="category-card-bg" src="<?php echo THEME_URL; ?>/assets/img/c3.png" width="270"
                         height="270" alt="Category image">
                    <div class="category-card-desc">
                        <h3>
                            <?php _e( 'Offices' ); ?>
                        </h3>
                        <p>
                            <?php echo esc_attr( $offices_count ); ?>
                            <?php _e( 'Properties' ); ?>
                        </p>
                    </div>
                </a>
                <a href="<?php echo esc_url( home_url( '/properties/?type=villas' ) ); ?>" class="category-card">
                    <img class="category-card-bg" src="<?php echo THEME_URL; ?>/assets/img/c4.png" width="270"
                         height="270" alt="<?php _e( 'Category image' ); ?>">
                    <div class="category-card-desc">
                        <h3>
                            <?php _e( 'Villas' ); ?>
                        </h3>
                        <p>
                            <?php echo esc_attr( $villas_count ); ?>
                            <?php _e( 'Properties' ); ?>
                        </p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>