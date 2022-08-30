<?php

namespace Symnext\AdminUI\Views;

use Symnext\AdminUI;
use Symnext\Toolkit\View;
use Symnext\Core\DateTimeObj;
use Symnext\Core\Configuration;

class Install extends View
{
    private $fields = [
        'general/site_name' => 'Symnext CMS',
        'admin/admin_path' => 'admin',
        'database/host' => 'localhost',
        'database/port' => '3306',
        'database/table_prefix' => 'sn_',
        'files/file_write_mode' => '0644',
        'files/directory_write_mode' => '0755'
    ];

    private $errors = [];

    protected function getViewTemplate(): string
    {
        return AdminUI\VIEW_TEMPLATES . '/install.xsl';
    }

    protected function beforeGenerate()
    {
        if (empty($_POST)) return;

        $tree = $this->xmlRoot;
        $this->fields = $_POST['fields'];
        $fields = $this->fields;
#var_dump($this->fields);die;
        // Check for errors.

        foreach ($fields as $name => $value) {
            if (trim($value) == '') {
                $this->addError("fields[$name]", 'required');
            }
        }
        $user_password = $fields['user/password'];
        $user_confirm_password = $fields['user/confirm_password'];
        if ($user_password && $user_confirm_password) {
            if ($user_confirm_password !== $user_password) {
                $this->addError('fields[user/confirm_password]', 'password_no_match');
            }
        }
        #echo $tree->asXML();die;
        if (!isset($tree->errors)) return;

        // No form errors. proceed with installation.

        $config = new Configuration();
        $config->load(\Symnext\TEMPLATES . '/config-default.xml');
        foreach ($fields as $name => $value) {
            $config->set($name, $value);
        }
        $config->write();

        $installer = new \Symnext\Install\Installer();
        $installer->run();
    }

    protected function buildView(): void
    {
        $tree = $this->xmlRoot;
        $tree->params->php_version = PHP_VERSION;
        $tree->params->php_version_min = '7.3.0';
        $requirements_not_met = self::checkRequirements();
        if (!empty($requirements_not_met)) {
            $x_req = $tree->appendElement('requirements');
            foreach ($requirements_not_met as $error) {
                $x_req->appendElement('requirement', $error);
            }
        } else {
            $x_form = $tree->appendElement('form');
            foreach ($this->fields as $path => $value) {
                $x_form->set($path, $value);
            }
            #echo $tree->asXML();die;
            $dtz_array = DateTimeObj::getTimezonesSelectOptions();
            #print_r($dtz_array); die;
            $data = $tree->appendElement('form');
            $zones = $this->xmlRoot->appendElement('timezones');
            foreach ($dtz_array as $region) {
                $el_region = $zones->appendElement('optgroup');
                $el_region->addAttribute('label', $region['label']);
                foreach ($region['options'] as $option) {
                    $el_option = $el_region->appendElement('option', $option[2]);
                    $el_option->addAttribute('value', $option[0]);
                }
            }
        }
    }

