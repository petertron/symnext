<?php

/**
 * @package Toolkit
 */

namespace Symnext\Toolkit;

/**
 * The Datasource class provides functionality to mainly process any parameters
 * that the fields will use in filters find the relevant Entries and return these Entries
 * data as XML so that XSLT can be applied on it to create your website. In Symphony,
 * there are four Datasource types provided, Section, Author, Navigation and Static XML.
 *
 * Section is the mostly commonly used Datasource, which allows the filtering
 * and searching for Entries in a Section to be returned as XML.
 *
 * Navigation datasources
 * expose the Symphony Navigation structure of the Pages in the installation.
 *
 * Authors datasources
 * expose the Symphony Authors that are registered as users of the backend.
 *
 * Static XML datasources
 * exposes some static XML to add to the page XML.
 *
 * Datasources are saved through the
 * Symphony backend, which uses a Datasource template defined in
 * `TEMPLATE . /datasource.tpl`.
 *
 * @link http://www.getsymphony.com/learn/concepts/view/data-sources/
 */
abstract class Datasource
{
    /**
     * A constant that represents if this filter is an AND filter in which
     * an Entry must match all these filters. This filter is triggered when
     * the filter string contains a ` + `.
     *
     * @since Symphony 2.3.2
     * @var integer
     */
    const FILTER_AND = 1;

    /**
     * A constant that represents if this filter is an OR filter in which an
     * entry can match any or all of these filters
     *
     * @since Symphony 2.3.2
     * @var integer
     */
    const FILTER_OR = 2;

    /**
     * Holds all the environment variables which include parameters set by
     * other Datasources or Events.
     * @var array
     */
    protected $_env = [];

    /**
     * If true, this datasource only will be outputting parameters from the
     * Entries, and no actual content.
     * @var boolean
     */
    protected $_param_output_only;

    /**
     * An array of datasource dependancies. These are datasources that must
     * run first for this datasource to be able to execute correctly
     * @var array
     */
    protected $_dependencies = [];

    /**
     * When there is no entries found by the Datasource, this parameter will
     * be set to true, which will inject the default Symphony 'No records found'
     * message into the datasource's result
     * @var boolean
     */
    protected $_force_empty_result = false;

    /**
     * When there is a negating parameter, this parameter will
     * be set to true, which will inject the default Symphony 'Results Negated'
     * message into the datasource's result
     * @var boolean
     */
    protected $_negate_result = false;

    /**
     * Constructor for the datasource sets the parent, if `$process_params` is
     * set, the `$env` variable will be run through
     * `Datasource::processParameters`.
     *
     * @see toolkit.Datasource#processParameters()
     * @param array $env
     *  The environment variables from the Frontend class which includes
     *  any params set by Symphony or Events or by other Datasources
     * @param boolean $process_params
     *  If set to true, `Datasource::processParameters` will be called. By default
     *  this is true
     * @throws FrontendPageNotFoundException
     */
    public function __construct(
        array $env = null,
        bool $process_params = true
    )
    {
        // Support old the __construct (for the moment anyway).
        // The old signature was array/array/boolean
        // The new signature is array/boolean
        $arguments = func_get_args();

        if (count($arguments) == 3 && is_bool($arguments[1]) && is_bool($arguments[2])) {
            $env = $arguments[0];
            $process_params = $arguments[1];
        }

        if ($process_params) {
            $this->processParameters($env);
        }
    }

    /**
     * Create instance of class given in XML file.
     *
     * @param string $file
     *  File path
     */
    public static function create(string $file)
    {
        if (!is_file($file)) {
            die("File <code>$file</code> not found.");
        }
        $xml = new XMLDocument();
        $xml->load($file);
        $root = $xml->documentElement;
        if (preg_match('/^data[/-_]{1}source/', $root->nodeName) === 0) {
            exit("File is not datasource");
        }
        $class =
    }

    /**
     * This function is required in order to edit it in the datasource editor
     * page. Do not overload this function if you are creating a custom
     * datasource. It is only used by the datasource editor. If this is set to
     * false, which is default, the Datasource's `about()` information will be
     * displayed.
     *
     * @return boolean
     *   true if the Datasource can be edited, false otherwise. Defaults to false
     */
    public function allowEditorToParse(): bool
    {
        return false;
    }

    /**
     * This function is required in order to identify what section this Datasource is for. It
     * is used in the datasource editor. It must remain intact. Do not overload this function in
     * custom events. Other datasources may return a string here defining their datasource
     * type when they do not query a section.
     *
     * @return string|integer|null
     */
    public function getSource(): string|int|null
    {
        return null;
    }

