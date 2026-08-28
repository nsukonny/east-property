<?php
/**
 * Dropdown component for forms
 */

$input_name = $args['input_name'] ?? null;
if ( empty( $input_name ) ) {
	return;
}

$wrapper_title  = $args['title'] ?? '';
$selected_title = $args['selected_title'] ?? __( 'Select', 'east-property' );
$selected_key   = $args['selected_key'] ?? '';
$items          = $args['items'] ?? '';
$search_enabled = $args['search_enabled'] ?? true;
$link_text      = $args['link_text'] ?? '';
$link_url       = $args['link_url'] ?? '';
$lang_sync      = $args['lang_sync'] ?? '';
?>
<div class="input-wrapper">
	<?php if ( ! empty( $wrapper_title ) ) { ?>
		<div class="label-text">
			<span><?php echo esc_html( $wrapper_title ); ?></span>
			<?php if ( ! empty( $link_text ) && ! empty( $link_url ) ) { ?>
				<a class="button link sm"
				   target="_blank"
				   href="<?php echo esc_url( $link_url ); ?>">
					<?php echo esc_html( $link_text ); ?>
				</a>
			<?php } ?>
		</div>
	<?php } ?>

	<div class="dropdown"<?php echo $lang_sync ? ' data-lang-sync="' . esc_attr( $lang_sync ) . '"' : ''; ?>>
		<button class="dropdown-button" type="button">
			<span class="dropdown-title">
				<?php echo esc_html( $selected_title ); ?>
			</span>
			<span class="dropdown-arrow">
				<img src="<?php echo esc_url( THEME_URL . '/assets/img/arrow-down.svg' ); ?>" width="16" height="16"
				     alt="<?php esc_html_e( 'Arrow down', 'east-property' ); ?>">
			</span>
		</button>

		<?php if ( $search_enabled ) { ?>
			<div class="dropdown-search">
				<label for="<?php echo esc_attr( $input_name ); ?>-search">
					<input type="text" class="dropdown-search-input"
					       id="<?php echo esc_attr( $input_name ); ?>-search"
					       placeholder="<?php esc_html_e( 'Search...', 'east-property' ); ?>">
				</label>
			</div>
		<?php } ?>

		<div class="dropdown-content">
			<div class="dropdown-inner">
				<?php foreach ( $items as $key => $item ) { ?>
					<button type="button" class="dropdown-option"
					        data-value="<?php echo esc_attr( $key ); ?>">
						<?php echo esc_html( $item ); ?>
					</button>
				<?php } ?>
			</div>
		</div>
	</div>
	<input type="hidden" name="<?php echo esc_attr( $input_name ); ?>" value="<?php echo esc_attr( $selected_key ); ?>"
	       data-required>
</div>