    /**
     * This function checks that the server can support a Symnext installation.
     * It checks that PHP is 7.3+, MySQL, Zlib, LibXML, XSLT modules are
     * enabled and a `install.sql` file exists.
     * If any of these requirements fail the installation will not proceed.
     *
     * @return array
     *  An associative array of errors, with `msg` and `details` keys
     */
    private function checkRequirements()
    {
        $errors = [];
        #$php_vc = new VersionComparator(phpversion());

        // Check for PHP 7.3+
        #if ($php_vc->lessThan('7.3')) {
        if (version_compare(PHP_VERSION, '7.3.0', '<')) {
            $errors[] = 'php-version';
        }

        // Make sure the install.sql file exists
        /*if (!file_exists('/includes/install.sql') || !is_readable('/includes/install.sql')) {
            $errors[] = 'install-sql';
        }*/

        // Is PDO available?
        if (!class_exists('PDO') || !extension_loaded('pdo_mysql')) {
            $errors[] = 'pdo';
        }

        // Is ZLib available?
        if (!extension_loaded('zlib')) {
            $errors[] = 'zlib';
        }

        // Is libxml available?
        if (!extension_loaded('xml') && !extension_loaded('libxml')) {
            $errors[] = 'xml';
        }

        // Is libxslt available?
        if (!extension_loaded('xsl') && !extension_loaded('xslt')) {
            $errors[] = 'xsl';
        }

        // Is json_encode available?
        if (!function_exists('json_decode')) {
            $errors[] = 'json';
        }

        // Cannot write to root folder.
        /*if (!is_writable(DOCROOT)) {
            $errors['no-write-permission-root'] = [
                'msg' => 'Root folder not writable: ' . DOCROOT,
                'details' => __('Symnext does not have write permission to the root directory. Please modify permission settings on %s. This can be reverted once installation is complete.', ['<code>' . DOCROOT . '</code>'])
            ];
        }

        // Cannot write to workspace
        if (is_dir(DOCROOT . '/workspace') && !is_writable(DOCROOT . '/workspace')) {
            $errors['no-write-permission-workspace'] = [
                'msg' => 'Workspace folder not writable: ' . DOCROOT . '/workspace',
                'details' => __('Symnext does not have write permission to the existing %1$s directory. Please modify permission settings on this directory and its contents to allow this, such as with a recursive %2$s command.', ['<code>/workspace</code>', '<code>chmod -R</code>'])
            ];
        }*/

        return $errors;
    }

    #####################################################


    //private static $POST = [];

    /**
     * Override the default Symnext constructor to initialise the Log, Config
     * and Database objects for installation/update. This allows us to use the
     * normal accessors.
     */
    /*protected function __construct()
    {
        self::$Profiler = Profiler::instance();
        self::$Profiler->sample('Engine Initialisation');

        // Include the default Config for installation.
        include(INSTALL . '/includes/config_default.php');
        static::initialiseConfiguration($settings);

        // Initialize date/time
        define_safe('__SYM_DATE_FORMAT__', self::Configuration()->get('date_format', 'region'));
        define_safe('__SYM_TIME_FORMAT__', self::Configuration()->get('time_format', 'region'));
        define_safe('__SYM_DATETIME_FORMAT__', __SYM_DATE_FORMAT__ . self::Configuration()->get('datetime_separator', 'region') . __SYM_TIME_FORMAT__);
        DateTimeObj::setSettings(self::Configuration()->get('region'));

        // Initialize Language, Logs and Database
        static::initialiseLang();
        static::initialiseLog(INSTALL_LOGS . '/install');

        // Initialize error handlers
        ExceptionHandler::initialise(Symnext::Log());
        ErrorHandler::initialise(Symnext::Log());

        // Copy POST
        self::$POST = $_POST;
    }*/

    /**
     * This function returns an instance of the Installer
     * class. It is the only way to create a new Installer, as
     * it implements the Singleton interface
     *
     * @return Installer
     */
    public static function instance()
    {
        if (!(self::$_instance instanceof Installer)) {
            self::$_instance = new Installer;
        }

        return self::$_instance;
    }

    /**
     * Initialises the language by looking at the `lang` key,
     * passed via GET or POST
     */
    public static function initialiseLang()
    {
        #$lang = !empty($_REQUEST['lang']) ? preg_replace('/[^a-zA-Z\-]/', null, $_REQUEST['lang']) : 'en';
        $lang = isset($_REQUEST['lang']) ? preg_replace('/[^a-zA-Z\-]/', null, $_REQUEST['lang']) : 'en';
        Lang::initialize();
        Lang::set($lang, false);
    }

    /**
     * Overrides the default `initialiseLog()` method and writes
     * logs to manifest/logs/install
     */
    public static function initialiseLog($filename = null)
    {
        if (is_dir(INSTALL_LOGS) || General::realiseDirectory(INSTALL_LOGS, self::Configuration()->get('write_mode', 'directory'))) {
            parent::initialiseLog($filename);
        }
    }

    /**
     * Overrides the default `initialiseDatabase()` method to do nothing
     */
    public static function initialiseDatabase()
    {
        // nothing
    }

