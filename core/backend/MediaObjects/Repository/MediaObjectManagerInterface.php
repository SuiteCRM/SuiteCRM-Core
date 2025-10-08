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

namespace App\MediaObjects\Repository;

use App\Data\Entity\Record;
use App\Data\Service\Record\Repository\RecordEntityRepository;
use App\MediaObjects\Entity\MediaObjectInterface;

interface MediaObjectManagerInterface
{
    /**
     * Returns the repository for the given type.
     *
     * @param string $type The type of media object (e.g., 'archived-document', 'private-document', etc.)
     * @return RecordEntityRepository|null The repository instance or null if not found
     */
    public function getRepository(string $type): ?RecordEntityRepository;

    /**
     * Returns a media object by its type and ID.
     *
     * @param string $type The type of media object (e.g., 'archived-document', 'private-document', etc.)
     * @param string $id The ID of the media object
     * @return MediaObjectInterface|null The media object instance or null if not found
     */
    public function getMediaObject(string $type, string $id): ?MediaObjectInterface;

    /**
     * Saves a media object to the appropriate repository based on its type.
     *
     * @param string $type The type of media object (e.g., 'archived-document', 'private-document', etc.)
     * @param MediaObjectInterface $mediaObject The media object to save
     */
    public function saveMediaObject(string $type, MediaObjectInterface $mediaObject): void;

    /**
     * Deletes a media object from the appropriate repository based on its type.
     *
     * @param string $type The type of media object (e.g., 'archived-document', 'private-document', etc.)
     * @param MediaObjectInterface $mediaObject The media object to delete
     */
    public function deleteMediaObject(string $type, MediaObjectInterface $mediaObject): void;

    /**
     * Returns all media objects linked to a parent object.
     *
     * @param string $type The type of media object (e.g., 'archived-document', 'private-document', etc.)
     * @param string $parentType The type of the parent object
     * @param string $parentId The ID of the parent object
     * @param string $parentField The field in the parent object that links to the media objects
     * @param bool $includeDeleted Whether to include deleted media objects
     * @return MediaObjectInterface[] An array of linked media objects
     */
    public function getLinkedMediaObjects(string $type, string $parentType, string $parentId, string $parentField, bool $includeDeleted = false): array;

    /**
     * Sets the parent type and ID for a media object.
     *
     * @param string $type The type of media object (e.g., 'archived-document', 'private-document', etc.)
     * @param string $id The ID of the media object
     * @param string $parentType The type of the parent object
     * @param string $parentId The ID of the parent object
     * @param string $parentField The field in the parent object that links to the media objects
     */
    public function linkParentById(string $type, string $id, string $parentType, string $parentId, string $parentField): void;

    /**
     * Maps a media object to a record.
     *
     * @param string $storageType
     * @param MediaObjectInterface|null $mediaObject The record to map
     * @return Record|null
     */
    public function mapToRecord(string $storageType, ?MediaObjectInterface $mediaObject): ?Record;

    /**
     * Synchronizes related records for a parent object.
     *
     * @param string $type The type of media object (e.g., 'archived-document', 'private-document', etc.)
     * @param Record $parent The parent record to which the media objects are linked
     * @param string $parentField The field in the parent record that links to the media objects
     * @param Record[] $records An array of records to sync with the parent
     */
    public function syncLinkedMediaObjects(string $type, Record $parent, string $parentField, array $records): void;

    /**
     * Sets the parent type and ID for a media object.
     *
     * @param string $type The type of media object (e.g., 'archived-document', 'private-document', etc.)
     * @param MediaObjectInterface $mediaObject The media object to link
     * @param string $parentType The type of the parent object
     * @param string $parentId The ID of the parent object
     * @param string $parentField The field in the parent object that links to the media objects
     */
    public function linkParent(string $type, MediaObjectInterface $mediaObject, string $parentType, string $parentId, string $parentField): void;

    /**
     * Builds a content URL for a media object.
     *
     * @param string $type The type of media object (e.g., 'archived-document', 'private-document', etc.)
     * @param mixed $object The media object or record to build the URL for
     * @return string The content URL
     */
    public function buildContentUrl(string $type, mixed $object): string;

    /**
     * Returns the storage type for a given media object.
     *
     * @param mixed $object The media object to get the storage type for
     * @return string The storage type (e.g., 'archived-document', 'private-document', etc.)
     */
    public function getObjectStorageType(object $object): string;
}
