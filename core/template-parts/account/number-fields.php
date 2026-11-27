<?php
/**
 * One number per language with a dropdown that adds a language
 *
 * @var array $args
 */

$languages = (array) ( $args['languages'] ?? array() );
$numbers   = (array) ( $args['numbers'] ?? array() );
$field     = (string) ( $args['field'] ?? 'phone' );
$title     = (string) ( $args['title'] ?? '' );
$add_label = (string) ( $args['add_label'] ?? '' );

if ( empty( $languages ) ) {
	return;
}

$shown = array_values( array_intersect( array_keys( $languages ), array_keys( $numbers ) ) );

if ( empty( $shown ) ) {
	$shown = array( (string) array_key_first( $languages ) );
}
?>
<div class="number-fields" data-number-fields="<?php echo esc_attr( $field ); ?>">
	<span class="number-fields-title"><?php echo esc_html( $title ); ?></span>

	<?php foreach ( $languages as $slug => $language ) { ?>
		<div class="input-group number-field<?php echo in_array( $slug, $shown, true ) ? '' : ' hidden'; ?>"
			 data-number-row="<?php echo esc_attr( $slug ); ?>">
			<label for="<?php echo esc_attr( $field . '_' . $slug ); ?>">
				<span class="number-field-lang">
					<img src="<?php echo esc_url( THEME_URL . '/assets/img/lang/' . $language['slug'] . '.png' ); ?>"
						 alt="" width="20" height="20"/>
					<?php echo esc_html( $language['name'] ); ?>
				</span>
				<input type="tel" id="<?php echo esc_attr( $field . '_' . $slug ); ?>"
					   name="<?php echo esc_attr( $field ); ?>[<?php echo esc_attr( $slug ); ?>]"
					   value="<?php echo esc_attr( $numbers[ $slug ] ?? '' ); ?>"
					   placeholder="971 58 523 5351"
					   inputmode="tel"
					   autocomplete="tel"
					   data-number-input>
			</label>
			<button type="button" class="number-field-remove" data-remove-number="<?php echo esc_attr( $slug ); ?>"
					title="<?php esc_attr_e( 'Remove number', 'east-property' ); ?>"
					aria-label="<?php esc_attr_e( 'Remove number', 'east-property' ); ?>">&times;</button>
		</div>
	<?php } ?>

	<div class="lang-add<?php echo count( $shown ) === count( $languages ) ? ' hidden' : ''; ?>" data-number-add>
		<button type="button" class="lang-add-toggle" aria-expanded="false" aria-haspopup="true">
			<?php echo esc_html( $add_label ); ?>
		</button>
		<ul class="lang-add-list" hidden>
			<?php foreach ( $languages as $slug => $language ) { ?>
				<li class="lang-add-item<?php echo in_array( $slug, $shown, true ) ? ' hidden' : ''; ?>"
					data-number-item="<?php echo esc_attr( $slug ); ?>">
					<button type="button" class="lang-add-option" data-add-number="<?php echo esc_attr( $slug ); ?>">
						<img src="<?php echo esc_url( THEME_URL . '/assets/img/lang/' . $language['slug'] . '.png' ); ?>"
							 alt="" width="20" height="20"/>
						<span><?php echo esc_html( $language['name'] ); ?></span>
					</button>
				</li>
			<?php } ?>
		</ul>
	</div>
</div>
