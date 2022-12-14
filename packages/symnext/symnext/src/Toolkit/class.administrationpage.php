<?php

/**
 * @package toolkit
 */
/**
 * The AdministrationPage class represents a Symphony backend page.
 * It extends the HTMLPage class and unlike the Frontend, is generated
 * using a number XMLElement objects. Instances of this class override
 * the view, switchboard and action functions to construct the page. These
 * functions act as pseudo MVC, with the switchboard being controller,
 * and the view/action being the view.
 */

class AdministrationPage extends HTMLPage
{
    /**
     * An array of `Alert` objects used to display page level
     * messages to Symphony backend users one by one. Prior to Symphony 2.3
     * this variable only held a single `Alert` object.
     * @var array
     */
    public $Alert = [];

    /**
     * Specifies the type of page that being created. This is used to
     * trigger various styling hooks. If your page is mainly a form,
     * pass 'form' as the parameter, if it's displaying a single entry,
     * pass 'single'. If any other parameter is passed, the 'index'
     * styling will be applied.
     *
     * @param string $type
     *  Accepts 'form' or 'single', any other `$type` will trigger 'index'
     *  styling.
     */
    public function setPageType($type = 'form')
    {
        $this->setBodyClass($type == 'form' || $type == 'page-single' ? 'page-single' : 'page-index');
    }

    /**
     * Setter function to set the class attribute on the `<body>` element.
     * This function will respect any previous classes that have been added
     * to this `<body>`
     *
     * @param string $class
     *  The string of the classname, multiple classes can be specified by
     *  uses a space separator
     */
    public function setBodyClass($class)
    {
        // Prevents duplicate "page-index" classes
        if (!isset($this->_context['page']) || !in_array('page-index', [$this->_context['page'], $class])) {
            $this->_body_class .= $class;
        }

        $this->Body->setAttribute('class', $this->_body_class);
    }

    /**
     * Accessor for `$this->_errors` which contains the list of errors that occurred
     * during the life cycle of this page.
     *
     * @since Symphony 3.0.0
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * Given a `$message` and an optional `$type`, this function will
     * add an Alert instance into this page's `$this->Alert` property.
     * Since Symphony 2.3, there may be more than one `Alert` per page.
     * Unless the Alert is an Error, it is required the `$message` be
     * passed to this function.
     *
     * @param string $message
     *  The message to display to users
     * @param string $type
     *  An Alert constant, being `Alert::NOTICE`, `Alert::ERROR` or
     *  `Alert::SUCCESS`. The differing types will show the error
     *  in a different style in the backend. If omitted, this defaults
     *  to `Alert::NOTICE`.
     * @throws Exception
     */
    public function pageAlert($message = null, $type = Alert::NOTICE)
    {
        if (is_null($message) && $type == Alert::ERROR) {
            $message = __('There was a problem rendering this page. Please check the activity log for more details.');
        } else {
            $message = __($message);
        }

        if (strlen(trim($message)) == 0) {
            throw new Exception(__('A message must be supplied unless the alert is of type Alert::ERROR'));
        }

        $this->Alert[] = new Alert($message, $type);
    }

    /**
     * Allows a Drawer element to added to the backend page in one of three
     * positions, `horizontal`, `vertical-left` or `vertical-right`. The button
     * to trigger the visibility of the drawer will be added after existing
     * actions by default.
     *
     * @since Symphony 2.3
     * @see core.Widget#Drawer
     * @param XMLElement $drawer
     *  An XMLElement representing the drawer, use `Widget::Drawer` to construct
     * @param string $position
     *  Where `$position` can be `horizontal`, `vertical-left` or
     *  `vertical-right`. Defaults to `horizontal`.
     * @param string $button
     *  If not passed, a button to open/close the drawer will not be added
     *  to the interface. Accepts 'prepend' or 'append' values, which will
     *  add the button before or after existing buttons. Defaults to `prepend`.
     *  If any other value is passed, no button will be added.
     * @throws InvalidArgumentException
     */
    public function insertDrawer(XMLElement $drawer, $position = 'horizontal', $button = 'append')
    {
        $drawer->addClass($position);
        $drawer->setAttribute('data-position', $position);
        $drawer->setAttribute('role', 'complementary');
        $this->Drawer[$position][] = $drawer;

        if (in_array($button, ['prepend', 'append'])) {
            $this->insertAction(
                Widget::Anchor(
                    $drawer->getAttribute('data-label'),
                    '#' . $drawer->getAttribute('id'),
                    null,
                    'button drawer ' . $position
                ),
                ($button === 'append' ? true : false)
            );
        }
    }

