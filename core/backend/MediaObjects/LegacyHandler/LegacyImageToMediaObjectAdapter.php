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

namespace App\MediaObjects\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use App\MediaObjects\Entity\LegacyImageMediaObject;

class LegacyImageToMediaObjectAdapter extends LegacyHandler
{
    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return 'legacy-image-to-media-object-adapter';
    }

    public function findMediaObjectsFileField(string $parentType, string $parentId, string $parentField): array
    {
        return $this->findMediaObjectsForBean($parentType, $parentId, $parentField);
    }

    public function deleteFileFieldMediaObject(string $parentType, ?string $parentId, ?string $parentField): void
    {
        $this->deleteMediaObjectsForBean($parentType, $parentId, $parentField);
    }

    protected function deleteMediaObjectsForBean(string $parentType, string $parentId, string $parentField): void
    {
        $this->init();
        /** @var \SugarBean|bool $parentBean */
        $parentBean = \BeanFactory::getBean($parentType, $parentId);
        if (empty($parentBean)) {
            $this->close();
            return;
        }

        $this->deleteAttachment($parentBean, $parentId, $parentField);

        $this->close();
    }

    /**
     * @param string $parentType
     * @param string $parentId
     * @param string $parentField
     * @return LegacyImageMediaObject[]|array
     */
    protected function findMediaObjectsForBean(string $parentType, string $parentId, string $parentField): array
    {
        $this->init();
        global $sugar_config;

        /** @var \SugarBean|bool $parentBean */
        $parentBean = \BeanFactory::getBean($parentType, $parentId);
        if (empty($parentBean)) {
            $this->close();
            return [];
        }

        if (empty($parentBean->$parentField)) {
            $this->close();
            return [];
        }

        $filePath = $parentBean->id . '_' . $parentField;
        $path = ($sugar_config['upload_dir'] ?? 'upload/') . $filePath;
        if (!file_exists($path)) {
            $this->close();
            return [];
        }

        $size = filesize($path);
        $fileName = $parentBean->$parentField;
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        $mimeType = $this->getMimeTypeFromExtension($extension) ?? '';

        if (empty($mimeType)) {
            $this->close();
            return [];
        }

        $mediaObject = $this->buildMediaObjectFromBean($parentBean, $fileName, $mimeType, $filePath, $parentField, $parentId, $parentType, $size);

        $this->close();

        return [$mediaObject];
    }

    /**
     * @param \SugarBean $bean
     * @param string $fileName
     * @param string $mimeType
     * @param string $filePath
     * @param string $parentField
     * @param string $parentId
     * @param string $parentType
     * @param string|null $size
     * @return LegacyImageMediaObject
     */
    protected function buildMediaObjectFromBean(
        \SugarBean $bean,
        string $fileName,
        string $mimeType,
        string $filePath,
        string $parentField,
        string $parentId,
        string $parentType,
        ?string $size
    ): LegacyImageMediaObject {
        $this->init();

        require_once 'include/portability/Services/DateTime/DateFormatService.php';

        $dateFormatService = new \DateFormatService();

        $mediaObject = new LegacyImageMediaObject();
        $id = $parentType . '---' . $parentId . '---' . $parentField;
        $mediaObject->setId($id);
        $mediaObject->setName($fileName);
        $mediaObject->setOriginalName($fileName);
        $mediaObject->setMimeType($mimeType);
        $mediaObject->setFilePath($filePath);
        $mediaObject->setParentField($parentField);
        $mediaObject->setParentId($parentId);
        $mediaObject->setParentType($parentType);
        $mediaObject->setDeleted(0);
        $mediaObject->setTemporary(0);
        $mediaObject->setAssignedUserId($bean->assigned_user_id);
        $mediaObject->setCreatedBy($bean->created_by);
        $mediaObject->setModifiedUserId($bean->assigned_user_id);
        $mediaObject->setDateEntered($dateFormatService->toDateTime($bean->date_entered));
        $mediaObject->setDateModified($dateFormatService->toDateTime($bean->date_modified));
        $mediaObject->setSize($size);
        $mediaObject->setDescription('');

        $this->close();

        return $mediaObject;
    }

    protected function deleteAttachment(\SugarBean $parentBean, string $parentId, string $parentField, $isDuplicate = 'false'): void
    {
        $this->init();
        global $log;
        if ($parentBean->ACLAccess('edit')) {
            if ($isDuplicate === 'true') {
                $this->close();
                return;
            }
            $removeFile = "upload://{$parentId}" . '_' . $parentField;
        }

        if (file_exists($removeFile)) {
            if (!unlink($removeFile)) {
                $log->error("*** Could not unlink() image: [ {$removeFile} ]");
            } else {
                $parentBean->$parentField = '';
                $parentBean->save();
            }
        } else {
            $parentBean->$parentField = '';
            $parentBean->save();
        }
        $this->close();
    }

    protected function getMimeTypeFromExtension(string $extension): ?string
    {
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
        ];

        return $mimeTypes[strtolower($extension)] ?? null;
    }
}
