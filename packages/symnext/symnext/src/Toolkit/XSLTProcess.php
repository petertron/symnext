<?php

/**
 * @package Toolkit
 */

namespace Symnext\Toolkit;

use DOMDocument, XSLTProcessor;

/**
 * The `XSLTProcess` class is responsible for taking a chunk of XML
 * and applying an XSLT stylesheet to it. Custom error handlers are
 * used to capture any errors that occurred during this process, and
 * are exposed to the `ExceptionHandler`'s for display to the user.
 */

class XSLTProcess
{
    /**
     * An array of all the parameters to be made available during the XSLT
     * transform
     * @var array
     */
    protected $param = [];

    /**
     * An array of the PHP functions to be made available during the XSLT
     * transform
     * @var array
     */
    protected $_registered_php_functions = [];

    /**
     * Any errors that occur during the transformation are stored in this array.
     * @var array
     */
    private $_errors = [];

    /**
     * The last context, i.e. xml data that the system uses right now.
     * Used when trapping errors, to be able to generate debug info.
     * @var string
     */
    private $_lastContext = null;

    /**
     * A path where the XSLTProc will write its profiling information.
     *
     * @var string
     */
    private $profiling = null;

    /**
     * Sets the parameters that will output with the resulting page
     * and be accessible in the XSLT. This function translates all ' into
     * `&apos;`, with the tradeoff being that a <xsl:value-of select='$param' />
     * that has a ' will output `&apos;` but the benefit that ' and " can be
     * in the params
     *
     * @link http://www.php.net/manual/en/xsltprocessor.setparameter.php#81077
     * @param array $param
     *  An associative array of params for this page
     */
    public function setRuntimeParam(array $param)
    {
        $this->param = str_replace("'", "&apos;", $param);
    }

    /**
     * Allows the registration of PHP functions to be used on the Frontend
     * by passing the function name or an array of function names
     *
     * @param mixed $function
     *  Either an array of function names, or just the function name as a
     *  string
     */
    public function registerPHPFunction(array|string $function)
    {
        if (is_array($function)) {
            $this->_registered_php_functions = array_unique(
              array_merge($this->_registered_php_functions, $function)
            );
        } else {
            $this->_registered_php_functions[] = $function;
        }
    }

    /**
     * Checks if there is an available `XSLTProcessor`
     *
     * @return boolean
     *  true if there is an existing `XSLTProcessor` class, false otherwise
     */
    public static function isXSLTProcessorAvailable(): bool
    {
        return (class_exists('\XSLTProcessor') || function_exists('xslt_process'));
    }

    /**
     * This function will take a given XML file, a stylesheet and apply
     * the transformation. Any errors will call the error function to log
     * them into the `$_errors` array
     *
     * @see toolkit.XSLTProcess#__error()
     * @see toolkit.XSLTProcess#__process()
     * @param string $xml
     *  The XML for the transformation to be applied to
     * @param string $xsl
     *  The XSL for the transformation
     * @return string|boolean
     *  The string of the resulting transform, or false if there was an error
     */
    public function process($xml_doc, $xsl_doc)
    {
        // dont let process continue if no xsl functionality exists
        if (!self::isXSLTProcessorAvailable()) {
            return false;
        }

        $XSLProc = new XSLTProcessor;

        if (!empty($this->_registered_php_functions)) {
            $XSLProc->registerPHPFunctions($this->_registered_php_functions);
        }

        if (!empty($this->profiling)) {
            $XSLProc->setProfiling($this->profiling);
        }

        $result = $this->__process(
            $XSLProc,
            $xml_doc,
            $xsl_doc,
            $this->param
        );

        unset($XSLProc);

        return $result;
    }