    /**
     * This function initialises a lot of the basic elements that make up a Symphony
     * backend page such as the default stylesheets and scripts, the navigation and
     * the footer. Any alerts are also appended by this function. `view()` is called to
     * build the actual content of the page. The `InitialiseAdminPageHead` delegate
     * allows extensions to add elements to the `<head>`. The `CanAccessPage` delegate
     * allows extensions to restrict access to pages.
     *
     * @see view()
     * @uses InitialiseAdminPageHead
     * @uses CanAccessPage
     * @param array $context
     *  An associative array describing this pages context. This
     *  can include the section handle, the current entry_id, the page
     *  name and any flags such as 'saved' or 'created'. This list is not exhaustive
     *  and extensions can add their own keys to the array.
     * @throws InvalidArgumentException
     * @throws SymphonyException
     */
    public function build(array $context = [])
    {
        parent::build($context);

        if (!$this->canAccessPage()) {
            Administration::instance()->throwCustomError(
                __('You are not authorised to access this page.'),
                __('Access Denied'),
                Page::HTTP_STATUS_UNAUTHORIZED
            );
        }

        $this->Html->setDTD('<!DOCTYPE html>');
        $this->Html->setAttribute('lang', Lang::get());
        $this->addElementToHead(new XMLElement('meta', null, ['charset' => 'UTF-8']), 0);
        $this->addElementToHead(new XMLElement('meta', null, ['http-equiv' => 'X-UA-Compatible', 'content' => 'IE=edge,chrome=1']), 1);
        $this->addElementToHead(new XMLElement('meta', null, ['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1']), 2);

        // Add styles
        $this->addStylesheetToHead(ASSETS_URL . '/css/symphony.min.css', 'screen', 2, false);

        // Calculate timezone offset from UTC
        $timezone = new DateTimeZone(DateTimeObj::getSetting('timezone'));
        $datetime = new DateTime('now', $timezone);
        $timezoneOffset = intval($timezone->getOffset($datetime)) / 60;

        // Add scripts
        $environment = [
            'root'     => URL,
            'symphony' => SYMPHONY_URL,
            'path'     => '/' . Symphony::Configuration()->get('admin-path', 'symphony'),
            'route'    => get_current_page(),
            'version'  => Symphony::Configuration()->get('version', 'symphony'),
            'lang'     => Lang::get(),
            'user'     => [
                'fullname' => Symphony::Author()->getFullName(),
                'name'     => Symphony::Author()->get('first_name'),
                'type'     => Symphony::Author()->get('user_type'),
                'id'       => Symphony::Author()->get('id')
            ],
            'datetime' => [
                'formats'         => DateTimeObj::getDateFormatMappings(),
                'timezone-offset' => $timezoneOffset
            ],
            'env' => array_merge(
                ['page-namespace' => Symphony::getPageNamespace()],
                $this->_context
            )
        ];

        $envJsonOptions = 0;
        if (defined('JSON_UNESCAPED_SLASHES') && defined('JSON_UNESCAPED_UNICODE')) {
            $envJsonOptions = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        }

        $this->addElementToHead(
            new XMLElement('script', json_encode($environment, $envJsonOptions), [
                'type' => 'application/json',
                'id' => 'environment'
            ]),
            4
        );

        $this->addScriptToHead(ASSETS_URL . '/js/symphony.min.js', 6, false);

        // Initialise page containers
        $this->Wrapper = new XMLElement('div', null, ['id' => 'wrapper']);
        $this->Header = new XMLElement('header', null, ['id' => 'header']);
        $this->Context = new XMLElement('div', null, ['id' => 'context']);
        $this->Breadcrumbs = new XMLElement('div', null, ['id' => 'breadcrumbs']);
        $this->Contents = new XMLElement('div', null, ['id' => 'contents', 'role' => 'main']);
        $this->Form = Widget::Form(Administration::instance()->getCurrentPageURL(), 'post', null, null, ['role' => 'form']);

        /**
         * Allows developers to insert items into the page HEAD. Use
         * `Administration::instance()->Page` for access to the page object.
         *
         * @since In Symphony 2.3.2 this delegate was renamed from
         *  `InitaliseAdminPageHead` to the correct spelling of
         *  `InitialiseAdminPageHead`. The old delegate is supported
         *  until Symphony 3.0
         *
         * @delegate InitialiseAdminPageHead
         * @param string $context
         *  '/backend/'
         */
        Symphony::ExtensionManager()->notifyMembers('InitialiseAdminPageHead', '/backend/');
        Symphony::ExtensionManager()->notifyMembers('InitaliseAdminPageHead', '/backend/');

        $this->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');
        $this->addHeaderToPage('Cache-Control', 'no-cache, must-revalidate, max-age=0');
        $this->addHeaderToPage('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');

        // If not set by another extension, lock down the backend
        if (!array_key_exists('x-frame-options', $this->headers())) {
            $this->addHeaderToPage('X-Frame-Options', 'SAMEORIGIN');
        }

        if (!array_key_exists('x-content-type-options', $this->headers())) {
            $this->addHeaderToPage('X-Content-Type-Options', 'nosniff');
        }

        if (!array_key_exists('x-xss-protection', $this->headers())) {
            $this->addHeaderToPage('X-XSS-Protection', '1; mode=block');
        }

        if (!array_key_exists('referrer-policy', $this->headers())) {
            $this->addHeaderToPage('Referrer-Policy', 'same-origin');
        }

        if (isset($_REQUEST['action'])) {
            $this->action();
            Symphony::Profiler()->sample('Page action run', PROFILE_LAP);
        }

        $h1 = new XMLElement('h1');
        $h1->appendChild(Widget::Anchor(Symphony::Configuration()->get('sitename', 'general'), rtrim(URL, '/') . '/'));
        $this->Header->appendChild($h1);

        $this->appendUserLinks();
        $this->appendNavigation();

        // Add Breadcrumbs
        $this->Context->prependChild($this->Breadcrumbs);
        $this->Contents->appendChild($this->Form);

        // Validate date time config
        $dateFormat = defined('__SYM_DATE_FORMAT__') ? __SYM_DATE_FORMAT__ : null;
        if (empty($dateFormat)) {
            $this->pageAlert(
                __('Your <code>%s</code> file does not define a date format', [basename(CONFIG)]),
                Alert::NOTICE
            );
        }
        $timeFormat = defined('__SYM_TIME_FORMAT__') ? __SYM_TIME_FORMAT__ : null;
        if (empty($timeFormat)) {
            $this->pageAlert(
                __('Your <code>%s</code> file does not define a time format.', [basename(CONFIG)]),
                Alert::NOTICE
            );
        }

        $this->view();

        $this->appendAlert();

        Symphony::Profiler()->sample('Page content created', PROFILE_LAP);
    }

