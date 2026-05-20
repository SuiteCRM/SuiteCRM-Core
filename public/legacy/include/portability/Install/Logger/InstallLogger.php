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

require_once 'include/SugarLogger/SugarLogger.php';

class InstallLogger extends SugarLogger
{

    protected $log_dir = '../../logs';
    protected $logfile = 'install';

    protected function init(): void
    {
        $config = SugarConfig::getInstance();
        $this->dateFormat = $config->get('logger.file.dateFormat', $this->dateFormat);
        $this->maxLogs = $config->get('logger.file.maxLogs', $this->maxLogs);
        $this->filesuffix = $config->get('logger.file.suffix', $this->filesuffix);
        $this->defaultPerms = $config->get('logger.file.perms', $this->defaultPerms);
        $this->logSize = '100MB';
        $this->logfile = 'install';
        $this->ext = '.log';
        $this->log_dir = $this->log_dir . (empty($this->log_dir)?'':'/');

        unset($config);
        $this->_doInitialization();
        InstallLoggerManager::setLogger('default', 'InstallLogger');
    }

    /**
     * @return string
     */
    protected function getDateFormatString(): string
    {
        return "[YYYY-MM-DD HH:mm:ss]";
    }

    /**
     * @param IntlDateFormatter $format
     * @param string $userID
     * @param $level
     * @param mixed $message
     * @return string
     */
    protected function formatLog(IntlDateFormatter $format, string $userID, $level, mixed $message): string
    {
        return $format->format(time()) . ' install.' . strtoupper($level) . ': ' . $message . "\n";
    }
}
