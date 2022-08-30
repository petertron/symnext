<?php

/**
 * @package Toolkit
 */

namespace Symnext\Toolkit;

use Symnext\Core\App;
use DOMDocument, DateTime;

/**
 * Setting the correct Content-Type for the page and executing any Datasources
 * Events attached to the page to generate a string of HTML that is returned to
 * the browser. If the resolved page does not exist or the user is not allowed
 * to view it, the appropriate 404/403 page will be shown instead.
 */
class WebView extends View
{
    /**
     * The URL of the current page that is being Rendered as returned
     * by `get_current_page`
     *
     * @var string
     * @see boot#get_current_page()
     */
    private $_page;

    /**
     * @var array
     */
    private array $params = [];

    /**
     * @var array
     */
    private $view_meta;

    private $view_name;

    private $events;

    private $data_sources;

    /**
     * Returns whether the user accessing this page is logged in as a Symnext
     * Author
     *
     * @var boolean
     */
    private $is_logged_in = false;

    /**
     * When events are processed, the results of them often can't be reproduced
     * when debugging the page as they happen during `$_POST`. There is a
     * Symnext configuration setting that allows the event results to be
     * appended as a HTML comment at the bottom of the page source, so logged
     * in Authors can view-source page to see the result of an event. This
     * variable holds the event XML so that it can be appended to the page
     * source if `display_event_xml_in_source` is set to 'yes'.
     * By default this is set to no.
     *
     * @var XMLElement
     */
    //private $_events_xml;

    /**
     * Holds all the environment variables which include parameters set by
     * other Datasources or Events.
     * @var array
     */
    private $_env = [];

    /**
     * Hold all the data sources that must not output their parameters in the xml.
     * @var array
     */
    private $_xml_excluded_params = [];

    /**
     * Constructor function sets the `$is_logged_in` variable.
     */
    public function __construct()
    {
        parent::__construct();
        #$this->is_logged_in = Frontend::instance()->isLoggedIn();
    }

    /**
     * Accessor function for the environment variables, aka `$this->_env`
     *
     * @return array
     */
    public function Env(): array
    {
        return $this->_env;
    }

    /**
     * Setter function for `$this->_env`, which takes an associative array
     * of environment information and replaces the existing `$this->_env`.
     *
     * @since Symnext 2.3
     * @param array $env
     *  An associative array of new environment values
     */
    public function setEnv(array $env = []): void
    {
        $this->_env = $env;
    }

    /**
     * Accessor function for this current page URL, `$this->_page`
     *
     * @return string
     */
    public function Page(): string
    {
        return $this->_page;
    }

    /**
     * Accessor function for the current page params, `$this->_param`
     *
     * @since Symnext 2.3
     * @return array
     */
    public function Params()
    {
        return $this->_param;
    }

    protected function getViewTemplate(): string|null
    {
        return $this->view_data['view'] ?? null;
        #return \VIEW_TEMPLATES . '/view.' . $view_handle . '.xsl';
    }

    /**
     * Before generate.
     */
    protected function beforeGenerate()
    {
    }

    protected function buildView(): void
    {
        global $Params;

        #print_r($Params); die;
            /*if (is_null($devkit) && !$output) {
                $errstr = null;

                while (list(, $val) = $this->Proc->getError()) {
                    $errstr .= 'Line: ' . $val['line'] . ' - ' . $val['message'] . PHP_EOL;
                }

                /*Frontend::instance()->throwCustomError(
                    trim($errstr),
                    __('XSLT Processing Error'),
                    Page::HTTP_STATUS_ERROR,
                    'xslt',
                    ['proc' => clone $this->Proc]
                );
                echo 'XSLT processing error'; die;
            }

            #App::Profiler()->sample('Page creation complete');
        }*/

        /*if (!is_null($devkit)) {
            $devkit->prepare($this, $this->_pageData, $this->_xml, $this->_param, $output);

            return $devkit->build();
        }*/

        // Display the Event Results in the page source if the user is logged
        // into Symnext, the page is not JSON and if it is enabled in the
        // configuration.
        /*if ($this->is_logged_in && !General::in_iarray('JSON', $this->_pageData['type']) && App::Configuration()->get('display_event_xml_in_source', 'public') === 'yes') {
            $output .= PHP_EOL . '<!-- ' . PHP_EOL . $this->_events_xml->generate(true) . ' -->';
        }*/

    }

