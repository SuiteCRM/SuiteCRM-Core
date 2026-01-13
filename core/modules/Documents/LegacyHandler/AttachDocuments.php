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

namespace App\Module\Documents\LegacyHandler;

use ApiPlatform\Exception\InvalidArgumentException;
use App\FieldDefinitions\Service\FieldDefinitionsProviderInterface;
use App\MediaObjects\Repository\MediaObjectManagerInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Psr\Log\LoggerInterface;

class AttachDocuments implements ProcessHandlerInterface {

    protected const MSG_OPTIONS_NOT_FOUND = 'Process options is not defined';
    protected const PROCESS_TYPE = 'attach-documents';

    public function __construct(
        protected MediaObjectManagerInterface $mediaObjectManager,
        protected FieldDefinitionsProviderInterface $fieldDefinitionsProvider,
        protected LoggerInterface $logger,
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

            $attributes = $record['attributes'] ?? [];
            $revisionId = $attributes['document_revision_id'] ?? null;
            if (!$revisionId) {
                $this->logger->warn('Cannot find revision id for document' . $record['id'] ?? '');
                $failedRecords = true;
                continue;
            }

            $storageType = $this->getStorageType();

            if (!$storageType) {
                $this->logger->warn('Unable to get storage type for Document Revisions');
                $failedRecords = true;
                continue;
            }

            $linkedMediaObject = $this->mediaObjectManager->getLinkedMediaObjects(
                $storageType,
                'DocumentRevisions',
                $revisionId,
                'filename',
            );

            if (empty($linkedMediaObject)) {
                $this->logger->warn('Unable to retrieve linked media object for revision id ' . $revisionId);
                $failedRecords = true;
                continue;
            }

            $contentUrl = $this->mediaObjectManager->buildContentUrl($storageType, $linkedMediaObject[0]);
            $linkedMediaObject[0]->setContentUrl($contentUrl);
            $record = $this->mediaObjectManager->mapToRecord($storageType, $linkedMediaObject[0]);
            $mediaObjects = [ ...$mediaObjects, $record->toArray()];
        }

        $data = [
            'failed_records' => $failedRecords,
            'media_objects' => $mediaObjects,
        ];

        $process->setStatus('success');
        $process->setData($data);
    }

    protected function getStorageType(): ?string
    {
        $fieldDef = $this->fieldDefinitionsProvider->getFieldDefinition('document-revisions', 'filename');
        if (!$fieldDef) {
            return null;
        }

        $storageType = $fieldDef['metadata']['storage_type'] ?? '';
        if (empty($storageType)) {
            return null;
        }

        return $storageType;
    }
}
