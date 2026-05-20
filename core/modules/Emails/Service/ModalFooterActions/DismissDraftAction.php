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

namespace App\Module\Emails\Service\ModalFooterActions;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\Data\Service\RecordDeletionServiceInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;

class DismissDraftAction implements ProcessHandlerInterface
{

    protected const MSG_OPTIONS_NOT_FOUND = 'Process options is not defined';
    protected const PROCESS_TYPE = 'modal-dismiss-draft-email';

    public function __construct(
        protected RecordDeletionServiceInterface $recordDeletionService,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    public function getHandlerKey(): string
    {
        return self::PROCESS_TYPE;
    }

    /**
     * @inheritDoc
     */
    public function requiredAuthRole(): string
    {
        return 'ROLE_USER';
    }

    /**
     * @inheritDoc
     */
    public function getRequiredACLs(Process $process): array
    {
        $options = $process->getOptions();
        $module = $options['module'] ?? '';
        $id = $options['id'] ?? [];

        return [
            $module => [
                [
                    'action' => 'delete',
                    'id' => $id
                ],
            ],
        ];
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
     *
     * @throws InvalidArgumentException
     */
    public function validate(Process $process): void
    {
        $options = $process->getOptions();

        if (empty($options)) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }

        $module = $options['module'] ?? '';

        if (empty($module)) {
            throw new InvalidArgumentException('Process option "module" is not defined');
        }

        if ($module !== 'emails'){
            throw new InvalidArgumentException('Module is not supported for draft saving');
        }

    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function run(Process $process): void
    {
        $options = $process->getOptions();

        $id = $options['id'] ?? null;
        if (!$id) {
            $process->setStatus('success');
            $process->setMessages(['LBL_UNABLE_TO_GET_DRAFT_ID']);
            return;
        }

        $deleted = $this->recordDeletionService->deleteRecord('Emails', $id);

        if (!$deleted) {
            $process->setStatus('error');
            $process->setMessages(['LBL_DRAFT_DELETED_UNSUCCESSFULLY']);
            return;
        }

        $data = [
            'handler' => 'emit-event',
            'params' => [
                'event' => 'refresh-drafts',
                'payload' => true,
            ],
        ];

        $process->setStatus('success');
        $process->setMessages(['LBL_DRAFT_DELETED_SUCCESSFULLY']);
        $process->setData($data);
    }
}
