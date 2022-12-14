<?php

/**
 * @package Interface
 */

namespace Symnext\Interface;

/**
 * The Singleton interface contains one function, `instance()`,
 * the will return an instance of an Object that implements this
 * interface.
 */
interface Singleton
{
    public static function instance();
}
