<?php

/**
 * @package Interface
 */

namespace Symnext\Interface;

/**
 * This interface is to be implemented by Extensions who wish to provide
 * cacheable objects for Symphony to use and whose cache providers support
 * namespacing to make bulk read, writes and deletes.
 *
 * @since Symphony 2.6.0
 */
interface NamespacedCache
{
    /**
     * Returns the human readable name of this cache type. This is
     * displayed in the system preferences cache options.
     *
     * @return string
     */
    public static function getName();

    /**
     * This function returns all the settings of the current Cache
     * instance.
     *
     * @return array
     *  An associative array of settings for this cache where the
     *  key is `getClass` and the value is an associative array of settings,
     *  key being the setting name, value being, the value
     */
    public function settings();

    /**
     * Given the hash of a some data, check to see whether it exists in
     * `tbl_cache`. If no cached object is found, this function will return
     * false, otherwise the cached object will be returned as an array.
     *
     * @param string $hash
     *  The hash of the Cached object, as defined by the user
     * @param string $namespace
     *  The namespace allows a group of data to be retrieved at once
     * @return array|boolean
     *  An associative array of the cached object including the creation time,
     *  expiry time, the hash and the data. If the object is not found, false will
     *  be returned.
     */
    public function read($hash, $namespace = null);

    /**
     * This function will compress data for storage in `tbl_cache`.
     * It is left to the user to define a unique hash for this data so that it can be
     * retrieved in the future. Optionally, a `$ttl` parameter can
     * be passed for this data. If this is omitted, it data is considered to be valid
     * forever. This function utilizes the Mutex class to act as a crude locking
     * mechanism.
     *
     * @see toolkit.Mutex
     * @param string $hash
     *  The hash of the Cached object, as defined by the user
     * @param string $data
     *  The data to be cached, this will be compressed prior to saving.
     * @param integer $ttl
     *  A integer representing how long the data should be valid for in minutes.
     *  By default this is null, meaning the data is valid forever
     * @param string $namespace
     *  The namespace allows data to be grouped and saved so it can be
     *  retrieved later.
     * @return boolean
     *  If an error occurs, this function will return false otherwise true
     */
    public function write(
        string $hash,
        string $data,
        int $ttl = null,
        string $namespace = null
    );

    /**
     * Given the hash of a cacheable object, remove it from `tbl_cache`
     * regardless of if it has expired or not. If no $hash is given,
     * this removes all cache objects from `tbl_cache` that have expired.
     * After removing, the function uses the `__optimise` function
     *
     * @see core.Cacheable#optimise()
     * @param string $hash
     *  The hash of the Cached object, as defined by the user
     * @param string $namespace
     *  The namespace allows similar data to be deleted quickly.
     */
    public function delete(string $hash, string $namespace = null);
}
