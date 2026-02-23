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

namespace App\MediaObjects\Services;

use App\Data\Entity\Record;
use App\MediaObjects\Repository\MediaObjectManagerInterface;
use Vich\UploaderBundle\FileAbstraction\ReplacingFile;

class LocalFileMediaObjectMigrator implements LocalFileMediaObjectMigratorInterface
{
    public function __construct(
        protected MediaObjectManagerInterface $mediaObjectManager
    ) {
    }

    public function migrate(
        string $filePath,
        string $storageType,
        string $mimeType,
        string $name,
        string $originalName,
        ?string $parentType = null,
        ?string $parentId = null,
        ?string $parentField = null,
        bool $temporary = false,
        bool $deleteSourceFile = true
    ): ?Record {
        $uploadedFile = new ReplacingFile($filePath);

        $attributes = [
            'file' => $uploadedFile,
            'parent_field' => $parentField,
            'parent_id' => $parentId,
            'parent_type' => $parentType,
            'mime_type' => $mimeType,
            'name' => $name,
            'original_name' => $originalName,
            'temporary' => $temporary,
        ];

        $mediaObject = $this->mediaObjectManager->createMediaObjectFromAttributes($storageType, $attributes);

        $this->mediaObjectManager->saveMediaObject($storageType, $mediaObject);

        if ($deleteSourceFile && file_exists($filePath)) {
            unlink($filePath);
        }

        return $this->mediaObjectManager->mapToRecord($storageType, $mediaObject);
    }
}
