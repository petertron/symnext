<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet>


    /**
     * This function will fetch the `fatalerror.fatal` template, and output the
     * Throwable in a user friendly way.
     *
     * @since Symphony 2.4
     * @since Symphony 2.6.4 the method is protected
     * @since Symphony 2.7.0
     *  This function works with both Exception and Throwable
     * @since Symphony 3.0.0
     *  This function enforces the protected visibility.
     *  This function has a new signature.
     *
     * @param Throwable $e
     *  The Throwable object
     * @return string
     *  The HTML of the formatted error message.
     */
    protected static function renderHtml(\Throwable $e)
    {
        $heading = $e instanceof \ErrorException ? ErrorHandler::$errorTypeStrings[$e->getSeverity()] : 'Fatal Error';
        $message = $e->getMessage() . ($e->getPrevious()
            ? '<br />' . __('Previous exception: ') . $e->getPrevious()->getMessage()
            : '');
        $lines = null;

        foreach (self::getNearbyLines($e->getLine(), $e->getFile()) as $line => $string) {
            $lines .= sprintf(
                '<li%s><strong>%d</strong> <code>%s</code></li>',
                (($line + 1) == $e->getLine() ? ' class="error"' : null),
                ++$line,
                str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;', htmlspecialchars($string))
            );
        }

        $trace = null;

        foreach ($e->getTrace() as $t) {
            $trace .= sprintf(
                '<li><code><em>[%s:%d]</em></code></li><li><code>&#160;&#160;&#160;&#160;%s%s%s();</code></li>',
                $t['file'] ?? null,
                (isset($t['line']) ? $t['line'] : null),
                (isset($t['class']) ? $t['class'] : null),
                (isset($t['type']) ? $t['type'] : null),
                $t['function']
            );
        }

        $queries = null;

        if (is_object(App::Database())) {
            $debug = App::Database()->getLogs();

            if (!empty($debug)) {
                foreach ($debug as $query) {
                    $queries .= sprintf(
                        '<li><em>[%01.4f]</em><code> %s;</code> </li>',
                        (isset($query['execution_time']) ? $query['execution_time'] : null),
                        htmlspecialchars($query['query'])
                    );
                }
            }
        }

        $template = 'fatalerror.generic';
        if (is_callable([$e, 'getTemplate'])) {
            $template = $e->getTemplate();
        }

        $html = sprintf(
            file_get_contents(self::getTemplate($template)),
            $heading,
            !ExceptionHandler::$enabled ? 'Something unexpected occurred.' : General::unwrapCDATA($message),
            !ExceptionHandler::$enabled ? '' : $e->getFile(),
            !ExceptionHandler::$enabled ? '' : $e->getLine(),
            !ExceptionHandler::$enabled ? null : $lines,
            !ExceptionHandler::$enabled ? null : $trace,
            !ExceptionHandler::$enabled ? null : $queries
        );

        $html = str_replace('{ASSETS_URL}', ASSETS_URL, $html);
        if (defined('SYMPHONY_URL')) { // Peter S mod.
            $html = str_replace('{SYMPHONY_URL}', SYMPHONY_URL, $html);
        }
        $html = str_replace('{URL}', URL, $html);
        $html = str_replace('{PHP}', PHP_VERSION, $html);
        $html = str_replace(
            '{MYSQL}',
            !App::Database() ? 'N/A' : App::Database()->getVersion(),
            $html
        );

        return $html;
    }


</xsl:stylesheet>
