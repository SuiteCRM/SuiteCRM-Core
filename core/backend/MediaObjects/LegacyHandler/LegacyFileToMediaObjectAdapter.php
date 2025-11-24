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

namespace App\MediaObjects\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use App\MediaObjects\Entity\LegacyDocumentMediaObject;

class LegacyFileToMediaObjectAdapter extends LegacyHandler
{
    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return 'legacy-file-to-media-object-adapter';
    }

    public function findMediaObjectsForNote(string $parentId, string $parentField): array
    {
        return $this->findMediaObjectsForBean('Notes', $parentId, $parentField);
    }

    public function findMediaObjectsForDocument(string $parentId, string $parentField): array
    {
        return [];
    }

    public function findMediaObjectsForDocumentRevision(string $parentId, string $parentField): array
    {
        return $this->findMediaObjectsForBean('DocumentRevisions', $parentId, $parentField);
    }

    public function findMediaObjectsForProduct(string $parentId, string $parentField): array
    {
        $this->init();
        global $sugar_config;

        $parentType = 'AOS_Products';

        /** @var \AOS_Products $parentBean */
        $parentBean = \BeanFactory::getBean($parentType, $parentId);
        if (empty($parentBean)) {
            $this->close();
            return [];
        }

        if (empty($parentBean->product_image)) {
            $this->close();
            return [];
        }

        $parts = explode(($sugar_config['upload_dir'] ?? 'upload/'), $parentBean->product_image);
        $filePath = $parts[1] ?? '';
        if (empty($filePath)) {
            $this->close();
            return [];
        }

        $path = ($sugar_config['upload_dir'] ?? 'upload/') . $filePath;
        if (!file_exists($path)) {
            $this->close();
            return [];
        }

        $size = filesize($path);

        $fileName = substr($filePath, 37);

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeMap = [
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'pdf'  => 'application/pdf',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt'  => 'text/plain',
            'csv'  => 'text/csv',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        $mimeType = $mimeMap[$ext] ?? 'application/octet-stream';

        $mediaObject = $this->buildMediaObjectFromBean($parentBean, $fileName, $mimeType, $filePath, $parentField, $parentId, $parentType, $size);

        $this->close();

        return [$mediaObject];
    }

    public function findMediaObjectsFileField(string $parentType, string $parentId, string $parentField): array
    {
        return $this->findMediaObjectsForBean($parentType, $parentId, $parentField);
    }

    public function deleteNoteMediaObject(string $parentId, ?string $parentField): void
    {
        $this->deleteMediaObjectsForBean('Notes', $parentId, $parentField);
    }

    public function deleteDocumentRevisionMediaObject(string $parentId, ?string $parentField): void
    {
        $this->deleteMediaObjectsForBean('DocumentRevisions', $parentId, $parentField);
    }

    public function deleteProductMediaObject(?string $parentId, ?string $parentField): void
    {
        $this->init();
        global $sugar_config;

        $parentType = 'AOS_Products';

        /** @var \AOS_Products $parentBean */
        $parentBean = \BeanFactory::getBean($parentType, $parentId);
        if (empty($parentBean)) {
            $this->close();
            return;
        }

        if (empty($parentBean->product_image)) {
            $this->close();
            return;
        }

        $parts = explode('upload/', $parentBean->product_image);
        $filePath = $parts[1] ?? '';
        if (empty($filePath)) {
            $this->close();
            return;
        }

        $path = ($sugar_config['upload_dir'] ?? 'upload/') . $filePath;
        if (file_exists($path)) {
            if (!unlink($path)) {
                $GLOBALS['log']->error("*** Could not unlink() file: [ {$path} ]");
            } else {
                $parentBean->product_image = '';
                $parentBean->save();
            }
        }

        $this->close();
    }

    public function deleteFileFieldMediaObject(string $parentType, ?string $parentId, ?string $parentField): void
    {
        $this->deleteMediaObjectsForBean($parentType, $parentId, $parentField);
    }

    protected function deleteMediaObjectsForBean(string $parentType, string $parentId, string $parentField): void {
        $this->init();
        /** @var \DocumentRevision|\Note|\File $parentBean */
        $parentBean = \BeanFactory::getBean($parentType, $parentId);
        if (empty($parentBean)) {
            $this->close();
            return;
        }

        if (method_exists($parentBean, 'deleteAttachment')) {
            $parentBean->deleteAttachment();
        }

        $this->close();
    }

    /**
     * @param string $parentType
     * @param string $parentId
     * @param string $parentField
     * @return LegacyDocumentMediaObject[]|array
     */
    protected function findMediaObjectsForBean(string $parentType, string $parentId, string $parentField): array
    {
        $this->init();
        global $sugar_config;

        /** @var \DocumentRevision|\Note|\File $parentBean */
        $parentBean = \BeanFactory::getBean($parentType, $parentId);
        if (empty($parentBean)) {
            $this->close();
            return [];
        }

        if (empty($parentBean->filename) || empty($parentBean->file_mime_type)) {
            $this->close();
            return [];
        }

        $filePath = $parentBean->id;
        $path = ($sugar_config['upload_dir'] ?? 'upload/') . $filePath;
        if (!file_exists($path)) {
            $this->close();
            return [];
        }

        $size = filesize($path);
        $fileName = $parentBean->filename;
        $mimeType = $parentBean->file_mime_type;

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
     * @return LegacyDocumentMediaObject
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
    ): LegacyDocumentMediaObject {
        $this->init();

        require_once 'include/portability/Services/DateTime/DateFormatService.php';

        $dateFormatService = new \DateFormatService();

        $mediaObject = new LegacyDocumentMediaObject();
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
}
