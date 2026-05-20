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

namespace App\Schedulers\Runners;

use ApiPlatform\Exception\ItemNotFoundException;
use App\Schedulers\Service\SchedulerRegistry;
use Psr\Log\LoggerInterface;

class SchedulerRunner
{

    protected SchedulerRegistry $schedulerRegistry;
    protected LoggerInterface $logger;

    public function __construct(
        SchedulerRegistry $schedulerRegistry,
        LoggerInterface $logger
    ) {
        $this->schedulerRegistry = $schedulerRegistry;
        $this->logger = $logger;
    }

    public function run(\SugarBean $job): bool
    {

        if (!($job->target ?? false)) {
            $this->logger->error('SchedulerRunner::run | Unable to get target for Job with id' . $job->id);
            return false;
        }

        $scheduler = null;
        try {
            $scheduler = $this->schedulerRegistry->get($job->target) ?? [];
        } catch (ItemNotFoundException $e) {
            $this->logger->error('SchedulerRunner::run | Unable to get scheduler for Job with target - ' . $job->target ?? '');
        }

        if (empty($scheduler)) {
            return false;
        }

        $this->logger->debug('Schedulers:SchedulerRunner::run | Running scheduler with target - ' . $job->target ?? '');

        $result = false;
        try {
            $result = $scheduler->run();
        } catch (\Throwable $t) {
            $this->logger->error(
                'Schedulers:SchedulerRunner::run | Error running scheduler with target - ' . $job->target ?? '' . ' |  message - ' . $t->getMessage() ?? '',
                [
                    'message' => $t->getMessage(),
                    'trace' => $t->getTrace(),
                ]
            );
        }

        return $result;
    }
}
