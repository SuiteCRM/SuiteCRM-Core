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

namespace App\Module\Documents\Service\SaveHandlers;

use App\Data\Entity\Record;
use App\Data\Service\Record\RecordSaveHandlers\RecordFieldSaveHandlerInterface;
use App\Data\Service\RecordProviderInterface;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\Service\FieldDefinitionsProviderInterface;
use App\MediaObjects\Entity\MediaObjectInterface;
use App\MediaObjects\Repository\MediaObjectManagerInterface;
use App\Module\Documents\LegacyHandler\DocumentsManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Mime\MimeTypes;

#[Autoconfigure(lazy: true)]
class CreateDocumentRevisionSaveHandler implements RecordFieldSaveHandlerInterface
{
    public function __construct(
        protected RecordProviderInterface $recordProvider,
        protected MediaObjectManagerInterface $mediaObjectManager,
        protected FieldDefinitionsProviderInterface $fieldDefinitionsProvider,
        protected DocumentsManagerInterface $documentsManager,
    )
    {
    }

    public function getField(): string
    {
        return 'filename';
    }

    public function replaceDefaultTypeMapper(): bool
    {
        return true;
    }

    public function getKey(): string
    {
        return 'documents-save-handler';
    }

    public function getModule(): string
    {
        return 'documents';
    }

    public function getOrder(): int
    {
        return 0;
    }

    public function getModes(): array
    {
        return ['after-save'];
    }

    /**
     * @throws \Exception
     */
    public function run(?Record $previousVersion, Record $inputRecord, ?Record $savedRecord, FieldDefinition $fieldDefinition): void
    {
        if ($savedRecord === null) {
            return;
        }

        $field = $this->getField();
        $vardef = $fieldDefinition->getVardef();
        $fieldVardef = $vardef[$field];

        if (empty($fieldVardef)) {
            return;
        }

        $attributes = $inputRecord->getAttributes();

        if (!isset($attributes[$field])) {
            return;
        }

        $fileFieldData = $attributes[$field];
        $mediaObjectId = $fileFieldData['id'] ?? '';

        if (empty($mediaObjectId)) {
            return;
        }

        $currentDocumentRevision = $this->getCurrentRevision($savedRecord->getId());

        if (empty($currentDocumentRevision?->getId())) {
            $mediaObject = $this->getMediaObject($mediaObjectId, $fieldVardef['metadata']['storage_type'] ?? 'private-documents');
            $this->createDocumentRevision($savedRecord, $mediaObject, $fieldDefinition);
            return;
        }

        $documentRevisionMediaObject = $this->getDocumentRevisionMediaObject($currentDocumentRevision);

        if ($documentRevisionMediaObject?->getId() === $mediaObjectId){
            return;
        }

        $mediaObject = $this->getMediaObject($mediaObjectId, $fieldVardef['metadata']['storage_type'] ?? 'private-documents');

        $this->createDocumentRevision($savedRecord, $mediaObject, $fieldDefinition, $currentDocumentRevision);
    }

    protected function createDocumentRevision(
        ?Record $savedRecord,
        MediaObjectInterface $mediaObject,
        FieldDefinition $fieldDefinition,
        Record $currentRevision = null
    ): void
    {
        $revisionId = $fieldDefinition->getVardef()['revision']['default'] ?? '1';

        if ($currentRevision && $currentRevision->getAttributes()['revision']){
            $revisionId = $this->documentsManager->increaseRevisionNumber($currentRevision->getAttributes()['revision']);
        }


        $extension = $this->documentsManager->getExtensionFromMimeType($mediaObject->getMimeType() ?? '');

        $record = new Record();
        $record->setModule('document-revisions');
        $record->setId('');
        $record->setAttributes([
            'document_id' => $savedRecord?->getId() ?? '',
            'doc_type' => $savedRecord?->getAttributes()['doc_type'] ?? '',
            'created_by' => $savedRecord?->getAttributes()['created_by'] ?? '',
            'revision' => $revisionId,
            'filename' => $mediaObject->getOriginalName() ?? '',
            'file_mime_type' => $mediaObject->getMimeType() ?? '',
            'file_size' => $mediaObject->getSize() ?? '',
            'file_ext' => $extension,
            'date_modified' => date('Y-m-d H:i:s'),
        ]);

        $storageType = $this->getDocumentRevisionStorageType();

        if (empty($storageType)) {
            return;
        }

        $savedRevision = $this->recordProvider->saveRecord($record);

        $mediaObject->setParentType('DocumentRevisions');
        $mediaObject->setParentId($savedRevision->getId());
        $mediaObject->setParentField('filename');
        $mediaObject->setTemporary(false);


        $this->mediaObjectManager->saveMediaObject($storageType, $mediaObject);
    }

    protected function getDocumentRevisionMediaObject(Record $currentDocumentRevision): ?MediaObjectInterface
    {
        return $this->mediaObjectManager->getLinkedMediaObjects(
            $this->getDocumentRevisionStorageType(),
            'DocumentRevisions',
            $currentDocumentRevision->getId(),
            'filename'
        )[0] ?? null;
    }

    protected function getMediaObject(string $mediaObjectId, string $storageType): ?MediaObjectInterface
    {
        return $this->mediaObjectManager->getMediaObject($storageType, $mediaObjectId);
    }

    protected function getDocumentRevisionStorageType(): string
    {
        $fieldDef = $this->fieldDefinitionsProvider->getFieldDefinition('document-revisions', 'filename');

        $storageType = $fieldDef['metadata']['storage_type'] ?? 'private-documents';
        if (empty($storageType)) {
            return '';
        }

        return $storageType;
    }

    /**
     * @throws \Exception
     */
    protected function getCurrentRevision(?string $id): Record
    {
        $revisionId = $this->documentsManager->getLatestRevisionId($id ?? '');
        return $this->recordProvider->getRecord('document-revisions', $revisionId);
    }


}
