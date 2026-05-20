<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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


namespace App\Schedulers\Service\Scheduler\Jobs;

use App\Data\Service\Record\RecordDeleteHandlers\RecordDeleteHandlerRunnerInterface;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use App\Schedulers\Service\SchedulerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PruneDatabaseScheduler extends LegacyHandler implements SchedulerInterface
{
    public const SCHEDULER_KEY = 'scheduler::prune-database';

    /**
     * LegacyHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param RequestStack $requestStack
     * @param RecordDeleteHandlerRunnerInterface $deleteHandlerRunner
     * @param ModuleNameMapperInterface $moduleNameMapper
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        protected RecordDeleteHandlerRunnerInterface $deleteHandlerRunner,
        protected ModuleNameMapperInterface $moduleNameMapper
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $requestStack
        );
    }

    public function getKey(): string
    {
        return self::SCHEDULER_KEY;
    }

    public function getHandlerKey(): string
    {
        return self::SCHEDULER_KEY;
    }

    public function run(): bool
    {
        $moduleNameMapper = $this->moduleNameMapper;
        $deleteHandlerRunner = $this->deleteHandlerRunner;

        return $this->callLegacyPruneDatabase(
            static function (string $legacyModuleName, string $id) use ($moduleNameMapper, $deleteHandlerRunner) {

                if (empty($legacyModuleName) || empty($id)) {
                    return;
                }

                $module = $moduleNameMapper->toFrontEnd($legacyModuleName);

                $deleteHandlerRunner->run($module, $id, 'before-hard-delete');
            }
        );
    }

    protected function callLegacyPruneDatabase(callable $onRecordDelete = null): bool
    {
        $this->init();

        require_once 'include/portability/Schedulers/PruneDatabaseService.php';

        $pruneDatabaseService = new \PruneDatabaseService();

        $result = $pruneDatabaseService->prune($onRecordDelete);
        $this->close();

        return $result;
    }

}