    /**
     * Checks the current Symphony Author can access the current page.
     * This check uses the `ASSETS . /xml/navigation.xml` file to determine
     * if the current page (or the current page namespace) can be viewed
     * by the currently logged in Author.
     *
     * @since Symphony 2.7.0
     * It fires a delegate, CanAccessPage, to allow extensions to restrict access
     * to the current page
     *
     * @uses CanAccessPage
     *
     * @link http://github.com/symphonycms/symphonycms/blob/master/symphony/assets/xml/navigation.xml
     * @return boolean
     *  true if the Author can access the current page, false otherwise
     */
    public function canAccessPage()
    {
        $nav = $this->getNavigationArray();
        $page = '/' . trim(get_current_page(), '/') . '/';

        $page_limit = 'author';

        foreach ($nav as $item) {
            if (
                // If page directly matches one of the children
                General::in_array_multi($page, $item['children'])
                // If the page namespace matches one of the children (this will usually drop query
                // string parameters such as /edit/1/)
                || General::in_array_multi(Symphony::getPageNamespace() . '/', $item['children'])
            ) {
                if (is_array($item['children'])) {
                    foreach ($item['children'] as $c) {
                        if ($c['link'] === $page && isset($c['limit'])) {
                            $page_limit = $c['limit'];
                            // TODO: break out of the loop here in Symphony 3.0.0
                        }
                    }
                }

                if (isset($item['limit']) && $page_limit !== 'primary') {
                    if ($page_limit === 'author' && $item['limit'] === 'developer') {
                        $page_limit = 'developer';
                    }
                }
            } elseif (isset($item['link']) && $page === $item['link'] && isset($item['limit'])) {
                $page_limit = $item['limit'];
            }
        }

        $hasAccess = $this->doesAuthorHaveAccess($page_limit);

        if ($hasAccess) {
            $page_context = $this->getContext();
            $section_handle = $page_context['section_handle'] ?? null;
            /**
             * Immediately after the core access rules allowed access to this page
             * (i.e. not called if the core rules denied it).
             * Extension developers must only further restrict access to it.
             * Extension developers must also take care of checking the current value
             * of the allowed parameter in order to prevent conflicts with other extensions.
             * `$context['allowed'] = $context['allowed'] && customLogic();`
             *
             * @delegate CanAccessPage
             * @since Symphony 2.7.0
             * @see doesAuthorHaveAccess()
             * @param string $context
             *  '/backend/'
             * @param bool $allowed
             *  A flag to further restrict access to the page, passed by reference
             * @param string $page_limit
             *  The computed page limit for the current page
             * @param string $page_url
             *  The computed page url for the current page
             * @param int $section.id
             *  The id of the section for this url
             * @param string $section.handle
             *  The handle of the section for this url
             */
            Symphony::ExtensionManager()->notifyMembers('CanAccessPage', '/backend/', [
                'allowed' => &$hasAccess,
                'page_limit' => $page_limit,
                'page_url' => $page,
                'section' => [
                    'id' => $section_handle ?  SectionManager::fetchIDFromHandle($section_handle) : 0,
                    'handle' => $section_handle
                ],
            ]);
        }

        return $hasAccess;
    }

    /**
     * Given the limit of the current navigation item or page, this function
     * returns if the current Author can access that item or not.
     *
     * @since Symphony 2.5.1
     * @param string $item_limit
     * @return boolean
     */
    public function doesAuthorHaveAccess($item_limit = null)
    {
        $can_access = false;

        if (!isset($item_limit) || $item_limit === 'author') {
            $can_access = true;
        } elseif ($item_limit === 'developer' && Symphony::Author()->isDeveloper()) {
            $can_access = true;
        } elseif ($item_limit === 'manager' && (Symphony::Author()->isManager() || Symphony::Author()->isDeveloper())) {
            $can_access = true;
        } elseif ($item_limit === 'primary' && Symphony::Author()->isPrimaryAccount()) {
            $can_access = true;
        }

        return $can_access;
    }

