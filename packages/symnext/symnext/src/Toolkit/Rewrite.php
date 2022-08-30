<?php

/**
 * @package Toolkit
 */

/**
 * URL rewriter for use in conjunction with the PHP 5.4 internal HTTP server and `router.php`.
 */
namespace Rewrite;

class Storage
{
    static $matches = [];
}

/**
 * Initialize the rewrite environment.
 */
function initialize()
{
    set_environment($_SERVER['REQUEST_URI']);
}

/**
 * Set important environment variables and re-parse the query string.
 * @return boolean
 */
function finalize(): bool
{
    if (defined('REWRITER_FINALIZED')) return false;

    define('REWRITER_FINALIZED', true);

    if (\is_file($_SERVER['DOCUMENT_ROOT'] . $_SERVER['SCRIPT_NAME'])) {
        $_SERVER['SCRIPT_FILENAME'] = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['SCRIPT_NAME'];
    }

    if (isset($_SERVER['QUERY_STRING'])) {
        $_GET = [];

        parse_str($_SERVER['QUERY_STRING'], $_GET);
    }

    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];

    return true;
}

/**
 * Adjust the server environment variables to match a given URL.
 * @param string $url
 */
function set_environment(string $url): void
{
    $url = rtrim($url, '&?');
    $request_uri = $script_name = $url;
    $query_string = null;

    if (strpos($url, '?') > 0) {
        $script_name = substr($url, 0, strpos($url, '?'));
        $query_string = substr($url, 1 + strpos($url, '?'));
    }

    $_SERVER['REQUEST_URI'] = $request_uri;
    $_SERVER['SCRIPT_NAME'] = $script_name;
    $_SERVER['QUERY_STRING'] = $query_string;
}

/**
 * Parse regular expression matches. eg. $0 or $1
 * @param string $url
 * @return string
 */
function parse_matches(string $url)
{
    $replace = function ($bit) {
        return Storage::$matches[$bit[1]] ?? null;
    };

    return preg_replace_callback('/\$([0-9]+)/', $replace, $url);
}

/**
 * Parse Apache style rewrite parameters. eg. %{QUERY_STRING}
 * @param string $url
 * @return string
 */
function parse_parameters(string $url)
{
    $replace = function($bit) {
        return $_SERVER[$bit[1]] ?? null;
    };

    return preg_replace_callback('/%\{([A-Z_+]+)\}/', $replace, $url);
}

/**
 * Change the internal url to a different url.
 * @param string $from Regular expression to match current url, or optional when used in conjunction with `test`.
 * @param string $to URL to redirect to.
 * @return boolean
 */
function rewrite(string $from, string $to = null): bool
{
    if (defined('REWRITER_FINALIZED')) return false;

    $url = $_SERVER['SCRIPT_NAME'];

    if (isset($to)) {
        // From and To given:
        $url = preg_replace($from, $to, $url);
    } else {
        // Use results from last test:
        $url = parse_matches($from);
    }

    set_environment(
        parse_parameters($url)
    );

    return true;
}

/**
 * Redirect the client to a different url.
 * @param string $from Regular expression to match current url, or optional when used in conjunction with `test`.
 * @param string $to URL to redirect to.
 */
function redirect(string $from, string $to = null) {
    if (defined('REWRITER_FINALIZED')) return false;

    $url = $_SERVER['SCRIPT_NAME'];

    // From and To given:
    if (isset($to)) {
        $url = preg_replace($from, $to, $url);
    }

    // Use results from last test:
    else {
        $url = parse_matches($from);
    }

    $url = parse_parameters($url);

    header('Location: ' . $url); exit;
}

/**
 * Compare a regular expression against the current request, store the matches for later use.
 * @return boolean
 */
function test($expression)
{
    if (defined('REWRITER_FINALIZED')) return false;

    return 0 < preg_match($expression, $_SERVER['SCRIPT_NAME'], Storage::$matches);
}

/**
 * Does the current request point to a directory?
 * @return boolean
 */
function is_dir()
{
    if (defined('REWRITER_FINALIZED')) return false;

    return \is_dir($_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI']);
}

/**
 * Does the current request point to a file?
 * @return boolean
 */
function is_file()
{
    if (defined('REWRITER_FINALIZED')) return false;

    return \is_file($_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI']);
}
