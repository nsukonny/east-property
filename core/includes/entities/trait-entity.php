<?php
/**
 * Entity for Property post type
 */

declare( strict_types=1 );

namespace Entities;

use WP_Post;

trait EntityTrait {

	protected int $id;
	protected array $fields_cache = array();
	protected array $post_meta;
	protected ?WP_Post $post = null;
	protected string $thumb;
	protected string $no_image_url = THEME_URL . '/assets/img/no-image.jpg';
	protected array $gallery;

	public function __construct( int|WP_Post $post ) {
		if ( $post instanceof WP_Post ) {
			$this->post = $post;
			$this->id   = (int) $post->ID;
		} else {
			$this->id   = (int) $post;
			$this->post = get_post( $this->id );
		}
	}

	/**
	 * Get ID of the author of this post
	 */
	public function get_author_id(): int {
		return (int) $this->get_wp_post()->post_author;
	}

	public function get_id(): int {
		return $this->id;
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
		return get_post_field( 'post_content', $this->id ) ?: '';
	}

	/**
	 * Get gallery images
	 *
	 * @return array
	 */
	public function get_gallery(): array {
		if ( ! empty( $this->gallery ) ) {
			return $this->gallery;
		}

		$gallery = $this->get_field( 'gallery' );
		if ( empty( $gallery ) ) {
			$this->gallery = array(
				array(
					'ID'    => 0,
					'id'    => 0,
					'url'   => $this->no_image_url,
					'sizes' => array(
						'medium'        => $this->no_image_url,
						'large'         => $this->no_image_url,
						'unit-card'     => $this->no_image_url,
						'product-thumb' => $this->no_image_url,
						'featured-card' => $this->no_image_url,
						'medium_large'  => $this->no_image_url,
					),
				),
			);

			return $this->gallery;
		}

		$this->gallery = $gallery;

		return $this->gallery;
	}

	/**
	 * Get gallery attachment ids
	 *
	 * @return array
	 */
	public function get_gallery_ids(): array {
		$attachment_ids = array();
		foreach ( $this->get_gallery() as $item ) {
			$attachment_ids[] = (int) $item['id'];
		}

		return $attachment_ids;
	}

	/**
	 * Get random thumbnail ids from the gallery
	 *
	 * @param int $count
	 *
	 * @return array
	 */
	public function get_random_gallery_ids( int $count = 3 ): array {
		$gallery        = $this->get_gallery();
		$attachment_ids = ! empty( $gallery ) ? array_map(
			static function ( $item ) {
				return $item['id'];
			},
			$gallery
		) : array();

		if ( empty( $attachment_ids ) ) {
			return array();
		}

		// array_rand() throws when asked for more items than the array holds, and
		// a gallery with one or two images is common.
		$count = min( $count, count( $attachment_ids ) );

		return (array) array_rand( array_flip( $attachment_ids ), $count );
	}

	/**
	 * Get post URL
	 *
	 * @return string
	 */
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
		if ( ! empty( $this->thumb ) ) {
			return $this->thumb;
		}

		//get post thumbnail
		$thumb_id = get_post_thumbnail_id( $this->id );
		if ( ! empty( $thumb_id ) ) {
			$this->thumb = wp_get_attachment_image_url( (int) $thumb_id, $size );

			return $this->thumb;
		}

		$gallery = $this->get_gallery();
		if ( empty( $gallery ) ) {
			return ''; //TODO Add no image here
		}

		if ( ! empty( $gallery[0]['sizes'][ $size ] ) ) {
			$this->thumb = $gallery[0]['sizes'][ $size ];

			return $this->thumb;
		}

		if ( ! empty( $gallery[0]['ID'] ) ) {
			$this->thumb = wp_get_attachment_image_url( (int) $gallery[0]['ID'], $size );
		}

		if ( empty( $this->thumb ) ) {
			return '';
		}

		return $this->thumb;
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
	 * Get post status
	 *
	 * @return string
	 */
	public function get_status(): string {
		return $this->get_wp_post()->post_status ?? '';
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
	 * Get all payment plans
	 * @return array
	 */
	public function get_payment_plans(): array {
		return $this->get_field( 'payment_plans' ) ?: array();
	}

	/**
	 * Get Carbon Field value
	 *
	 * @param string $field_name
	 *
	 * @return mixed
	 */
	public function get_field( string $field_name ): mixed {
		if ( ! empty( $this->fields_cache[ $field_name ] ) ) {
			return $this->fields_cache[ $field_name ];
		}

		if ( ! function_exists( 'get_field' ) ) {
			return null;
		}

		$this->fields_cache[ $field_name ] = get_field( $field_name, $this->get_id() );

		return $this->fields_cache[ $field_name ];
	}

	/**
	 * Get post slug
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return $this->get_wp_post()->post_name ?? '';
	}

	/**
	 * Delete post and all meta data
	 *
	 * @return bool
	 */
	public function delete(): bool {
		wp_delete_post( $this->id, true );

		return true;
	}
}
