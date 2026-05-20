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

namespace App\Module\Service\Fields\Attachments\ProcessHandlers;

use ApiPlatform\Exception\InvalidArgumentException;
use App\FieldDefinitions\Service\FieldDefinitionsProviderInterface;
use App\MediaObjects\Repository\MediaObjectManagerInterface;
use App\Module\Service\Fields\Attachments\AttachmentTypeHandlers\AttachmentTypeHandlers;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Psr\Log\LoggerInterface;

class RetrieveAttachmentMediaObjects implements ProcessHandlerInterface {

    protected const MSG_OPTIONS_NOT_FOUND = 'Process options is not defined';
    protected const PROCESS_TYPE = 'retrieve-attachment-media-objects';

    public function __construct(
        protected MediaObjectManagerInterface $mediaObjectManager,
        protected FieldDefinitionsProviderInterface $fieldDefinitionsProvider,
        protected LoggerInterface $logger,
        protected AttachmentTypeHandlers $attachmentTypeHandlers,
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

    public function getRequiredACLs(Process $process): array
    {
        $options = $process->getOptions();
        $baseIds = $options['ids'] ?? [];
        $records = $options['records'] ?? [];
        foreach ($records as $record) {
            $baseIds[] = $record['id'];
        }

        return [
            'Documents' => [
                [
                    'action' => 'view',
                    'ids' => $baseIds
                ]
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
        if (empty($process->getOptions())) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $records = $options['records'] ?? [];

        $failedRecords = false;
        $mediaObjects = [];

        foreach ($records as $record) {
            $type = $record['module'] ?? null;
            $id = $record['id'] ?? null;
            if (!$type || !$id) {
                continue;
            }

            $attachmentTypeHandler = $this->attachmentTypeHandlers->getHandler($type);
            if (!$attachmentTypeHandler) {
                $this->logger->warn('No attachment type handler found for type ' . $type);
                continue;
            }

            $attachments = $attachmentTypeHandler->getAttachments($type, $id);

            if (!$attachments) {
                $this->logger->warn('No attachment data found for record ' . ($record['id'] ?? 'unknown'));
                $failedRecords = true;
                continue;
            }

            $mediaObjects = [...$mediaObjects, ...$attachments];
        }

        $data = [
            'failed_records' => $failedRecords,
            'media_objects' => $mediaObjects,
        ];

        $process->setStatus('success');
        $process->setData($data);
    }


}
