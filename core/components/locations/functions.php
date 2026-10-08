<?php
/**
 * Per-language descriptions of location terms.
 *
 * Polylang does not translate this taxonomy: one set of terms serves every
 * language. The term's own description field keeps the default language and
 * every other language keeps its text in term meta, so nothing has to be
 * migrated and the editor keeps the box the screen always had.
 */

/**
 * Languages that keep their description in term meta.
 *
 * @return string[] Language slugs, without the default one.
 */
function core_location_languages(): array {
	if ( ! function_exists( 'pll_languages_list' ) || ! function_exists( 'pll_default_language' ) ) {
		return array();
	}

	$default = (string) pll_default_language( 'slug' );

	return array_values(
		array_filter(
			array_map( 'strval', (array) pll_languages_list() ),
			static function ( string $slug ) use ( $default ): bool {
				return '' !== $slug && $slug !== $default;
			}
		)
	);
}

/**
 * Meta key holding the description of a location in one language.
 *
 * @param string $language Language slug.
 *
 * @return string
 */
function core_location_description_key( string $language ): string {
	return 'description_' . $language;
}

/**
 * Description of a location in the language being rendered.
 *
 * Only the text of that language is returned: an English paragraph on a Russian
 * page is exactly what the translation backlog exists to avoid.
 *
 * @param WP_Term|int|string $term     Term, its id, or an empty value.
 * @param string             $language Defaults to the current language.
 *
 * @return string
 */
function core_location_description( $term, string $language = '' ): string {
	if ( is_numeric( $term ) ) {
		$term = get_term( (int) $term, 'location' );
	}

	if ( ! $term instanceof WP_Term ) {
		return '';
	}

	if ( '' === $language ) {
		$language = function_exists( 'pll_current_language' ) ? (string) pll_current_language( 'slug' ) : '';
	}

	if ( '' === $language || ! in_array( $language, core_location_languages(), true ) ) {
		return (string) $term->description;
	}

	return (string) get_term_meta( $term->term_id, core_location_description_key( $language ), true );
}

/**
 * One textarea per extra language on the location edit screen.
 *
 * @param WP_Term $term
 *
 * @return void
 */
function core_location_description_fields( $term ): void {
	$languages = core_location_languages();

	if ( empty( $languages ) || ! $term instanceof WP_Term ) {
		return;
	}

	wp_nonce_field( 'core_location_descriptions', 'core_location_descriptions_nonce' );

	foreach ( $languages as $language ) {
		$key   = core_location_description_key( $language );
		$value = (string) get_term_meta( $term->term_id, $key, true );
		?>
		<tr class="form-field term-<?php echo esc_attr( $key ); ?>-wrap">
			<th scope="row">
				<label for="<?php echo esc_attr( $key ); ?>">
					<?php
					printf(
						/* translators: %s: language code. */
						esc_html__( 'Description (%s)', 'east-property' ),
						esc_html( strtoupper( $language ) )
					);
					?>
				</label>
			</th>
			<td>
				<textarea name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>"
						rows="5" cols="50" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
				<p class="description">
					<?php esc_html_e( 'Shown on pages in this language. Left empty, the language shows no description.',
						'east-property' ); ?>
				</p>
			</td>
		</tr>
		<?php
	}
}

add_action( 'location_edit_form_fields', 'core_location_description_fields' );

/**
 * Store the per-language descriptions of a location.
 *
 * @param int $term_id
 *
 * @return void
 */
function core_location_save_descriptions( int $term_id ): void {
	$languages = core_location_languages();

	if ( empty( $languages ) ) {
		return;
	}

	$nonce = isset( $_POST['core_location_descriptions_nonce'] )
		? sanitize_key( wp_unslash( $_POST['core_location_descriptions_nonce'] ) )
		: '';

	if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'core_location_descriptions' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_term', $term_id ) ) {
		return;
	}

	foreach ( $languages as $language ) {
		$key = core_location_description_key( $language );

		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}

		$value = trim( sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) );

		if ( '' === $value ) {
			delete_term_meta( $term_id, $key );

			continue;
		}

		update_term_meta( $term_id, $key, $value );
	}
}

add_action( 'edited_location', 'core_location_save_descriptions' );
