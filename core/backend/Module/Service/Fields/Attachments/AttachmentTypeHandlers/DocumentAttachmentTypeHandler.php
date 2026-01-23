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

namespace App\Module\Service\Fields\Attachments\AttachmentTypeHandlers;

use App\Data\Service\RecordProviderInterface;
use App\FieldDefinitions\Service\FieldDefinitionsProviderInterface;
use App\MediaObjects\Repository\MediaObjectManagerInterface;
use Psr\Log\LoggerInterface;

class DocumentAttachmentTypeHandler implements AttachmentTypeHandlerInterface
{
    public function __construct(
        protected FieldDefinitionsProviderInterface $fieldDefinitionsProvider,
        protected MediaObjectManagerInterface $mediaObjectManager,
        protected LoggerInterface $logger,
        protected RecordProviderInterface $recordProvider,
    )
    {
    }

    public function getType(): string
    {
        return 'documents';
    }

    public function getAttachments(string $module, string $id): ?array
    {

        $mediaObjects = [];
        try {
            $record = $this->recordProvider->getRecord($module, $id);
        } catch (\Exception $e) {
            $this->logger->warn('Unable to retrieve record for module ' . $module . ' with id ' . $id . '. Error: ' . $e->getMessage());
            return null;
        }

        $attributes = $record->getAttributes() ?? [];
        $revisionId = $attributes['document_revision_id'] ?? null;
        if (!$revisionId) {
            $this->logger->warn('Cannot find revision id for document' . $id);
            return null;
        }

        $storageType = $this->getStorageType();

        if (!$storageType) {
            $this->logger->warn('Unable to get storage type for Document Revisions');
            return null;
        }

        $linkedMediaObjects = $this->mediaObjectManager->getLinkedMediaObjects(
            $storageType,
            'DocumentRevisions',
            $revisionId,
            'filename',
        );

        if (empty($linkedMediaObjects)) {
            $this->logger->warn('Unable to retrieve linked media object for revision id ' . $revisionId);
            return null;
        }

        foreach ($linkedMediaObjects as $linkedMediaObject) {
            $contentUrl = $this->mediaObjectManager->buildContentUrl($storageType, $linkedMediaObject);
            $linkedMediaObject->setContentUrl($contentUrl);
            $mediaObjectRecord = $this->mediaObjectManager->mapToRecord($storageType, $linkedMediaObject);
            $attributes = $mediaObjectRecord->getAttributes();
            $attributes['attachmentType'] = 'documents';
            $attributes['source_record_id'] = $id;
            $mediaObjectRecord->setAttributes($attributes);
            $mediaObjects[] = $mediaObjectRecord->toArray();
        }

        return $mediaObjects;
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
