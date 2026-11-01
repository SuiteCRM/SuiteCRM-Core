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

namespace App\Module\Users\Service\RecordActions;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\Module\Users\LegacyHandler\UserProfileHandler;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;

/**
 * "Change Password" action of the user record view: the current, new and confirmed passwords
 * entered in the action modal are given to User::change_password() (password rules, current
 * password unless an administrator changes another user's password).
 */
class ChangePasswordAction implements ProcessHandlerInterface
{
    protected const PROCESS_TYPE = 'record-user-change-password';

    public function __construct(protected UserProfileHandler $handler)
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
        $options = $process->getOptions();

        return [
            'users' => [
                [
                    'action' => 'edit',
                    'record' => $options['id'] ?? '',
                ],
            ],
        ];
    }

    public function configure(Process $process): void
    {
        $process->setId(self::PROCESS_TYPE);
        $process->setAsync(false);
    }

    public function validate(Process $process): void
    {
        $options = $process->getOptions();

        if (empty($options['id']) || ($options['module'] ?? '') !== 'users') {
            throw new InvalidArgumentException('Process options are not defined');
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $fields = $options['params']['fields'] ?? [];
        $value = static fn (string $name): string => (string)($fields[$name]['value'] ?? '');

        [$changed, $message] = $this->handler->changePasswordFromAction(
            (string)$options['id'],
            $value('old_password'),
            $value('new_password'),
            $value('confirm_new_password')
        );

        // Translated messages: the front end shows a message that is not an application label as is.
        $process->setStatus($changed ? 'success' : 'error');
        $process->setMessages([$message]);
        $process->setData([]);
    }
}
