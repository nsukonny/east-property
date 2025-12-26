<?php
/**
 * Categories section template
 */
?>
<section class="categories">
    <div class="container">
        <div class="categories-wrapper">
            <?php
            get_template_part( 'template-parts/components/titles/top-title', null,
                    array(
                            'h2'   => __( 'Explore new properties' ),
                            'desc' => __( 'Quick picks for you in UAE' ),
                            'href' => '#',
                            'link' => __( 'All Types' ),
                    )
            );
            ?>
            <div class="categories-cards">
                <a href="#" class="category-card">
                    <img class="category-card-bg" src="<?php echo THEME_URL; ?>/assets/img/c1.png" width="270"
                         height="270" alt="Category image">
                    <div class="category-card-desc">
                        <h3>
                            <?php _e( 'Apartments' ); ?>
                        </h3>
                        <p>
                            <?php _e( '233 Properties' ); ?>
                        </p>
                    </div>
                </a>
                <a href="#" class="category-card">
                    <img class="category-card-bg" src="<?php echo THEME_URL; ?>/assets/img/c2.png" width="270"
                         height="270" alt="Category image">
                    <div class="category-card-desc">
                        <h3>
                            <?php _e( 'Family homes' ); ?>
                        </h3>
                        <p>
                            <?php _e( '127 Properties' ); ?>
                        </p>
                    </div>
                </a>
                <a href="#" class="category-card">
                    <img class="category-card-bg" src="<?php echo THEME_URL; ?>/assets/img/c3.png" width="270"
                         height="270" alt="Category image">
                    <div class="category-card-desc">
                        <h3>
                            <?php _e( 'Offices' ); ?>
                        </h3>
                        <p>
                            <?php _e( '42 Properties' ); ?>
                        </p>
                    </div>
                </a>
                <a href="#" class="category-card">
                    <img class="category-card-bg" src="<?php echo THEME_URL; ?>/assets/img/c4.png" width="270"
                         height="270" alt="<?php _e( 'Category image' ); ?>">
                    <div class="category-card-desc">
                        <h3>
                            <?php _e( 'Villas' ); ?>
                        </h3>
                        <p>
                            <?php _e( '34 Properties' ); ?>
                        </p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>