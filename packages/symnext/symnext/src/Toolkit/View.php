<?php

/**
 * @package Toolkit
 */

namespace Symnext\Toolkit;

use Symnext\Core\App;
use Symnext\Toolkit\XMLDocument;
use DOMImplementation;
#use DOMDocument, DateTime;

/**
 * Setting the correct Content-Type for the page and executing any Datasources
 * Events attached to the page to generate a string of HTML that is returned to
 * the browser. If the resolved page does not exist or the user is not allowed
 * to view it, the appropriate 404/403 page will be shown instead.
 */
abstract class View extends XSLTPage
{
    protected $view_data;

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
     * Constructor function sets the `$is_logged_in` variable.
     */
    public function __construct()
    {
        parent::__construct();
        $view_template = $this->getViewTemplate();

//         $imp = new DOMImplementation;
//         $doc = $imp->createDocument(null, 'data');
//         $doc->xmlVersion = '1.0';
//         $doc->registerNodeClass('DOMDocument', 'Symnext\Toolkit\XMLDocument');
//         $doc->registerNodeClass('DOMElement', 'Symnext\Toolkit\XMLElement');
//         $root = $doc->documentElement;
//         $root->setAttribute('version', '1.0');
//         $x_params = $root->appendElement('params');
//         $x_params->appendElement('domain', \BASE_URL);
//         $this->xmlDoc = $doc;
//         $this->xmlRoot = $root;
//
//         $doc = $imp->createDocument(\XSL_NAMESPACE, 'xsl:stylesheet');
//         $doc->registerNodeClass('DOMElement', 'Symnext\Toolkit\XMLElement');
//         $root = $doc->documentElement;
//         $root->setAttribute('version', '1.0');
//         $root->appendElement('xsl:import', null, ['href' => $view_template]);
//         $this->xslDoc = $doc;
//         $this->xslRoot = $root;
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

    protected function setXMLDocument()
    {
        $document_class = property_exists(self::class, 'XML_DOCUMENT_CLASS') ?
            self::XML_DOCUMENT_CLASS : null;
        $doc = XMLElement::newDocument($document_class);
        $doc->appendChild($doc->createElement('data'));
        $this->xmlDoc = $doc;
    }

    protected function getXMLRoot()
    {
        if (!isset($this->xmlDoc)) die("No XML document set.");
        return $this->xmlDoc->documentElement;
    }

    protected function getXSLRoot()
    {
        if (!isset($this->xslDoc)) die("No XML document set.");
        return $this->xslDoc->documentElement;
    }

    /**
     * Before generate.
     */
    protected function beforefilter()
    {
    }

    /**
     * This function is called immediately from the class passing the current
     * URL for generation. Generate will execute all events and datasources
     * registered to this view so that it can be rendered. A number of
     * delegates are fired during execution for extensions to hook into.
     *
     * @uses FrontendDevKitResolve
     * @uses FrontendOutputPreGenerate
     * @uses FrontendPreRenderHeaders
     * @uses FrontendOutputPostGenerate
     * @see buildPage()
     * @param string $page
     * The URL of the current page as returned by `get_uri`.
     * @throws Exception
     * @throws FrontendPageNotFoundException
     * @throws SymnextException
     * @return string
     * The page source after the XSLT has transformed this page's XML.
     */
    public function generate(array $view_data = null): string
    {
        $this->view_data = $view_data;

        $this->params = $view_data['params'] ?? [];

        if (method_exists($this, 'beforeFilter')) {
            $this->beforeFilter();
        }

        if (method_exists($this, 'initialise')) {
            $this->initializeView();
        }

        $method = $view_data['method'] ?? null;
        if ($method and method_exists($this, $method)) {
            call_user_func($this, $method);
        }


        $view_template = null;
        if (isset($view_data['view'])) {
            $view_template = $view_data['view'];
        } elseif (method_exists($this, 'getViewTemplate')) {
            $view_template = $this->getViewTemplate();
        }
        if (!$view_template) {
            exit("No view template given");
        }

        // Create XML document.
        $this->setXMLDocument();
        $xml_root = $this->getXMLRoot();
        $x_params = $xml_root->appendElement('params');
        $x_params->appendElement('domain', \BASE_URL);

        // Create XSL document.
        $doc = XMLElement::newDocument();
        $doc->load($view_template);
        $this->xslDoc = $doc;
        $this->xslDir = dirname($view_template);
        /*$xsl_root = $doc->appendChild($doc->createElementNS(\XSL_NAMESPACE, 'xsl:stylesheet'));
        $xsl_root->setAttribute('version', '1.0');
        $xsl_root->setAttribute('xmlns:xsl', \XSL_NAMESPACE);
        $xsl_root->appendElement(\XSL_NAMESPACE, 'xsl:import', null, ['href' => $view_template]);*/

        $this->setHeaders();
        $this->setDefaultParams();
        $params = $view_data['params'] ?? null;
        if (is_array($params)) {
            $this->addParams($params);
        }
#echo $this->tree->asXML(); die;
        $full_generate = true;
        $devkit = null;
        $output = null;

        if ($this->is_logged_in) {
            /**
             * Allows a devkit object to be specified, and stop continued execution:
             *
             * @delegate FrontendDevKitResolve
             * @param string $context
             * '/frontend/'
             * @param boolean $full_generate
             *  Whether this page will be completely generated (ie. invoke the XSLT transform)
             *  or not, by default this is true. Passed by reference
             * @param mixed $devkit
             *  Allows a devkit to register to this page
             */
            /*App::ExtensionManager()->notifyMembers(
                'FrontendDevKitResolve', '/frontend/', [
                    'full_generate' => &$full_generate,
                    'devkit' => &$devkit
                ]
            );*/
        }

        #App::Profiler()->sample('Page creation started');
        #$this->_page = $page;
        /*if (method_exists($this, 'addToXMLTree')) {
            $this->addToXMLTree();
        }*/

        $this->buildView();

        if ($full_generate) {
            /**
             * Immediately before generating the page. Provided with the page object, XML and XSLT
             * @delegate FrontendOutputPreGenerate
             * @param string $context
             * '/frontend/'
             * @param FrontendPage $page
             *  This FrontendPage object, by reference
             * @param XMLElement $xml
             *  This pages XML, including the Parameters, Datasource and Event XML, by reference as
             *  an XMLElement
             * @param string $xsl
             *  This pages XSLT, by reference
             */
            /*App::ExtensionManager()->notifyMembers(
                'FrontendOutputPreGenerate', '/frontend/', [
                    'page'  => &$this,
                    'xml'   => &$this->_xml,
                    'xsl'   => &$this->_xsl
                ]
            );*/

            /*if (is_null($devkit)) {
                if (General::in_iarray('XML', $this->_pageData['type'])) {
                    $this->addHeaderToPage('Content-Type', 'text/xml; charset=utf-8');
                } elseif (General::in_iarray('JSON', $this->_pageData['type'])) {
                    $this->addHeaderToPage('Content-Type', 'application/json; charset=utf-8');
                } else {
                    $this->addHeaderToPage('Content-Type', 'text/html; charset=utf-8');
                }

                if (in_array('404', $this->_pageData['type'])) {
                    $this->setHttpStatus(self::HTTP_STATUS_NOT_FOUND);
                } elseif (in_array('403', $this->_pageData['type'])) {
                    $this->setHttpStatus(self::HTTP_STATUS_FORBIDDEN);
                }
            }*/

            // Lock down the frontend first so that extensions can easily remove these
            // headers if desired. RE: #2480
            $this->addHeaderToPage('X-Frame-Options', 'SAMEORIGIN');
            // Add more http security headers, RE: #2248
            $this->addHeaderToPage('X-Content-Type-Options', 'nosniff');
            $this->addHeaderToPage('X-XSS-Protection', '1; mode=block');

            /**
             * This is just prior to the page headers being rendered, and is suitable for changing them
             * @delegate FrontendPreRenderHeaders
             * @param string $context
             * '/frontend/'
             */
            /*App::ExtensionManager()->notifyMembers(
                'PreRenderHeaders', '/frontend/'
            );*/

            #$backup_param = $this->_param;
            #$this->_param['current-query-string'] = #General::wrapInCDATA($this->_param['current-query-string']);

            // In Symnext 2.4, the XML structure stays as an object until
            // the very last moment.
            /*App::Profiler()->seed(precision_timer());
            if ($this->_xml instanceof XMLElement) {
                $this->setXML($this->_xml->generate(true, 0));
            }
            App::Profiler()->sample('XML Generation', PROFILE_LAP);*/

            #$this->_param = $backup_param;

            #App::Profiler()->sample('XSLT Transformation', PROFILE_LAP);

            #echo $this->xslDoc->saveXML(); die;

            $output = parent::generate();
            /**
             * Immediately after generating the page. Provided with string containing page source
             * @delegate FrontendOutputPostGenerate
             * @param string $context
             * '/frontend/'
             * @param string $output
             *  The generated output of this page, ie. a string of HTML, passed by reference
             */
            /*App::ExtensionManager()->notifyMembers(
                'FrontendOutputPostGenerate', '/frontend/', ['output' => &$output]
            );*/

            if (is_null($devkit) && !$output) {
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
                );*/
                echo "XSLT processing error:\n\n" . $errstr; die;
            }

            #App::Profiler()->sample('Page creation complete');
        }

        /*if (!is_null($devkit)) {
            $devkit->prepare($this, $this->_pageData, $this->_xml, $this->_param, $output);

            return $devkit->build();
        }*/

        // Display the Event Results in the page source if the user is logged
        // into Symnext, the page is not JSON and if it is enabled in the
        // configuration.
        /*if ($this->is_logged_in && !General::in_iarray('JSON', $this->_pageData['type']) && $Config->get('display_event_xml_in_source', 'public') === 'yes') {
            $output .= PHP_EOL . '<!-- ' . PHP_EOL . $this->_events_xml->generate(true) . ' -->';
        }*/

        $this->renderHeaders();
        return $output;
    }

    protected function buildView(): void
    {
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
        $upload_size_sym = $Config->get('max_upload_size', 'admin');
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

        #$xml_doc = $this->xml_doc;

        #$xml_doc->renderHeader();

        #$events = $xml_doc->createElement('events');
        #$this->processEvents($page['events'], $events);
        #$xml_doc->appendChild($events);

        #$this->_events_xml = clone $events;

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

        $params = $xml_doc['params'];

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
    protected function setDefaultParams(): void
    {
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

    protected function getViewTemplate(): string|null
    {
        return null;
    }

    protected function addParams(array $params): void
    {
        $this->params = array_merge($this->params, $params);
    }

    protected function addImport(
        string $href,
        string $category = null
    ): void
    {
        $this->xsl_doc->add([
            [
                'tag' => 'import',
                'attributes' => ['href' => $href]
            ]
        ]);
        if ($category) {
            $this->xml_tree->imports->add([
                'tag' => 'item',
                'value' => $href,
                'attributes' => ['category' => $category]
            ]);
        }
    }
}
