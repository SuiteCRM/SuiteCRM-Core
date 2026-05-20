<?php
/**
 * SuiteCRM is a customer relationship management program developed by SalesAgility Ltd.
 * Copyright (C) 2025 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SALESAGILITY, SALESAGILITY DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */

if (!defined('sugarEntry')) {
    define('sugarEntry', true);
}

class AppInstallService
{

    protected array $debug = [];
    protected array $messages = [];
    protected mixed $loggerBackup;
    protected bool $loggerToggled = false;

    public function runInstall(): array
    {
        // Set Globals
        global $sugar_version, $suitecrm_version;
        global $current_user, $sugar_config;
        global $beanFiles, $beanList;
        global $module, $action;
        global $disable_date_format;
        global $fill_in_rel_depth;
        global $layout_defs;
        global $dictionary, $buildingRelCache;
        global $silentInstall;
        global $disable_echos;
        global $timedate;
        global $locale;
        global $setup_sugar_version;
        global $supportedLanguages;
        global $install_script;
        require_once 'include/utils.php';

        $this->resetDebug();
        $this->resetMessages();

        @session_start();

        //  recover smtp settings
        if (isset($_POST['smtp_tab_selected'])) {
            $_POST = array_merge($_POST, $_POST[$_POST['smtp_tab_selected']]);
        }

        /**
         * Check php version
         *
         * If less than minimum we refuse to install.
         */
        if (check_php_version() === -1) {

            $currentPhpVersion = constant('PHP_VERSION');
            $recommendedPhpVersion =  constant('SUITECRM_PHP_REC_VERSION');
            $minumumPhpVersion =  constant('SUITECRM_PHP_MIN_VERSION');

            $this->addMessage("Current PHP version '$currentPhpVersion' not supported | Recommended: '$recommendedPhpVersion'");
            $debugMessage = 'The recommended PHP version to install SuiteCRM is ';
            $debugMessage .= constant('SUITECRM_PHP_REC_VERSION');
            $debugMessage .= 'Although the minimum PHP version required is ';
            $debugMessage .= constant('SUITECRM_PHP_MIN_VERSION') . ', ';
            $debugMessage .= 'is not recommended due to the large number of fixed bugs, including security fixes, ';
            $debugMessage .= 'released in the more modern versions.';
            $debugMessage .= 'You are using PHP version  ' . constant('PHP_VERSION') . ', which is EOL: <a href="http://php.net/eol.php">http://php.net/eol.php</a>.<br />';
            $this->addDebug($debugMessage);

            return $this->buildResult(false);
        }

        $session_id = session_id();
        if (empty($session_id)) {
            @session_start();
        }

        $GLOBALS['installing'] = true;
        define('SUGARCRM_IS_INSTALLING', $GLOBALS['installing']);
        $GLOBALS['sql_queries'] = 0;


        require_once 'include/SugarLogger/LoggerManager.php';
        require_once 'include/portability/Install/Logger/InstallLoggerManager.php';
        require_once 'sugar_version.php';
        require_once 'suitecrm_version.php';
        require_once 'install/install_utils.php';
        require_once 'install/install_defaults.php';
        require_once 'include/TimeDate.php';
        require_once 'include/Localization/Localization.php';
        require_once 'include/SugarTheme/SugarTheme.php';
        require_once 'include/utils/LogicHook.php';
        require_once 'data/SugarBean.php';
        require_once 'include/entryPoint.php';

        $this->switchLogger();

        if (!empty($sugar_config['installer_locked']) && (isset($sugar_config['installed']) && $sugar_config['installed'] === true)) {
            return $this->buildResult(false, ['Installer has been disabled']);
        }

        //check to see if the script files need to be rebuilt, add needed variables to request array
        $_REQUEST['root_directory'] = getcwd();
        $_REQUEST['js_rebuild_concat'] = 'rebuild';
        require_once 'jssource/minify.php'; // Do we need?

        //Set whether the install is silent or not
        $silentInstall = true;
        $disable_echos = true;


        $timedate = TimeDate::getInstance();
        // cn: set php.ini settings at entry points
        setPhpIniSettings();

        $locale = new Localization();

        $setup_sugar_version = $suitecrm_version;

        $install_script = true;

        ///////////////////////////////////////////////////////////////////////////////
        //// INSTALL RESOURCE SETUP
        $css = 'install/install.css';
        $icon = 'include/images/sugar_icon.ico';
        $sugar_md = 'include/images/sugar_md_open.png';
        $loginImage = 'include/images/suitecrm_login.png';
        $common = 'install/installCommon.js';
        [$supportedLanguages, $default_lang, $current_language, $mod_strings, $app_list_strings] = $this->loadInstallLaguages($supportedLanguages ?? []);

        //get the url for the helper link
        global $help_url;
        $help_url = get_help_button_url();


        foreach ($installer_defaults ?? [] as $key => $val) {
            if (!isset($_SESSION[$key])) {
                $_SESSION[$key] = $val;
            }
        }

        // always perform
        clean_special_arguments();

        //define web root, which will be used as default for site_url
        if ($_SERVER['SERVER_PORT'] == '80') {
            $web_root = $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
        } else {
            $web_root = $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['PHP_SELF'];
        }
        $web_root = str_replace("/install.php", "", $web_root);
        $web_root = "http://$web_root";

        if (empty($sugar_config['cache_dir']) && !empty($_SESSION['cache_dir'])) {
            $sugar_config['cache_dir'] = $_SESSION['cache_dir'];
        }

        // set the form's php var to the loaded config's var else default to sane settings
        if (!isset($_SESSION['setup_site_url']) || empty($_SESSION['setup_site_url'])) {
            if (isset($sugar_config['site_url']) && !empty($sugar_config['site_url'])) {
                $_SESSION['setup_site_url'] = $sugar_config['site_url'];
            } else {
                $_SESSION['setup_site_url'] = $web_root;
            }
        }

        if (!isset($_SESSION['setup_system_name']) || empty($_SESSION['setup_system_name'])) {
            $_SESSION['setup_system_name'] = 'SugarCRM';
        }
        if (!isset($_SESSION['setup_site_session_path']) || empty($_SESSION['setup_site_session_path'])) {
            $_SESSION['setup_site_session_path'] = (isset($sugar_config['session_dir'])) ? $sugar_config['session_dir'] : '';
        }
        if (!isset($_SESSION['setup_site_log_dir']) || empty($_SESSION['setup_site_log_dir'])) {
            $_SESSION['setup_site_log_dir'] = (isset($sugar_config['log_dir'])) ? $sugar_config['log_dir'] : '../../logs/legacy';
        }
        if (!isset($_SESSION['setup_site_guid']) || empty($_SESSION['setup_site_guid'])) {
            $_SESSION['setup_site_guid'] = (isset($sugar_config['unique_key'])) ? $sugar_config['unique_key'] : '';
        }
        if (!isset($_SESSION['cache_dir']) || empty($_SESSION['cache_dir'])) {
            $_SESSION['cache_dir'] = isset($sugar_config['cache_dir']) ? $sugar_config['cache_dir'] : 'cache/';
        }

        // Add check here to see if a silent install config file exists; if so then launch silent installer

        try {
            pullSilentInstallVarsIntoSession();
        } catch (Exception $e) {
            $this->addDebug($e->getMessage());
            $this->switchLogger();
            return $this->buildResult(false);
        }

        $si_errors = false;

        /*
         * Make sure we are using the correct unique_key. The logic
         * to save a custom unique_key happens lower in the process.
         * However because of the initial FTS check we are already
         * relying on this value which will not get reinitialized
         * when we actual need it during index creation because
         * SilentInstaller runs in one single process.
         */
        if (!empty($_SESSION['setup_site_specify_guid']) && !empty($_SESSION['setup_site_guid'])) {
            $sugar_config['unique_key'] = $_SESSION['setup_site_guid'];
        } else {
            $sugar_config['unique_key'] = md5(create_guid());
        }

        $validation_errors = validate_dbConfig();
        if (!empty($validation_errors) && is_array($validation_errors)) {
            $this->addMessage('DB configuration is not valid.');
            $this->addDebugArray($validation_errors);
            $this->switchLogger();
            return $this->buildResult(false);
        }

        $validation_errors = validate_siteConfig('a');
        if (!empty($validation_errors) && is_array($validation_errors)) {
            $this->addMessage('Site configuration is not valid.');
            $this->addDebugArray($validation_errors);
            $this->switchLogger();
            return $this->buildResult(false);
        }

        $validation_errors = validate_siteConfig('b');
        if (!empty($validation_errors) && is_array($validation_errors)) {
            $this->addMessage('Site configuration is not valid.');
            $this->addDebugArray($validation_errors);
            $this->switchLogger();
            return $this->buildResult(false);
        }

        //since this is a SilentInstall we still need to make sure that
        //the appropriate files are writable
        // config.php
        $result = make_writable('./config.php');

        if (!$result) {
            $this->addDebug('Not able to make writable: /public/legacy/config.php');
        }

        // custom dir
        $isCustomWritable = make_writable('./custom');
        if (!$isCustomWritable) {
            $this->addDebug('Not able to make writable: /public/legacy/custom');
        }

        // modules dir
        $isModulesWritable = recursive_make_writable('./modules');
        if (!$isModulesWritable) {
            $this->addDebug('Not able to make writable: /public/legacy/modules');
        }

        // cache dir
        create_writable_dir(sugar_cached('custom_fields'));
        create_writable_dir(sugar_cached('dyn_lay'));
        create_writable_dir(sugar_cached('images'));
        create_writable_dir(sugar_cached('modules'));
        create_writable_dir(sugar_cached('layout'));
        create_writable_dir(sugar_cached('pdf'));
        create_writable_dir(sugar_cached('upload/import'));
        create_writable_dir(sugar_cached('xml'));
        create_writable_dir(sugar_cached('include/javascript'));
        $isModulesCacheWritable = recursive_make_writable(sugar_cached('modules'));
        if (!$isModulesCacheWritable) {
            $this->addDebug('Not able to make writable: /public/legacy/cache/modules');
        }

        // public dir
        recursive_make_writable('./public');

        installerHook('pre_installFileRequire', ['the_file' => 'install/perform_setup.php']);

        try {
            $performSetupResult = $this->performSetup();
        } catch (Exception $e) {
            $this->addDebug($e->getMessage());
            $this->switchLogger();
            return $this->buildResult(false);
        }

        installerHook('post_installFileRequire', ['the_file' => 'install/perform_setup.php']);

        $success = true;
        if (isset($performSetupResult['success']) && $performSetupResult['success'] === false) {
            $success = false;
        }
        $this->switchLogger();
        return $this->buildResult($success);
    }

