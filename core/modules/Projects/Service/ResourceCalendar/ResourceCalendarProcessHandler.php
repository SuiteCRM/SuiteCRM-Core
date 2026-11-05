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

namespace App\Module\Projects\Service\ResourceCalendar;

use App\Module\Projects\LegacyHandler\ResourceCalendarHandler;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;

/**
 * Process `project-resource-calendar` of the resource calendar page: the
 * calendar of a chart type (weekly, monthly, quarterly) from a start date, for
 * the projects, users and contacts chosen (ResourceCalendarHandler).
 */
class ResourceCalendarProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = 'project-resource-calendar';

    public function __construct(protected ResourceCalendarHandler $handler)
    {
    }

    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    public function requiredAuthRole(): string
    {
        return 'ROLE_USER';
    }

    public function getRequiredACLs(Process $process): array
    {
        return ['Project' => [['action' => 'view']]];
    }

    public function configure(Process $process): void
    {
        $process->setId(self::PROCESS_TYPE);
        $process->setAsync(false);
    }

    public function validate(Process $process): void
    {
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions() ?? [];
        $data = $this->handler->build([
            'type' => (string)($options['type'] ?? ''),
            'start' => (string)($options['start'] ?? ''),
            'projects' => is_array($options['projects'] ?? null) ? $options['projects'] : [],
            'users' => is_array($options['users'] ?? null) ? $options['users'] : [],
            'contacts' => is_array($options['contacts'] ?? null) ? $options['contacts'] : [],
        ]);

        $process->setStatus($data !== null ? 'success' : 'error');
        $process->setMessages($data !== null ? [] : ['LBL_NOT_AUTHORIZED']);
        $process->setData(($data ?? []) + ['labels' => $this->handler->labels()]);
    }
}
