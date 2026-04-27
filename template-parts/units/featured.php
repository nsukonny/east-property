<?php
/**
 * Featured properties section
 */

$units = $args['units'] ?? null;

$h2            = $args['h2'] ?? '';
$h3            = $args['h3'] ?? '';
$title         = $args['title'] ?? '';
$description   = $args['description'] ?? '';
$href          = $args['href'] ?? '';
$link_text     = $args['link_text'] ?? __( 'See all properties' );
$card_template = $args['card_template'] ?? 'unit-card';
$before        = $args['before'] ?? '<section class="properties"><div class="container">';
$after         = $args['after'] ?? '</div></section>';
$button_text   = $args['button_text'] ?? '';
$button_url    = $args['button_url'] ?? '';

echo wp_kses_post( $before );
?>
    <div class="properties-wrapper">
		<?php
		get_template_part(
			'core/components/titles/top-title',
			null,
			array(
				'h2'   => $h2,
				'desc' => $description,
				'href' => $href,
				'link' => $link_text,
			)
		);
		?>

		<?php if ( ! empty( $units ) ) { ?>
            <div class="properties-cards">
				<?php
				foreach ( $units as $unit ) {
					get_template_part(
						'core/components/cards/unit-card',
						null,
						array(
							'unit'     => $unit,
							'template' => $card_template,
						)
					);
				}
				?>
            </div>
			<?php if ( ! empty( $button_text ) && ! empty( $button_url ) ) { ?>
                <div class="properties-show-more-btn">
					<?php
					get_template_part(
						'core/components/ui/button',
						null,
						array(
							'class' => 'button sm orange',
							'text'  => $button_text,
							'src'   => null,
							'link'  => $button_url,
							'modal' => null,
						)
					);
					?>
                </div>
			<?php } ?>
		<?php } ?>
    </div>
<?php
echo wp_kses_post( $after );