    /**
     * Appends the `$this->Header`, `$this->Context` and `$this->Contents`
     * to `$this->Wrapper` before adding the ID and class attributes for
     * the `<body>` element. This function will also place any Drawer elements
     * in their relevant positions in the page. After this has completed the
     * parent `generate()` is called which will convert the `XMLElement`'s
     * into strings ready for output.
     *
     * @see core.HTMLPage#generate()
     * @param null $page
     * @return string
     */
    public function generate($page = null): string
    {
        $this->Wrapper->appendChild($this->Header);

        // Add horizontal drawers (inside #context)
        if (isset($this->Drawer['horizontal'])) {
            $this->Context->appendChildArray($this->Drawer['horizontal']);
        }

        $this->Wrapper->appendChild($this->Context);

        // Add vertical-left drawers (between #context and #contents)
        if (isset($this->Drawer['vertical-left'])) {
            $this->Contents->appendChildArray($this->Drawer['vertical-left']);
        }

        // Add vertical-right drawers (after #contents)
        if (isset($this->Drawer['vertical-right'])) {
            $this->Contents->appendChildArray($this->Drawer['vertical-right']);
        }

        $this->Wrapper->appendChild($this->Contents);

        $this->Body->appendChild($this->Wrapper);

        $this->appendBodyId();
        $this->appendBodyAttributes($this->_context);

        /**
         * This is just prior to the page headers being rendered, and is suitable for changing them
         * @delegate PreRenderHeaders
         * @since Symphony 2.7.0
         * @param string $context
         * '/backend/'
         */
        Symphony::ExtensionManager()->notifyMembers('PreRenderHeaders', '/backend/');

        return parent::generate($page);
    }

    /**
     * Uses this pages PHP classname as the `<body>` ID attribute.
     * This function removes 'content' from the start of the classname
     * and converts all uppercase letters to lowercase and prefixes them
     * with a hyphen.
     */
    private function appendBodyId()
    {
        // trim "content" from beginning of class name
        $body_id = preg_replace("/^content/", '', get_class($this));

        // lowercase any uppercase letters and prefix with a hyphen
        $body_id = trim(
            preg_replace_callback(
                "/([A-Z])/",
                function($id) {
                    return "-" . strtolower($id[0]);
                },
                $body_id
            ),
            '-'
        );

        if (!empty($body_id)) {
            $this->Body->setAttribute('id', $body_id);
        }
    }

    /**
     * Given the context of the current page, which is an associative
     * array, this function will append the values to the page's body as
     * data attributes. If an context value is numeric it will be given
     * the key 'id' otherwise all attributes will be prefixed by the context key.
     *
     * If the context value is an array, it will be JSON encoded.
     *
     * @param array $context
     */
    private function appendBodyAttributes(array $context = [])
    {
        foreach ($context as $key => $value) {
            if (is_numeric($value)) {
                $key = 'id';

                // Add prefixes to all context values by making the
                // data-attribute be a handle of {key}. #1397 ^BA
            } elseif (!is_numeric($key) && isset($value)) {
                $key = str_replace('_', '-', General::createHandle($key));
            }

            // JSON encode any array values
            if (is_array($value)) {
                $value = json_encode($value);
            }

            $this->Body->setAttribute('data-' . $key, $value);
        }
    }

    /**
     * Called to build the content for the page. This function immediately calls
     * `__switchboard()` which acts a bit of a controller to show content based on
     * off a type, such as 'view' or 'action'. `AdministrationPages` can override this
     * function to just display content if they do not need the switchboard functionality
     *
     * @see __switchboard()
     */
    public function view()
    {
        $this->__switchboard();
    }

    /**
     * This function is called when `$_REQUEST` contains a key of 'action'.
     * Any logic that needs to occur immediately for the action to complete
     * should be contained within this function. By default this calls the
     * `__switchboard` with the type set to 'action'.
     *
     * @see __switchboard()
     */
    public function action()
    {
        $this->__switchboard('action');
    }

    /**
     * The `__switchboard` function acts as a controller to display content
     * based off the $type. By default, the `$type` is 'view' but it can be set
     * also set to 'action'. The `$type` is prepended by __ and the context is
     * append to the $type to create the name of the function that will provide
     * that logic. For example, if the $type was action and the context of the
     * current page was new, the resulting function to be called would be named
     * `__actionNew()`. If an action function is not provided by the Page, this function
     * returns nothing, however if a view function is not provided, a 404 page
     * will be returned.
     *
     * @param string $type
     *  Either 'view' or 'action', by default this will be 'view'
     * @throws SymphonyException
     */
    public function __switchboard(string $type = 'view')
    {
        if (!isset($this->_context['action']) || trim($this->_context['action']) === '') {
            $action = 'index';
        } else {
            $action = $this->_context['action'];
        }

        $function = ($type == 'action' ? '__action' : '__view') . ucfirst($action);

        if (!method_exists($this, $function)) {
            // If there is no action function, just return without doing anything
            if ($type == 'action') {
                return;
            }

            Administration::instance()->errorPageNotFound();
        }

        $this->$function(null);
    }

