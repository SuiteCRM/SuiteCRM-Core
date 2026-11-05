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

namespace App\Module\Schedulers\Service\ProcessHandlers;

use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Psr\Log\LoggerInterface;
use App\Module\Schedulers\LegacyHandler\SchedulerFieldsHandler;

/**
 * Process `scheduler-job-log` of the job log widget of the schedulers
 * (SchedulerFieldsHandler::getJobLog()). Admins only, as the legacy module.
 *
 * Options: id (scheduler), offset, sort (status, resolution, execute_time, date_modified),
 * direction (asc, desc).
 */
class SchedulerJobLogProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = 'scheduler-job-log';

    public function __construct(protected SchedulerFieldsHandler $fields, protected LoggerInterface $logger)
    {
    }

    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    public function requiredAuthRole(): string
    {
        return 'ROLE_ADMIN';
    }

    public function getRequiredACLs(Process $process): array
    {
        return [];
    }

    public function configure(Process $process): void
    {
        $process->setId(self::PROCESS_TYPE);
        $process->setAsync(false);
    }

    public function validate(Process $process): void
    {
        if (empty($process->getOptions()['id'])) {
            throw new \InvalidArgumentException('Process options are not defined');
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();

        try {
            $data = $this->fields->getJobLog(
                (string)$options['id'],
                (int)($options['offset'] ?? 0),
                (string)($options['sort'] ?? 'execute_time'),
                (string)($options['direction'] ?? 'desc')
            );
        } catch (\Throwable $throwable) {
            $this->logger->error('Scheduler job log failed: ' . $throwable->getMessage());
            $data = null;
        }

        $process->setStatus($data !== null ? 'success' : 'error');
        $process->setMessages([]);
        $process->setData($data ?? []);
    }
}
