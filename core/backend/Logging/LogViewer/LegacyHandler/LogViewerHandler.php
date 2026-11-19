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
use App\Logging\LogViewer\Service\LogJournal;
use App\Logging\LogViewer\Source\LogSourceProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The log viewer of the administration (View Log of the System Settings), in two
 * parts: the Symfony logs, read as one by date, and the legacy logs. The page never sends a
 * path: it reads the logs declared by the log source providers, by their key.
 *
 * Not available when the logger settings are hidden (logger_visible of the config).
 */
class LogViewerHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'log-viewer';

    /**
     * The parts of the page, with the levels of their filter (Monolog, legacy logger).
     */
    protected const PARTS = [
        LogSource::PART_SYMFONY => ['DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'],
        LogSource::PART_LEGACY => ['DEBUG', 'INFO', 'WARN', 'DEPRECATED', 'ERROR', 'FATAL', 'SECURITY'],
    ];

    protected const ENTRIES = 200;
    protected const SEARCH_LENGTH = 500;

    /**
     * @param iterable<LogSourceProviderInterface> $providers
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        protected iterable $providers,
        protected LogJournal $journal
    ) {
        parent::__construct($projectDir, $legacyDir, $legacySessionName, $defaultSessionName, $legacyScopeState, $requestStack);
    }

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The parts of the page with their logs, and the labels.
     *
     * @return array{available: bool, parts: array[], labels: array<string, string>}
     */
    public function load(): array
    {
        $this->init();
        $this->startLegacyApp();

        try {
            global $current_language;

            $labels = [];
            foreach (return_module_language($current_language, 'Configurator') as $key => $label) {
                if (is_string($label)) {
                    $labels[$key] = html_entity_decode($label, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
            }

            $available = $this->isAvailable();

            return ['available' => $available, 'parts' => $available ? $this->getParts() : [], 'labels' => $labels];
        } finally {
            $this->close();
        }
    }

    /**
     * The parts of the page, with the levels of their filter and their logs: size, last write
     * and end (the position of a mark point).
     *
     * @return array[]
     */
    public function getParts(): array
    {
        $parts = [];
        foreach (self::PARTS as $part => $levels) {
            $parts[$part] = ['key' => $part, 'levels' => $levels, 'sources' => []];
        }

        clearstatcache();
        foreach ($this->getSources() as $source) {
            $parts[$source->part]['sources'][] = [
                'key' => $source->key,
                'label' => $source->label,
                'main' => $source->main,
                'size' => (int)array_sum(array_map('filesize', $source->files)),
                'modified' => gmdate('Y-m-d H:i:s', (int)max(array_map('filemtime', $source->files))),
                'end' => ['file' => basename($source->files[0]), 'pos' => (int)filesize($source->files[0])],
            ];
        }

        return array_values($parts);
    }

    /**
     * Reads the logs of a part as one, a step at a time (LogJournal::read()).
     *
     * @param array<string, mixed> $options part; sources (keys of the logs to read); positions
     *     (the ones returned by the step before, none to read from the end); floors (mark
     *     point); level; search; limit; parts (whether to return the parts of the page again:
     *     the sizes of the logs, the files written since)
     * @return array<string, mixed>|null the result of the step, its entries with the name of
     *     their user; null when the viewer is not available or the part unknown
     */
    public function read(array $options): ?array
    {
        $part = is_string($options['part'] ?? null) ? $options['part'] : '';
        if (!isset(self::PARTS[$part]) || !$this->isAvailable()) {
            return null;
        }

        $keys = array_filter(is_array($options['sources'] ?? null) ? $options['sources'] : [], 'is_string');
        $sources = [];
        foreach ($this->getSources() as $source) {
            if ($source->part === $part && in_array($source->key, $keys, true)) {
                $sources[] = $source;
            }
        }

        $result = $this->journal->read(
            $sources,
            is_array($options['positions'] ?? null) ? $options['positions'] : [],
            is_array($options['floors'] ?? null) ? $options['floors'] : [],
            is_string($options['level'] ?? null) ? $options['level'] : '',
            is_string($options['search'] ?? null) ? mb_substr($options['search'], 0, self::SEARCH_LENGTH, 'UTF-8') : '',
            is_numeric($options['limit'] ?? null) ? (int)$options['limit'] : self::ENTRIES
        );
        $result['entries'] = $this->withUserNames($result['entries']);
        if (!empty($options['parts'])) {
            $result['parts'] = $this->getParts();
        }

        return $result;
    }

    /**
     * Adds the name of their user to the entries: the legacy logger writes the id of the user
     * of the request ("-none-" without one: command line, scheduler). One query for the users
     * of the entries, the deleted ones too (a log is older than a deletion).
     *
     * @param array[] $entries
     * @return array[] the entries with userName, '' when the user is not known
     */
    protected function withUserNames(array $entries): array
    {
        $ids = [];
        foreach ($entries as $entry) {
            if ($entry['user'] !== '' && $entry['user'] !== '-none-') {
                $ids[$entry['user']] = true;
            }
        }

        $names = [];
        if (!empty($ids)) {
            $this->init();
            try {
                $db = \DBManagerFactory::getInstance();
                $quoted = array_map(static fn ($id): string => $db->quoted((string)$id), array_keys($ids));
                $rows = $db->query('SELECT id, user_name FROM users WHERE id IN (' . implode(', ', $quoted) . ')');
                while ($rows && ($row = $db->fetchByAssoc($rows, false))) {
                    $names[$row['id']] = (string)$row['user_name'];
                }
            } catch (\Throwable $throwable) {
                // The logs are read to find out what fails: without the names, the ids are shown.
                $names = [];
            } finally {
                $this->close();
            }
        }

        foreach ($entries as $index => $entry) {
            $entries[$index]['userName'] = $names[$entry['user']] ?? '';
        }

        return $entries;
    }

    /**
     * Whether the logger settings are shown (they are hidden on hosted instances): as them, the
     * logs are then not to be read.
     */
    protected function isAvailable(): bool
    {
        $this->init();
        try {
            $visible = \SugarConfig::getInstance()->get('logger_visible');

            return $visible === null || !empty($visible);
        } finally {
            $this->close();
        }
    }

    /**
     * The logs of the providers, of the known parts. A file is offered once: when two providers
     * declare it (a same folder for all the logs), by the main log of a part first (the legacy
     * log), then by the first provider.
     *
     * @return LogSource[]
     */
    protected function getSources(): array
    {
        $declared = [];
        foreach ($this->providers as $provider) {
            foreach ($provider->getSources() as $source) {
                if (isset(self::PARTS[$source->part]) && !empty($source->files)) {
                    $declared[] = $source;
                }
            }
        }

        $sources = [];
        $files = [];
        foreach ([true, false] as $main) {
            foreach ($declared as $index => $source) {
                if ($source->main !== $main) {
                    continue;
                }
                $paths = array_map(static fn (string $file): string => realpath($file) ?: $file, $source->files);
                if (empty(array_intersect($paths, $files))) {
                    $files = array_merge($files, $paths);
                    $sources[$index] = $source;
                }
            }
        }
        ksort($sources);

        return array_values($sources);
    }
}