    public function run()
    {
        // Make sure a log file is available
        if (is_null(Symnext::Log()) || !file_exists(Symnext::Log()->getLogPath())) {
            $this->render(new InstallerPage('missing-log'));
        }

        // Check essential server requirements
        $errors = $this->checkRequirements();
        if (!empty($errors)) {
            Symnext::Log()->pushToLog(
                sprintf('Installer - Missing requirements.'),
                E_ERROR, true
            );

            foreach ($errors as $err) {
                Symnext::Log()->pushToLog(
                    sprintf('Requirement - %s', $err['msg']),
                    E_ERROR, true
                );
            }

            $this->render(new InstallerPage('requirements', [
                'errors'=> $errors
            ]));
        }

        // Check for unattended installation
        $unattended = $this->checkUnattended();
        if (!empty($unattended)) {
            // Merge unattended information with the POST
            self::$POST = array_replace_recursive($unattended, self::$POST);
        }

        // If language is not set and there are language packs available, show
        // language selection pages.
        if (!isset(self::$POST['lang']) && count(Lang::getAvailableLanguages(false)) > 1) {
            $this->render(new InstallerPage('languages'));
        }

        // Check for configuration errors and, if there are no errors, install Symnext!
        if (isset(self::$POST['fields'])) {
            $errors = $this->checkConfiguration();
            if (!empty($errors)) {
                Symnext::Log()->pushToLog(
                    sprintf('Installer - Wrong configuration.'),
                    E_ERROR, true
                );

                foreach ($errors as $err) {
                    Symnext::Log()->pushToLog(
                        sprintf('Configuration - %s', $err['msg']),
                        E_ERROR, true
                    );
                }
            } elseif (isset(self::$POST['action']['install'])) {
                $disabled_extensions = $this->install();

                $this->render(new InstallerPage('success', [
                    'disabled-extensions' => $disabled_extensions
                ]));
            }
        }

        // Display the Installation page
        $this->render(new InstallerPage('configuration', [
            'errors' => $errors,
            'default-config' => !empty($unattended) ? $unattended['fields'] : Symnext::Configuration()->get()
        ]));
    }

    /**
     * Extends the given $fields array with the mandatory database values from
     * the default configuration.
     *
     * @param array $fields
     *  The array to extend
     * @return array
     *  The modified array
     */
    protected function extendDatabaseFields(array $fields)
    {
        $db = self::Configuration()->get('database');
        foreach (['engine', 'driver', 'charset', 'collate'] as $key) {
            $fields['database'][$key] = $db[$key];
        }
        return $fields;
    }