    protected function performSetup(): array
    {

        global $mod_strings;
        global $install_script;
        global $bottle;

        $this->installStatus('Starting "perform setup" install step', null, true, '');

        // This file will load the configuration settings from session data,
        // write to the config file, and execute any necessary database steps.
        $GLOBALS['installing'] = true;
        if (!isset($install_script) || !$install_script) {
            return [
                'success' => false,
                'messages' => ['Install error: Unable to process script directly. ']
            ];
        }

        ini_set("output_buffering", "0");
        set_time_limit(3600);
        // flush after each output so the user can see the progress in real-time
        ob_implicit_flush();


        require_once('install/install_utils.php');

        require_once('modules/TableDictionary.php');

        global $trackerManager;

        $trackerManager = TrackerManager::getInstance();
        $trackerManager->pause();

        global $cache_dir;
        global $dictionary;
        global $line_entry_format;
        global $line_exit_format;
        global $rel_dictionary;
        global $render_table_close;
        global $render_table_open;
        global $setup_db_admin_password;
        global $setup_db_admin_user_name;
        global $setup_db_create_database;
        global $setup_db_create_sugarsales_user;
        global $setup_db_database_name;
        global $setup_db_drop_tables;
        global $setup_db_host_instance;
        global $setup_db_port_num;
        global $setup_db_host_name;
        global $demoData;
        global $setup_db_sugarsales_password;
        global $setup_db_sugarsales_user;
        global $setup_site_admin_user_name;
        global $setup_site_admin_password;
        global $setup_site_guid;
        global $setup_site_url;
        global $parsed_url;
        global $setup_site_host_name;
        global $setup_site_log_dir;
        global $setup_site_log_file;
        global $setup_site_session_path;
        global $setup_site_log_level;
        global $beanFiles;


        $cache_dir = sugar_cached("");
        $line_entry_format = "&nbsp&nbsp&nbsp&nbsp&nbsp<b>";
        $line_exit_format = "... &nbsp&nbsp</b>";
        $rel_dictionary = $dictionary; // sourced by modules/TableDictionary.php
        $render_table_close = "";
        $render_table_open = "";
        $setup_db_admin_password = $_SESSION['setup_db_admin_password'];
        $setup_db_admin_user_name = $_SESSION['setup_db_admin_user_name'];
        $setup_db_create_database = $_SESSION['setup_db_create_database'];
        $setup_db_create_sugarsales_user = $_SESSION['setup_db_create_sugarsales_user'];
        $setup_db_database_name = $_SESSION['setup_db_database_name'];
        $setup_db_drop_tables = $_SESSION['setup_db_drop_tables'];
        $setup_db_host_instance = $_SESSION['setup_db_host_instance'];
        $setup_db_port_num = $_SESSION['setup_db_port_num'];
        $setup_db_host_name = $_SESSION['setup_db_host_name'];
        $demoData = $_SESSION['demoData'];
        $setup_db_sugarsales_password = $_SESSION['setup_db_sugarsales_password'];
        $setup_db_sugarsales_user = $_SESSION['setup_db_sugarsales_user'];
        $setup_site_admin_user_name = $_SESSION['setup_site_admin_user_name'];
        $setup_site_admin_password = $_SESSION['setup_site_admin_password'];
        $setup_site_guid = (isset($_SESSION['setup_site_specify_guid']) && $_SESSION['setup_site_specify_guid'] != '') ? $_SESSION['setup_site_guid'] : '';
        $setup_site_url = $_SESSION['setup_site_url'];
        $parsed_url = parse_url((string)$setup_site_url);
        $setup_site_host_name = $parsed_url['host'];
        $setup_site_log_dir = isset($_SESSION['setup_site_custom_log_dir']) ? $_SESSION['setup_site_log_dir'] : '../../logs/legacy';
        $setup_site_log_file = 'suitecrm.log';  // may be an option later
        $setup_site_session_path = isset($_SESSION['setup_site_custom_session_path']) ? $_SESSION['setup_site_session_path'] : '';
        $setup_site_log_level = 'fatal';

        $langHeader = get_language_header();

        $this->installStatus('Configurating relationships...', null, false, '');
        $this->addDebug("calling handleSugarConfig()");
        $configResult = handleSugarConfigSilent();
        $this->addDebugArray($configResult['debug'] ?? []);
        $this->addMessageArray($configResult['messages'] ?? []);

        $server_software = $_SERVER["SERVER_SOFTWARE"];
        if (str_contains((string)$server_software, 'Microsoft-IIS')) {
            $this->addDebug("calling handleWebConfig()");
            handleWebConfig();
        } else {
            $this->addDebug("calling handleHtaccess()");
            $htAccessResult = handleHtaccessSilent();
            $this->addDebugArray($htAccessResult['debug'] ?? []);
        }

        ///////////////////////////////////////////////////////////////////////////////
        ////    START TABLE STUFF

        $this->addDebug('Creating SuiteCRM application tables, audit tables and relationship metadata');

        // create the SugarCRM database
        if ($setup_db_create_database) {
            $this->addDebug("calling handleDbCreateDatabase()");
            installerHook('pre_handleDbCreateDatabase');
            $databaseCreationResult = handleDbCreateDatabaseSilent();

            if (empty($databaseCreationResult['success'])) {
                $this->addMessageArray($databaseCreationResult['messages'] ?? []);
                $this->addDebugArray($databaseCreationResult['debug'] ?? []);
                return [
                    'success' => false
                ];
            }

            installerHook('post_handleDbCreateDatabase');
        } else {

            // ensure the charset and collation are utf8
            $this->addDebug("calling handleDbCharsetCollation()");
            installerHook('pre_handleDbCharsetCollation');
            handleDbCharsetCollation();
            installerHook('post_handleDbCharsetCollation');
        }

        // create the SugarCRM database user
        if ($setup_db_create_sugarsales_user) {
            installerHook('pre_handleDbCreateSugarUser');
            $dbUserCreationResult = handleDbCreateSugarUserSilent();

            if (empty($dbUserCreationResult['success'])) {
                $this->addMessageArray($dbUserCreationResult['messages'] ?? []);
                $this->addDebugArray($dbUserCreationResult['debug'] ?? []);
                return [
                    'success' => false
                ];
            }
            installerHook('post_handleDbCreateSugarUser');
        }

        foreach ($beanFiles as $bean => $file) {
            require_once($file);
        }

        // load up the config_override.php file.
        // This is used to provide default user settings
        if (is_file("config_override.php")) {
            require_once("config_override.php");
        }

        global $startTime;
        global $focus;
        global $processed_tables;
        global $db;
        global $empty;
        global $new_tables;
        global $new_config;
        global $new_report;
        global $nonStandardModules;


        $db = DBManagerFactory::getInstance();
        $startTime = microtime(true);
        $focus = 0;
        $processed_tables = []; // for keeping track of the tables we have worked on
        $empty = [];
        $new_tables = 1; // is there ever a scenario where we DON'T create the admin user?
        $new_config = 1;
        $new_report = 1;

        // add non-module Beans to this array to keep the installer from erroring.
        $nonStandardModules = [//'Tracker',
        ];


        /**
         * loop through all the Beans and create their tables
         */
        $this->installStatus('Create database');
        $this->addDebug("looping through all the Beans and create their tables");

        //start by clearing out the vardefs
        VardefManager::clearVardef();
        installerHook('pre_createAllModuleTables');


        foreach ($beanFiles as $bean => $file) {
            $doNotInit = ['Scheduler', 'SchedulersJob', 'ProjectTask', 'jjwg_Maps', 'jjwg_Address_Cache', 'jjwg_Areas', 'jjwg_Markers'];

            if (in_array($bean, $doNotInit)) {
                $focus = new $bean(false);
            } else {
                $focus = new $bean();
            }

            if ($bean === 'Configurator') {
                continue;
            }

            $table_name = $focus->table_name;
            $this->addDebug("processing table " . $focus->table_name);
            // check to see if we have already setup this table
            if (!in_array($table_name, $processed_tables, true)) {
                if (!file_exists("modules/" . $focus->module_dir . "/vardefs.php")) {
                    continue;
                }
                if (!in_array($bean, $nonStandardModules)) {
                    require_once("modules/" . $focus->module_dir . "/vardefs.php"); // load up $dictionary
                    if (isset($dictionary[$focus->object_name]['table']) && $dictionary[$focus->object_name]['table'] === 'does_not_exist') {
                        continue; // support new vardef definitions
                    }
                } else {
                    continue; //no further processing needed for ignored beans.
                }

                // table has not been setup...we will do it now and remember that
                $processed_tables[] = $table_name;

                $focus->db->database = $db->database; // set db connection so we do not need to reconnect

                if ($setup_db_drop_tables) {
                    drop_table_install($focus);
                    $this->addDebug("dropping table " . $focus->table_name);
                }

                if (create_table_if_not_exist($focus)) {
                    $this->addDebug("creating table " . $focus->table_name);
                    if ($bean == "User") {
                        $new_tables = 1;
                    }
                    if ($bean == "Administration") {
                        $new_config = 1;
                    }
                }

                $this->addDebug("creating Relationship Meta for " . $focus->getObjectName());
                installerHook('pre_createModuleTable', array('module' => $focus->getObjectName()));
                SugarBean::createRelationshipMeta($focus->getObjectName(), $db, $table_name, $empty, $focus->module_dir);
                installerHook('post_createModuleTable', array('module' => $focus->getObjectName()));
            } // end if()
        }


        installerHook('post_createAllModuleTables');

        ////    END TABLE STUFF

        ///////////////////////////////////////////////////////////////////////////////
        ////    START RELATIONSHIP CREATION

        ksort($rel_dictionary);
        foreach ($rel_dictionary as $rel_name => $rel_data) {
            $table = $rel_data['table'];

            if ($setup_db_drop_tables) {
                if ($db->tableExists($table)) {
                    $db->dropTableName($table);
                }
            }

            if (!$db->tableExists($table)) {
                $db->createTableParams($table, $rel_data['fields'], $rel_data['indices']);
            }

            SugarBean::createRelationshipMeta($rel_name, $db, $table, $rel_dictionary, '');
        }

        ///////////////////////////////////////////////////////////////////////////////
        ////    START CREATE DEFAULTS
        $this->installStatus('Create default settings...');
        $this->addDebug("Begin creating default SuiteCRM data");
        installerHook('pre_createDefaultSettings');
        if ($new_config) {
            $this->addDebug("insert defaults into config table");
            insert_default_settings();
        }
        installerHook('post_createDefaultSettings');


        installerHook('pre_createUsers');
        if ($new_tables) {
            $this->addDebug('Creating default users');
            create_default_users();
            $this->addDebug('Creating default users - done');
        } else {
            $this->addDebug('Setting site admin password');
            $db->setUserName($setup_db_sugarsales_user);
            $db->setUserPassword($setup_db_sugarsales_password);
            set_admin_password($setup_site_admin_password);
            $this->addDebug('Setting site admin password - done');
        }
        installerHook('post_createUsers');


        // default OOB schedulers

        $this->addDebug('Creating default scheduler jobs');
        $scheduler = BeanFactory::newBean('Schedulers');
        installerHook('pre_createDefaultSchedulers');
        $scheduler->rebuildDefaultSchedulers();
        installerHook('post_createDefaultSchedulers');
        $this->addDebug('Creating default scheduler jobs - done');


        // Enable Sugar Feeds and add all feeds by default
        $this->addDebug("Enable SugarFeeds");
        enableSugarFeeds();

        // Install the logic hook for WorkFLow
        $this->addDebug("Creating WorkFlow logic hook");

        $this->createWorkFlowLogicHook();

        ///////////////////////////////////////////////////////////////////////////
        ////    FINALIZE LANG PACK INSTALL
        if (isset($_SESSION['INSTALLED_LANG_PACKS']) && is_array($_SESSION['INSTALLED_LANG_PACKS']) && !empty($_SESSION['INSTALLED_LANG_PACKS'])) {
            updateUpgradeHistory();
        }


        //require_once('modules/Connectors/InstallDefaultConnectors.php');

        ///////////////////////////////////////////////////////////////////////////////
        ////    INSTALL PASSWORD TEMPLATES
        include('install/seed_data/Advanced_Password_SeedData.php');

        ///////////////////////////////////////////////////////////////////////////////
        ////    SETUP DONE
        $this->addDebug("Installation has completed *********");

        $memoryUsed = '';
        if (function_exists('memory_get_usage')) {
            $memoryUsed = 'Approximate memory used: ' . memory_get_usage() . ' bytes.';
            $this->addDebug($memoryUsed);
        }


        $errTcpip = '';
        $fp = @fsockopen("www.suitecrm.com", 80, $errno, $errstr, 3);
        if (!$fp) {
            $this->addDebug('We could not detect an Internet connection.');
        }

        if (isset($_SESSION['setup_site_sugarbeet_automatic_checks']) && $_SESSION['setup_site_sugarbeet_automatic_checks'] == true) {
            set_CheckUpdates_config_setting('automatic');
        } else {
            set_CheckUpdates_config_setting('manual');
        }

        if (!empty($_SESSION['setup_system_name'])) {
            $admin = BeanFactory::newBean('Administration');
            $admin->saveSetting('system', 'name', $_SESSION['setup_system_name']);
        }

        // Bug 28601 - Set the default list of tabs to show
        $enabled_tabs = array();
        $enabled_tabs[] = 'Home';
        $enabled_tabs[] = 'Accounts';
        $enabled_tabs[] = 'Contacts';
        $enabled_tabs[] = 'Opportunities';
        $enabled_tabs[] = 'Leads';
        $enabled_tabs[] = 'AOS_Quotes';
        $enabled_tabs[] = 'Calendar';
        $enabled_tabs[] = 'Documents';
        $enabled_tabs[] = 'Emails';
        $enabled_tabs[] = 'Campaigns';
        $enabled_tabs[] = 'Calls';
        $enabled_tabs[] = 'Meetings';
        $enabled_tabs[] = 'Tasks';
        $enabled_tabs[] = 'Notes';
        $enabled_tabs[] = 'AOS_Invoices';
        $enabled_tabs[] = 'AOS_Contracts';
        $enabled_tabs[] = 'Cases';
        $enabled_tabs[] = 'Prospects';
        $enabled_tabs[] = 'ProspectLists';
        $enabled_tabs[] = 'Project';
        $enabled_tabs[] = 'AM_ProjectTemplates';
        $enabled_tabs[] = 'AM_TaskTemplates';
        $enabled_tabs[] = 'FP_events';
        $enabled_tabs[] = 'FP_Event_Locations';
        $enabled_tabs[] = 'AOS_Products';
        $enabled_tabs[] = 'AOS_Product_Categories';
        $enabled_tabs[] = 'AOS_PDF_Templates';
        $enabled_tabs[] = 'AOR_Reports';
        $enabled_tabs[] = 'AOK_KnowledgeBase';
        $enabled_tabs[] = 'AOK_Knowledge_Base_Categories';
        $enabled_tabs[] = 'EmailTemplates';
        $enabled_tabs[] = 'Surveys';

        //Beginning of the scenario implementations
        //We need to load the tabs so that we can remove those which are scenario based and un-selected
        //Remove the custom tabConfig as this overwrites the complete list containined in the include/tabConfig.php
        if (file_exists('custom/include/tabConfig.php')) {
            unlink('custom/include/tabConfig.php');
        }
        require_once('include/tabConfig.php');

        //Remove the custom dashlet so that we can use the complete list of defaults to filter by category
        if (file_exists('custom/modules/Home/dashlets.php')) {
            unlink('custom/modules/Home/dashlets.php');
        }

        //Check if the folder is in place
        if (!file_exists('custom/modules/Home')) {
            sugar_mkdir('custom/modules/Home', 0775);
        }

        //Check if the folder is in place
        if (!file_exists('custom/include')) {
            sugar_mkdir('custom/include', 0775);
        }


        require_once('modules/Home/dashlets.php');

        if (isset($_SESSION['installation_scenarios'])) {
            foreach ($_SESSION['installation_scenarios'] as $scenario) {
                //If the item is not in $_SESSION['scenarios'], then unset them as they are not required
                if (!in_array($scenario['key'], $_SESSION['scenarios'])) {
                    foreach ($scenario['modules'] as $module) {
                        if (($removeKey = array_search($module, $enabled_tabs, true)) !== false) {
                            unset($enabled_tabs[$removeKey]);
                        }
                    }

                    //Loop through the dashlets to remove from the default home page based on this scenario
                    foreach ($scenario['dashlets'] as $dashlet) {
                        if (isset($defaultDashlets[$dashlet])) {
                            unset($defaultDashlets[$dashlet]);
                        }
                    }

                    //If the scenario has an associated group tab, remove accordingly (by not adding to the custom tabconfig.php
                    if (isset($scenario['groupedTabs'])) {
                        unset($GLOBALS['tabStructure'][$scenario['groupedTabs']]);
                    }
                }
            }
        }

        //Have a 'core' options, with accounts / contacts if no other scenario is selected
        if (!is_null($_SESSION['scenarios'])) {
            unset($GLOBALS['tabStructure']['LBL_TABGROUP_DEFAULT']);
        }


        //Write the tabstructure to custom so that the grouping are not shown for the un-selected scenarios
        $fileContents = "<?php \n" . '$GLOBALS["tabStructure"] =' . var_export($GLOBALS['tabStructure'], true) . ';';
        sugar_file_put_contents('custom/include/tabConfig.php', $fileContents);

        //Write the dashlets to custom so that the dashlets are not shown for the un-selected scenarios
        $fileContents = "<?php \n" . '$defaultDashlets =' . var_export($defaultDashlets, true) . ';';
        sugar_file_put_contents('custom/modules/Home/dashlets.php', $fileContents);


        // End of the scenario implementations


        installerHook('pre_setSystemTabs');
        require_once('modules/MySettings/TabController.php');
        $tabs = new TabController();
        $tabs->set_system_tabs($enabled_tabs);
        installerHook('post_setSystemTabs');
        include_once('install/suite_install/suite_install.php');

        post_install_modules();

        ///////////////////////////////////////////////////////////////////////////////
        ////    START DEMO DATA

        // populating the db with seed data
        if ($_SESSION['demoData'] !== 'no') {
            $this->addDebug("Populating the db with seed data");
            installerHook('pre_installDemoData');
            set_time_limit(301);

            global $current_user;
            $current_user = BeanFactory::newBean('Users');
            $current_user->retrieve(1);
            include("install/seed_data/populateSeedData.php");
            installerHook('post_installDemoData');
        }

        /////////////////////////////////////////////////////////////
        //// Store information by installConfig.php form

        // save current superglobals and vars
        $varStack['GLOBALS'] = $GLOBALS;
        $varStack['defined_vars'] = get_defined_vars();

        // restore previously posted form
        $_REQUEST = array_merge($_REQUEST, $_SESSION);
        $_POST = array_merge($_POST, $_SESSION);


        $this->installStatus('Install database steps finished...');
        $this->addDebug('Save configuration settings..');

        //      <--------------------------------------------------------
        //          from ConfigurationConroller->action_saveadminwizard()
        //          ---------------------------------------------------------->

        $this->addDebug('save locale');


        //global $current_user;
        $this->addDebug('new Administration');
        $focus = BeanFactory::newBean('Administration');
        $this->addDebug('retrieveSettings');
        //$focus->retrieveSettings();
        // switch off the adminwizard (mark that we have got past this point)
        $this->addDebug('AdminWizard OFF');
        $focus->saveSetting('system', 'adminwizard', 1);

        $this->addDebug('saveConfig');
        $focus->saveConfig();

        $this->addDebug('new Configurator');
        global $configurator;
        $configurator = new Configurator();
        $this->addDebug('populateFromPost');
        $configurator->populateFromPost();


        $this->addDebug('handleOverride');
        // add local settings to config overrides
        if (!empty($_SESSION['default_date_format'])) {
            $sugar_config['default_date_format'] = $_SESSION['default_date_format'];
        }
        if (!empty($_SESSION['default_time_format'])) {
            $sugar_config['default_time_format'] = $_SESSION['default_time_format'];
        }
        if (!empty($_SESSION['default_language'])) {
            $sugar_config['default_language'] = $_SESSION['default_language'];
        }
        if (!empty($_SESSION['default_locale_name_format'])) {
            $sugar_config['default_locale_name_format'] = $_SESSION['default_locale_name_format'];
        }
        //$configurator->handleOverride();


        // save current web-server user for the cron user check mechanism:
        $this->addDebug('addCronAllowedUser');
        addCronAllowedUser(getRunningUser());


        $this->addDebug('saveConfig');
        $configurator->saveConfig();


        // Bug 37310 - Delete any existing currency that matches the one we've just set the default to during the admin wizard
        $this->addDebug('new Currency');
        $currency = new Currency();
        $this->addDebug('retrieve');
        $currency->retrieve($currency->retrieve_id_by_name($_REQUEST['default_currency_name']));
        if (!empty($currency->id)
            && $currency->symbol == $_REQUEST['default_currency_symbol']
            && $currency->iso4217 == $_REQUEST['default_currency_iso4217']) {
            $currency->deleted = 1;
            $this->addDebug('Save currency');
            $currency->save();
        }


        $this->addDebug('Save user settings..');

        //      <------------------------------------------------
        //          from UsersController->action_saveuserwizard()
        //          ---------------------------------------------------------->


        // set all of these default parameters since the Users save action will undo the defaults otherwise

        // load admin
        if (empty($current_user) || empty($current_user->id)) {
            $current_user = BeanFactory::newBean('Users');
            $current_user->retrieve(1);
            $current_user->is_admin = '1';
        }

        if (empty($sugar_config)) {
            $sugar_config = get_sugar_config_defaults();
        }

        // set local settings -  if neccessary you can set here more fields as named in User module / EditView form...
        if (isset($_REQUEST['timezone']) && $_REQUEST['timezone']) {
            $current_user->setPreference('timezone', $_REQUEST['timezone']);
        }

        if (file_exists(__DIR__ . '/../modules/ACL/install_actions.php')) {
            require_once(__DIR__ . '/../modules/ACL/install_actions.php');
        }

        $_POST['dateformat'] = $_REQUEST['default_date_format'];
        $_POST['record'] = $current_user->id;
        $_POST['is_admin'] = ($current_user->is_admin ? 'on' : '');
        $_POST['use_real_names'] = true;
        $_POST['reminder_checked'] = '1';
        $_POST['reminder_time'] = 1800;
        $_POST['email_reminder_time'] = 3600;
        $_POST['mailmerge_on'] = 'on';
        $_POST['receive_notifications'] = $current_user->receive_notifications;
        $this->addDebug('DBG: SugarThemeRegistry::getDefault');
        $_POST['user_theme'] = (string)SugarThemeRegistry::getDefault();

        // save and redirect to new view
        $_REQUEST['do_not_redirect'] = true;

        // restore superglobals and vars
        foreach ($varStack['GLOBALS'] ?? [] as $index => $item) {
            $GLOBALS[$index] = $item;
        }
        foreach ($varStack['defined_vars'] as $__key => $__value) {
            $$__key = $__value;
        }


        $endTime = microtime(true);
        $deltaTime = $endTime - $startTime;

        if (!empty($bottle) && is_array($bottle)) {
            foreach ($bottle as $bottle_message) {
                $this->addDebug($bottle_message);
            }
        }

        $GLOBALS['sugar_config']['installer_locked'] = true;
        $GLOBALS['sugar_config']['installed'] = true;
        write_array_to_file("sugar_config", $GLOBALS['sugar_config'], "config.php");

        installerHook('post_installModules');

        $this->installStatus('Installation process finished');

        return [
            'success' => true
        ];
    }

