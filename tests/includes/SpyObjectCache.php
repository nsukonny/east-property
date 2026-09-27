<?php
/**
 * Object cache test double.
 */

namespace EastProperty\Tests;

use WP_Object_Cache;

/**
 * Runtime object cache that records reads and writes per group and can answer a whole group with a preset value.
 *
 * Stands in for the persistent cache: a preset group behaves as if an earlier request had already filled it.
 */
class SpyObjectCache extends WP_Object_Cache {

	/**
	 * Keys read, by group.
	 *
	 * @var array<string, string[]>
	 */
	public array $reads = array();

	/**
	 * Values written, by group and key.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	public array $writes = array();

	/**
	 * Values served for every key of a group.
	 *
	 * @var array<string, mixed>
	 */
	private array $presets = array();

	/**
	 * Answer every read of a group with the given value.
	 *
	 * @param string $group Cache group.
	 * @param mixed  $value Value to serve.
	 *
	 * @return void
	 */
	public function preset( string $group, $value ): void {
		$this->presets[ $group ] = $value;
	}

	/**
	 * Record the read, then serve the preset or the stored value.
	 *
	 * @param int|string $key   Cache key.
	 * @param string     $group Cache group.
	 * @param bool       $force Unused, kept for the parent signature.
	 * @param bool|null  $found Whether the key was found.
	 *
	 * @return mixed
	 */
	public function get( $key, $group = 'default', $force = false, &$found = null ) {
		$group = '' === $group ? 'default' : $group;

		$this->reads[ $group ][] = (string) $key;

		if ( array_key_exists( $group, $this->presets ) ) {
			$found = true;

			return $this->presets[ $group ];
		}

		return parent::get( $key, $group, $force, $found );
	}

	/**
	 * Record the write, then store the value.
	 *
	 * @param int|string $key    Cache key.
	 * @param mixed      $data   Value.
	 * @param string     $group  Cache group.
	 * @param int        $expire Expiration in seconds.
	 *
	 * @return bool
	 */
	public function set( $key, $data, $group = 'default', $expire = 0 ) {
		$group = '' === $group ? 'default' : $group;

		$this->writes[ $group ][ (string) $key ] = $data;

		return parent::set( $key, $data, $group, $expire );
	}
}