    /**
     * This function checks the current Configuration (which is the values entered
     * by the user on the installation form) to ensure that `/symphony` and `/workspace`
     * folders exist and are writable and that the Database credentials are correct.
     * Once those initial checks pass, the rest of the form values are validated.
     *
     * @return
     *  An associative array of errors if something went wrong, otherwise an empty array.
     */
    private function checkConfiguration()
    {
        $db = null;
        $errors = [];
        $fields = $this->extendDatabaseFields(self::$POST['fields']);

        // Testing the database connection
        try {
            $db = new Database($fields['database']);
            $db->connect();
        } catch (DatabaseException $e) {
            // Invalid credentials
            // @link http://dev.mysql.com/doc/refman/5.5/en/error-messages-server.html
            if ($e->getDatabaseErrorCode() === 1044 || $e->getDatabaseErrorCode() === 1045) {
                $errors['database-invalid-credentials'] = [
                    'msg' => 'Database credentials were denied',
                    'details' => __('Symnext was unable to access the database with these credentials.'),
                ];
            }
            // Connection related
            else {
                $errors['no-database-connection'] = [
                    'msg' => 'Could not establish database connection.',
                    'details' => __(
                        'Symnext was unable to establish a valid database connection. You may need to modify host or port settings.'
                    ) . ' ' . $e->getDatabaseErrorMessage(),
                ];
            }
        }

        try {
            // Check the database table prefix is legal. #1815
            if (!preg_match('/^[0-9a-zA-Z_]+$/', $fields['database']['tbl_prefix'])) {
                $errors['database-table-prefix'] = [
                    'msg' => 'Invalid database table prefix: ‘' . $fields['database']['tbl_prefix'] . '’',
                    'details' =>  __('The table prefix %s is invalid. The table prefix must only contain numbers, letters or underscore characters.', ['<code>' . $fields['database']['tbl_prefix'] . '</code>'])
                ];
            }
            // Check the database credentials
            elseif ($db && $db->isConnected()) {
                // Incorrect MySQL version
                $mysqlVc = new VersionComparator($db->getVersion());
                if ($mysqlVc->lessThan('5.6')) {
                    $errors['database-incorrect-version'] = [
                        'msg' => 'MySQL Version is not correct. '. $db->getVersion() . ' detected.',
                        'details' => __('Symnext requires %1$s or greater to work, however version %2$s was detected. This requirement must be met before installation can proceed.', [
                            '<code>MySQL 5.6</code>',
                            '<code>' . $db->getVersion() . '</code>'
                        ])
                    ];
                } else {
                    // Existing table prefix
                    $tables = $db->show()
                        ->from($fields['database']['db'])
                        ->like($fields['database']['tbl_prefix'] . '%')
                        ->execute()
                        ->rows();

                    if (!empty($tables)) {
                        $errors['database-table-prefix'] = [
                            'msg' => 'Database table prefix clash with ‘' . $fields['database']['db'] . '’',
                            'details' =>  __('The table prefix %s is already in use. Please choose a different prefix to use with Symnext.', ['<code>' . $fields['database']['tbl_prefix'] . '</code>'])
                        ];
                    }
                }
            }
        } catch (DatabaseException $e) {
            $errors['unknown-database'] = [
                'msg' => 'Database ‘' . $fields['database']['db'] . '’ not found.',
                'details' =>  __('Symnext was unable to connect to the specified database.')
            ];
        }

        // Website name not entered
        if (trim($fields['general']['sitename']) == '') {
            $errors['general-no-sitename'] = [
                'msg' => 'No sitename entered.',
                'details' => __('You must enter a Site name. This will be shown at the top of your backend.')
            ];
        }

        // Username Not Entered
        if (trim($fields['user']['username']) == '') {
            $errors['user-no-username'] = [
                'msg' => 'No username entered.',
                'details' => __('You must enter a Username. This will be your Symnext login information.')
            ];
        }

        // Password Not Entered
        if (trim($fields['user']['password']) == '') {
            $errors['user-no-password'] = [
                'msg' => 'No password entered.',
                'details' => __('You must enter a Password. This will be your Symnext login information.')
            ];
        }

        // Password mismatch
        elseif ($fields['user']['password'] != $fields['user']['confirm-password']) {
            $errors['user-password-mismatch'] = [
                'msg' => 'Passwords did not match.',
                'details' => __('The password and confirmation did not match. Please retype your password.')
            ];
        }

        // No Name entered
        if (trim($fields['user']['firstname']) == '' || trim($fields['user']['lastname']) == '') {
            $errors['user-no-name'] = [
                'msg' => 'Did not enter First and Last names.',
                'details' =>  __('You must enter your name.')
            ];
        }

        // Invalid Email
        if (!preg_match('/^\w(?:\.?[\w%+-]+)*@\w(?:[\w-]*\.)+?[a-z]{2,}$/i', $fields['user']['email'])) {
            $errors['user-invalid-email'] = [
                'msg' => 'Invalid email address supplied.',
                'details' =>  __('This is not a valid email address. You must provide an email address since you will need it if you forget your password.')
            ];
        }

        // Admin path not entered
        if (trim($fields['symphony']['admin-path']) == '') {
            $errors['no-symphony-path'] = [
                'msg' => 'No Symnext path entered.',
                'details' => __('You must enter a path for accessing Symnext, or leave the default. This will be used to access Symnext\'s backend.')
            ];
        }

        return $errors;
    }

    /**
     * This function checks if there is a unattend.php file in the MANIFEST folder.
     * If it finds one, it will load it and check for the $settings variable.
     * It will also merge the default config values into the 'fields' array.
     *
     * You can find an empty version at install/include/unattend.php
     *
     * @return array
     *   An associative array of values, as if it was submitted by a POST
     */
    private function checkUnattended()
    {
        $filepath = MANIFEST . '/unattend.php';
        if (!@file_exists($filepath) || !@is_readable($filepath)) {
            return false;
        }
        try {
            include $filepath;
            if (!isset($settings) || !is_array($settings) || !isset($settings['fields'])) {
                return false;
            }
            // Merge with default values
            $settings['fields'] = array_replace_recursive(Symnext::Configuration()->get(), $settings['fields']);
            // Special case for the password
            if (isset($settings['fields']['user']) && isset($settings['fields']['user']['password'])) {
                $settings['fields']['user']['confirm-password'] = $settings['fields']['user']['password'];
            }
            return $settings;
        } catch (Exception $ex) {
            Symnext::Log()->pushExceptionToLog($ex, true);
        }
        return false;
    }

