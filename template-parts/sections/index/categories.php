<?php
/**
 * Categories section template
 */

$apartments_count = wp_count_posts( 'unit' )->publish;;
$houses_count       = ceil( $apartments_count / 3 );
$new_projects_count = wp_count_posts( 'property' )->publish;
$villas_count       = ceil( $new_projects_count / 3 );
?>
<section class="categories">
    <div class="container">
        <div class="categories-wrapper">
            <?php
            get_template_part( 'core/components/titles/top-title', null,
                    array(
                            'h2'   => __( 'Explore new properties' ),
                            'desc' => __( 'Quick picks for you in UAE' ),
                            'href' => home_url( '/properties/' ),
                            'link' => __( 'All Types' ),
                    )
            );
            ?>
            <div class="categories-cards">
                <a href="<?php echo esc_url( home_url( '/units' ) ); ?>" class="category-card">
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
                            <?php _e( 'New projects' ); ?>
                        </h3>
                        <p>
                            <?php echo esc_attr( $new_projects_count ); ?>
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