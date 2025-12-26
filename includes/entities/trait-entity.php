<?php
/**
 * Entity for Property post type
 */

declare( strict_types=1 );

namespace MessiaTheme\Entities;

use WP_Post;

trait EntityTrait {

	protected int $id;
	protected int $segment_id = 0; //term_id of segment for this object
	protected array $constructed_data;
	protected array $post_meta;
	protected ?WP_Post $post = null;

	public function __construct( int|WP_Post $post ) {
		if ( $post instanceof WP_Post ) {
			$this->post = $post;
			$this->id   = (int) $post->ID;
		} else {
			$this->id   = (int) $post;
			$this->post = get_post( $this->id );
		}
	}

	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Get main segment term_id for this object
	 *
	 * @return int
	 */
	public function get_segment_id(): int {
		if ( $this->segment_id > 0 ) {
			return $this->segment_id;
		}

		$object_segment_terms = get_the_terms( $this->get_id(), MESSIA_OBJECT_SEGMENT );

		if ( 0 === count( $object_segment_terms ) || ! isset( $object_segment_terms[0]->term_id ) ) {
			return 0;
		}

		return $object_segment_terms[0]->term_id;
	}

	protected function get_wp_post(): ?WP_Post {
		if ( $this->post instanceof WP_Post ) {
			return $this->post;
		}

		$p = get_post( $this->id );
		if ( ! $p instanceof WP_Post ) {
			return null;
		}

		$this->post = $p;

		return $this->post;
	}

	public function exists(): bool {
		return (bool) $this->get_wp_post();
	}

	public function get_title(): string {
		return get_the_title( $this->id ) ?: '';
	}

	public function get_description_short(): string {
		$desc = get_the_excerpt( $this->id );
		if ( empty( $desc ) ) {
			$desc = $this->get_description_full();
			$desc = mb_substr( $desc, 0, 155 );
		}

		return $desc;
	}

	public function get_description_full(): string {
		$description = get_post_field( 'post_content', $this->id );
		if ( empty( $description ) ) {
			$description = '';
		}

		return wp_strip_all_tags( $description );
	}

	public function get_url(): string {
		return get_permalink( $this->id ) ?: '';
	}

	/**
	 * Get post thumbnail URL
	 *
	 * @param string $size
	 *
	 * @return string
	 */
	public function get_thumb( string $size = 'medium_large' ): string {
		$thumb = get_the_post_thumbnail_url( $this->id, $size );
		if ( ! $thumb ) {
			$thumb = THEME_URL . '/assets/img/no-image.png';
		}

		return $thumb;
	}

	/**
	 * Get all segments
	 *
	 * @return array
	 */
	public function get_segments(): array {
		$segments = wp_get_post_terms( $this->get_id(), 'messia_object_segment' );
		if ( ! is_array( $segments ) ) {
			return array();
		}


		return $segments;
	}

	/**
	 * Get main segment
	 *
	 * @return ?array
	 */
	public function get_segment(): ?array {
		$segments = $this->get_segments();
		if ( empty( $segments ) ) {
			return null;
		}

		return array(
			'id'   => $segments[0]->term_id,
			'name' => $segments[0]->name,
			'slug' => $segments[0]->slug,
		);
	}

	/**
	 * Get all post meta for this post
	 *
	 * @param string $field
	 *
	 * @return mixed
	 */
	private function get_post_meta( string $field = '' ): mixed {

		if ( empty( $this->post_meta ) ) {
			$this->post_meta = get_post_meta( $this->get_id() );
		}

		if ( ! empty( $field ) && isset( $this->post_meta[ $field ] ) ) {
			return $this->post_meta[ $field ];
		}

		return $this->post_meta;
	}

	/**
	 * Get constructed data from postmeta
	 *
	 * @return mixed
	 */
	private function get_constructed_data(): mixed {
		if ( ! empty( $this->constructed_data ) ) {
			return $this->constructed_data;
		}

		$this->constructed_data = get_post_meta( $this->get_id(), MESSIA_POSTMETA_CONSTRUCTED_PREFIX . $this->get_segment_id(), true );

		return $this->constructed_data;
	}

	/**
	 * Get Carbon Field value
	 *
	 * @param string $field_name
	 *
	 * @return mixed
	 */
	public function get_field( string $field_name ): mixed {

		$constructed_data               = $this->get_constructed_data();
		$constructed_data['is_popular'] = $this->get_post_meta( 'stuff_meta_is_popular_segment_term_id_' . $this->get_segment_id() )[0] ?? 0;

		return $constructed_data[ $field_name ] ?? null;
	}

}