    /**
     * If `$this->Alert` is set, it will be added to this page. The
     * `AppendPageAlert` delegate is fired to allow extensions to provide their
     * their own Alert messages for this page. Since Symphony 2.3, there may be
     * more than one `Alert` per page. Alerts are displayed in the order of
     * severity, with Errors first, then Success alerts followed by Notices.
     *
     * @uses AppendPageAlert
     */
    public function appendAlert()
    {
        /**
         * Allows for appending of alerts. Administration::instance()->Page->Alert is way to tell what
         * is currently in the system
         *
         * @delegate AppendPageAlert
         * @param string $context
         *  '/backend/'
         */
        Symphony::ExtensionManager()->notifyMembers('AppendPageAlert', '/backend/');


        if (!is_array($this->Alert) || empty($this->Alert)) {
            return;
        }

        usort($this->Alert, [$this, 'sortAlerts']);

        // Using prependChild ruins our order (it's backwards, but with most
        // recent notices coming after oldest notices), so reversing the array
        // fixes this. We need to prepend so that without Javascript the notices
        // are at the top of the markup. See #1312
        $this->Alert = array_reverse($this->Alert);

        foreach ($this->Alert as $alert) {
            $this->Header->prependChild($alert->asXML());
        }
    }

    // Errors first, success next, then notices.
    public function sortAlerts($a, $b)
    {
        if ($a->{'type'} === $b->{'type'}) {
            return 0;
        }

        if (
            ($a->{'type'} === Alert::ERROR && $a->{'type'} !== $b->{'type'})
            || ($a->{'type'} === Alert::SUCCESS && $b->{'type'} === Alert::NOTICE)
        ) {
            return -1;
        }

        return 1;
    }

    /**
     * This function will append the Navigation to the AdministrationPage.
     * It fires a delegate, NavigationPreRender, to allow extensions to manipulate
     * the navigation. Extensions should not use this to add their own navigation,
     * they should provide the navigation through their fetchNavigation function.
     * Note with the Section navigation groups, if there is only one section in a group
     * and that section is set to visible, the group will not appear in the navigation.
     *
     * @uses NavigationPreRender
     * @see getNavigationArray()
     * @see toolkit.Extension#fetchNavigation()
     */
    public function appendNavigation()
    {
        $nav = $this->getNavigationArray();

        /**
         * Immediately before displaying the admin navigation. Provided with the
         * navigation array. Manipulating it will alter the navigation for all pages.
         *
         * @delegate NavigationPreRender
         * @param string $context
         *  '/backend/'
         * @param array $nav
         *  An associative array of the current navigation, passed by reference
         */
        Symphony::ExtensionManager()->notifyMembers('NavigationPreRender', '/backend/', [
            'navigation' => &$nav,
        ]);

        $navElement = new XMLElement('nav', null, ['id' => 'nav', 'role' => 'navigation']);
        $contentNav = new XMLElement('ul', null, ['class' => 'content', 'role' => 'menubar']);
        $structureNav = new XMLElement('ul', null, ['class' => 'structure', 'role' => 'menubar']);

        foreach ($nav as $n) {
            if (isset($n['visible']) && $n['visible'] === 'no') {
                continue;
            }

            $item_limit = isset($n['limit']) ? $n['limit'] : null;

            if ($this->doesAuthorHaveAccess($item_limit)) {
                $xGroup = new XMLElement('li', General::sanitize($n['name']), ['role' => 'presentation']);

                if (isset($n['class']) && trim($n['name']) !== '') {
                    $xGroup->setAttribute('class', $n['class']);
                }

                $hasChildren = false;
                $xChildren = new XMLElement('ul', null, ['role' => 'menu']);

                if (is_array($n['children']) && !empty($n['children'])) {
                    foreach ($n['children'] as $c) {
                        // adapt for Yes and yes
                        if (strtolower($c['visible']) !== 'yes') {
                            continue;
                        }

                        $child_item_limit = isset($c['limit']) ? $c['limit'] : null;

                        if ($this->doesAuthorHaveAccess($child_item_limit)) {
                            $xChild = new XMLElement('li');
                            $xChild->setAttribute('role', 'menuitem');
                            $linkChild = Widget::Anchor(General::sanitize($c['name']), SYMPHONY_URL . $c['link']);
                            if (isset($c['target'])) {
                                $linkChild->setAttribute('target', $c['target']);
                            }
                            $xChild->appendChild($linkChild);
                            $xChildren->appendChild($xChild);
                            $hasChildren = true;
                        }
                    }

                    if ($hasChildren) {
                        $xGroup->setAttribute('aria-haspopup', 'true');
                        $xGroup->appendChild($xChildren);

                        if ($n['type'] === 'content') {
                            $contentNav->appendChild($xGroup);
                        } elseif ($n['type'] === 'structure') {
                            $structureNav->prependChild($xGroup);
                        }
                    }
                }
            }
        }

        $navElement->appendChild($contentNav);
        $navElement->appendChild($structureNav);
        $this->Header->appendChild($navElement);
        Symphony::Profiler()->sample('Navigation Built', PROFILE_LAP);
    }

    /**
     * Returns the `$_navigation` variable of this Page. If it is empty,
     * it will be built by `__buildNavigation`
     *
     * When it calls `__buildNavigation`, it fires a delegate, NavigationPostBuild,
     * to allow extensions to manipulate the navigation.
     *
     * @uses NavigationPostBuild
     * @see __buildNavigation()
     * @return array
     */
    public function getNavigationArray()
    {
        if (empty($this->_navigation)) {
            $this->__buildNavigation();
        }

        return $this->_navigation;
    }

