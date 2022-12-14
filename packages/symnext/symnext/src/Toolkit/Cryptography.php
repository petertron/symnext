<?php

/**
 * @package toolkit
 */

namespace Symnext\Toolkit;

/**
 * Cryptography is a utility class that offers a number of general purpose cryptography-
 * related functions for message digestation as well as (backwards-)compatibility
 * checking. The message digestation algorithms are placed in the subclasses
 * `SHA1` and `PBKDF2`.
 *
 * @since Symphony 2.3.1
 * @see PBKDF2
 */

class Cryptography
{
    /**
     * Uses an instance of `PBKDF2` to create a hash.
     *
     * @uses PBKDF2::hash()
     * @param string $input
     *  the string to be hashed
     * @return string
     *  the hashed string
     */
    public static function hash($input)
    {
        return PBKDF2::hash($input);
    }

    /**
     * Compares a given hash with a clean text password by figuring out the
     * algorithm that has been used and then calling the appropriate sub-class
     *
     * @uses hash_equals()
     * @uses PBKDF2::compare()
     * @param string $input
     *  the clear text password
     * @param string $hash
     *  the hash the password should be checked against
     * @param boolean $isHash
     *  if the $input is already a hash
     * @return boolean
     *  the result of the comparison
     */
    public static function compare($input, $hash, $isHash = false)
    {
        $version = substr($hash, 0, 8);
        if (!$input || !$hash) {
            return false;
        }

        if ($isHash === true) {
            return hash_equals($hash, $input);
        } elseif ($version === PBKDF2::PREFIX) { // salted PBKDF2
            return PBKDF2::compare($input, $hash);
        }
        // the hash provided doesn't make any sense
        return false;
    }

    /**
     * Checks if provided hash has been computed by most recent algorithm
     * returns true if otherwise
     *
     * @param string $hash
     * the hash to be checked
     * @return boolean
     * whether the hash should be re-computed
     */
    public static function requiresMigration($hash)
    {
        $version = substr($hash, 0, 8);

        if ($version === PBKDF2::PREFIX) { // salted PBKDF2, let the responsible class decide
            return PBKDF2::requiresMigration($hash);
        }
        return true;
    }

    /**
     * Generates a salt to be used in message digestation.
     *
     * @param integer $length
     * the length of the salt
     * @return string
     * a hexadecimal string
     */
    public static function generateSalt($length)
    {
        mt_srand(intval(microtime(true)*100000 + memory_get_usage(true)));
        return substr(sha1(uniqid(mt_rand(), true)), 0, $length);
    }

    /**
     * Returns a string generated from random bytes.
     * It requires a minimum length of 16.
     * It first tries to call PHP's random_byte.
     * If not available, it will try openssl_random_pseudo_bytes.
     * If not available, it will revert to `Cryptography::generateSalt()`.
     *
     * @uses Cryptography::generateSalt()
     * @param integer $length
     *  The number of random bytes to get.
     *  The minimum is 16.
     *  Defaults to 40, which is 160 bits of entropy.
     * @return string
     * @throws Exception
     *  If the requested length is smaller than 16.
     */
    public static function randomBytes($length = 40)
    {
        if ($length < 16) {
            throw new Exception('Can not generate less than 16 random bytes');
        }
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length / 2));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($length / 2));
        }
        return self::generateSalt($length);
    }
}
