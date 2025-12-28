}
	}
		return $params;

		}
			$params['meta'] = $request['meta'];
		if ( isset( $request['meta'] ) && is_array( $request['meta'] ) ) {
		// Optional meta filters come as meta[key]=value

		}
			$params['order'] = strtoupper( sanitize_text_field( (string) $request['order'] ) );
		if ( isset( $request['order'] ) ) {
		}
			$params['orderby'] = sanitize_key( (string) $request['orderby'] );
		if ( isset( $request['orderby'] ) ) {

		$params['per_page'] = isset( $request['per_page'] ) ? (int) $request['per_page'] : self::DEFAULT_PER_PAGE;
		$params['page']     = isset( $request['page'] ) ? (int) $request['page'] : 1;

		}
			$params['q'] = (string) $request['q'];
		if ( isset( $request['q'] ) ) {

		$params = array();
	public static function params_from_request( array $request ): array {
	 */
	 * @return array
	 *
	 * @param array $request
	 *
	 * Extract safe params from request (GET/POST) for template or AJAX.
	/**

	}
		return new \WP_Query( $args );

		}
			}
				$args['meta_query'] = $meta_query;
			if ( count( $meta_query ) > 1 ) {

			}
				);
					'compare' => '=',
					'value'   => is_scalar( $value ) ? (string) $value : $value,
					'key'     => $key,
				$meta_query[] = array(
				}
					continue;
				if ( $key === '' || $value === null || $value === '' ) {
				$key = sanitize_key( (string) $key );
			foreach ( $params['meta'] as $key => $value ) {
			$meta_query = array( 'relation' => 'AND' );
		if ( ! empty( $params['meta'] ) && is_array( $params['meta'] ) ) {
		// Exact meta filters: ['meta' => ['delivery_date' => '2026-01-01']]

		}
			$args['s'] = sanitize_text_field( (string) $params['q'] );
		if ( ! empty( $params['q'] ) ) {

		);
			'order'          => $params['order'] ?? 'DESC',
			'orderby'        => $params['orderby'] ?? 'date',
			'paged'          => $page,
			'posts_per_page' => $per_page,
			'post_status'    => 'publish',
			'post_type'      => 'properties',
		$args = array(

		}
			$per_page = self::DEFAULT_PER_PAGE;
		if ( $per_page <= 0 ) {
		$per_page = (int) ( $params['per_page'] ?? self::DEFAULT_PER_PAGE );
		$page     = max( 1, (int) ( $params['page'] ?? 1 ) );
	public static function query( array $params = array() ): \WP_Query {
	 */
	 * @return \WP_Query
	 *
	 * @param array $params
	 *
	 * - meta: array<string, mixed> (exact meta matches)
	 * - order: string (ASC|DESC)
	 * - orderby: string (date|title|meta_value_num)
	 * - per_page: int
	 * - page: int (1-based)
	 * - q: string
	 * Expected $params keys (all optional):
	 *
	 * Run properties search.
	/**

	public const DEFAULT_PER_PAGE = 12;
final class PropertiesSearch {

namespace EastProperty\Search;

declare(strict_types=1);

 */
 * so it can be reused from templates and from AJAX.
 * Goal: keep *all* query/filter/sort/pagination logic in one place,
 *
 * Properties search service (CPT: properties)
/**

