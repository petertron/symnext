<?php

/**
 * @package Core
 */

namespace Symnext\Core;

/**
 * The Administration class is an instance of Symphony that controls
 * all backend pages. These pages are HTMLPages are usually generated
 * using XMLElement before being rendered as HTML. These pages do not
 * use XSLT. The Administration is only accessible by logged in Authors
 */

class Administration extends Symphony
{
    /**
     * The path of the current page, ie. '/blueprints/sections/'
     * @var string
     */
    private $_currentPage  = null;

    /**
     * An associative array of the page's callback, including the keys
     * 'driver', which is a lowercase version of `$this->_currentPage`
     * with any slashes removed, 'classname', which is the name of the class
     * for this page, 'pageroot', which is the root page for the given page, (ie.
     * excluding /saved/, /created/ or any sub pages of the current page that are
     * handled using the _switchboard function.
     *
     * @see toolkit.AdministrationPage#__switchboard()
     * @var array|boolean
     */
    private $_callback = null;

    /**
     * The class representation of the current Symphony backend page,
     * which is a subclass of the `HTMLPage` class. Symphony uses a convention
     * of prefixing backend page classes with 'content'. ie. 'contentBlueprintsSections'
     * @var HTMLPage
     */
    public $Page;

    /**
     * Overrides the default Symphony constructor to add XSRF checking
     */
    protected function __construct()
    {
        parent::__construct();

        // Ensure the request is legitimate. RE: #1874
        if (self::isXSRFEnabled()) {
            XSRF::validateRequest();
        }
    }

    /**
     * This function returns an instance of the Administration
     * class. It is the only way to create a new Administration, as
     * it implements the Singleton interface
     *
     * @return Administration
     */
    public static function instance(): self
    {
        if (!(self::$_instance instanceof Administration)) {
            self::$_instance = new Administration;
        }

        return self::$_instance;
    }

    /**
     * Returns the current Page path, excluding the domain and Symphony path.
     *
     * @return string
     *  The path of the current page, ie. '/blueprints/sections/'
     */
    public function getCurrentPageURL(): string
    {
        return $this->_currentPage;
    }

    /**
     * Overrides the Symphony isLoggedIn function to allow Authors
     * to become logged into the backend when `$_REQUEST['auth-token']`
     * is present. This logs an Author in using the loginFromToken function.
     *
     * @uses loginFromToken()
     * @uses isLoggedIn()
     * @return boolean
     */
    public static function isLoggedIn(): bool
    {
        if (isset($_REQUEST['auth-token']) && $_REQUEST['auth-token']) {
            return static::loginFromToken($_REQUEST['auth-token']);
        }

        return parent::isLoggedIn();
    }

    public static function login(
        ?string $username,
        ?string $password,
        bool $isHash = false
    ): bool
    {
        $loggedin = parent::login($username, $password, $isHash);
        if ($loggedin) {
            Lang::set(static::Author()->get('language'));
        }
        return $loggedin;
    }