    /**
     * This function sets the page's parameters, processes the Datasources and
     * Events and sets the `$xml` and `$xsl` variables. This functions resolves the `$page`
     * by calling the `resolvePage()` function. If a page is not found, it attempts
     * to locate the Symnext 404 page set in the backend otherwise it throws
     * the default Symnext 404 page. If the page is found, the page's XSL utility
     * is found, and the system parameters are set, including any URL parameters,
     * params from the Symnext cookies. Events and Datasources are executed and
     * any parameters  generated by them are appended to the existing parameters
     * before setting the Page's XML and XSL variables are set to the be the
     * generated XML (from the Datasources and Events) and the XSLT (from the
     * file attached to this Page)
     *
     * @uses FrontendPageResolved
     * @uses FrontendParamsResolve
     * @uses FrontendParamsPostResolve
     * @see resolvePage()
     */
    private function buildPage()
    {
        $start = precision_timer();

        /*if (!$page = $this->resolvePage()) {
            throw new FrontendPageNotFoundException;
        }*/

        /**
         * Just after having resolved the page, but prior to any commencement of output creation
         * @delegate FrontendPageResolved
         * @param string $context
         * '/frontend/'
         * @param FrontendPage $page
         *  An instance of this class, passed by reference
         * @param array $page_data
         *  An associative array of page data, which is a combination from `tbl_pages` and
         *  the path of the page on the filesystem. Passed by reference
         */
        /*App::ExtensionManager()->notifyMembers(
            'FrontendPageResolved', '/frontend/', [
                'page' => &$this,
                'page_data' => &$page
            ]
        );*/

        /*$current_path = explode(
            dirname(server_safe('SCRIPT_NAME')), server_safe('REQUEST_URI'), 2
        );
        $current_path = '/' . ltrim(end($current_path), '/');
        $split_path = explode('?', $current_path, 3);
        $current_path = rtrim(current($split_path), '/');
        $querystring = next($split_path);

        // Get max upload size from php and symnext config then choose the smallest
        $upload_size_php = ini_size_to_bytes(ini_get('upload_max_filesize'));
        $upload_size_sym = App::Configuration()->get('max_upload_size', 'admin');
        $date = new DateTime();*/

        /*if (isset($this->_env['url']) && is_array($this->_env['url'])) {
            foreach ($this->_env['url'] as $key => $val) {
                $this->_param[$key] = $val;
            }
        }*/

        /*if (is_array($_GET) && !empty($_GET)) {
            foreach ($_GET as $key => $val) {
                if (in_array($key, ['symnext-page', 'debug', 'profile'])) {
                    continue;
                }

                // If the browser sends encoded entities for &, ie. a=1&amp;b=2
                // this causes the $_GET to output they key as amp;b, which results in
                // $url-amp;b. This pattern will remove amp; allow the correct param
                // to be used, $url-b
                $key = preg_replace('/(^amp;|\/)/', null, $key);

                // If the key gets replaced out then it will break the XML so prevent
                // the parameter being set.
                $key = General::createHandle($key);
                if (!$key) {
                    continue;
                }

                // Handle ?foo[bar]=hi as well as straight ?foo=hi RE: #1348
                if (is_array($val)) {
                    $val = General::array_map_recursive(['FrontendPage', 'sanitizeParameter'], $val);
                } else {
                    $val = self::sanitizeParameter($val);
                }

                $this->_param['url-' . $key] = $val;
            }
        }*/

        // Flatten parameters:
        //General::flattenArray($this->_param);

        // Add Page Types to parameters so they are not flattened too early
        //$this->_param['page-types'] = $page['type'];

        // Add Page events the same way
        //$this->_param['page-events'] = explode(',', trim(str_replace('_', '-', $page['events']), ','));

        /**
         * Just after having resolved the page params, but prior to any commencement of output creation
         * @delegate FrontendParamsResolve
         * @param string $context
         * '/frontend/'
         * @param array $params
         *  An associative array of this page's parameters
         */
        /*App::ExtensionManager()->notifyMembers(
            'FrontendParamsResolve', '/frontend/', ['params' => &$this->_param]
        );*/

        $xml_build_start = precision_timer();

        $xml_doc = $this->xml_doc;

        #$xml_doc->renderHeader();

        #$events = $xml_doc->createElement('events');
        #$this->processEvents($page['events'], $events);
        #$xml_doc->appendChild($events);

        #$this->_events_xml = clone $events;

        $this->processDatasources($this->datasources, $xml_doc->documentElement);

        #App::Profiler()->seed($xml_build_start);
        #App::Profiler()->sample('XML Built', PROFILE_LAP);

        if (isset($this->_env['pool']) && is_array($this->_env['pool']) && !empty($this->_env['pool'])) {
            foreach ($this->_env['pool'] as $handle => $p) {
                if (!is_array($p)) {
                    $p = [$p];
                }

                // Check if the data source is excluded from xml output
                $dsName = current(explode('.', $handle));
                $excluded_params = $this->_xml_excluded_params[$dsName] ?? false;
                if ($dsName && $excluded_params) {
                    continue;
                }

                // Flatten and add all values
                General::flattenArray($p);
                $this->_param[$handle] = implode(', ', $p);
            }
        }

        /**
         * Access to the resolved param pool, including additional parameters provided by Data Source outputs
         * @delegate FrontendParamsPostResolve
         * @param string $context
         * '/frontend/'
         * @param array $params
         *  An associative array of this page's parameters
         */
        /*App::ExtensionManager()->notifyMembers(
            'FrontendParamsPostResolve', '/frontend/', ['params' => &$this->_param]
        );*/

        //$params = $xml_doc['params'][1]->item(0);

        /*foreach ($this->params as $key => $value) {
            // To support multiple parameters using the 'datasource.field'
            // we explode the string and create handles from it parts.
            // This is because of a limitation where Lang::createHandle()
            // will strip '.' as it's technically punctuation.
            if (strpos($key, '.') !== false) {
                $parts = explode('.', $key);
                $key = implode('.', array_map(function ($part) {
                    return Lang::createHandle($part);
                }, $parts));
            } else {
                $key = Lang::createHandle($key);
            }

            $param = $params->appendElement($key);

            // DS output params get flattened to a string, so get the original pre-flattened array
            if (isset($this->_env['pool'][$key])) {
                $value = $this->_env['pool'][$key];
            }

            if (is_array($value) && !(count($value) == 1 && empty($value[0]))) {
                foreach ($value as $key => $value) {
                    $item = new XMLElement('item', General::sanitize($value));
                    $item->setAttribute('handle', Lang::createHandle($value));
                    $param->appendChild($item);
                }
            } elseif (is_array($value)) {
                $param->setValue(General::sanitize($value[0]));
            } elseif (in_array($key, ['xsrf-token', 'current-query-string'])) {
                $param->setValue(General::wrapInCDATA($value));
            } else {
                $param->setValue(General::sanitize($value));
            }

            #$params->appendChild($param);
        }*/
        #$xml_doc->prependChild($params);

        $this->setXML($xml_doc);
        /*$xsl = '<?xml version="1.0" encoding="UTF-8"?>' .
               '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">' .
               '    <xsl:import href="/' . rawurlencode(ltrim($page['filelocation'], '/')) . '"/>' .
               '</xsl:stylesheet>';

        $this->setXSL($xsl, false);*/
#echo $this->xsl_doc->saveXML(); die;
        #App::Profiler()->seed($start);
        #App::Profiler()->sample('Page Built', PROFILE_LAP);
    }

/*
    if (empty($row)) {
        Frontend::instance()->throwCustomError(
            __('Please login to view this page.') . ' <a href="' . SYMPHONY_URL . '/login/">' . __('Take me to the login page') . '</a>.',
            __('Forbidden'),
            Page::HTTP_STATUS_FORBIDDEN
        );
    }
*/

