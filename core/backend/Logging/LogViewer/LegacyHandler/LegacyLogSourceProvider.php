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

namespace App\Logging\LogViewer\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Logging\LogViewer\Model\LogSource;
use App\Logging\LogViewer\Source\LogFileFinder;
use App\Logging\LogViewer\Source\LogSourceProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The legacy logs: the log of the legacy logger (SugarLogger: log_dir and logger.file
 * of the config) followed by its rotated files, and the other log files of its folder and of the
 * legacy folder (search index, legacy install).
 */
class LegacyLogSourceProvider extends LegacyHandler implements LogSourceProviderInterface
{
    public const HANDLER_KEY = 'log-viewer-legacy-sources';

    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        protected LogFileFinder $finder
    ) {
        parent::__construct($projectDir, $legacyDir, $legacySessionName, $defaultSessionName, $legacyScopeState, $requestStack);
    }

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    public function getSources(): array
    {
        $this->init();
        try {
            $config = \SugarConfig::getInstance();
            // The defaults of SugarLogger.
            $name = (string)$config->get('logger.file.name', 'suitecrm');
            $extension = (string)$config->get('logger.file.ext', '.log');
            $directory = (string)$config->get('log_dir', '../../logs/legacy');
        } finally {
            $this->close();
        }

        // The legacy code runs in its folder: a relative log_dir is from there.
        $legacyDir = rtrim($this->legacyDir, '/');
        if ($directory === '' || $directory === '.') {
            $directory = $legacyDir;
        } elseif (!str_starts_with($directory, '/')) {
            $directory = $legacyDir . '/' . $directory;
        }

        // The log, with the date suffix of the config (suitecrm_11_2026.log) and its rotated
        // files (suitecrm_1.log...): the last written first.
        $main = $this->finder->find($directory, '/^' . preg_quote($name, '/') . '(_[0-9_]+)?' . preg_quote($extension, '/') . '$/');
        usort($main, static fn (string $a, string $b): int => [filemtime($b), $a] <=> [filemtime($a), $b]);

        $sources = [];
        if (!empty($main)) {
            $label = $this->finder->getLabel($main[0]);
            $sources[] = new LogSource(LogSource::PART_LEGACY . ':' . $label, LogSource::PART_LEGACY, $label, $main, true);
        }

        $known = array_map('realpath', $main);
        foreach (array_unique([$directory, $legacyDir]) as $folder) {
            foreach ($this->finder->find($folder) as $path) {
                if (in_array(realpath($path), $known, true)) {
                    continue;
                }
                $known[] = realpath($path);
                $label = $this->finder->getLabel($path);
                $sources[] = new LogSource(LogSource::PART_LEGACY . ':' . $label, LogSource::PART_LEGACY, $label, [$path]);
            }
        }

        return $sources;
    }
}