    /**
     * If something went wrong, the `abort` function will write an entry to the Log
     * file and display the failure page to the user.
     * @todo: Resume installation after an error has been fixed.
     */
    protected function abort($message, $start)
    {
        $result = Symnext::Log()->pushToLog($message, E_ERROR, true);

        if ($result) {
            Symnext::Log()->writeToLog('============================================', true);
            Symnext::Log()->writeToLog(sprintf('INSTALLATION ABORTED: Execution Time - %d sec (%s)',
                max(1, time() - $start),
                date('d.m.y H:i:s')
            ), true);
            Symnext::Log()->writeToLog('============================================' . PHP_EOL . PHP_EOL . PHP_EOL, true);
        }

        $this->render(new InstallerPage('failure'));
    }

    private function install()
    {
        $db = null;
        $fields = $this->extendDatabaseFields(self::$POST['fields']);
        $start = time();

        Symnext::Log()->writeToLog(PHP_EOL . '============================================', true);
        Symnext::Log()->writeToLog('INSTALLATION PROCESS STARTED (' . DateTimeObj::get('c') . ')', true);
        Symnext::Log()->writeToLog('============================================', true);

        // MySQL: Establishing connection
        Symnext::Log()->pushToLog('MYSQL: Establishing Connection', E_NOTICE, true, true);

        try {
            $db = new Database($fields['database']);
            $db->connect();
        } catch (DatabaseException $e) {
            $this->abort(
                'There was a problem while trying to establish a connection to the MySQL server. ' .
                'Please check your settings.',
                $start
            );
        }

        // MySQL: Importing schema
        Symnext::Log()->pushToLog('MYSQL: Importing Table Schema', E_NOTICE, true, true);

        try {
            $db->import(file_get_contents(INSTALL . '/includes/install.sql'));
        } catch (DatabaseException $e) {
            $this->abort(
                __('There was an error while trying to import data to the database. MySQL returned: ') .
                $e->getDatabaseErrorCode() . ': ' . $e->getDatabaseErrorMessage() .
                __(' in query ') . PHP_EOL . $e->getQuery(),
                $start
            );
        }

        // MySQL: Creating default author
        Symnext::Log()->pushToLog('MYSQL: Creating Default Author', E_NOTICE, true, true);

        try {
            $db->insert('tbl_authors')->values([
                'id'                    => 1,
                'username'              => $fields['user']['username'],
                'password'              => Cryptography::hash($fields['user']['password']),
                'first_name'            => $fields['user']['firstname'],
                'last_name'             => $fields['user']['lastname'],
                'email'                 => $fields['user']['email'],
                'last_seen'             => null,
                'user_type'             => 'developer',
                'primary'               => 'yes',
                'default_area'          => '/blueprints/sections/',
                'auth_token'            => null,
            ])->execute();
        } catch (DatabaseException $e) {
            $this->abort(
                'There was an error while trying create the default author. MySQL returned: ' .
                $e->getDatabaseErrorCode() . ': ' . $e->getDatabaseErrorMessage(),
                $start
            );
        }

        // Configuration: Populating array
        $conf = Symnext::Configuration()->get();

        if (!is_array($conf)) {
            $this->abort('The configuration is not an array, can not continue', $start);
        }
        foreach ($conf as $group => $settings) {
            if (!is_array($settings)) {
                continue;
            }
            foreach ($settings as $key => $value) {
                if (isset($fields[$group]) && isset($fields[$group][$key])) {
                    $conf[$group][$key] = $fields[$group][$key];
                }
            }
        }

        // Create manifest folder structure
        Symnext::Log()->pushToLog('WRITING: Creating ‘manifest’ folder (/manifest)', E_NOTICE, true, true);
        if (!General::realiseDirectory(MANIFEST, $conf['directory']['write_mode'])) {
            $this->abort(
                'Could not create ‘manifest’ directory. Check permission on the root folder.',
                $start
            );
        }

        Symnext::Log()->pushToLog('WRITING: Creating ‘logs’ folder (/manifest/logs)', E_NOTICE, true, true);
        if (!General::realiseDirectory(LOGS, $conf['directory']['write_mode'])) {
            $this->abort(
                'Could not create ‘logs’ directory. Check permission on /manifest.',
                $start
            );
        }

        Symnext::Log()->pushToLog('WRITING: Creating ‘cache’ folder (/manifest/cache)', E_NOTICE, true, true);
        if (!General::realiseDirectory(CACHE, $conf['directory']['write_mode'])) {
            $this->abort(
                'Could not create ‘cache’ directory. Check permission on /manifest.',
                $start
            );
        }

        Symnext::Log()->pushToLog('WRITING: Creating ‘tmp’ folder (/manifest/tmp)', E_NOTICE, true, true);
        if (!General::realiseDirectory(MANIFEST . '/tmp', $conf['directory']['write_mode'])) {
            $this->abort(
                'Could not create ‘tmp’ directory. Check permission on /manifest.',
                $start
            );
        }

        // Writing configuration file
        Symnext::Log()->pushToLog('WRITING: Configuration File', E_NOTICE, true, true);

        Symnext::Configuration()->setArray($conf);

        if (!Symnext::Configuration()->write(CONFIG, $conf['file']['write_mode'])) {
            $this->abort(
                'Could not create config file ‘' . CONFIG . '’. Check permission on /manifest.',
                $start
            );
        }

        // Writing .htaccess file
        Symnext::Log()->pushToLog('CONFIGURING: Frontend', E_NOTICE, true, true);

        $rewrite_base = ltrim(preg_replace('/\/install$/i', null, dirname($_SERVER['PHP_SELF'])), '/');
        $htaccess = str_replace(
            '<!-- REWRITE_BASE -->',
            $rewrite_base,
            file_get_contents(INSTALL . '/includes/htaccess.txt')
        );

        if (!General::writeFile(DOCROOT . "/.htaccess", $htaccess, $conf['file']['write_mode'], 'a')) {
            $this->abort(
                'Could not write ‘.htaccess’ file. Check permission on ' . DOCROOT,
                $start
            );
        }

        // Writing /workspace folder
        if (!is_dir(DOCROOT . '/workspace')) {
            // Create workspace folder structure
            Symnext::Log()->pushToLog('WRITING: Creating ‘workspace’ folder (/workspace)', E_NOTICE, true, true);
            if (!General::realiseDirectory(WORKSPACE, $conf['directory']['write_mode'])) {
                $this->abort(
                    'Could not create ‘workspace’ directory. Check permission on the root folder.',
                    $start
                );
            }

            Symnext::Log()->pushToLog('WRITING: Creating ‘data-sources’ folder (/workspace/data-sources)', E_NOTICE, true, true);
            if (!General::realiseDirectory(DATASOURCES, $conf['directory']['write_mode'])) {
                $this->abort(
                    'Could not create ‘workspace/data-sources’ directory. Check permission on the root folder.',
                    $start
                );
            }

            Symnext::Log()->pushToLog('WRITING: Creating ‘events’ folder (/workspace/events)', E_NOTICE, true, true);
            if (!General::realiseDirectory(EVENTS, $conf['directory']['write_mode'])) {
                $this->abort(
                    'Could not create ‘workspace/events’ directory. Check permission on the root folder.',
                    $start
                );
            }

            Symnext::Log()->pushToLog('WRITING: Creating ‘pages’ folder (/workspace/pages)', E_NOTICE, true, true);
            if (!General::realiseDirectory(PAGES, $conf['directory']['write_mode'])) {
                $this->abort(
                    'Could not create ‘workspace/pages’ directory. Check permission on the root folder.',
                    $start
                );
            }

            Symnext::Log()->pushToLog('WRITING: Creating ‘utilities’ folder (/workspace/utilities)', E_NOTICE, true, true);
            if (!General::realiseDirectory(UTILITIES, $conf['directory']['write_mode'])) {
                $this->abort(
                    'Could not create ‘workspace/utilities’ directory. Check permission on the root folder.',
                    $start
                );
            }
        } else {
            Symnext::Log()->pushToLog('An existing ‘workspace’ directory was found at this location. Symnext will use this workspace.', E_NOTICE, true, true);

            // MySQL: Importing workspace data
            Symnext::Log()->pushToLog('MYSQL: Importing Workspace Data...', E_NOTICE, true, true);

            if (General::checkFileReadable(WORKSPACE . '/install.sql')) {
                try {
                    $db->import(
                        file_get_contents(WORKSPACE . '/install.sql'),
                        true
                    );
                } catch (DatabaseException $e) {
                    $this->abort(
                        'There was an error while trying to import data to the database. MySQL returned: ' . $e->getDatabaseErrorCode() . ': ' . $e->getDatabaseErrorMessage(),
                        $start
                    );
                }
            }
        }

        // Write extensions folder
        if (!is_dir(EXTENSIONS)) {
            // Create extensions folder
            Symnext::Log()->pushToLog('WRITING: Creating ‘extensions’ folder (/extensions)', E_NOTICE, true, true);
            if (!General::realiseDirectory(EXTENSIONS, $conf['directory']['write_mode'])) {
                $this->abort(
                    'Could not create ‘extension’ directory. Check permission on the root folder.',
                    $start
                );
            }
        }

        // Configure Symnext Database object
        parent::initialiseDatabase();

        // Configure a fake Administration page
        Administration::instance()->Page = new AdministrationPage;

        // Install existing extensions
        Symnext::Log()->pushToLog('CONFIGURING: Installing existing extensions', E_NOTICE, true, true);
        $disabled_extensions = [];
        foreach (new DirectoryIterator(EXTENSIONS) as $e) {
            if ($e->isDot() || $e->isFile() || !is_file($e->getRealPath() . '/extension.driver.php')) {
                continue;
            }

            $handle = $e->getBasename();
            try {
                if (!ExtensionManager::enable($handle)) {
                    $disabled_extensions[] = $handle;
                    Symnext::Log()->pushToLog('Could not enable the extension ‘' . $handle . '’.', E_NOTICE, true, true);
                }
            } catch (Exception $ex) {
                $disabled_extensions[] = $handle;
                Symnext::Log()->pushToLog('Could not enable the extension ‘' . $handle . '’. '. $ex->getMessage(), E_NOTICE, true, true);
            }
        }

        // Loading default language
        if (isset($_REQUEST['lang']) && $_REQUEST['lang'] != 'en') {
            Symnext::Log()->pushToLog('CONFIGURING: Default language', E_NOTICE, true, true);

            $language = Lang::Languages();
            $language = $language[$_REQUEST['lang']];

            // Is the language extension enabled?
            if (in_array('lang_' . $language['handle'], ExtensionManager::listInstalledHandles())) {
                Symnext::Configuration()->set('lang', $_REQUEST['lang'], 'symphony');
                if (!Symnext::Configuration()->write(CONFIG, $conf['file']['write_mode'])) {
                    Symnext::Log()->pushToLog('Could not write default language ‘' . $language['name'] . '’ to config file.', E_NOTICE, true, true);
                }
            } else {
                Symnext::Log()->pushToLog('Could not enable the desired language ‘' . $language['name'] . '’.', E_NOTICE, true, true);
            }
        }

        // Installation completed. Woo-hoo!
        Symnext::Log()->writeToLog('============================================', true);
        Symnext::Log()->writeToLog(sprintf(
            'INSTALLATION COMPLETED: Execution Time - %d sec (%s)',
            max(1, time() - $start),
            date('d.m.y H:i:s')
        ), true);
        Symnext::Log()->writeToLog('============================================' . PHP_EOL . PHP_EOL . PHP_EOL, true);

        return $disabled_extensions;
    }

    protected function addError(string $field, string $error): void
    {
        if (!isset($this->xmlRoot->errors)) {
            $this->xmlRoot->appendElement('errors');
        }
        $x_errors = $this->xmlRoot->errors;
        $x_error = $x_errors->appendElement('error', $field);
        $x_error->addAttribute('type', $error);
    }
}