    /**
     * Given the URL path of a Symphony backend page, this function will
     * attempt to resolve the URL to a Symphony content page in the backend
     * or a page provided by an extension. This function checks to ensure a user
     * is logged in, otherwise it will direct them to the login page
     *
     * @param string $page
     *  The URL path after the root of the Symphony installation, including a starting
     *  slash, such as '/login/'
     * @throws SymphonyException
     * @throws Exception
     * @return HTMLPage
     */
    private function __buildPage(?string $page): HTMLPage
    {
        $is_logged_in = static::isLoggedIn();

        if (empty($page) || is_null($page)) {
            if (!$is_logged_in) {
                $page  = "/login";
            } else {
                // Will redirect an Author to their default area of the Backend
                // Integers are indicative of section's, text is treated as the path
                // to the page after `SYMPHONY_URL`
                $default_area = null;

                if (is_numeric(Symphony::Author()->get('default_area'))) {
                    $default_section = (new SectionManager)
                        ->select()
                        ->section(Symphony::Author()->get('default_area'))
                        ->execute()
                        ->next();

                    if ($default_section) {
                        $section_handle = $default_section->get('handle');
                    }

                    if (!$section_handle) {
                        $all_sections = (new SectionManager)->select()->execute()->rows();

                        if (!empty($all_sections)) {
                            $section_handle = $all_sections[0]->get('handle');
                        } else {
                            $section_handle = null;
                        }
                    }

                    if (!is_null($section_handle)) {
                        $default_area = "/publish/{$section_handle}/";
                    }
                } elseif (!is_null(Symphony::Author()->get('default_area'))) {
                    $default_area = preg_replace('/^' . preg_quote(SYMPHONY_URL, '/') . '/i', '', Symphony::Author()->get('default_area'));
                }

                // Fallback: No default area found
                if (is_null($default_area)) {
                    if (Symphony::Author()->isDeveloper()) {
                        // Redirect to the section index if author is a developer
                        redirect(SYMPHONY_URL . '/blueprints/sections/');
                    } else {
                        // Redirect to the author page if author is not a developer
                        redirect(SYMPHONY_URL . "/system/authors/edit/".Symphony::Author()->get('id')."/");
                    }
                } else {
                    redirect(SYMPHONY_URL . $default_area);
                }
            }
        }

        if (!$this->_callback = $this->getPageCallback($page)) {
            if ($page === '/publish/') {
                $sections = (new SectionManager)->select()->sort('sortorder')->execute()->rows();
                $section = current($sections);
                redirect(SYMPHONY_URL . '/publish/' . $section->get('handle') . '/');
            } else {
                $this->errorPageNotFound();
            }
        }

        require_once($this->_callback['driver_location']);
        $this->Page = new $this->_callback['classname'];

        if (!$is_logged_in && $this->_callback['driver'] !== 'login') {
            if (is_callable(array($this->Page, 'handleFailedAuthorisation'))) {
                $this->Page->handleFailedAuthorisation();
            } else {
                $this->Page = new contentLogin;

                // Include the query string for the login, RE: #2324
                if ($queryString = $this->Page->__buildQueryString(array('symphony-page', 'mode'), FILTER_SANITIZE_STRING)) {
                    $page .= '?' . $queryString;
                }
                $this->Page->build(array('redirect' => $page));
            }
        } else {
            if (!is_array($this->_callback['context'])) {
                $this->_callback['context'] = [];
            }

            if ($this->__canAccessAlerts()) {
                // Can the core be updated?
                $this->checkCoreForUpdates();
                // Do any extensions need updating?
                $this->checkExtensionsForUpdates();
            }

            $this->Page->build($this->_callback['context']);
        }

        return $this->Page;
    }

    /**
     * Scan the install directory to look for new migrations that can be applied
     * to update this version of Symphony. If one if found, a new Alert is added
     * to the page.
     *
     * @since Symphony 2.5.2
     * @return boolean
     *  Returns true if there is an update available, false otherwise.
     */
    public function checkCoreForUpdates(): bool
    {
        // Is there even an install directory to check?
        if ($this->isInstallerAvailable() === false) {
            return false;
        }

        try {
            // The updater contains a version higher than the current Symphony version.
            if ($this->isUpgradeAvailable()) {
                $message = __('An update has been found in your installation to upgrade Symphony to %s.', [$this->getMigrationVersion()]) . ' <a href="' . URL . '/install/">' . __('View update.') . '</a>';

                // The updater contains a version lower than the current Symphony version.
                // The updater is the same version as the current Symphony install.
            } else {
                $message = __('Your Symphony installation is up to date, but the installer was still detected. For security reasons, it should be removed.') . ' <a href="' . URL . '/install/?action=remove">' . __('Remove installer?') . '</a>';
            }

            // Can't detect update Symphony version
        } catch (Exception $e) {
            $message = __('An update script has been found in your installation.') . ' <a href="' . URL . '/install/">' . __('View update.') . '</a>';
        }

        $this->Page->pageAlert($message, Alert::NOTICE);

        return true;
    }

    /**
     * Checks all installed extensions to see any have an outstanding update. If any do
     * an Alert will be added to the current page directing the Author to the Extension page
     *
     * @since Symphony 2.5.2
     */
    public function checkExtensionsForUpdates(): void
    {
        $extensions = Symphony::ExtensionManager()->listInstalledHandles();

        if (is_array($extensions) && !empty($extensions)) {
            foreach ($extensions as $name) {
                $about = Symphony::ExtensionManager()->about($name);

                if (array_key_exists('status', $about) && in_array(Extension::EXTENSION_REQUIRES_UPDATE, $about['status'])) {
                    $this->Page->pageAlert(
                        __('An extension requires updating.') . ' <a href="' . SYMPHONY_URL . '/system/extensions/">' . __('View extensions') . '</a>'
                    );
                    break;
                }
            }
        }
    }

