<?php
/**
 * The template for displaying all single posts (News).
 */

global $post;

get_header( null, array( 'color' => 'white' ) );

if ( is_tax( 'product_cat' ) ) {
	get_template_part( 'template-parts/sections/catalog/catalog', null, array(
		'category' => get_queried_object(),
	) );
	get_footer();
	return;
}

if ( ! have_posts() ) {
	wp_safe_redirect( core_home_url( '/404' ) );
	exit;
}

the_post();

$post_id     = get_the_ID();
$author_name = get_the_author();
$location    = get_post_meta( $post_id, 'news_location', true ) ?: 'Dubai, UAE';
$date        = get_the_date( 'F j, Y' );
$mod_date    = get_the_modified_date( 'F j, Y' );
$pub_time    = (int) get_the_time( 'U' );
$mod_time    = (int) get_the_modified_time( 'U' );
$is_updated  = ( $mod_time > $pub_time );
$thumb_url   = get_the_post_thumbnail_url( $post_id, 'full' );

$breadcrumbs_links = array(
	array( 'title' => __( 'News', 'east-property' ), 'url' => core_home_url( '/news/' ) ),
	array( 'title' => get_the_title(), 'url' => '' ),
);

$other_news = core_get_other_news( $post_id, 3 );
?>

<main class="news-single-page">
	<article class="news-single-article">
		<div class="container">
			<?php
			get_template_part( 'core/components/common/breadcrumbs', null, array(
				'type'        => 'mb-32',
				'force_links' => $breadcrumbs_links,
			) );
			?>

			<header class="news-single-header">
				<h1 class="news-single-title">
					<?php the_title(); ?>
				</h1>

				<?php if ( has_excerpt() ) : ?>
					<div class="news-single-excerpt">
						<p><?php echo esc_html( get_the_excerpt() ); ?></p>
					</div>
				<?php endif; ?>

				<div class="news-single-meta">
					<div class="news-meta-info">
						<span class="news-meta-author"><?php echo esc_html( $author_name ); ?></span>,
						<span class="news-meta-location"><?php echo esc_html( $location ); ?></span>
						<span class="news-meta-divider">–</span>
						<time class="news-meta-date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
							<?php echo esc_html( $date ); ?>
						</time>
					</div>

					<div class="news-meta-actions">
						<div class="news-share-wrapper">
							<button type="button"
							        class="button orange lg news-share-btn"
							        id="news-share-toggle"
							        aria-expanded="false"
							        aria-haspopup="true">
								<?php _e( 'Share', 'east-property' ); ?>
							</button>

							<div class="news-share-popover" id="news-share-popover">
								<div class="news-social-links">
									<?php
									$share_url   = rawurlencode( get_permalink() );
									$share_title = rawurlencode( get_the_title() );
									?>
									<a href="https://api.whatsapp.com/send?text=<?php echo $share_title; ?>%20<?php echo $share_url; ?>"
									   target="_blank"
									   rel="noopener noreferrer"
									   class="social-btn whatsapp"
									   title="WhatsApp">
										<svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52s.198-.298.298-.497c.099-.198.05-.371-.025-.52s-.669-1.612-.916-2.207c-.242-.579-.487-.5-.669-.51a13 13 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074s2.096 3.2 5.077 4.487c.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413s.248-1.289.173-1.413c-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.82 9.82 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.82 11.82 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.9 11.9 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.82 11.82 0 0 0-3.48-8.413"/></svg>
									</a>

									<a href="https://t.me/share/url?url=<?php echo $share_url; ?>&text=<?php echo $share_title; ?>"
									   target="_blank"
									   rel="noopener noreferrer"
									   class="social-btn telegram"
									   title="Telegram">
										<svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M20.888 3.551c.168-.003.54.039.781.235.162.14.264.335.288.547.026.156.06.514.033.793-.302 3.189-1.616 10.924-2.285 14.495-.282 1.512-.838 2.017-1.378 2.066-1.17.11-2.058-.773-3.192-1.515-1.774-1.165-2.777-1.889-4.5-3.025-1.99-1.31-.7-2.033.434-3.209.297-.309 5.455-5.002 5.556-5.427.012-.054.024-.252-.094-.356s-.292-.069-.418-.04q-.267.061-8.504 5.62-1.208.831-2.187.806c-.72-.013-2.104-.405-3.134-.739C1.025 13.39.022 13.174.11 12.476q.068-.544 1.5-1.114 8.816-3.84 11.758-5.064c5.599-2.328 6.763-2.733 7.521-2.747Z"/></svg>
									</a>

									<a href="https://twitter.com/intent/tweet?url=<?php echo $share_url; ?>&text=<?php echo $share_title; ?>"
									   target="_blank"
									   rel="noopener noreferrer"
									   class="social-btn twitter"
									   title="X (Twitter)">
										<svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
									</a>

									<a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $share_url; ?>"
									   target="_blank"
									   rel="noopener noreferrer"
									   class="social-btn linkedin"
									   title="LinkedIn">
										<svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.06 2.06 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065m1.782 13.019H3.555V9h3.564zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0z"/></svg>
									</a>

									<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>"
									   target="_blank"
									   rel="noopener noreferrer"
									   class="social-btn facebook"
									   title="Facebook">
										<svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073"/></svg>
									</a>

									<button type="button"
									        class="social-btn copy-url"
									        id="news-copy-btn"
									        data-url="<?php echo esc_url( get_permalink() ); ?>"
									        title="<?php esc_attr_e( 'Copy link', 'east-property' ); ?>">
										<svg class="icon-copy" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
										<svg class="icon-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
									</button>
								</div>

								<div class="news-copy-notice" id="news-copy-notice">
									<?php _e( 'Link copied to clipboard!', 'east-property' ); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</header>

			<?php if ( ! empty( $thumb_url ) ) : ?>
				<div class="news-single-cover">
					<img src="<?php echo esc_url( $thumb_url ); ?>"
					     alt="<?php echo esc_attr( get_the_title() ); ?>"
					     width="1200"
					     height="600">
				</div>
			<?php endif; ?>

			<div class="news-single-body">
				<div class="news-content">
					<?php the_content(); ?>
				</div>

				<?php if ( $is_updated ) : ?>
					<div class="news-updated-date">
						<?php
						printf(
							__( 'Updated: %s', 'east-property' ),
							esc_html( $mod_date )
						);
						?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</article>

	<?php if ( $other_news->have_posts() ) : ?>
		<section class="news-other-section">
			<div class="container">
				<div class="news-other-wrapper">
					<h2 class="news-other-title">
						<?php _e( 'Other news', 'east-property' ); ?>
					</h2>

					<div class="news-other-grid">
						<?php
						while ( $other_news->have_posts() ) :
							$other_news->the_post();
							get_template_part( 'template-parts/cards/news-card', null, array(
								'post_id' => get_the_ID(),
							) );
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				</div>
			</div>
		</section>
	<?php endif; ?>
</main>

<?php
get_footer();
