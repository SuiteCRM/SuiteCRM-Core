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

interface LocalFileMediaObjectMigratorInterface
{
    /**
     * Moves a local file into the media object system.
     *
     * The file at $filePath is wrapped in a ReplacingFile so Vich Uploader
     * handles the storage move. If $deleteSourceFile is true the original file
     * is removed after a successful save.
     *
     * @param string      $filePath        Absolute path to the source file.
     * @param string      $storageType     Media object storage type (e.g. 'private-documents').
     * @param string      $mimeType        MIME type of the file.
     * @param string      $name            Display name for the media object.
     * @param string      $originalName    Original filename.
     * @param string|null $parentType      Parent module name (e.g. 'Notes').
     * @param string|null $parentId        Parent record ID.
     * @param string|null $parentField     Parent field name (e.g. 'filename').
     * @param bool        $temporary       Whether the media object is temporary.
     * @param bool        $deleteSourceFile Whether to delete the source file after migration.
     * @return Record|null                 The mapped Record, or null if mapping fails.
     */
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
    ): ?Record;
}
