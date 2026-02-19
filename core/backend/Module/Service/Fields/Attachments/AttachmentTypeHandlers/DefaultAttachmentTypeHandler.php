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

use App\FieldDefinitions\Service\FieldDefinitionsProviderInterface;
use App\MediaObjects\Repository\MediaObjectManagerInterface;
use App\Module\Service\ModuleNameMapperInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(lazy: true)]
class DefaultAttachmentTypeHandler implements AttachmentTypeHandlerInterface
{

    public function __construct(
        protected FieldDefinitionsProviderInterface $fieldDefinitionsProvider,
        protected MediaObjectManagerInterface       $mediaObjectManager,
        protected ModuleNameMapperInterface         $moduleNameMapper,
    )
    {
    }

    public function getType(): string
    {
        return 'default';
    }

    public function getAttachments(string $module, string $id): ?array
    {
        $mediaObjects = [];

        if (empty($id) || empty($module)) {
            return null;
        }

        $legacyModuleName = $this->moduleNameMapper->toLegacy($module);
        $fieldName = 'filename';
        $storageType = $this->getStorageType($module, $fieldName);

        $linkedMediaObjects = $this->mediaObjectManager->getLinkedMediaObjects($storageType, $legacyModuleName, $id, $fieldName);

        foreach ($linkedMediaObjects as $linkedMediaObject) {
            $contentUrl = $this->mediaObjectManager->buildContentUrl($storageType, $linkedMediaObject);
            $linkedMediaObject->setContentUrl($contentUrl);
            $mediaObjectRecord = $this->mediaObjectManager->mapToRecord($storageType, $linkedMediaObject);
            $attributes = $mediaObjectRecord->getAttributes();
            $attributes['attachmentType'] = $module;
            $attributes['source_record_id'] = $id;
            $mediaObjectRecord->setAttributes($attributes);
            $mediaObjects[] = $mediaObjectRecord->toArray();
        }

        return $mediaObjects;
    }


    protected function getStorageType(string $module, string $fieldName): ?string
    {
        $fieldDef = $this->fieldDefinitionsProvider->getFieldDefinition($module, $fieldName);
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
