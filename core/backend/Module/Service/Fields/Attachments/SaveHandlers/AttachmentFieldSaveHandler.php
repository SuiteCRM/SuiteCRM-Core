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

namespace App\Module\Service\Fields\Attachments\SaveHandlers;

use App\Data\Entity\Record;
use App\Data\Service\Record\RecordSaveHandlers\RecordFieldTypeSaveHandlerInterface;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\MediaObjects\Repository\MediaObjectManagerInterface;
use App\Module\Service\ModuleNameMapperInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;


#[Autoconfigure(lazy: true)]
class AttachmentFieldSaveHandler implements RecordFieldTypeSaveHandlerInterface
{
    public function __construct(
        protected MediaObjectManagerInterface $mediaObjectManager,
        protected ModuleNameMapperInterface $moduleNameMapper
    ) {
    }


    public function getModule(): string
    {
        return 'default';
    }

    public function getFieldType(): string
    {
        return 'attachment';
    }

    public function getKey(): string
    {
        return 'default';
    }

    public function getModes(): array
    {
        return ['after-save'];
    }

    public function getOrder(): int
    {
        return 0;
    }

    public function getHandlerKey(): string
    {
        return $this->getKey();
    }


    public function run(?Record $previousVersion, Record $inputRecord, ?Record $savedRecord, FieldDefinition $fieldDefinition, string $field): void
    {
        if ($savedRecord === null) {
            return;
        }

        $vardef = $fieldDefinition->getVardef();
        $fieldVardef = $vardef[$field];

        if (empty($fieldVardef)) {
            return;
        }

        $storageType = $fieldVardef['metadata']['storage_type'] ?? '';
        if (empty($storageType)) {
            return;
        }

        $attributes = $inputRecord->getAttributes();


        if (!isset($attributes[$field])) {
            return;
        }

        $parentType = $this->moduleNameMapper->toLegacy($savedRecord->getModule());
        $parentId = $savedRecord->getId();
        $currentMediaObjects = $this->mediaObjectManager->getLinkedMediaObjects($storageType, $parentType, $parentId, $field) ?? [];

        $attachments = $attributes[$field];

        if (empty($attachments)) {
            foreach ($currentMediaObjects as $currentMediaObject) {
                // This will delete the media object from the repository and file system
                $this->mediaObjectManager->deleteMediaObject($storageType, $currentMediaObject);
            }
            return;
        }

        $currentMediaObjectIds = [];
        foreach ($currentMediaObjects as $currentMediaObject) {
            $currentMediaObjectIds[$currentMediaObject->getId()] = true;
        }

        $receivedItems = [];

        foreach ($attachments as $key => $attachment) {
            $id = $attachment['id'] ?? '';
            if (empty($id)) {
                continue;
            }

            $mediaObject = $this->mediaObjectManager->getMediaObject($storageType, $id);

            if (!$mediaObject) {
                return;
            }

            if (isset($currentMediaObjectIds[$mediaObject->getId()])) {
                $receivedItems[$mediaObject->getId()] = true;
                continue;
            }

            $mediaObject->setParentType($parentType);
            $mediaObject->setParentId($parentId);
            $mediaObject->setParentField($field);
            $mediaObject->setTemporary(false);

            $receivedItems[$mediaObject->getId()] = true;

            $this->mediaObjectManager->saveMediaObject($storageType, $mediaObject);
        }

        foreach ($currentMediaObjects as $currentMediaObject) {
            if (!isset($receivedItems[$currentMediaObject->getId()])) {
                // This will delete the media object from the repository and file system
                $this->mediaObjectManager->deleteMediaObject($storageType, $currentMediaObject);
            }
        }
    }
}