    /**
     * The processEvents function executes all Events attached to the resolved
     * page in the correct order determined by `__findEventOrder()`. The results
     * from the Events are appended to the page's XML. Events execute first,
     * before Datasources.
     *
     * @uses FrontendProcessEvents
     * @uses FrontendEventPostProcess
     * @param string $events
     *  A string of all the Events attached to this page, comma separated.
     * @param XMLElement $wrapper
     *  The XMLElement to append the Events results to. Event results are
     *  contained in a root XMLElement that is the handlised version of
     *  their name.
     * @throws Exception
     */
    private function processEvents(string $events, XMLElement &$wrapper)
    {
        /**
         * Manipulate the events array and event element wrapper
         * @delegate FrontendProcessEvents
         * @param string $context
         * '/frontend/'
         * @param array $env
         * @param string $events
         *  A string of all the Events attached to this page, comma separated.
         * @param XMLElement $wrapper
         *  The XMLElement to append the Events results to. Event results are
         *  contained in a root XMLElement that is the handlised version of
         *  their name.
         * @param array $page_data
         *  An associative array of page meta data
         */
        App::ExtensionManager()->notifyMembers(
            'FrontendProcessEvents', '/frontend/', [
                'env' => $this->_env,
                'events' => &$events,
                'wrapper' => &$wrapper,
                'page_data' => $this->_pageData
            ]
        );

        if (strlen(trim($events)) > 0) {
            $events = preg_split('/,\s*/i', $events, -1, PREG_SPLIT_NO_EMPTY);
            $events = array_map('trim', $events);

            if (!is_array($events) || empty($events)) {
                return;
            }

            $pool = [];

            foreach ($events as $handle) {
                $pool[$handle] = EventManager::create($handle, ['env' => $this->_env, 'param' => $this->_param]);
            }

            uasort($pool, [$this, '__findEventOrder']);

            foreach ($pool as $handle => $event) {
                $startTime = precision_timer();
                $queries = App::Database()->queryCount();

                if ($xml = $event->load()) {
                    if (is_object($xml)) {
                        $wrapper->appendChild($xml);
                    } else {
                        $wrapper->setValue(
                            $wrapper->getValue() . PHP_EOL . '    ' . trim($xml)
                        );
                    }
                }

                $queries = App::Database()->queryCount() - $queries;
                App::Profiler()->seed($startTime);
                App::Profiler()->sample($handle, PROFILE_LAP, 'Event', $queries);
            }
        }

        /**
         * Just after the page events have triggered. Provided with the XML object
         * @delegate FrontendEventPostProcess
         * @param string $context
         * '/frontend/'
         * @param XMLElement $xml
         *  The XMLElement to append the Events results to. Event results are
         *  contained in a root XMLElement that is the handlised version of
         *  their name.
         */
        App::ExtensionManager()->notifyMembers('FrontendEventPostProcess', '/frontend/', ['xml' => &$wrapper]);
    }