    /**
     * This function determines whether an administrative alert can be
     * displayed on the current page. It ensures that the page exists,
     * and the user is logged in and a developer
     *
     * @since Symphony 2.2
     * @return boolean
     */
    private function __canAccessAlerts(): bool
    {
        if ($this->Page instanceof AdministrationPage && static::isLoggedIn() && static::Author()->isDeveloper()) {
            return true;
        }

        return false;
    }

    /**
     * This function resolves the string of the page to the relevant
     * backend page class. The path to the backend page is split on
     * the slashes and the resulting pieces used to determine if the page
     * is provided by an extension, is a section (index or entry creation)
     * or finally a standard Symphony content page. If no page driver can
     * be found, this function will return false.
     *
     * @uses AdminPagePostCallback
     * @param string $page
     *  The full path (including the domain) of the Symphony backend page
     * @return array|boolean
     *  If successful, this function will return an associative array that at the
     *  very least will return the page's classname, pageroot, driver, driver_location
     *  and context, otherwise this will return false.
     */
    public function getPageCallback($page = null): array|bool
    {
        if (!$page && $this->_callback) {
            return $this->_callback;
        } elseif (!$page && !$this->_callback) {
            $this->throwCustomError(__('Cannot request a page callback without first specifying the page.'));
        }

        $this->_currentPage = SYMPHONY_URL . preg_replace('/\/{2,}/', '/', $page);
        $bits = preg_split('/\//', trim($page, '/'), 3, PREG_SPLIT_NO_EMPTY);
        $callback = [
            'driver' => null,
            'driver_location' => null,
            'context' => [],
            'classname' => null,
            'pageroot' => null
        ];

        // Login page, /symphony/login/
        if ($bits[0] == 'login') {
            $callback['driver'] = 'login';
            $callback['driver_location'] = CONTENT . '/content.login.php';
            $callback['classname'] = 'contentLogin';
            $callback['pageroot'] = '/login/';

        // Extension page, /symphony/extension/{extension_name}/
        } elseif ($bits[0] == 'extension' && isset($bits[1])) {
            $extension_name = $bits[1];
            $bits = preg_split('/\//', trim($bits[2], '/'), 2, PREG_SPLIT_NO_EMPTY);

            // check if extension is enabled, if it's not, pretend the extension doesn't
            // even exist. #2367
            if (!ExtensionManager::isInstalled($extension_name)) {
                return false;
            }

            $callback['driver'] = 'index';
            $callback['classname'] = 'contentExtension' . ucfirst($extension_name) . 'Index';
            $callback['pageroot'] = '/extension/' . $extension_name. '/';
            $callback['extension'] = $extension_name;

            if (isset($bits[0])) {
                $callback['driver'] = $bits[0];
                $callback['classname'] = 'contentExtension' . ucfirst($extension_name) . ucfirst($bits[0]);
                $callback['pageroot'] .= $bits[0] . '/';
            }

            if (isset($bits[1])) {
                $callback['context'] = preg_split('/\//', $bits[1], -1, PREG_SPLIT_NO_EMPTY);
            }

            $callback['driver_location'] = EXTENSIONS . '/' . $extension_name . '/content/content.' . $callback['driver'] . '.php';
            #var_dump($callback); die;
            // Extensions won't be part of the autoloader chain, so first try to require them if they are available.
            if (!is_file($callback['driver_location'])) {
                return false;
            } else {
                require_once $callback['driver_location'];
            }

        // Publish page, /symphony/publish/{section_handle}/
        } elseif ($bits[0] == 'publish') {
            if (!isset($bits[1])) {
                return false;
            }

            $callback['driver'] = 'publish';
            $callback['driver_location'] = CONTENT . '/content.publish.php';
            $callback['pageroot'] = '/' . $bits[0] . '/' . $bits[1] . '/';
            $callback['classname'] = 'contentPublish';

        // Everything else
        } else {
            $callback['driver'] = ucfirst($bits[0]);
            $callback['pageroot'] = '/' . $bits[0] . '/';

            if (isset($bits[1])) {
                $callback['driver'] = $callback['driver'] . ucfirst($bits[1]);
                $callback['pageroot'] .= $bits[1] . '/';
            }

            $callback['classname'] = 'content' . $callback['driver'];
            $callback['driver'] = strtolower($callback['driver']);
            $callback['driver_location'] = CONTENT . '/content.' . $callback['driver'] . '.php';
        }

        /**
         * Immediately after determining which class will resolve the current page, this
         * delegate allows extension to modify the routing or provide additional information.
         *
         * @since Symphony 2.3.1
         * @delegate AdminPagePostCallback
         * @param string $context
         *  '/backend/'
         * @param string $page
         *  The current URL string, after the SYMPHONY_URL constant (which is `/symphony/`
         *  at the moment.
         * @param array $parts
         *  An array representation of `$page`
         * @param array $callback
         *  An associative array that contains `driver`, `pageroot`, `classname` and
         *  `context` keys. The `driver_location` is the path to the class to render this
         *  page, `driver` should be the view to render, the `classname` the name of the
         *  class, `pageroot` the rootpage, before any extra URL params and `context` can
         *  provide additional information about the page
         */
        Symphony::ExtensionManager()->notifyMembers(
            'AdminPagePostCallback',
            '/backend/',
            [
                'page' => $this->_currentPage,
                'parts' => $bits,
                'callback' => &$callback
            ]
        );

        // Parse the context
        if (isset($callback['classname'])) {
            $classname = $callback['classname'];
            // Check if the class exists
            if (!class_exists($classname) && is_file($callback['driver_location'])) {
                require_once $callback['driver_location'];
            }
            if (!class_exists($classname)) {
                $this->errorPageNotFound();
            }
            // Create the page
            $page = new $classname;

            // Named context
            if (method_exists($page, 'parseContext')) {
                $page->parseContext($callback['context'], $bits);

            // Default context
            } elseif (isset($bits[2])) {
                $callback['context'] = preg_split('/\//', $bits[2], -1, PREG_SPLIT_NO_EMPTY);
            }
        }

        if (!isset($callback['driver_location']) || !is_file($callback['driver_location'])) {
            return false;
        }

        return $callback;
    }

