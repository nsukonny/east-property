<?php

$post_id   = $args['post_id'] ?? get_the_ID();
$title     = get_the_title( $post_id );
$permalink = get_permalink( $post_id );
$date      = get_the_date( 'F j, Y', $post_id );
$thumb_url = get_the_post_thumbnail_url( $post_id, 'large' );
?>

<article class="news-card" id="post-<?php echo esc_attr( $post_id ); ?>">
	<a href="<?php echo esc_url( $permalink ); ?>" class="news-card-link">
		<?php if ( ! empty( $thumb_url ) ) : ?>
			<div class="news-card-thumb">
				<img src="<?php echo esc_url( $thumb_url ); ?>"
				     alt="<?php echo esc_attr( $title ); ?>"
				     loading="lazy"
				     width="388"
				     height="240">
			</div>
		<?php else : ?>
			<div class="news-card-thumb news-card-thumb--placeholder">
				<img src="<?php echo THEME_URL; ?>/assets/img/logo.svg"
				     alt="<?php echo esc_attr( $title ); ?>"
				     class="news-card-placeholder-logo"
				     loading="lazy"
				     width="132"
				     height="50">
			</div>
		<?php endif; ?>

		<div class="news-card-body">
			<h3 class="news-card-title">
				<?php echo esc_html( $title ); ?>
			</h3>

			<time class="news-card-date" datetime="<?php echo esc_attr( get_the_date( 'c', $post_id ) ); ?>">
				<?php echo esc_html( $date ); ?>
			</time>
		</div>
	</a>
</article>
