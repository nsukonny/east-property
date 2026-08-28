<?php

$top_title = $args['top_title'] ?? array();
$cards     = $args['cards'] ?? array();

?>
    <section class="categories">
        <div class="container">
            <div class="categories-wrapper">
				<?php
				if ( ! empty( $top_title ) ) {
					get_template_part( 'core/components/titles/top-title', null,
						array(
							'h2'   => $top_title['h2'] ?? '',
							'desc' => $top_title['desc'] ?? '',
							'href' => $top_title['href'] ?? '',
							'link' => $top_title['link'] ?? '',
						)
					);
				}

				if ( ! empty( $cards ) ) {
					?>
                    <div class="categories-cards">
						<?php foreach ( $cards as $card ) { ?>
                            <a href="<?php echo esc_url( $card['href'] ); ?>" class="category-card">
                                <img class="category-card-bg" src="<?php echo esc_url( $card['image'] ); ?>" width="270"
                                     height="270" alt="<?php echo esc_html( $card['title'] ); ?>">
                                <div class="category-card-desc">
                                    <h3>
										<?php echo esc_html( $card['title'] ); ?>
                                    </h3>
                                    <p><?php echo esc_attr( $card['count'] ); ?>
										<?php esc_html_e( 'Properties' , 'east-property' ); ?>
                                    </p>
                                </div>
                            </a>
						<?php } ?>
                    </div>
				<?php } ?>
            </div>
        </div>
    </section>
<?php