    /**
     * Accessor function to return this Datasource's dependencies
     *
     * @return array
     */
    public function getDependencies(): array
    {
        return $this->_dependencies;
    }

    /**
     * Returns an associative array of information about a datasource.
     *
     * @return array
     */
    public function about(): array
    {
        return [];
    }

    /**
     * The meat of the Datasource, this function includes the datasource
     * type's file that will preform the logic to return the data for this datasource
     * It is passed the current parameters.
     *
     * @param array $param_pool
     *  The current parameter pool that this Datasource can use when filtering
     *  and finding Entries or data.
     * @return XMLElement
     *  The XMLElement to add into the XML for a page.
     */
    public function execute(
        XMLElement &$wrapper,
        array &$param_pool = null
    ): XMLElement
    {
        //$result = new XMLElement($this->dsParamROOTELEMENT);
        $result = $wrapper->appendElement($this->dsParamROOTELEMENT);

        try {
            $result = $this->execute($param_pool);
        } catch (FrontendPageNotFoundException $e) {
            // Work around. This ensures the 404 page is displayed and
            // is not picked up by the default catch() statement below
            FrontendPageNotFoundExceptionRenderer::render($e);
        } catch (Exception $e) {
            $result->appendChild(new XMLElement('error', General::wrapInCDATA($e->getMessage())));
            return $result;
        }

        if ($this->_force_empty_result) {
            $result = $this->emptyXMLSet();
        }

        if ($this->_negate_result) {
            $result = $this->negateXMLSet();
        }

        return $result;
    }

    /**
     * By default, all Symphony filters are considering to be OR and " + " filters
     * are used for AND. They are all used and Entries must match each filter to be included.
     * It is possible to use OR filtering in a field by using an ", " to separate the values.
     *
     * If the filter is "test1, test2", this will match any entries where this field
     * is test1 OR test2. If the filter is "test1 + test2", this will match entries
     * where this field is test1 AND test2. The spaces around the + are required.
     *
     * Not all fields supports this feature.
     *
     * This function is run on each filter (ie. each field) in a datasource.
     *
     * @param string $value
     *  The filter string for a field.
     * @return integer
     *  Datasource::FILTER_OR or Datasource::FILTER_AND
     */
    public static function determineFilterType($value): int
    {
        // Check for two possible combos
        //  1. The old pattern, which is ' + '
        //  2. A new pattern, which accounts for '+' === ' ' in urls
        $pattern = '/(\s+\+\s+)|(\+\+\+)/';
        return preg_match($pattern, $value) === 1 ? Datasource::FILTER_AND : Datasource::FILTER_OR;
    }

    /**
     * Splits the filter string value into an array.
     *
     * @since Symphony 2.7.0
     * @param int $filter_type
     *  The filter's type, as determined by `determineFilterType()`.
     *  Valid values are Datasource::FILTER_OR or Datasource::FILTER_AND
     * @param string $value
     *  The filter's value
     * @return array
     *  The splitted filter value, according to its type
     */
    public static function splitFilter(int $filter_type, string $value): array
    {
        $pattern = $filter_type === Datasource::FILTER_AND ? '\+' : '(?<!\\\\),';
        $value = preg_split('/\s*' . $pattern . '\s*/', $value, -1, PREG_SPLIT_NO_EMPTY);
        $value = array_map('trim', $value);
        $value = array_map(array('Datasource', 'removeEscapedCommas'), $value);
        return $value;
    }

    /**
     * If there is no results to return this function calls `Datasource::noRecordsFound`
     * which appends an XMLElement to the current root element.
     *
     * @param XMLElement $xml
     *  The root element XMLElement for this datasource. By default, this will
     *  the handle of the datasource, as defined by `$this->dsParamROOTELEMENT`
     * @return XMLElement
     */
    public function emptyXMLSet(XMLElement $xml = null): XMLElement
    {
        if (is_null($xml)) {
            $xml = new XMLElement($this->dsParamROOTELEMENT);
        }

        $xml->appendChild($this->noRecordsFound());

        return $xml;
    }

    /**
     * If the datasource has been negated this function calls `Datasource::negateResult`
     * which appends an XMLElement to the current root element.
     *
     * @param XMLElement $xml
     *  The root element XMLElement for this datasource. By default, this will
     *  the handle of the datasource, as defined by `$this->dsParamROOTELEMENT`
     * @return XMLElement
     */
    public function negateXMLSet(XMLElement $xml = null): XMLElement
    {
        if (is_null($xml)) {
            $xml = new XMLElement($this->dsParamROOTELEMENT);
        }

        $xml->appendChild($this->negateResult());

        return $xml;
    }

