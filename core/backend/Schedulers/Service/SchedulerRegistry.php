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

namespace App\Schedulers\Service;

use ApiPlatform\Exception\ItemNotFoundException;

class SchedulerRegistry {

    protected const MSG_SCHEDULER_NOT_FOUND = 'Scheduler is not defined';

    /**
     * @var SchedulerInterface[]
     */
    protected $registry = [];

    /**
     * SchedulerRegistry constructor.
     * @param iterable $schedulers
     */
    public function __construct(iterable $schedulers)
    {
        /**
         * @var SchedulerInterface[] $schedulers
         */
        $schedulers = iterator_to_array($schedulers);

        foreach ($schedulers as $scheduler) {
            $key = $scheduler->getKey();
            $this->registry[$key] = $scheduler;
        }

    }

    /**
     * @param String $schedulerKey
     * @param SchedulerInterface $scheduler
     */
    public function register(string $schedulerKey, SchedulerInterface $scheduler): void
    {
        $this->registry[$schedulerKey] = $scheduler;
    }

    /**
     * Get the scheduler for the given key
     * @param string $schedulerKey
     * @return SchedulerInterface
     */
    public function get(string $schedulerKey): SchedulerInterface
    {

        if (empty($this->registry[$schedulerKey])) {
            throw new ItemNotFoundException(self::MSG_SCHEDULER_NOT_FOUND);
        }

        return $this->registry[$schedulerKey];
    }

    public function getAllKeys(): array {
        return array_keys($this->registry);
    }
}
