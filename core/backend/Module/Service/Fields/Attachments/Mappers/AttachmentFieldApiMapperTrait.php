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

namespace App\Module\Service\Fields\Attachments\Mappers;

use App\Data\Entity\Record;
use App\MediaObjects\Repository\MediaObjectManagerInterface;
use App\Module\Service\Fields\Attachments\AttachmentsManagerInterface;
use App\Module\Service\ModuleNameMapperInterface;

trait AttachmentFieldApiMapperTrait
{
    /**
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param Record $record
     * @param MediaObjectManagerInterface $mediaObjectManager
     * @param string $type
     * @param string $field
     * @return array
     */
    protected function getRelatedFiles(ModuleNameMapperInterface $moduleNameMapper, Record $record, MediaObjectManagerInterface $mediaObjectManager, string $type, string $field): array
    {
        $module = $moduleNameMapper->toLegacy($record->getModule());

        $mediaObjects = $mediaObjectManager->getLinkedMediaObjects($type, $module, $record->getId(), $field);

        if (empty($mediaObjects)) {
            return [];
        }

        if (empty($mediaObjects)) {
            $mediaObjects = $mediaObjectManager->getLinkedMediaObjects('legacy-documents', $module, $record->getId(), $field) ?? [];
        }

        if (empty($mediaObjects)) {
            return [];
        }

        $injectedRecords = [];

        foreach ($mediaObjects as $mediaObject) {
            $mediaObjectRecord = $mediaObjectManager->mapToRecord($type, $mediaObject);

            if (!$mediaObjectRecord) {
                continue;
            }

            $injectedRecords[] = $mediaObjectRecord->toArray();
        }

        return $injectedRecords;
    }

    /**
     * @param Record $record
     * @param string $field
     * @param string $type
     * @param MediaObjectManagerInterface $mediaObjectManager
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param AttachmentsManagerInterface $attachmentsManager
     * @return void
     */
    protected function injectMediaObjects(
        Record $record,
        string $field,
        string $type,
        MediaObjectManagerInterface $mediaObjectManager,
        ModuleNameMapperInterface $moduleNameMapper,
        AttachmentsManagerInterface $attachmentsManager
    ): void {

        $injectedFiles = $this->getRelatedFiles($moduleNameMapper, $record, $mediaObjectManager, $type, $field);
        $injectedAttachments = $attachmentsManager->getLinkedAttachments($record->getModule(), $record->getId(), $field);

        $injectedRecords = array_merge($injectedFiles, $injectedAttachments);

        $recordAttributes = $record->getAttributes();
        $recordAttributes[$field] = $injectedRecords;
        $record->setAttributes($recordAttributes);
    }
}
