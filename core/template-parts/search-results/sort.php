<?php
/**
 * Sort order dropdown of a listing.
 *
 * Takes `param`, the query argument that holds the order (`sort` on the listings), and `value`, the current order.
 */

$param   = $args['param'] ?? 'sort';
$choices = core_get_listing_sort_choices();
$current = core_get_listing_sort( $args['value'] ?? null );
?>
<div class="sort-dropdown" data-sort data-sort-param="<?php echo esc_attr( $param ); ?>"
     data-value="<?php echo esc_attr( $current ); ?>">
	<button class="sort" type="button" aria-haspopup="listbox" aria-expanded="false" data-sort-toggle>
		<span data-sort-label><?php echo esc_html( $choices[ $current ] ?? reset( $choices ) ); ?></span>
		<img src="<?php echo esc_url( THEME_URL . '/assets/img/arrow-down.svg' ); ?>" width="16" height="16" alt="">
	</button>
	<div class="sort-options" role="listbox" data-sort-options hidden>
		<?php foreach ( $choices as $value => $label ) { ?>
			<button class="sort-option<?php echo $value === $current ? ' is-selected' : ''; ?>" type="button" role="option"
			        aria-selected="<?php echo $value === $current ? 'true' : 'false'; ?>"
			        data-value="<?php echo esc_attr( $value ); ?>">
				<?php echo esc_html( $label ); ?>
			</button>
		<?php } ?>
	</div>
</div>
