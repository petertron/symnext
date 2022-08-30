<?php

namespace Symnext\Stream;

class Stream
{
    protected $glob_exp;

    protected $params = array();

    protected $calling_file;

    private $position = 0;

    private $path = null;

    protected $output = null;

    public function __construct()
    {
        /*$backtrace = debug_backtrace(
            defined('DEBUG_BACKTRACE_IGNORE_ARGS') ? DEBUG_BACKTRACE_IGNORE_ARGS : false
        );
        $calling_file = $backtrace[1]['file'];
        if (substr($calling_file, 0, strlen(SOURCE_FILES)) == SOURCE_FILES) {
            $this->calling_file = $calling_file;
        }*/
    }

    final function stream_set_option($option, $arg1 = null, $arg2 = null)
    {
        return false;
    }

    final function stream_open($path)//, $mode, $options, &$opened_path)
    {
        $path_parts = null;
        preg_match('/^sn:\/\/([\w\.]+)\/(.+)$/', $path, $path_parts);
        #echo \get_user_contant($path_parts[1]) . '/' . $path_parts[2]; die;
        if ($path_parts[1] == 'memory') {
            $this->output = $this->memory_files[$path_parts[2]];
        }
        $this->output = \file_get_contents(\get_user_constant(str_replace('.', '\\', $path_parts[1])) . '/' . $path_parts[2]);
        return true;
    }

    final function stream_read($count)
    {
       $output = \substr($this->output, \intval($this->position), $count);
       $this->position += $count;
       return $output;
    }

    final function stream_write($data)
    {
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
}