    /**
     * Called by `symphony_launcher()`, this function is responsible for rendering the current
     * page on the Frontend. Two delegates are fired, AdminPagePreGenerate and
     * AdminPagePostGenerate. This function runs the Profiler for the page build
     * process.
     *
     * @uses AdminPagePreBuild
     * @uses AdminPagePreGenerate
     * @uses AdminPagePostGenerate
     * @see core.Symphony#__buildPage()
     * @see boot.getCurrentPage()
     * @param string $page
     *  The result of getCurrentPage, which returns the $_GET['symphony-page']
     *  variable.
     * @throws Exception
     * @throws SymphonyException
     * @return string
     *  The content of the page to echo to the client
     */
    public function display(?string $page): string
    {
        Symphony::Profiler()->sample('Page build process started');

        /**
         * Immediately before building the admin page. Provided with the page parameter
         * @delegate AdminPagePreBuild
         * @since Symphony 2.6.0
         * @param string $context
         *  '/backend/'
         * @param string $page
         *  The result of getCurrentPage, which returns the $_GET['symphony-page']
         *  variable.
         */
        Symphony::ExtensionManager()->notifyMembers(
            'AdminPagePreBuild',
            '/backend/',
            ['page' => $page]
        );

        $this->__buildPage($page);

        // Add XSRF token to form's in the backend
        if (self::isXSRFEnabled() && isset($this->Page->Form)) {
            $this->Page->Form->prependChild(XSRF::formToken());
        }

        /**
         * Immediately before generating the admin page. Provided with the page object
         * @delegate AdminPagePreGenerate
         * @param string $context
         *  '/backend/'
         * @param HTMLPage $oPage
         *  An instance of the current page to be rendered, this will usually be a class that
         *  extends HTMLPage. The Symphony backend uses a convention of contentPageName
         *  as the class that extends the HTMLPage
         */
        Symphony::ExtensionManager()->notifyMembers(
            'AdminPagePreGenerate',
            '/backend/',
            ['oPage' => &$this->Page]
        );

        $output = $this->Page->generate();

        /**
         * Immediately after generating the admin page. Provided with string containing page source
         * @delegate AdminPagePostGenerate
         * @param string $context
         *  '/backend/'
         * @param string $output
         *  The resulting backend page HTML as a string, passed by reference
         */
        Symphony::ExtensionManager()->notifyMembers(
            'AdminPagePostGenerate',
            '/backend/',
            ['output' => &$output]
        );

        Symphony::Profiler()->sample('Page built');

        return $output;
    }

    /**
     * If a page is not found in the Symphony backend, this function should
     * be called which will raise a customError to display the default Symphony
     * page not found template
     */
    public function errorPageNotFound(): void
    {
        $this->throwCustomError(
            __('The page you requested does not exist.'),
            __('Page Not Found'),
            Page::HTTP_STATUS_NOT_FOUND
        );
    }
}
