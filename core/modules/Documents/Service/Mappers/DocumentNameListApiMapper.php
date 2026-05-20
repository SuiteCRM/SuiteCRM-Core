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

namespace App\Module\Documents\Service\Mappers;


use App\Data\Entity\Record;
use App\Data\Service\Record\ApiRecordMappers\ApiRecordFieldMapperInterface;
use App\Data\Service\RecordProviderInterface;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\Service\FieldDefinitionsProviderInterface;
use App\MediaObjects\Repository\MediaObjectManagerInterface;
use App\Module\Documents\LegacyHandler\DocumentsManagerInterface;
use App\Module\Service\ModuleNameMapperInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(lazy: true)]
class DocumentNameListApiMapper implements ApiRecordFieldMapperInterface {

    use DocumentNameApiMapperTrait;


    public function __construct(
        protected FieldDefinitionsProviderInterface $fieldDefinitionsProvider,
        protected MediaObjectManagerInterface $mediaObjectManager,
        protected ModuleNameMapperInterface $moduleNameMapper,
        protected RecordProviderInterface $recordProvider,
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
        return 'documents-name-retrieve-mapper';
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
        return ['list'];
    }

    public function toInternal(Record $record, FieldDefinition $fieldDefinitions): void
    {
    }

    /**
     * @throws \Exception
     */
    public function toExternal(Record $record, FieldDefinition $fieldDefinitions): void
    {
        $revisionFieldDef = $this->fieldDefinitionsProvider->getFieldDefinition('document-revisions', 'filename');
        if (!$revisionFieldDef) {
            return;
        }

        $storageType = $revisionFieldDef['metadata']['storage_type'] ?? 'private-documents';
        if (empty($storageType)) {
            return;
        }

        $latestRevisionId = $this->documentsManager->getLatestRevisionId($record->getId() ?? null);

        $documentRevision = $this->recordProvider->getRecord('DocumentRevisions', $latestRevisionId);

        $this->injectMediaObject(
            $documentRevision,
            $record,
            'filename',
            $this->getField(),
            $storageType,
            $this->mediaObjectManager,
            $this->moduleNameMapper
        );
    }
}
