<?php
/**
 * Language tabs of the account form with a dropdown that adds a missing language
 *
 * @var array $args
 */

$languages = $args['languages'] ?? array();
$current   = (string) ( $args['current'] ?? '' );
$active    = (array) ( $args['active'] ?? array() );
$type      = 'property' === ( $args['type'] ?? '' ) ? 'property' : 'unit';

if ( 2 > count( $languages ) ) {
	return;
}

$slugs  = array_keys( $languages );
$active = array_values(
	array_unique( array_merge( array( $current ), array_intersect( $slugs, $active ) ) )
);
$rest   = array_diff( $slugs, $active );
?>
<div class="lang-switcher" data-lang-switcher>
	<?php
	foreach ( $languages as $slug => $language ) {
		$classes = array( 'button', 'sm' );

		if ( $current === $slug ) {
			$classes[] = 'current';
		}
		?>
		<span class="lang-tab<?php echo in_array( $slug, $active, true ) ? '' : ' hidden'; ?>"
			  data-lang-tab="<?php echo esc_attr( $slug ); ?>">
			<a data-switch-to-lang="<?php echo esc_attr( $slug ); ?>" href="#"
			   class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
				<img src="<?php echo esc_url( THEME_URL . '/assets/img/lang/' . $language['slug'] . '.png' ); ?>"
					 alt="<?php echo esc_attr( $language['name'] ); ?>" width="20" height="20"/>
				<span><?php echo esc_html( $language['name'] ); ?></span>
			</a>
			<button type="button" class="lang-tab-remove" data-remove-lang="<?php echo esc_attr( $slug ); ?>"
					title="<?php esc_attr_e( 'Remove language', 'east-property' ); ?>"
					aria-label="<?php esc_attr_e( 'Remove language', 'east-property' ); ?>" hidden>&times;</button>
		</span>
	<?php } ?>

	<div class="lang-add<?php echo empty( $rest ) ? ' hidden' : ''; ?>" data-lang-add>
		<button type="button" class="lang-add-toggle" aria-expanded="false" aria-haspopup="true">
			<?php esc_html_e( '+ Add language', 'east-property' ); ?>
		</button>
		<ul class="lang-add-list" hidden>
			<?php foreach ( $languages as $slug => $language ) { ?>
				<li class="lang-add-item<?php echo in_array( $slug, $active, true ) ? ' hidden' : ''; ?>"
					data-lang-item="<?php echo esc_attr( $slug ); ?>">
					<button type="button" class="lang-add-option"
							data-add-lang="<?php echo esc_attr( $slug ); ?>">
						<img src="<?php echo esc_url( THEME_URL . '/assets/img/lang/' . $language['slug'] . '.png' ); ?>"
							 alt="" width="20" height="20"/>
						<span><?php echo esc_html( $language['name'] ); ?></span>
					</button>
				</li>
			<?php } ?>
		</ul>
	</div>
</div>

<?php
foreach ( $languages as $slug => $language ) {
	$switched = switch_to_lang( $language['locale'] );

	$hint = 'property' === $type
		? __(
			'This project will be translated into the other languages automatically. You can also add a translation yourself by picking a language.',
			'east-property'
		)
		: __(
			'This listing will be translated into the other languages automatically. You can also add a translation yourself by picking a language.',
			'east-property'
		);
	?>
	<p class="lang-switcher-hint<?php echo $current === $slug ? '' : ' hidden'; ?>"
	   data-show-on-lang="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $hint ); ?></p>
	<?php
	if ( $switched ) {
		restore_previous_locale();
	}
}