    /**
     * Uses `DOMDocument` to transform the document. Any errors that
     * occur are trapped by custom error handlers, `trapXMLError` or
     * `trapXSLError`.
     *
     * @param XSLTProcessor $XSLProc
     *  An instance of `XSLTProcessor`
     * @param string $xml
     *  The XML for the transformation to be applied to
     * @param string $xsl
     *  The XSL for the transformation
     * @param array $parameters
     *  An array of available parameters the XSL will have access to
     * @return string
     */
    private function __process(
        XSLTProcessor $XSLProc,
        string|DOMDocument $xml,
        string|DOMDocument $xsl,
        array $parameters = []
    ): string
    {
        // Create instances of the DOMDocument class
        if (is_string($xml)) {
            $xml_doc = new XMLDocument($xml);
        } else {
            $xml_doc = $xml;
        }

        if (is_string($xsl)) {
            $xsl_doc = new XMLDocument();
            $xsl_doc->loadXML($xsl);
        } else {
            $xsl_doc = $xsl;
        }

        // Set up error handling
        if (function_exists('ini_set')) {
            $ehOLD = ini_set('html_errors', false);
        }

        // Load the xml document
        $this->_lastContext = $xml;
        set_error_handler([$this, 'trapXMLError']);
        // Remove null bytes from XML
        #$xml = str_replace(chr(0), '', $xml);
        #$xml_doc->loadXML($xml, LIBXML_NONET | LIBXML_DTDLOAD | LIBXML_DTDATTR | defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0);
        //libxml_disable_entity_loader($elOLD);

        // Must restore the error handler to avoid problems
        restore_error_handler();

        // Load the xsl document
        $this->_lastContext = $xsl;
        set_error_handler([$this, 'trapXSLError']);
        // Ensure that the XSLT can be loaded with `false`. RE: #1939
        // Note that `true` will cause `<xsl:import />` to fail.
        #$xsl_doc->loadXML($xsl, LIBXML_NONET | LIBXML_DTDLOAD | LIBXML_DTDATTR | defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0);
        #$xsl_doc->loadXML($xsl);
        //libxml_disable_entity_loader($elOLD);

        // Load the xsl template
        if (!$XSLProc->importStyleSheet($xsl_doc)) {
            die("Stylesheet import failed");
        }

        // Set parameters when defined
        if (!empty($parameters)) {
            General::flattenArray($parameters);

            $XSLProc->setParameter('', $parameters);
        }

        // Must restore the error handler to avoid problems
        restore_error_handler();

        // Start the transformation
        set_error_handler([$this, 'trapXMLError']);
        $current_dir = \getcwd();
        chdir(\WORKSPACE);
        $processed = $XSLProc->transformToXML($xml_doc);
        chdir($current_dir);
        // Restore error handling
        if (function_exists('ini_set') && isset($ehOLD)) {
            ini_set('html_errors', $ehOLD);
        }

        // Must restore the error handler to avoid problems
        restore_error_handler();
        $this->_lastContext = null;

        return $processed;
    }

    /**
     * That validate function takes an XSD to valid against `$xml`
     * returning boolean.
     *
     * @since Symphony 2.3
     * @param string $xsd
     *  The XSD to validate against
     * @param string $xml
     *  The XML to validate
     * @return boolean
     *  Returns true if the `$xml` validates against `$xsd`, false otherwise.
     *  If false is returned, the errors can be obtained with `XSLTProcess->getErrors()`
     */
    public function validate($xsd, $xml)
    {
        if (is_null($xsd) || is_null($xml)) {
            return false;
        }

        // Create instances of the DOMDocument class
        $xml_doc = new DOMDocument;

        // Set up error handling
        if (function_exists('ini_set')) {
            $ehOLD = ini_set('html_errors', false);
        }

        // Load the xml document
        $this->_lastContext = $xml;
        set_error_handler([$this, 'trapXMLError']);
        $elOLD = libxml_disable_entity_loader(true);
        $xml_doc->loadXML($xml, LIBXML_NONET | LIBXML_DTDLOAD | LIBXML_DTDATTR | defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0);
        //libxml_disable_entity_loader($elOLD);

        // Must restore the error handler to avoid problems
        restore_error_handler();

        // Validate the XML against the XSD
        $this->_lastContext = $xsd;
        set_error_handler(array($this, 'trapXSDError'));
        $result = $xml_doc->schemaValidateSource($xsd);

        // Restore error handling
        if (function_exists('ini_set') && isset($ehOLD)) {
            ini_set('html_errors', $ehOLD);
        }

        // Must restore the error handler to avoid problems
        restore_error_handler();
        $this->_lastContext = null;

        return $result;
    }

