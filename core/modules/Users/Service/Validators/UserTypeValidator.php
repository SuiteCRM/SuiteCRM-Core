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

namespace App\Module\Users\Service\Validators;

use App\Module\Users\LegacyHandler\UserProfileHandler;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;

/**
 * Validation of the User Type field of the user edit view (asyncValidators of the field in the
 * Users vardefs): as in the legacy edit view, users do not change the type of their own
 * profile. The message is shown under the field and the record is not saved.
 */
class UserTypeValidator implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = 'user-type-validation';

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

    /**
     * Nothing is read but the type of the current user.
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
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions() ?? [];
        $userId = (string)($options['attributes']['id'] ?? $options['originalAttributes']['id'] ?? '');
        $type = (string)($options['value'] ?? '');

        $process->setMessages([]);

        if ($this->handler->isOwnTypeChange($userId, $type)) {
            $process->setStatus('error');
            $process->setData(['errors' => ['startLabelKey' => UserProfileHandler::OWN_USER_TYPE_LABEL]]);

            return;
        }

        $process->setStatus('success');
        $process->setData([]);
    }
}