    /**
     * This function determines the correct order that events should be executed in.
     * Events are executed based off priority, with `Event::kHIGH` priority executing
     * first. If there is more than one Event of the same priority, they are then
     * executed in alphabetical order. This function is designed to be used with
     * PHP's uasort function.
     *
     * @link http://php.net/manual/en/function.uasort.php
     * @param Event $a
     * @param Event $b
     * @return integer
     */
    private function __findEventOrder($a, $b)
    {
        if ($a->priority() == $b->priority()) {
            $a = $a->about();
            $b = $b->about();

            $handles = [$a['name'], $b['name']];
            asort($handles);

            return (key($handles) == 0) ? -1 : 1;
        }
        return $a->priority() > $b->priority() ? -1 : 1;
    }

    /**
     * Given an array of all the Datasources for this page, sort them into the
     * correct execution order and append the Datasource results to the
     * page XML. If the Datasource provides any parameters, they will be
     * added to the `$env` pool for use by other Datasources and eventual
     * inclusion into the page parameters.
     *
     * @param array $datasources
     *  A list of Datasources attached to this page.
     * @param XMLElement $wrapper
     *  The XMLElement to append the Datasource results to. Datasource
     *  results are contained in a root XMLElement that is the handlised
     *  version of their name.
     * @param array $params
     *  Any params to automatically add to the `$env` pool, by default this
     *  is an empty array. It looks like Symnext does not utilise this parameter
     *  at all
     * @throws Exception
     */
    public function processDatasources(
        array $datasources, XMLElement &$wrapper , array $params = []
    )
    {
        if (empty($datasources)) return;

        #return; //Return anyway (temporary)

        #$datasources = array_map('trim', $datasources);

        #$this->_env['pool'] = $params;
        #$pool = $params;
        $dependencies = [];

        /*foreach ($datasources as $handle) {
            $pool[$handle] = DatasourceManager::create($handle, [], false);
            $dependencies[$handle] = $pool[$handle]->getDependencies();
        }*/

        foreach ($datasources as $ds_info) {
            $pool[$handle] = DatasourceManager::create($handle, [], false);
            $dependencies[$handle] = $pool[$handle]->getDependencies();
        }

        #$dsOrder = $this->findDatasourceOrder($dependencies);

        foreach ($dsOrder as $handle) {
            #$startTime = precision_timer();
            #$queries = App::Database()->queryCount();

            // default to no XML
            $xml = null;
            $ds = $pool[$handle];

            // Handle redirect on empty setting correctly RE: #1539
            /*try {
                $ds->processParameters(['env' => $this->_env, 'param' => $this->_param]);
            } catch (FrontendPageNotFoundException $e) {
                // Work around. This ensures the 404 page is displayed and
                // is not picked up by the default catch() statement below
                FrontendPageNotFoundExceptionRenderer::render($e);
            }*/

            /**
             * Allows extensions to execute the data source themselves (e.g. for caching)
             * and providing their own output XML instead
             *
             * @since Symnext 2.3
             * @delegate DataSourcePreExecute
             * @param string $context
             * '/frontend/'
             * @param DataSource $datasource
             *  The Datasource object
             * @param mixed $xml
             *  The XML output of the data source. Can be an `XMLElement` or string.
             * @param array $param_pool
             *  The existing param pool including output parameters of any previous data sources
             */
            /*App::ExtensionManager()->notifyMembers(
                'DataSourcePreExecute',
                '/frontend/',
                [
                    'datasource' => &$ds,
                    'xml' => &$xml,
                    'param_pool' => &$this->_env['pool']
                ]
            );*/

            // if the XML is still null, an extension has not run the data source, so run normally
            // This is deprecated and will be replaced by execute in Symnext 3.0.0
            if (is_null($xml)) {
                $xml = $ds->execute($wrapper, $this->_env['pool']);
            }

            // If the data source does not want to output its xml, keep the info for later
            if (isset($ds->dsParamPARAMXML) && $ds->dsParamPARAMXML !== 'yes') {
                $this->_xml_excluded_params['ds-' . $ds->dsParamROOTELEMENT] = true;
            }

            if ($xml) {
                /**
                 * After the datasource has executed, either by itself or via the
                 * `DataSourcePreExecute` delegate, and if the `$xml` variable is truthy,
                 * this delegate allows extensions to modify the output XML and parameter pool
                 *
                 * @since Symnext 2.3
                 * @delegate DataSourcePostExecute
                 * @param string $context
                 * '/frontend/'
                 * @param DataSource $datasource
                 *  The Datasource object
                 * @param mixed $xml
                 *  The XML output of the data source. Can be an `XMLElement` or string.
                 * @param array $param_pool
                 *  The existing param pool including output parameters of any previous data sources
                 */
                /*App::ExtensionManager()->notifyMembers('DataSourcePostExecute', '/frontend/', [
                    'datasource' => $ds,
                    'xml' => &$xml,
                    'param_pool' => &$this->_env['pool']
                ]);

                if ($xml instanceof XMLElement) {
                    $wrapper->appendChild($xml);
                } else {
                    $wrapper->appendChild('    ' . trim($xml) . PHP_EOL);
                }*/
            }

            $queries = App::Database()->queryCount() - $queries;
            App::Profiler()->seed($startTime);
            App::Profiler()->sample($handle, PROFILE_LAP, 'Datasource', $queries);
            unset($ds);
        }
    }

