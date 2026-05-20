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

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once 'include/portability/Install/Logger/InstallLogger.php';

class InstallLoggerManager extends LoggerManager
{

    /**
     * This is the current log level
     * @var string
     */
    private static $_level = 'fatal';

    /**
     * This is the instance of the LoggerManager
     * @var null|LoggerManager
     */
    private static $_instance;

    //these are the mappings for levels to different log types
    private static $_logMapping = [
        'default' => 'InstallLogger',
    ];

    // These are the log level mappings anything with a lower value than your current log level will be logged
    private static $_levelMapping = [
        'debug' => 100,
        'info' => 70,
        'warn' => 50,
        'deprecated' => 40,
        'error' => 25,
        'fatal' => 10,
        'feedback' => 7,
        'security' => 5,
        'off' => 0,
    ];

    //only let the getLogger instantiate this object
    private function __construct()
    {
        $this->setLevel('fatal');

        $this->_findAvailableLoggers();
    }

    /**
     * Returns a logger instance
     * @return LoggerManager
     */
    public static function getLogger()
    {
        if (!self::$_instance) {
            self::$_instance = new InstallLoggerManager();
        }

        return self::$_instance;
    }

    protected function _findAvailableLoggers()
    {
        foreach (['include/portability/Install/Logger', 'custom/include/portability/Install/Logger'] as $location) {
            if (is_dir($location) && $dir = opendir($location)) {
                while (($file = readdir($dir)) !== false) {
                    if ($file === '..'
                        || $file === '.'
                        || $file === 'LoggerTemplate.php'
                        || $file === 'LoggerManager.php'
                        || !is_file("$location/$file")
                    ) {
                        continue;
                    }
                    require_once("$location/$file");
                    $loggerClass = basename($file, '.php');
                    if (class_exists($loggerClass) && class_implements($loggerClass, 'LoggerTemplate')) {
                        self::$_loggers[$loggerClass] = new $loggerClass();
                    }
                }
            }
        }
        self::$_loggers['SugarLogger'] = new InstallLogger();
        self::$_loggers['InstallLogger'] = new InstallLogger();

    }


}