    /**
     * This method fills the `$nav` array with value
     * from the `ASSETS/xml/navigation.xml` file
     *
     * @link http://github.com/symphonycms/symphonycms/blob/master/symphony/assets/xml/navigation.xml
     *
     * @since Symphony 2.3.2
     *
     * @param array $nav
     *  The navigation array that will receive nav nodes
     */
    private function buildXmlNavigation(&$nav)
    {
        $xml = simplexml_load_file(ASSETS . '/xml/navigation.xml');

        // Loop over the default Symphony navigation file, converting
        // it into an associative array representation
        foreach ($xml->xpath('/navigation/group') as $n) {
            $index = (string)$n->attributes()->index;
            $children = $n->xpath('children/item');
            $content = $n->attributes();

            // If the index is already set, increment the index and check again.
            // Rinse and repeat until the index is not set.
            if (isset($nav[$index])) {
                do {
                    $index++;
                } while (isset($nav[$index]));
            }

            $nav[$index] = [
                'name' => __(strval($content->name)),
                'type' => 'structure',
                'index' => $index,
                'children' => []
            ];

            if (strlen(trim((string)$content->limit)) > 0) {
                $nav[$index]['limit'] = (string)$content->limit;
            }

            if (count($children) > 0) {
                foreach ($children as $child) {
                    $item = [
                        'link' => (string)$child->attributes()->link,
                        'name' => __(strval($child->attributes()->name)),
                        'visible' => ((string)$child->attributes()->visible == 'no' ? 'no' : 'yes'),
                    ];

                    $limit = (string)$child->attributes()->limit;

                    if (strlen(trim($limit)) > 0) {
                        $item['limit'] = $limit;
                    }

                    $nav[$index]['children'][] = $item;
                }
            }
        }
    }

    /**
     * This method fills the `$nav` array with value
     * from each Section
     *
     * @since Symphony 2.3.2
     *
     * @param array $nav
     *  The navigation array that will receive nav nodes
     */
    private function buildSectionNavigation(&$nav)
    {
        // Build the section navigation, grouped by their navigation groups
        $sections = (new SectionManager)->select()->sort('sortorder')->execute()->rows();

        foreach ($sections as $s) {
            $group_index = self::__navigationFindGroupIndex($nav, $s->get('navigation_group'));

            if ($group_index === false) {
                $group_index = General::array_find_available_index($nav, 0);

                $nav[$group_index] = [
                    'name' => $s->get('navigation_group'),
                    'type' => 'content',
                    'index' => $group_index,
                    'children' => []
                ];
            }

            $hasAccess = true;
            $url = '/publish/' . $s->get('handle') . '/';
            /**
             * Immediately after the core access rules allowed access to this page
             * (i.e. not called if the core rules denied it).
             * Extension developers must only further restrict access to it.
             * Extension developers must also take care of checking the current value
             * of the allowed parameter in order to prevent conflicts with other extensions.
             * `$context['allowed'] = $context['allowed'] && customLogic();`
             *
             * @delegate CanAccessPage
             * @since Symphony 2.7.0
             * @see doesAuthorHaveAccess()
             * @param string $context
             *  '/backend/'
             * @param bool $allowed
             *  A flag to further restrict access to the page, passed by reference
             * @param string $page_limit
             *  The computed page limit for the current page
             * @param string $page_url
             *  The computed page url for the current page
             * @param int $section.id
             *  The id of the section for this url
             * @param string $section.handle
             *  The handle of the section for this url
             */
            Symphony::ExtensionManager()->notifyMembers('CanAccessPage', '/backend/', [
                'allowed' => &$hasAccess,
                'page_limit' => 'author',
                'page_url' => $url,
                'section' => [
                    'id' => $s->get('id'),
                    'handle' => $s->get('handle')
                ],
            ]);

            if ($hasAccess) {
                $nav[$group_index]['children'][] = [
                    'link' => $url,
                    'name' => $s->get('name'),
                    'type' => 'section',
                    'section' => [
                        'id' => $s->get('id'),
                        'handle' => $s->get('handle')
                    ],
                    'visible' => ($s->get('hidden') == 'no' ? 'yes' : 'no')
                ];
            }
        }
    }