    /**
     * The function finds the correct order Datasources need to be processed
     * in to satisfy all dependencies that parameters can resolve correctly
     * and in time for other Datasources to filter on.
     *
     * @param array $dependenciesList
     *  An associative array with the key being the Datasource handle and the
     * values being it's dependencies.
     * @return array
     *  The sorted array of Datasources in order of how they should be executed
     */
    private function findDatasourceOrder(array $dependenciesList = null)
    {
        if (!is_array($dependenciesList) || empty($dependenciesList)) {
            return;
        }

        foreach ($dependenciesList as $handle => $dependencies) {
            foreach ($dependencies as $i => $dependency) {
                $dependency = explode('.', $dependency);
                $dependenciesList[$handle][$i] = reset($dependency);
            }
        }

        $orderedList = [];
        $dsKeyArray = $this->buildDatasourcePooledParamList(
            array_keys($dependenciesList)
        );

        // 1. First do a cleanup of each dependency list, removing non-existant DS's and find
        //    the ones that have no dependencies, removing them from the list
        foreach ($dependenciesList as $handle => $dependencies) {
            $dependenciesList[$handle] = array_intersect($dsKeyArray, $dependencies);

            if (empty($dependenciesList[$handle])) {
                unset($dependenciesList[$handle]);
                $orderedList[] = str_replace('_', '-', $handle);
            }
        }

        // 2. Iterate over the remaining DS's. Find if all their dependencies are
        //    in the $orderedList array. Keep iterating until all DS's are in that list
        //    or there are circular dependencies (list doesn't change between iterations
        //    of the while loop)
        do {
            $last_count = count($dependenciesList);

            foreach ($dependenciesList as $handle => $dependencies) {
                if (General::in_array_all(array_map(
                    function($a) {
                        return str_replace('$ds-', '', $a);
                    },
                    $dependencies),
                    $orderedList)
                ) {
                    $orderedList[] = str_replace('_', '-', $handle);
                    unset($dependenciesList[$handle]);
                }
            }
        } while (!empty($dependenciesList) && $last_count > count($dependenciesList));

        if (!empty($dependenciesList)) {
            $orderedList = array_merge($orderedList, array_keys($dependenciesList));
        }

        return array_map(function($a) {return str_replace('-', '_', $a);}, $orderedList);
    }

