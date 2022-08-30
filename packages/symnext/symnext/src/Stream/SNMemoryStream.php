<?php

namespace Symnext\Stream;

class SNMemoryStream
{
    protected $position = 0;

    protected $path = null;

    public static $store = [];

    protected static $handle = [];

    public function __construct()
    {
    }

    final function stream_set_option($option, $arg1 = null, $arg2 = null)
    {
        return false;
    }

    final function stream_open($path)//, $mode, $options, &$opened_path)
    {
        $path_parts = null;
        preg_match('/^sn-memory:\/\/(.+)$/', $path, $path_parts);
        $handle = $path_parts[1];
        self::$handle = $handle;
        if (!isset(self::$store[$handle])) {
            self::$store[$handle] = ['position' => 0, 'contents' => null];
        } else {
            self::$store[$handle]['position'] = 0;
        }
        return true;
    }

    final function stream_read($count)
    {
        $handle = self::$handle;
        $output = \substr(
            self::$store[$handle]['contents'],
            self::$store[$handle]['position'],
            $count
        );
        self::$store[$handle]['position'] += $count;
        return $output;
    }

    final function stream_write($data)
    {
        $handle = self::$handle;
        self::$store[$handle]['contents'] .= $data;
        self::$store[$handle]['position'] += strlen($data);
        return $data;
    }


    final function url_stat()
    {
        return [];
    }

    final function stream_stat()
    {
        return [];
    }

    final function stream_eof()
    {
        return true;
    }

    public static function loadContents(string $handle, string $data): void
    {
        self::$store[$handle] = [
            'contents' => $data,
            'position' => strlen($data)
        ];
        self::$handle = $handle;
    }
}
