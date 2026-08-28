<?php
/**
 * Pagination template
 */

$current_page = $args['current_page'] ?? 1;
$pages        = $args['pages'] ?? array();

if ( empty( $pages ) ) {
    return;
}
?>
<div class="pagination">
    <nav aria-label="Pagination">
        <ul class="pagination__list">

            <?php foreach ( $pages as $page ) {
                if ( ! $page['is_prev'] ) {
                    continue;
                }
                ?>
                <li class="pagination__item pagination__item--prev">
                    <a class="pagination__link pagination__link--prev"
                       href="<?php echo esc_url( $page['link'] ); ?>"
                       rel="prev"
                       aria-label="<?php _e( 'Previous page' , 'east-property' ); ?>">
                        <img src="<?php echo THEME_URL ?>/assets/img/swiper-arr.svg"
                             width="16" height="16" alt="<?php _e( 'Prev' , 'east-property' ); ?>">
                    </a>
                </li>
            <?php } ?>

            <?php foreach ( $pages as $page ) {
                if ( (int) $page['number'] === (int) $current_page ) { ?>
                    <li class="pagination__item" aria-current="page">
                        <span class="pagination__link pagination__link--current">
                            <?php echo esc_html( $page['number'] ); ?>
                        </span>
                    </li>
                <?php } else { ?>
                    <li class="pagination__item">
                        <a class="pagination__link"
                           href="<?php echo esc_url( $page['link'] ); ?>"
                           aria-label="<?php _e( 'Page' , 'east-property' ); ?> <?php echo esc_html( $page['number'] ); ?>">
                            <?php echo esc_html( $page['number'] ); ?>
                        </a>
                    </li>
                <?php } ?>
            <?php } ?>

            <?php foreach ( $pages as $page ) {
                if ( ! $page['is_next'] ) {
                    continue;
                }
                ?>
                <li class="pagination__item pagination__item--next">
                    <a class="pagination__link pagination__link--next"
                       href="<?php echo esc_url( $page['link'] ); ?>"
                       rel="prev"
                       aria-label="<?php _e( 'Previous page' , 'east-property' ); ?>">
                        <img src="<?php echo THEME_URL ?>/assets/img/swiper-arr.svg"
                             width="16" height="16" alt="<?php _e( 'Prev' , 'east-property' ); ?>">
                    </a>
                </li>
            <?php } ?>
        </ul>
    </nav>
</div>