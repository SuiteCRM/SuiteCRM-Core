<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2026 SuiteCRM Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUITECRM, SUITECRM DISCLAIMS THE
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

namespace App\Logging\LogViewer\Source;

use App\Logging\LogViewer\Model\LogSource;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * The Symfony logs: the log files of the folders Monolog writes to (the folder of the
 * environment, and the one of the upgrade and install logs), whatever handler wrote them.
 *
 * First of the providers: when all the logs are in a same folder, its files are Symfony logs,
 * but the legacy log itself (LogViewerHandler::getSources()).
 */
#[AsTaggedItem(priority: 10)]
class SymfonyLogSourceProvider implements LogSourceProviderInterface
{
    /**
     * @param string $logDir the folder of the logs of the environment (config/packages/monolog.yaml)
     * @param string $baseLogDir the folder of the upgrade and install logs
     */
    public function __construct(
        protected LogFileFinder $finder,
        protected string $logDir,
        protected string $baseLogDir
    ) {
    }

    public function getSources(): array
    {
        $sources = [];
        foreach ([$this->logDir, $this->baseLogDir] as $directory) {
            foreach ($this->finder->find($directory) as $path) {
                $label = $this->finder->getLabel($path);
                $key = LogSource::PART_SYMFONY . ':' . $label;
                $sources[$key] = new LogSource($key, LogSource::PART_SYMFONY, $label, [$path]);
            }
        }

        return array_values($sources);
    }
}