    /**
     * This method fills the `$nav` array with value
     * from each Extension's `fetchNavigation` method
     *
     * @since Symphony 2.3.2
     *
     * @param array $nav
     *  The navigation array that will receive nav nodes
     * @throws Exception
     * @throws SymphonyException
     */
    private function buildExtensionsNavigation(&$nav)
    {
        // Loop over all the installed extensions to add in other navigation items
        $extensions = Symphony::ExtensionManager()->listInstalledHandles();

        foreach ($extensions as $e) {
            $extension = Symphony::ExtensionManager()->getInstance($e);
            $extension_navigation = $extension->fetchNavigation();

            if (is_array($extension_navigation) && !empty($extension_navigation)) {
                foreach ($extension_navigation as $item) {
                    $type = isset($item['children']) ? Extension::NAV_GROUP : Extension::NAV_CHILD;

                    switch ($type) {
                        case Extension::NAV_GROUP:
                            $index = General::array_find_available_index($nav, $item['location']);

                            // Actual group
                            $nav[$index] = self::createParentNavItem($index, $item);

                            // Render its children
                            foreach ($item['children'] as $child) {
                                $nav[$index]['children'][] = self::createChildNavItem($child, $e);
                            }

                            break;

                        case Extension::NAV_CHILD:
                            if (!is_numeric($item['location'])) {
                                // is a navigation group
                                $group_name = $item['location'];
                                $group_index = self::__navigationFindGroupIndex($nav, $item['location']);
                            } else {
                                // is a legacy numeric index
                                $group_index = $item['location'];
                            }

                            $child = self::createChildNavItem($item, $e);

                            if ($group_index === false) {
                                $group_index = General::array_find_available_index($nav, 0);

                                $nav_parent = self::createParentNavItem($group_index, $item);
                                $nav_parent['name'] = $group_name;
                                $nav_parent['children'] = [$child];

                                // add new navigation group
                                $nav[$group_index] = $nav_parent;
                            } else {
                                // add new location by index
                                $nav[$group_index]['children'][] = $child;
                            }

                            break;
                    }
                }
            }
        }
    }

    /**
     * This function builds out a navigation menu item for parents. Parents display
     * in the top level navigation of the backend and may have children (dropdown menus)
     *
     * @since Symphony 2.5.1
     * @param integer $index
     * @param array $item
     * @return array
     */
    private static function createParentNavItem($index, $item)
    {
        $nav_item = [
            'name' => $item['name'],
            'type' => isset($item['type']) ? $item['type'] : 'structure',
            'index' => $index,
            'children' => [],
            'limit' => isset($item['limit']) ? $item['limit'] : null
        ];

        return $nav_item;
    }

    /**
     * This function builds out a navigation menu item for children. Children
     * live under a parent navigation item and are shown on hover.
     *
     * @since Symphony 2.5.1
     * @param array $item
     * @param string $extension_handle
     * @return array
     */
    private static function createChildNavItem($item, $extension_handle)
    {
        if (!isset($item['relative']) || $item['relative'] === true) {
            $link = '/extension/' . $extension_handle . '/' . ltrim($item['link'], '/');
        } else {
            $link = '/' . ltrim($item['link'], '/');
        }

        $nav_item = [
            'link' => $link,
            'name' => $item['name'],
            'visible' => (isset($item['visible']) && $item['visible'] == 'no') ? 'no' : 'yes',
            'limit' => isset($item['limit']) ? $item['limit'] : null,
            'target' => isset($item['target']) ? $item['target'] : null
        ];

        return $nav_item;
    }

    /**
     * This function populates the `$_navigation` array with an associative array
     * of all the navigation groups and their links. Symphony only supports one
     * level of navigation, so children links cannot have children links. The default
     * Symphony navigation is found in the `ASSETS/xml/navigation.xml` folder. This is
     * loaded first, and then the Section navigation is built, followed by the Extension
     * navigation. Additionally, this function will set the active group of the navigation
     * by checking the current page against the array of links.
     *
     * It fires a delegate, NavigationPostBuild, to allow extensions to manipulate
     * the navigation.
     *
     * @uses NavigationPostBuild
     * @link https://github.com/symphonycms/symphonycms/blob/master/symphony/assets/xml/navigation.xml
     * @link https://github.com/symphonycms/symphonycms/blob/master/symphony/lib/toolkit/class.extension.php
     */
    public function __buildNavigation()
    {
        $nav = [];

        $this->buildXmlNavigation($nav);
        $this->buildSectionNavigation($nav);
        $this->buildExtensionsNavigation($nav);

        $pageCallback = Administration::instance()->getPageCallback();

        $pageRoot = $pageCallback['pageroot'] . (isset($pageCallback['context'][0]) ? $pageCallback['context'][0] . '/' : '');
        $found = self::__findActiveNavigationGroup($nav, $pageRoot);

        // Normal searches failed. Use a regular expression using the page root. This is less
        // efficient and should never really get invoked unless something weird is going on
        if (!$found) {
            self::__findActiveNavigationGroup($nav, '/^' . str_replace('/', '\/', $pageCallback['pageroot']) . '/i', true);
        }

        ksort($nav);
        $this->_navigation = $nav;

        /**
         * Immediately after the navigation array as been built. Provided with the
         * navigation array. Manipulating it will alter the navigation for all pages.
         * Developers can also alter the 'limit' property of each page to allow more
         * or less access to them.
         * Preventing a user from accessing the page affects both the navigation and the
         * page access rights: user will get a 403 Forbidden error if not authorized.
         *
         * @delegate NavigationPostBuild
         * @since Symphony 2.7.0
         * @param string $context
         *  '/backend/'
         * @param array $nav
         *  An associative array of the current navigation, passed by reference
         */
        Symphony::ExtensionManager()->notifyMembers('NavigationPostBuild', '/backend/', [
            'navigation' => &$this->_navigation,
        ]);
    }

