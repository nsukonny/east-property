<?php

/**
 * Template Name: News Listing Page
 */

get_header( null, array( 'color' => 'white' ) );

$paged      = pagination_get_current_page();
$news_query = core_get_news( array( 'paged' => $paged ) );
$has_more   = ( $paged < $news_query->max_num_pages );
?>

<main class="news-archive-page">
	<div class="container">
		<?php get_template_part( 'core/components/common/breadcrumbs', null, array( 'type' => 'mb-32' ) ); ?>

		<div class="news-archive-header">
			<h1 class="news-archive-title">
				<?php _e( 'News', 'east-property' ); ?>
			</h1>
		</div>

		<div class="news-archive-grid" id="news-grid">
			<?php
			if ( $news_query->have_posts() ) :
				while ( $news_query->have_posts() ) :
					$news_query->the_post();
					get_template_part( 'template-parts/cards/news-card', null, array(
						'post_id' => get_the_ID(),
					) );
				endwhile;
				wp_reset_postdata();
			else :
				?>
				<div class="news-empty">
					<div class="news-empty-icon">
						<img src="<?php echo THEME_URL; ?>/assets/img/logo.svg"
						     alt="<?php esc_attr_e( 'East Property', 'east-property' ); ?>"
						     width="140"
						     height="53">
					</div>
					<h3 class="news-empty-title">
						<?php _e( 'No news articles found', 'east-property' ); ?>
					</h3>
					<p class="news-empty-desc">
						<?php _e( 'We are preparing the latest market updates and property news. Please check back later.', 'east-property' ); ?>
					</p>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $has_more ) : ?>
			<div class="news-archive-more">
				<button type="button"
				        class="button gray sm full-width news-load-more-btn"
				        id="news-load-more"
				        data-next-page="<?php echo esc_attr( $paged + 1 ); ?>"
				        data-total-pages="<?php echo esc_attr( $news_query->max_num_pages ); ?>">
					<?php _e( 'Show older news', 'east-property' ); ?>
				</button>
			</div>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();
