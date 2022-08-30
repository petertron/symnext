<?php

/**
* @package Boot
*/

use Symnext\Core\App;
use Symnext\Core\ExceptionHandler;
use Symnext\Core\Configuration;
use Symnext\Core\Log;

if (!is_file(CONFIG) and PATH_INFO != 'install') {
    redirect('/install');
}

$Params = [];

// Start with the Exception handler disable before authentication.
// This limits the possibility of leaking infos.
ExceptionHandler::$enabled = false;

App::initialiseConfiguration();

// Report all errors
if (App::Configuration()->get('error_reporting_all', 'symnext') === 'yes') {
    error_reporting(E_ALL);
}

// Set up error handler
App::initialiseErrorHandler();

// Set up database and extensions
if (!defined('SYMNEXT_LAUNCHER_NO_DB')) {
    App::initialiseDatabase();
    #App::initialiseExtensionManager();
    /**
     * Overload the default Symnext launcher logic.
     * @delegate ModifySymnextLauncher
     * @since Symnext 2.5.0
     * @param string $context
     * '/all/'
     */
    /*App::ExtensionManager()->notifyMembers(
        'ModifySymnextLauncher', '/all/'
    );*/
}

// Use default launcher:
if (defined('SYMNEXT_LAUNCHER') === false) {
    define('SYMNEXT_LAUNCHER', 'symnext_launcher');
}

/*if ($inside_installer) return;

if (App::isInstallerAvailable()) {
    redirect(URL . '/install/');
    exit;
}

if (!$inside_installer) {
    die('<h2>Error</h2><p>Could not locate Symnext configuration file. Please check <code>manifest/config.php</code> exists.</p>');
}*/
#var_dump(\Symnext\Toolkit\SectionManager::getTables('article'));
#die();