    /**
     * Given an associative array representing the navigation, and a group,
     * this function will attempt to return the index of the group in the navigation
     * array. If it is found, it will return the index, otherwise it will return false.
     *
     * @param array $nav
     *  An associative array of the navigation where the key is the group
     *  index, and the value is an associative array of 'name', 'index' and
     *  'children'. Name is the name of the this group, index is the same as
     *  the key and children is an associative array of navigation items containing
     *  the keys 'link', 'name' and 'visible'. The 'haystack'.
     * @param string $group
     *  The group name to find, the 'needle'.
     * @return integer|boolean
     *  If the group is found, the index will be returned, otherwise false.
     */
    private static function __navigationFindGroupIndex(array $nav, $group)
    {
        foreach ($nav as $index => $item) {
            if ($item['name'] === $group) {
                return $index;
            }
        }

        return false;
    }

    /**
     * Given the navigation array, this function will loop over all the items
     * to determine which is the 'active' navigation group, or in other words,
     * what group best represents the current page `$this->Author` is viewing.
     * This is done by checking the current page's link against all the links
     * provided in the `$nav`, and then flagging the group of the found link
     * with an 'active' CSS class. The current page's link omits any flags or
     * URL parameters and just uses the root page URL.
     *
     * @param array $nav
     *  An associative array of the navigation where the key is the group
     *  index, and the value is an associative array of 'name', 'index' and
     *  'children'. Name is the name of the this group, index is the same as
     *  the key and children is an associative array of navigation items containing
     *  the keys 'link', 'name' and 'visible'. The 'haystack'. This parameter is passed
     *  by reference to this function.
     * @param string $pageroot
     *  The current page the Author is the viewing, minus any flags or URL
     *  parameters such as a Symphony object ID. eg. Section ID, Entry ID. This
     *  parameter is also be a regular expression, but this is highly unlikely.
     * @param boolean $pattern
     *  If set to true, the `$pageroot` represents a regular expression which will
     *  determine if the active navigation item
     * @return boolean
     *  Returns true if an active link was found, false otherwise. If true, the
     *  navigation group of the active link will be given the CSS class 'active'
     */
    private static function __findActiveNavigationGroup(array &$nav, $pageroot, $pattern = false)
    {
        foreach ($nav as $index => $contents) {
            if (is_array($contents['children']) && !empty($contents['children'])) {
                foreach ($contents['children'] as $item) {
                    if ($pattern && preg_match($pageroot, $item['link'])) {
                        $nav[$index]['class'] = 'active';
                        return true;
                    } elseif ($item['link'] == $pageroot) {
                        $nav[$index]['class'] = 'active';
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Creates the Symphony footer for an Administration page. By default
     * this includes the installed Symphony version and the currently logged
     * in Author. A delegate is provided to allow extensions to manipulate the
     * footer HTML, which is an XMLElement of a `<ul>` element.
     * Since Symphony 2.3, it no longer uses the `AddElementToFooter` delegate.
     */
    public function appendUserLinks()
    {
        $ul = new XMLElement('ul', null, ['id' => 'session']);

        $li = new XMLElement('li');
        $li->appendChild(
            Widget::Anchor(
                Symphony::Author()->getFullName(),
                SYMPHONY_URL . '/system/authors/edit/' . Symphony::Author()->get('id') . '/'
            )
        );
        $ul->appendChild($li);

        $li = new XMLElement('li');
        $li->appendChild(Widget::Anchor(__('Log out'), SYMPHONY_URL . '/logout/', null, null, null, ['accesskey' => 'l']));
        $ul->appendChild($li);

        $this->Header->appendChild($ul);
    }

    /**
     * Adds a localized Alert message for failed timestamp validations.
     * It also adds meta information about the last author and timestamp.
     *
     * @since Symphony 2.7.0
     * @param string $errorMessage
     *  The error message to display.
     * @param Entry|Section $existingObject
     *  The Entry or section object that failed validation.
     * @param string $action
     *  The requested action.
     */
    public function addTimestampValidationPageAlert($errorMessage, $existingObject, $action)
    {
        $authorId = $existingObject->get('modification_author_id');
        if (!$authorId) {
            $authorId = $existingObject->get('author_id');
        }
        $author = AuthorManager::fetchByID($authorId);
        $formatteAuthorName = $authorId === Symphony::Author()->get('id')
            ? __('yourself')
            : (!$author
                ? __('an unknown user')
                : $author->get('first_name') . ' ' . $author->get('last_name'));

        $msg = $this->_errors['timestamp'] . ' ' . __(
            'made by %s at %s.', [
                $formatteAuthorName,
                Widget::Time($existingObject->get('modification_date'))->generate(),
            ]
        );

        $currentUrl = Administration::instance()->getCurrentPageURL();
        $overwritelink = Widget::Anchor(
            __('Replace changes?'),
            $currentUrl,
            __('Overwrite'),
            'js-tv-overwrite',
            null,
            [
                'data-action' => General::sanitize($action)
            ]
        );
        $ignorelink = Widget::Anchor(
            __('View changes.'),
            $currentUrl,
            __('View the updated entry')
        );
        $actions = $overwritelink->generate() . ' ' . $ignorelink->generate();

        $this->pageAlert("$msg $actions", Alert::ERROR);
    }
}