    protected function getSupportedInstallLanguages(): array
    {
        $supportedLanguages = [
            'en_us' => 'English (US)',
        ];

        if (file_exists('install/lang.config.php')) {
            $config = [];
            include('install/lang.config.php');
            if (!empty($config['languages'])) {
                foreach ($config['languages'] as $k => $v) {
                    if (file_exists('install/language/' . $k . '.lang.php')) {
                        $supportedLanguages[$k] = $v;
                    }
                }
            }
        }
        return $supportedLanguages;
    }

    /**
     * @param array $supportedLanguages
     * @return array
     */
    protected function loadInstallLaguages(array $supportedLanguages): array
    {
///////////////////////////////////////////////////////////////////////////////
        ////	INSTALLER LANGUAGE

        $supportedLanguages = $this->getSupportedInstallLanguages();

        // after install language is selected, use that pack
        global $default_lang;
        $default_lang = 'en_us';
        if (!isset($_POST['language']) && (!isset($_SESSION['language']) && empty($_SESSION['language']))) {
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $lang = parseAcceptLanguage();
                if (isset($supportedLanguages[$lang])) {
                    $_POST['language'] = $lang;
                } else {
                    $_POST['language'] = $default_lang;
                }
            }
        }

        if (isset($_POST['language'])) {
            $_SESSION['language'] = str_replace('-', '_', (string)$_POST['language']);
        }

