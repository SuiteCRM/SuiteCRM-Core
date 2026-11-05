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

namespace App\Module\Activities\Service\RecordActions;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\Module\Activities\LegacyHandler\ActivityCloser;
use App\Module\Service\ModuleNameMapperInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;

/**
 * Call and meeting record actions that close the activity (status Held), as the
 * legacy "Close" buttons do.
 */
abstract class CloseActivityBaseAction implements ProcessHandlerInterface
{
    protected const MODULES = ['calls', 'meetings'];
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options are not defined';

    public function __construct(
        protected ActivityCloser $closer,
        protected ModuleNameMapperInterface $moduleNameMapper
    ) {
    }

    /**
     * Data of the closed activity for the action handler.
     */
    abstract protected function getSuccessData(string $module, string $id, array $options): array;

    public function requiredAuthRole(): string
    {
        return 'ROLE_USER';
    }

    public function getRequiredACLs(Process $process): array
    {
        $options = $process->getOptions();

        return [
            (string)($options['module'] ?? '') => [
                [
                    'action' => 'edit',
                    'record' => $options['id'] ?? '',
                ],
            ],
        ];
    }

    public function configure(Process $process): void
    {
        $process->setId($this->getProcessType());
        $process->setAsync(false);
    }

    public function validate(Process $process): void
    {
        $options = $process->getOptions();

        if (empty($options['id']) || !in_array($options['module'] ?? '', self::MODULES, true)) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $module = (string)$options['module'];
        $id = (string)$options['id'];

        if (!$this->closer->closeActivity($this->moduleNameMapper->toLegacy($module), $id)) {
            $process->setStatus('error');
            $process->setMessages(['LBL_ACTION_ERROR']);
            $process->setData(['reload' => true]);

            return;
        }

        $process->setStatus('success');
        $process->setMessages([]);
        $process->setData($this->getSuccessData($module, $id, $options));
    }
}
