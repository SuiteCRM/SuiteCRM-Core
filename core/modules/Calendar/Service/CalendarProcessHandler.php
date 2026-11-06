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

namespace App\Module\Calendar\Service;

use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Psr\Log\LoggerInterface;
use App\Module\Calendar\LegacyHandler\CalendarHandler;

/**
 * Process `calendar-activities` of the calendar view (CalendarHandler).
 *
 * Options, by action:
 * - load: view (day, week, month, sharedWeek, sharedMonth), date (Y-m-d), the
 *   period, its activities by user, the settings and the users of the shared
 *   calendar;
 * - move: module (Meetings, Calls), id, start (Y-m-d H:i:s), moves the activity;
 * - resize: module (Meetings, Calls), id, minutes, changes its duration;
 * - remove: module, id, deletes the activity;
 * - settings: settings, saves the calendar settings;
 * - users: ids, saves the users of the shared calendar.
 */
class CalendarProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = 'calendar-activities';

    protected const ACTIONS = ['load', 'move', 'resize', 'remove', 'settings', 'users'];

    public function __construct(
        protected CalendarHandler $builder,
        protected LoggerInterface $logger
    ) {
    }

    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    public function requiredAuthRole(): string
    {
        return 'ROLE_USER';
    }

    /**
     * @inheritDoc
     * The calendar access (Calendar module) and the access to each activity are
     * checked by the builder, as the legacy calendar.
     */
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
        $options = $process->getOptions();

        if (!is_array($options) || !in_array($options['action'] ?? null, self::ACTIONS, true)) {
            throw new \InvalidArgumentException('Process options are not defined');
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();

        try {
            $data = $this->runAction($options);
        } catch (\Throwable $throwable) {
            $this->logger->error('Calendar ' . $options['action'] . ' failed: ' . $throwable->getMessage());
            $data = null;
        }

        if ($options['action'] === 'load') {
            $process->setStatus($data !== null ? 'success' : 'error');
            $process->setMessages($data !== null ? [] : ['LBL_ERROR_LOADING']);
            $process->setData(($data ?? []) + ['labels' => $this->builder->labels()]);

            return;
        }

        $done = !empty($data['done']);
        $process->setStatus($done ? 'success' : 'error');
        $process->setMessages($done ? [] : ['LBL_ERROR_SAVING']);
        $process->setData(['done' => $done]);
    }

    protected function runAction(array $options): ?array
    {
        $module = (string)($options['module'] ?? '');
        $id = (string)($options['id'] ?? '');

        return match ($options['action']) {
            'load' => $this->builder->load((string)($options['view'] ?? ''), (string)($options['date'] ?? '')),
            'move' => ['done' => $this->builder->move($module, $id, (string)($options['start'] ?? ''))],
            'resize' => ['done' => $this->builder->resize($module, $id, (int)($options['minutes'] ?? 0))],
            'remove' => ['done' => $this->builder->remove($module, $id)],
            'settings' => ['done' => $this->builder->saveSettings(
                is_array($options['settings'] ?? null) ? $options['settings'] : []
            )],
            'users' => ['done' => $this->builder->saveSharedUsers(
                is_array($options['ids'] ?? null) ? $options['ids'] : []
            )],
        };
    }
}