    /**
     * Returns an error XMLElement with 'No records found' text
     *
     * @return XMLElement
     */
    public function noRecordsFound(): XMLElement
    {
        return new XMLElement('error', __('No records found.'));
    }

    /**
     * Returns an error XMLElement with 'Result Negated' text
     *
     * @return XMLElement
     */
    public function negateResult(): XMLElement
    {
        $error = new XMLElement('error', __("Data source not executed, forbidden parameter was found."), [
            'forbidden-param' => $this->dsParamNEGATEPARAM
        ]);

        return $error;
    }

    /**
     * This function iterates over the filters and replace any parameters
     * with their actual values. All other Datasource variables such sorting,
     * ordering and pagination variables are also set by this function.
     *
     * @param array $env
     *  The environment variables from the Frontend class which includes
     *  any params set by Symphony or Events or by other Datasources
     * @throws FrontendPageNotFoundException
     */
    public function processParameters(array $env = null): void
    {
        if ($env) {
            $this->_env = $env;
        }

        if ((isset($this->_env) && is_array($this->_env)) && isset($this->dsParamFILTERS) && is_array($this->dsParamFILTERS) && !empty($this->dsParamFILTERS)) {
            foreach ($this->dsParamFILTERS as $key => $value) {
                $value = stripslashes($value);
                $new_value = $this->processParametersInString($value, $this->_env);

                // If a filter gets evaluated to nothing, eg. ` + ` or ``, then remove
                // the filter. Respects / as this may be real from current-path. RE: #1759
                if (strlen(trim($new_value)) === 0 || !preg_match('/[^\s|+|,]+/u', $new_value)) {
                    unset($this->dsParamFILTERS[$key]);
                } else {
                    $this->dsParamFILTERS[$key] = $new_value;
                }
            }
        }

        if (isset($this->dsParamORDER)) {
            $this->dsParamORDER = $this->processParametersInString($this->dsParamORDER, $this->_env);
        }

        if (isset($this->dsParamSORT)) {
            $this->dsParamSORT = $this->processParametersInString($this->dsParamSORT, $this->_env);
        }

        if (isset($this->dsParamSTARTPAGE)) {
            $this->dsParamSTARTPAGE = $this->processParametersInString($this->dsParamSTARTPAGE, $this->_env);
            if ($this->dsParamSTARTPAGE === '') {
                $this->dsParamSTARTPAGE = '1';
            }
        }

        if (isset($this->dsParamLIMIT)) {
            $this->dsParamLIMIT = $this->processParametersInString($this->dsParamLIMIT, $this->_env);
        }

        if (
            isset($this->dsParamREQUIREDPARAM)
            && strlen(trim($this->dsParamREQUIREDPARAM)) > 0
            && $this->processParametersInString(trim($this->dsParamREQUIREDPARAM), $this->_env, false) === ''
        ) {
            $this->_force_empty_result = true; // don't output any XML
            if (isset($this->dsParamPARAMOUTPUT)) {
                $this->dsParamPARAMOUTPUT = null; // don't output any parameters
            }
            if (isset($this->dsParamINCLUDEDELEMENTS)) {
                $this->dsParamINCLUDEDELEMENTS = null; // don't query any fields in this section
            }
            return;
        }

        if (
            isset($this->dsParamNEGATEPARAM)
            && strlen(trim($this->dsParamNEGATEPARAM)) > 0
            && $this->processParametersInString(trim($this->dsParamNEGATEPARAM), $this->_env, false) !== ''
        ) {
            $this->_negate_result = true; // don't output any XML
            if (isset($this->dsParamPARAMOUTPUT)) {
                $this->dsParamPARAMOUTPUT = null; // don't output any parameters
            }
            if (isset($this->dsParamINCLUDEDELEMENTS)) {
                $this->dsParamINCLUDEDELEMENTS = null; // don't query any fields in this section
            }
            return;
        }

        $this->_param_output_only = ((!isset($this->dsParamINCLUDEDELEMENTS) || !is_array($this->dsParamINCLUDEDELEMENTS) || empty($this->dsParamINCLUDEDELEMENTS)) && !isset($this->dsParamGROUP));

        if (isset($this->dsParamREDIRECTONEMPTY) && $this->dsParamREDIRECTONEMPTY === 'yes' && $this->_force_empty_result) {
            throw new FrontendPageNotFoundException;
        }
    }

