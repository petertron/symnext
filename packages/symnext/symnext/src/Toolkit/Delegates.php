<?php

namespace Symnext\Toolkit;

class Delegates
{
    protected static $register = [];

    /**
     * @param string $name
     *  Delegate name
     * @param string $call
     *  Delegate method
     */
    public static function register(
        string $name,
        string $call
    ): void
    {
        if (!isset(self::$register[$name])) {
            self::$register[$name] = [];
        }
        self::$register[$name][] = $callback;
    }

    public static function call(
        string $name,
        array $context = []
    ): void
    {
    }
}