        global $current_language;
        $current_language = isset($_SESSION['language']) ? $_SESSION['language'] : $default_lang;

        if (file_exists("install/language/{$current_language}.lang.php")) {
            require_once("install/language/{$current_language}.lang.php");
        } else {
            require_once("install/language/{$default_lang}.lang.php");
        }

        global $mod_strings;

        if ($current_language != 'en_us') {
            $my_mod_strings = $mod_strings;
            include('install/language/en_us.lang.php');
            $mod_strings = sugarLangArrayMerge($mod_strings, $my_mod_strings);
        }

        global $app_list_strings;
        $app_list_strings = return_app_list_strings_language($current_language);
        return array($supportedLanguages, $default_lang, $current_language, $mod_strings, $app_list_strings);
        ////	END INSTALLER LANGUAGE
        ///////////////////////////////////////////////////////////////////////////////
    }

    protected function installStatus($msg, $cmd = null, $overwrite = false, $before = '[ok]<br>')
    {
        $fname = 'install/status.json';
        if (!$overwrite && file_exists($fname)) {
            $stat = json_decode(file_get_contents($fname), null, 512, JSON_THROW_ON_ERROR);
            $msg = $stat->message . $before . $msg;
        }

        $json = json_encode(
            array(
                'message' => $msg,
                'command' => $cmd,
            ), JSON_THROW_ON_ERROR
        );

        $this->addDebug('install status: ' . $json);

        file_put_contents($fname, $json);
    }

    public function getDebug(): array
    {
        return $this->debug;
    }

    protected function resetDebug(): array
    {
        return $this->debug = [];
    }

    protected function addDebug(string $message): void
    {
        if (empty($message)) {
            return;
        }
        $this->debug[] = $message;
    }

    protected function addDebugArray(array $messages): void
    {
        foreach ($messages as $message) {
            $this->addDebug($message);
        }
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    protected function resetMessages(): array
    {
        return $this->messages = [];
    }


    protected function addMessage(string $message): void
    {
        if (empty($message)) {
            return;
        }
        $this->messages[] = $message;
    }

    protected function addMessageArray(array $messages): void
    {
        foreach ($messages as $message) {
            $this->addMessage($message);
        }
    }

    /**
     * @param bool $success
     * @param array $messages
     * @param array $debug
     * @return array
     */
    protected function buildResult(bool $success, array $messages = [], array $debug = []): array
    {
        return [
            'success' => $success,
            'messages' => array_merge($this->getMessages(), $messages),
            'debug' => array_merge($this->getDebug(), $debug)
        ];
    }

    protected function createWorkFlowLogicHook($filePath = 'Extension/application/Ext/LogicHooks/AOW_WorkFlow_Hook.php')
    {
        $customFileLoc = create_custom_directory($filePath);
        $templateLogicHookFileContents = file_get_contents('include/portability/Install/templates/workflow_logic_hook.php');
        $fp = sugar_fopen($customFileLoc, 'wb');
        fwrite($fp, $templateLogicHookFileContents);
        fclose($fp);

    }

    /**
     * @return void
     */
    protected function switchLogger(): void
    {
        if ($this->loggerToggled === false) {
            $this->loggerBackup = $GLOBALS['log'] ?? null;
            $GLOBALS['log'] = InstallLoggerManager::getLogger();
            $GLOBALS['install_log'] = &$GLOBALS['log'];
            $this->loggerToggled = true;
            return;
        }

        $GLOBALS['log'] = $this->loggerBackup;
        $this->loggerToggled = false;
    }

}
