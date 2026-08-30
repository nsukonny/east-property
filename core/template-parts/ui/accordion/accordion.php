<?php

$title_cols   = $args['title_cols'] ?? array();
$content_rows = $args['content_rows'] ?? array();

if ( empty( $title_cols ) ) {
	return;
}

?>
<div class="accordion">
	<button class="accordion-button" type="button">
		<span class="accordion-arrow">
			<img src="<?php echo esc_url( THEME_URL ); ?>/assets/img/arrow-down.svg" width="16"
			     height="16" alt="vector arrow">
		</span>
		<?php foreach ( $title_cols as $title_col ) { ?>
			<div class="accordion-col">
				<?php if ( ! empty( $title_col['img'] ) ) { ?>
					<img src="<?php echo esc_url( $title_col['img'] ); ?>" alt="">
				<?php } ?>
				<?php echo $title_col['text']; ?>
			</div>
		<?php } ?>
	</button>

	<div class="accordion-content">
		<?php foreach ( $content_rows as $content_row ) { ?>
			<div class="accordion-row">
				<?php foreach ( $content_row as $content_col ) {
					if ( empty( $content_col ) ) {
						continue;
					}

					$is_have_floor_image = ! empty( $content_col['img'] ) && ! empty( $content_col['modal']['image'] );
					?>
					<div class="accordion-col<?php echo $is_have_floor_image ? ' image-col' : ''; ?>"
						<?php if ( $is_have_floor_image ) { ?>
							data-modal-open="plan-modal"
							data-plan-src="<?php echo esc_url( $content_col['modal']['image'] ); ?>"
						<?php } ?>
					>
						<?php if ( ! empty( $content_col['img'] ) ) { ?>
							<img src="<?php echo esc_url( $content_col['img'] ); ?>"
							     alt="<?php echo esc_attr( $content_col['alt'] ?? '' ); ?>"
							>
						<?php } ?>

						<?php if ( ! empty( $content_col['text'] ) ) { ?>
							<span><?php echo $content_col['text']; ?></span>
						<?php } ?>
					</div>
				<?php } ?>
			</div>
		<?php } ?>
	</div>

</div>