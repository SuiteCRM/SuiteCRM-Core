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

namespace App\Module\DocumentRevisions\Service\RecordActions;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\Data\Service\RecordDeletionServiceInterface;
use App\Module\Documents\LegacyHandler\DocumentsManagerInterface;
use App\Module\Service\ModuleNameMapperInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;

class DocumentRevisionDeleteRecordAction implements ProcessHandlerInterface
{

    protected const MSG_OPTIONS_NOT_FOUND = 'Process options are not defined';

    protected const PROCESS_TYPE = 'record-document-revision-delete';

    public function __construct(
        protected ModuleNameMapperInterface $moduleNameMapper,
        protected RecordDeletionServiceInterface $recordDeletionProvider,
        protected DocumentsManagerInterface $documentsManager
    )
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
     * @inheritDoc
     */
    public function validate(Process $process): void
    {
        if (empty($process->getOptions())) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }

        $options = $process->getOptions();

        if (empty($options['module']) || empty($options['action']) || empty($options['id'])) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }
    }

    /**
     * @inheritDoc
     */
    public function configure(Process $process): void
    {
        $process->setId(static::PROCESS_TYPE);
        $process->setAsync(false);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredACLs(Process $process): array
    {
        $module = 'Documents';

        return [
            $module => [
                [
                    'action' => 'delete',
                    'record' => $options['id'] ?? ''
                ]
            ],
        ];
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $payload = $options['payload'] ?? [];

        $recordId = $options['id'] ?? '';
        $baseRecordId = $payload['baseRecordId'] ?? '';

        if (!$recordId || !$baseRecordId) {
            $process->setStatus('error');
            $process->setMessages(['LBL_NO_RECORD_ID_PROVIDED']);
            return;
        }

        $latestRevisionId = $this->documentsManager->getLatestRevisionId($baseRecordId);

        if ($recordId === $latestRevisionId) {
            $process->setStatus('error');
            $process->setMessages(['LBL_CANNOT_DELETE_LATEST_REVISION']);

            return;
        }

        $result = $this->deleteRecord($process);

        $process->setStatus('success');
        $process->setMessages(['LBL_RECORD_DELETE_SUCCESS']);
        if (!$result) {
            $process->setStatus('error');
            $process->setMessages(['LBL_ACTION_ERROR']);

            return;
        }

        $responseData = [
            'handler' => 'redirect',
            'params' => [
                'route' => 'documents/record/' . $baseRecordId,
                'queryParams' => []
            ],
            'reloadRecentlyViewed' => true,
            'reloadFavorites' => true
        ];

        $process->setData($responseData);

    }

    /**
     * @param Process $process
     * @return bool
     */
    protected function deleteRecord(Process $process): bool
    {
        $options = $process->getOptions();

        return $this->recordDeletionProvider->deleteRecord(
            $this->moduleNameMapper->toLegacy($options['module']),
            $options['id']
        );
    }
}