    /**
     * This function will parse a string (usually a URL) and fully evaluate any
     * parameters (defined by {$param}) to return the absolute string value.
     *
     * @since Symphony 2.3
     * @param string $url
     *  The string (usually a URL) that contains the parameters (or doesn't)
     * @return string
     *  The parsed URL
     */
    public function parseParamURL(string $url = null): string
    {
        if (!isset($url)) {
            return null;
        }

        // urlencode parameters
        $params = [];

        if (preg_match_all('@{([^}]+)}@i', $url, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $params[$m[1]] = [
                    'param' => preg_replace('/:encoded$/', null, $m[1]),
                    'encode' => preg_match('/:encoded$/', $m[1])
                ];
            }
        }

        foreach ($params as $key => $info) {
            $replacement = $this->processParametersInString($info['param'], $this->_env, false);
            if ($info['encode'] == true) {
                $replacement = urlencode($replacement);
            }
            $url = str_replace("{{$key}}", $replacement, $url);
        }

        return $url;
    }

    /**
     * This function will replace any parameters in a string with their value.
     * Parameters are defined by being prefixed by a `$` character. In certain
     * situations, the parameter will be surrounded by `{}`, which Symphony
     * takes to mean, evaluate this parameter to a value, other times it will be
     * omitted which is usually used to indicate that this parameter exists
     *
     * @param string $value
     *  The string with the parameters that need to be evaluated
     * @param array $env
     *  The environment variables from the Frontend class which includes
     *  any params set by Symphony or Events or by other Datasources
     * @param boolean $includeParenthesis
     *  Parameters will sometimes not be surrounded by `{}`. If this is the case
     *  setting this parameter to false will make this function automatically add
     *  them to the parameter. By default this is true, which means all parameters
     *  in the string already are surrounded by `{}`
     * @param boolean $escape
     *  If set to true, the resulting value will passed through `urlencode` before
     *  being returned. By default this is `false`
     * @return string
     *  The string with all parameters evaluated. If a parameter is not found, it will
     *  not be replaced and remain in the `$value`.
     */
    public function processParametersInString(
        string $value,
        array $env,
        bool $includeParenthesis = true,
        bool $escape = false
    ): string
    {
        if (trim($value) == '') {
            return null;
        }

        if (!$includeParenthesis) {
            $value = '{'.$value.'}';
        }

        if (preg_match_all('@{([^}]+)}@i', $value, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                list($source, $cleaned) = $match;

                $replacement = null;

                $bits = preg_split('/:/', $cleaned, -1, PREG_SPLIT_NO_EMPTY);

                foreach ($bits as $param) {
                    if ($param[0] !== '$') {
                        $replacement = $param;
                        break;
                    }

                    $param = trim($param, '$');

                    $replacement = Datasource::findParameterInEnv($param, $env);

                    if (is_array($replacement)) {
                        $replacement = array_map(array('Datasource', 'escapeCommas'), $replacement);
                        if (count($replacement) > 1) {
                            $replacement = implode(',', $replacement);
                        } else {
                            $replacement = end($replacement);
                        }
                    }

                    if (!empty($replacement)) {
                        break;
                    }
                }

                if ($escape) {
                    $replacement = urlencode($replacement);
                }

                $value = str_replace($source, $replacement, $value);
            }
        }

        return $value;
    }

    /**
     * Using regexp, this escapes any commas in the given string
     *
     * @param string $string
     *  The string to escape the commas in
     * @return string
     */
    public static function escapeCommas(string $string): string
    {
        return preg_replace('/(?<!\\\\),/', "\\,", $string);
    }

    /**
     * Used in conjunction with escapeCommas, this function will remove
     * the escaping pattern applied to the string (and commas)
     *
     * @param string $string
     *  The string with the escaped commas in it to remove
     * @return string
     */
    public static function removeEscapedCommas(string $string): string
    {
        return preg_replace('/(?<!\\\\)\\\\,/', ',', $string);
    }

    /**
     * Parameters can exist in three different facets of Symphony; in the URL,
     * in the parameter pool or as an Symphony param. This function will attempt
     * to find a parameter in those three areas and return the value. If it is not found
     * null is returned
     *
     * @param string $needle
     *  The parameter name
     * @param array $env
     *  The environment variables from the Frontend class which includes
     *  any params set by Symphony or Events or by other Datasources
     * @return mixed
     *  If the value is not found, null, otherwise a string or an array is returned
     */
    public static function findParameterInEnv(string $needle, array $env)
    {
        if (isset($env['env']['url'][$needle])) {
            return $env['env']['url'][$needle];
        }

        if (isset($env['env']['pool'][$needle])) {
            return $env['env']['pool'][$needle];
        }

        if (isset($env['param'][$needle])) {
            return $env['param'][$needle];
        }

        return null;
    }
}
