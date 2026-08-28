<?php
/**
 * Large card template
 * @var array $args
 */

$card_title     = $args['title'] ?? '';
$price          = $args['price'] ?? '';
$pure_price     = $args['pure_price'] ?? '';
$location       = $args['location'] ?? null;
$gallery        = $args['gallery'] ?? '';
$labels         = $args['labels'] ?? '';
$specifications = $args['specifications'] ?? array();
$url            = $args['url'] ?? '#';
$edit_link      = $args['edit_link'] ?? '';
$is_can_boost   = $args['is_can_boost'] ?? false;

if ( empty( $card_title ) || empty( $price ) || empty( $gallery ) ) {
	return;
}

$first_image = $gallery[0];
unset( $gallery[0] );
?>

<div class="large-card">
	<div class="large-card-inner">
		<div class="large-card-left">
			<div class="large-card-images">
				<div class="large-card-images-left">
					<?php if ( ! empty( $first_image ) ) { ?>
						<img src="<?php echo esc_url( $first_image['sizes']['large'] ); ?>" alt="Image">
					<?php } ?>
				</div>

				<div class="large-card-images-right">
					<?php if ( ! empty( $gallery ) ) { ?>
						<?php
						$limit = 3;
						foreach ( $gallery as $image ) {
							-- $limit;
							if ( 0 >= $limit ) {
								break;
							}
							?>
							<img src="<?php echo esc_url( $image['sizes']['medium'] ); ?>" alt="Image">
						<?php } ?>
					<?php } ?>
				</div>
			</div>
		</div>
		<div class="large-card-right">
			<div class="large-card-top">
				<div class="large-card-top-left">
					<?php if ( 0 < $pure_price ) { ?>
						<div class="large-card-top-title">
							<span class="subtext">
								<?php esc_html_e( 'from' , 'east-property' ); ?>
							</span>
							<span class="large-card-price">
								<?php echo esc_html( $price ); ?>
							</span>
						</div>
						<span class="large-card-dev">
							<?php echo esc_html( $card_title ); ?>
						</span>
					<?php } else { ?>
						<div class="large-card-top-title">
							<span class="subtext">
								<?php esc_html_e( 'Average price unavailable' , 'east-property' ); ?>
							</span>
							<span class="large-card-price">
								<?php echo esc_html( $card_title ); ?>
							</span>
						</div>
					<?php } ?>
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
					<?php if ( ! empty( $specifications ) ) { ?>
						<div class="large-card-info-top">
							<div class="large-card-info-items">
								<?php foreach ( $specifications as $specification ) { ?>
									<span>
										<img src="<?php echo esc_url( $specification['icon'] ); ?>" width="16"
										     height="16"
										     alt="<?php _e( 'Vector icon' , 'east-property' ); ?>">
										<?php echo esc_html( $specification['value'] ); ?>
									</span>
								<?php } ?>
							</div>
						</div>
					<?php } ?>

					<div class="large-card-info-bottom">
						<p><?php echo esc_html( $location ); ?></p>

						<?php
						if ( ! empty( $edit_link ) || true === $is_can_boost ) {
							if ( ! empty( $edit_link ) ) {
								?>
								<a href="<?php echo esc_url( $edit_link ); ?>"
								   class="button gray sm edit-property-link">
									<img src="<?php echo esc_url( THEME_URL . '/assets/img/edit.svg' ); ?>"
									     alt="<?php esc_html_e( 'Edit' , 'east-property' ); ?>">
									<?php esc_html_e( 'Edit property' , 'east-property' ); ?>
								</a>
								<?php
							}
						} elseif ( ! empty( $url ) ) { ?>
							<a href="<?php echo esc_url( $url ); ?>" class="button gray sm property-details-lnk">
								<?php esc_html_e( 'View details' , 'east-property' ); ?>
							</a>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>