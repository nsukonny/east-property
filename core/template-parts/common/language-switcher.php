<?php
/**
 * Language switcher as a dropdown.
 *
 * The language being read is the first option and carries the selected state,
 * so the list always shows where the visitor stands.
 *
 * @var array $args Expects `switcher` from core_get_language_switcher().
 */

$switcher = (array) ( $args['switcher'] ?? array() );
$modifier = (string) ( $args['modifier'] ?? '' );

if ( count( $switcher ) < 2 ) {
	return;
}

$current = array();

foreach ( $switcher as $entry ) {
	if ( ! empty( $entry['is_current'] ) ) {
		$current = $entry;

		break;
	}
}

if ( empty( $current ) ) {
	$current = reset( $switcher );
}
?>
<div class="lang-switch<?php echo '' !== $modifier ? ' ' . esc_attr( $modifier ) : ''; ?>" data-lang-switch>
	<button class="lang-switch-toggle" type="button" aria-expanded="false" aria-haspopup="true"
			aria-label="<?php esc_attr_e( 'Change language', 'east-property' ); ?>">
		<?php require THEME_PATH . '/assets/img/lang/earth.svg'; ?>
		<span class="lang-switch-code"><?php echo esc_html( (string) $current['label'] ); ?></span>
		<img class="lang-switch-arrow" src="<?php echo THEME_URL; ?>/assets/img/arrow-down.svg"
			 width="16" height="16" alt="" aria-hidden="true">
	</button>

	<ul class="lang-switch-list" hidden>
		<?php foreach ( $switcher as $entry ) { ?>
			<?php $selected = ! empty( $entry['is_current'] ); ?>
			<li class="lang-switch-item">
				<a class="lang-switch-option<?php echo $selected ? ' is-selected' : ''; ?>"
				   lang="<?php echo esc_attr( (string) $entry['locale'] ); ?>"
				   hreflang="<?php echo esc_attr( (string) $entry['locale'] ); ?>"
				   href="<?php echo esc_url( (string) $entry['url'] ); ?>"
					<?php echo $selected ? ' aria-current="true"' : ''; ?>>
					<span class="lang-switch-code"><?php echo esc_html( (string) $entry['label'] ); ?></span>
					<span class="lang-switch-name"><?php echo esc_html( (string) $entry['name'] ); ?></span>
					<img class="lang-switch-check" src="<?php echo THEME_URL; ?>/assets/img/check.svg"
						 width="16" height="16" alt="" aria-hidden="true">
				</a>
			</li>
		<?php } ?>
	</ul>
</div>
