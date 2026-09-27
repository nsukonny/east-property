<?php
/**
 * Filters form of an account list.
 */

$tab       = $args['tab'] ?? '';
$search    = $args['search'] ?? array();
$fields    = $args['fields'] ?? array();
$keep      = $args['keep'] ?? array();
$reset_url = $args['reset_url'] ?? '';
?>
<form class="account-filters" method="get" action="<?php echo esc_url( core_home_url( '/account/' ) ); ?>"
      data-account-filters>
	<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
	<?php foreach ( $keep as $name => $value ) { ?>
		<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
	<?php } ?>

	<label class="account-filters-search">
		<input type="search"
		       name="<?php echo esc_attr( $search['name'] ?? '' ); ?>"
		       value="<?php echo esc_attr( $search['value'] ?? '' ); ?>"
		       placeholder="<?php echo esc_attr( $search['placeholder'] ?? '' ); ?>">
	</label>

	<?php
	foreach ( $fields as $field ) {
		get_component_template(
			'ui/dropdown',
			array(
				'input_name'     => $field['name'],
				'selected_title' => $field['items'][ $field['value'] ] ?? reset( $field['items'] ),
				'selected_key'   => $field['value'],
				'items'          => $field['items'],
				'search_enabled' => 8 < count( $field['items'] ),
			)
		);
	}
	?>

	<?php if ( '' !== $reset_url ) { ?>
		<a class="button link sm account-filters-reset" href="<?php echo esc_url( $reset_url ); ?>">
			<?php esc_html_e( 'Reset filters', 'east-property' ); ?>
		</a>
	<?php } ?>
</form>
