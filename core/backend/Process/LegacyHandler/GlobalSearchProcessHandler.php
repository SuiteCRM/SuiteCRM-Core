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

namespace App\Process\LegacyHandler;

use App\Data\Service\RecordProviderInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use App\Search\Service\GlobalSearchProviderInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

/**
 * Global search used by the search view.
 *
 * Options:
 * - searchTerm: searched text;
 * - modules (optional): front-end names of the modules to search in, saved as the user's choice;
 * - module (optional): module to page through, empty for the first results of every module;
 * - offset (optional): first result of the module page.
 *
 * Data: the module choice, the result count per module and, for every module shown,
 * its records as loaded by the record view (records the user may not view are left out).
 */
class GlobalSearchProcessHandler implements ProcessHandlerInterface
{
    protected const PROCESS_TYPE = 'global-search';
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options are not defined';

    // Results per module when every module is shown, and per page of a module.
    protected const PREVIEW_SIZE = 3;
    protected const PAGE_SIZE = 20;

    public function __construct(
        protected GlobalSearchProviderInterface $globalSearch,
        protected RecordProviderInterface $recordProvider,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    /**
     * @inheritDoc
     */
    public function requiredAuthRole(): string
    {
        return 'ROLE_USER';
    }

    /**
     * @inheritDoc
     * Module access is checked by the search and record access when loading each record.
     */
    public function getRequiredACLs(Process $process): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function configure(Process $process): void
    {
        $process->setId(self::PROCESS_TYPE);
        $process->setAsync(false);
    }

    /**
     * @inheritDoc
     */
    public function validate(Process $process): void
    {
        $options = $process->getOptions();

        if (!is_array($options) || !is_string($options['searchTerm'] ?? null)) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }
    }

    /**
     * @inheritDoc
     */
    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $modules = isset($options['modules']) && is_array($options['modules'])
            ? array_values(array_filter($options['modules'], 'is_string'))
            : null;

        try {
            $result = $this->globalSearch->search(
                trim($options['searchTerm']),
                $modules,
                (string)($options['module'] ?? ''),
                max(0, (int)($options['offset'] ?? 0)),
                self::PREVIEW_SIZE,
                self::PAGE_SIZE
            );
        } catch (Throwable $throwable) {
            // e.g. the search engine is not reachable
            $this->logger->error('Global search failed: ' . $throwable->getMessage());
            $process->setStatus('error');
            $process->setMessages(['LBL_GLOBAL_SEARCH_UNAVAILABLE']);
            $process->setData([]);

            return;
        }

        $sections = [];
        foreach ($result['ids'] as $module => $ids) {
            $sections[] = [
                'module' => $module,
                'count' => $result['counts'][$module] ?? 0,
                'records' => $this->getRecords($module, $ids),
            ];
        }

        $process->setStatus('success');
        $process->setMessages([]);
        $process->setData([
            'modules' => $result['modules'],
            'counts' => $result['counts'],
            'module' => $result['module'],
            'offset' => max(0, (int)($options['offset'] ?? 0)),
            'pageSize' => self::PAGE_SIZE,
            'sections' => $sections,
        ]);
    }

    /**
     * Records the user may view, in the search engine's order.
     */
    protected function getRecords(string $module, array $ids): array
    {
        $records = [];

        foreach ($ids as $id) {
            try {
                $record = $this->recordProvider->getRecord($module, (string)$id);
            } catch (AccessDeniedHttpException $exception) {
                continue;
            }

            // Deleted since it was indexed
            if (empty($record->getAttributes()['id'])) {
                continue;
            }

            $records[] = $record->toArray();
        }

        return $records;
    }
}
