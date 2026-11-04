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

namespace App\Module\OAuthTokens\Service\RecordActions;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\Data\Service\RecordDeletionServiceInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;

/**
 * Delete of a token in the Tokens subpanel of an OAuth key (line action oauth-token-delete), as
 * the Delete button of the legacy subpanel: the token is removed and the consumer has to be
 * authorized again. The OAuth keys are for the admins, as in the legacy pages.
 */
class OAuthTokenDeleteRecordAction implements ProcessHandlerInterface
{
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options are not defined';

    protected const PROCESS_TYPE = 'record-oauth-token-delete';

    public function __construct(protected RecordDeletionServiceInterface $recordDeletionProvider)
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

    /**
     * @inheritDoc
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
        if (empty($process->getOptions()['id'])) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }
    }

    public function run(Process $process): void
    {
        $id = (string)($process->getOptions()['id'] ?? '');

        if (!$this->recordDeletionProvider->deleteRecord('OAuthTokens', $id)) {
            $process->setStatus('error');
            $process->setMessages(['LBL_ACTION_ERROR']);

            return;
        }

        $process->setStatus('success');
        $process->setMessages(['LBL_RECORD_DELETE_SUCCESS']);
        $process->setData(['reload' => true]);
    }
}
