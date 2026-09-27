<?php
/**
 * Response captured before WordPress exits.
 */

namespace EastProperty\Tests;

use Exception;

/**
 * Thrown from the status_header and wp_redirect filters so a test can read a response the theme would end with exit.
 */
final class StoppedResponse extends Exception {

	/**
	 * HTTP status the response was sent with.
	 *
	 * @var int
	 */
	public int $status;

	/**
	 * Redirect target, null for a response without one.
	 *
	 * @var string|null
	 */
	public ?string $location;

	/**
	 * Capture the response.
	 *
	 * @param int         $status   HTTP status.
	 * @param string|null $location Redirect target.
	 */
	public function __construct( int $status, ?string $location = null ) {
		parent::__construct( 'Response stopped with HTTP ' . $status );

		$this->status   = $status;
		$this->location = $location;
	}
}