    /**
     * Given an array of datasource dependancies, this function will translate
     * each of them to be a valid datasource handle.
     *
     * @param array $datasources
     *  The datasource dependencies
     * @return array
     *  An array of the handlised datasources
     */
    private function buildDatasourcePooledParamList(array $datasources =  null)
    {
        if (!is_array($datasources) || empty($datasources)) {
            return [];
        }

        $list = [];

        foreach ($datasources as $handle) {
            $rootelement = str_replace('_', '-', $handle);
            $list[] = '$ds-' . $rootelement;
        }

        return $list;
    }

    /**
     * Given a string (expected to be a URL parameter) this function will
     * ensure it is safe to embed in an XML document.
     *
     * @since Symnext 2.3.1
     * @param string $parameter
     *  The string to sanitize for XML
     * @return string
     *  The sanitized string
     */
    public static function sanitizeParameter(string $parameter)
    {
        return XMLElement::stripInvalidXMLCharacters($parameter);
    }

    protected function setHeaders()
    {
        $this->addHeaderToPage(
            'Cache-Control', 'no-cache, must-revalidate, max-age=0'
        );
        $this->addHeaderToPage(
            'Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT'
        );
    }

    protected function setDefaultParams(): void
    {
        $date = new DateTime();
        $this->addParams([
            'today' => $date->format('Y-m-d'),
            'current-time' => $date->format('H:i'),
            'this-year' => $date->format('Y'),
            'this-month' => $date->format('m'),
            'this-day' => $date->format('d'),
            'timezone' => $date->format('P'),
            'timestamp' => $date->format('U'),
            'site-name' => App::configuration()->get('site_name', 'general'),
            #'page-title' => $page['title'],
            'root' => \URL,
            'workspace' => \URL . '/workspace',
            #'workspace-path' => DIRROOT . '/workspace',
            #'http-host' => HTTP_HOST,
            #'root-page' => ($root_page ? $root_page : $page['handle']),
            #'current-page' => $page['handle'],
            #'current-path' => ($current_path == '') ? '/' : $current_path,
            #'current-query-string' => self::sanitizeParameter($querystring),
            #'current-url' => URL . $current_path,
            #'upload-limit' => min($upload_size_php, $upload_size_sym),
            'symnext-version' => App::Configuration()->get('version', 'symnext'),
        ]);
    }

    protected function setEventsAndDatasources(): void
    {
        $meta = $this->xsl_doc->getMetaData();
        foreach ($meta as $meta_item) {
            if ($meta_item['name'] == 'datasource') {
                $this->datasources[] = $meta_item['attributes'];
            } elseif ($meta_item['name'] == 'event') {
                $this->events[] = $meta_item['attributes'];
            }
        }
    }
}