    /**
     * A custom error handler especially for XML errors.
     *
     * @link http://au.php.net/manual/en/function.set-error-handler.php
     * @param integer $errno
     * @param string $errstr
     * @param string $errfile
     * @param integer $errline
     */
    public function trapXMLError(
        int $errno, string $errstr, string $errfile, int $errline
    ): void
    {
        echo $errno . ': ' . $errstr;
        die;
        $this->__error($errno, str_replace('DOMDocument::', null, $errstr), $errfile, $errline, 'xml');
    }

    /**
     * A custom error handler especially for XSL errors.
     *
     * @link http://au.php.net/manual/en/function.set-error-handler.php
     * @param integer $errno
     * @param integer $errstr
     * @param integer $errfile
     * @param integer $errline
     */
    public function trapXSLError($errno, $errstr, $errfile, $errline)
    {
        echo $errno . ' ' . $errstr . ', line ' . $errline;
        die;
        #$this->__error($errno, str_replace('DOMDocument::', null, $errstr), #$errfile, $errline, 'xsl');
    }

    /**
     * A custom error handler especially for XSD errors.
     *
     * @since Symphony 2.3
     * @link http://au.php.net/manual/en/function.set-error-handler.php
     * @param integer $errno
     * @param integer $errstr
     * @param integer $errfile
     * @param integer $errline
     */
    public function trapXSDError(int $errno, int $errstr, int $errfile, int $errline): void
    {
        $this->__error($errno, str_replace('DOMDocument::', null, $errstr), $errfile, $errline, 'xsd');
    }

    /**
     * Writes an error to the `$_errors` array, which contains the error information
     * and some basic debugging information.
     *
     * @link http://au.php.net/manual/en/function.set-error-handler.php
     * @param integer $number
     * @param string $message
     * @param string $file
     * @param string $line
     * @param string $type
     *  Where the error occurred, can be either 'xml', 'xsl' or `xsd`
     */
    public function __error(
        int $number,
        string $message,
        string $file = null,
        string $line = null,
        string $type = null
    ): void
    {
        $this->_errors[] = [
            'number' => $number,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'type' => $type,
            'context' => $this->_lastContext,
        ];
    }

    /**
     * Returns boolean if any errors occurred during the transformation.
     *
     * @see getError
     * @return boolean
     */
    public function isErrors()
    {
        return !empty($this->_errors);# ? true : false;
    }

    /**
     * Provides an Iterator interface to return an error from the `$_errors`
     * array. Repeat calls to this function to get all errors
     *
     * @param boolean $all
     *  If true, return all errors instead of one by one. Defaults to false
     * @param boolean $rewind
     *  If rewind is true, resets the internal array pointer to the start of
     *  the `$_errors` array. Defaults to false.
     * @return array
     *  Either an array of error array's or just an error array
     */
    public function getError(bool $all = false, bool $rewind = false)
    {
        if ($rewind) {
            reset($this->_errors);
        }

        //return ($all ? $this->_errors : each($this->_errors));
        if ($all) {
            return $this->_errors;
        }

        $current = current($this->_errors);
        if ($current === false) {
            return false;
        }

        $key = key($this->_errors);
        next($this->_errors);
        return [$key, $current];
    }

    /**
     * Gets the current profiling file path
     *
     * @return string
     */
    public function getProfiling(): string
    {
        return $this->profiling;
    }

    /**
     * Sets the current profiling file path
     *
     * @param $profiling string
     *  The file path
     */
    public function setProfiling(string $profiling)
    {
        $this->profiling = $profiling;
    }
}
