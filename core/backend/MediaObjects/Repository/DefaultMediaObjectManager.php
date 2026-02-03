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
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\MediaObjects\Entity\ArchivedDocumentMediaObject;
use App\MediaObjects\Entity\LegacyDocumentMediaObject;
use App\MediaObjects\Entity\LegacyImageMediaObject;
use App\MediaObjects\Entity\MediaObjectInterface;
use App\MediaObjects\Entity\PrivateDocumentMediaObject;
use App\MediaObjects\Entity\PrivateImageMediaObject;
use App\MediaObjects\Entity\PublicDocumentMediaObject;
use App\MediaObjects\Entity\PublicImageMediaObject;
use App\SystemConfig\Service\SystemConfigProviderInterface;
use Imagine\Gd\Imagine;
use Imagine\Gmagick\Imagine as GmagickImagine;
use Imagine\Imagick\Imagine as ImagickImagine;
use Imagine\Image\Box;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mime\MimeTypes;
use Vich\UploaderBundle\FileAbstraction\ReplacingFile;
use Vich\UploaderBundle\Storage\StorageInterface;

class DefaultMediaObjectManager extends LegacyHandler implements MediaObjectManagerInterface
{
    protected array $typeMap = [];
    protected array $objectTypeMap;

    public function getHandlerKey(): string
    {
        return 'default-media-object-manager';
    }

    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        protected ArchivedDocumentMediaObjectRepository $archivedDocumentRepository,
        protected PrivateDocumentMediaObjectRepository $privateDocumentRepository,
        protected PrivateImageMediaObjectRepository $privateImageRepository,
        protected PublicDocumentMediaObjectRepository $publicDocumentRepository,
        protected PublicImageMediaObjectRepository $publicImageRepository,
        protected LegacyDocumentMediaObjectRepository $legacyDocumentRepository,
        protected LegacyImageMediaObjectRepository $legacyImageRepository,
        protected SystemConfigProviderInterface $systemConfigProvider,
        protected LoggerInterface $logger,
        protected StorageInterface $storage
    )
    {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $requestStack
        );

        $this->typeMap = [
            'archived-documents' => $archivedDocumentRepository,
            'private-documents' => $privateDocumentRepository,
            'private-images' => $privateImageRepository,
            'public-documents' => $publicDocumentRepository,
            'public-images' => $publicImageRepository,
            'legacy-documents' => $legacyDocumentRepository,
            'legacy-images' => $legacyImageRepository,
        ];

        $this->objectTypeMap = [
            ArchivedDocumentMediaObject::class => 'archived-documents',
            PrivateDocumentMediaObject::class => 'private-documents',
            PrivateImageMediaObject::class => 'private-images',
            PublicDocumentMediaObject::class => 'public-documents',
            PublicImageMediaObject::class => 'public-images',
            LegacyDocumentMediaObject::class => 'legacy-documents',
            LegacyImageMediaObject::class => 'legacy-images',
        ];
    }

    /**
     * Returns the repository for the given type.
     *
     * @param string $type The type of media object (e.g., 'archived-document', 'private-document', etc.)
     * @return RecordEntityRepository|null The repository instance or null if not found
     */
    public function getRepository(string $type): ?RecordEntityRepository
    {
        return $this->typeMap[$type] ?? null;
    }

    /**
     * Returns a media object by its type and ID.
     *
     * @param string $type The type of media object (e.g., 'archived-document', 'private-document', etc.)
     * @param string $id The ID of the media object
     * @return MediaObjectInterface|null The media object instance or null if not found
     */
    public function getMediaObject(string $type, string $id): ?MediaObjectInterface
    {
        $repository = $this->getRepository($type);
        if ($repository) {
            return $repository->find($id);
        }
        return null;
    }

    /**
     * Saves a media object to the appropriate repository based on its type.
     *
     * @param string $type The type of media object (e.g., 'archived-document', 'private-document', etc.)
     * @param MediaObjectInterface $mediaObject The media object to save
     */
    public function saveMediaObject(string $type, MediaObjectInterface $mediaObject): void
    {
        $repository = $this->getRepository($type);
        if ($repository) {
            $repository->save($mediaObject, true);
        }
    }

    public function getCompressedMediaObject(string $storageType, MediaObjectInterface $mediaObject): ?MediaObjectInterface
    {
        if ($mediaObject->getId() === null) {
            return null;
        }

        $linkedMediaObjects = $this->getLinkedMediaObjects(
            $storageType,
            'MediaObject',
            $mediaObject->getId(),
            'file'
        );

        return !empty($linkedMediaObjects) ? $linkedMediaObjects[0] : null;
    }

    public function createMediaObjectFromType(string $type): ?MediaObjectInterface
    {
        $classMap = array_flip($this->objectTypeMap);
        $className = $classMap[$type] ?? null;

        if ($className === null || !class_exists($className)) {
            return null;
        }

        return new $className();
    }

    public function getMediaObjectClassFromType(string $type): ?string
    {
        $classMap = array_flip($this->objectTypeMap);
        $className = $classMap[$type] ?? null;

        if ($className === null || !class_exists($className)) {
            return null;
        }

        return $className;
    }

    /**
     * Deletes a media object from the appropriate repository based on its type.
     *
     * @param string $type The type of media object (e.g., 'archived-document', 'private-document', etc.)
     * @param MediaObjectInterface $mediaObject The media object to delete
     */
    public function deleteMediaObject(string $type, MediaObjectInterface $mediaObject): void
    {
        $repository = $this->getRepository($type);
        if ($repository) {
            $repository->remove($mediaObject, true);
        }
    }


    /**
     * Sets the parent type and ID for a media object.
     *
     * @param string $type The type of media object (e.g., 'archived-document', 'private-document', etc.)
     * @param MediaObjectInterface $mediaObject The media object to link
     * @param string $parentType The type of the parent object
     * @param string $parentId The ID of the parent object
     * @param string $parentField The name of the parent field
     */
    public function linkParent(string $type, MediaObjectInterface $mediaObject, string $parentType, string $parentId, string $parentField): void
    {
        $repository = $this->getRepository($type);

        if (!$repository || !$mediaObject instanceof MediaObjectInterface) {
            return;
        }

        $mediaObject->setParentType($parentType);
        $mediaObject->setParentId($parentId);
        $mediaObject->setParentField($parentField);

        $repository->save($mediaObject);
    }

    /**
     * Returns all media objects linked to a parent object.
     *
     * @param string $type The type of media object (e.g., 'archived-document', 'private-document', etc.)
     * @param string $parentType The type of the parent object
     * @param string $parentId The ID of the parent object
     * @param string $parentField The name of the parent field
     * @param bool $includeDeleted Whether to include deleted media objects
     * @return MediaObjectInterface[] An array of linked media objects
     */
    public function getLinkedMediaObjects(string $type, string $parentType, string $parentId, string $parentField, bool $includeDeleted = false): array
    {
        $repository = $this->getRepository($type);
        if ($repository) {
            $filters = ['parentType' => $parentType, 'parentId' => $parentId, 'parentField' => $parentField, 'temporary' => 0];

            if ($includeDeleted === false) {
                $filters['deleted'] = 0;
            }
            return $repository->findBy($filters);
        }
        return [];
    }

    /**
     * Sets the parent type and ID for a media object.
     *
     * @param string $type The type of media object (e.g., 'archived-document', 'private-document', etc.)
     * @param string $id The ID of the media object
     * @param string $parentType The type of the parent object
     * @param string $parentId The ID of the parent object
     * @param string $parentField The name of the parent field
     */
    public function linkParentById(string $type, string $id, string $parentType, string $parentId, string $parentField): void
    {
        $repository = $this->getRepository($type);

        if (!$repository) {
            return;
        }

        $mediaObject = $this->getMediaObject($type, $id);
        if (!$mediaObject) {
            return;
        }

        $mediaObject->setParentType($parentType);
        $mediaObject->setParentId($parentId);
        $mediaObject->setParentField($parentField);

        $repository->save($mediaObject);
    }

    /**
     * Maps a media object to a record.
     *
     * @param string $storageType
     * @param MediaObjectInterface|null $mediaObject The record to map
     * @return Record|null
     */
    public function mapToRecord(string $storageType, ?MediaObjectInterface $mediaObject): ?Record
    {
        if (!$mediaObject instanceof MediaObjectInterface) {
            return null;
        }

        $record = new Record();
        $record->setId($mediaObject->getId());
        $record->setModule('media-objects');
        $record->setType('media-objects');

        $record->setAttributes(
            [
                'id' => $mediaObject->getId(),
                'name' => $mediaObject->getName(),
                'file_path' => $mediaObject->getFilePath(),
                'size' => $mediaObject->getSize(),
                'mime_type' => $mediaObject->getMimeType(),
                'original_name' => $mediaObject->getOriginalName(),
                'dimensions' => $mediaObject->getDimensions(),
                'parent_type' => $mediaObject->getParentType(),
                'parent_id' => $mediaObject->getParentId(),
                'parent_field' => $mediaObject->getParentField(),
                'temporary' => $mediaObject->getTemporary(),
                'contentUrl' => $this->buildContentUrl($storageType, $mediaObject),
                'date_entered' => $mediaObject->getDateEntered(),
                'date_modified' => $mediaObject->getDateModified(),
                'created_by' => $mediaObject->getCreatedBy(),
                'modified_user_id' => $mediaObject->getModifiedUserId(),
                'deleted' => $mediaObject->isDeleted(),
                'module' => 'MediaObjects'
            ]
        );

        return $record;
    }

    /**
     * Synchronizes related records for a parent object.
     *
     * @param string $type The type of media object (e.g., 'archived-document', 'private-document', etc.)
     * @param Record $parent The parent record to which the media objects are linked
     * @param string $parentField The name of the parent field
     * @param Record[] $records An array of records to sync with the parent
     */
    public function syncLinkedMediaObjects(string $type, Record $parent, string $parentField, array $records): void
    {
        $repository = $this->getRepository($type);

        if (!$repository) {
            return;
        }

        $parentType = $parent->getAttributes()['module_name'] ?? '';
        $parentId = $parent->getId();

        $records = $records ?? [];
        $relatedRecordIds = [];
        $relatedMediaObjects = $this->getLinkedMediaObjects($type, $parentType, $parentId, $parentField) ?? [];

        foreach ($relatedMediaObjects as $mediaObject) {
            $id = $mediaObject->getId();
            if (!empty($id)) {
                $relatedRecordIds[$id] = true;
            }
        }

        $submittedRecordIds = [];
        foreach ($records as $record) {
            $id = $record->getId();

            $mediaObject = $this->getMediaObject($type, $id);

            if (!$mediaObject) {
                continue;
            }

            $submittedRecordIds[$record->getId()] = true;

            if (empty($relatedRecordIds[$id])) {
                $this->linkParent($type, $mediaObject, $parentType, $parentId, $parentField);
            }
        }

        foreach ($relatedMediaObjects as $mediaObject) {
            $id = $mediaObject->getId();
            if (empty($submittedRecordIds[$id])) {
                $this->deleteMediaObject($type, $mediaObject);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function buildContentUrl(string $type, mixed $object): string
    {
        $privateTypes = [
            'archived-documents' => true,
            'private-documents' => true,
            'private-images' => true,
            'legacy-documents' => true,
            'legacy-images' => true
        ];
        if ($privateTypes[$type] ?? false) {
            $prefix = $this->getPath($object);
            return $prefix . $object->id;
        }

        $publicTypes = [
            'public-documents' => true,
            'public-images' => true,
        ];

        if ($publicTypes[$type] ?? false) {
            return $this->storage->resolveUri($object, 'file');
        }

        return '';
    }

    /**
     * @param mixed $object
     * @return string
     */
    protected function getPath(mixed $object): string
    {
        $prefixMap = [
            ArchivedDocumentMediaObject::class => '/media/archived/',
            PrivateDocumentMediaObject::class => '/media/documents/',
            PrivateImageMediaObject::class => '/media/images/',
            LegacyDocumentMediaObject::class => '/media/legacy/documents/',
            LegacyImageMediaObject::class => '/media/legacy/images/',
        ];

        foreach ($prefixMap as $type => $prefix) {
            if ($object instanceof $type) {
                return $prefix;
            }
        }

        return 'media';
    }

    public function getObjectStorageType(object $object): string
    {
        foreach ($this->objectTypeMap as $class => $type) {
            if ($object instanceof $class) {
                return $type;
            }
        }

        return '';
    }

    public function createMediaObjectFromAttributes(string $storageType, array $attributes): MediaObjectInterface
    {
        $mediaObject = $this->createMediaObjectFromType($storageType);

        $mediaObject->setParentField($attributes['parent_field'] ?? 'file');
        $mediaObject->setParentId($attributes['parent_id'] ?? null);
        $mediaObject->setParentType($attributes['parent_type'] ?? null);
        $mediaObject->setMimeType($attributes['mime_type'] ?? '');
        $mediaObject->setName($attributes['name'] ?? '');
        $mediaObject->setOriginalName($attributes['original_name'] ?? $attributes['name'] ?? '');
        $mediaObject->setTemporary($attributes['temporary'] ?? false);
        $mediaObject->setFile($attributes['file'] ?? null);

        return $mediaObject;
    }

    public function getExtension(MediaObjectInterface $mediaObject): string
    {
        $mimeType = $mediaObject->getMimeType();
        $mimeTypes = new MimeTypes();
        $extensions = $mimeTypes->getExtensions($mimeType);
        return !empty($extensions) ? '.' . $extensions[0] : '.jpg';
    }

    public function deleteCompressedMediaObject(string $storageType, ?MediaObjectInterface $currentMediaObject): void
    {
        $mediaObject = $this->getCompressedMediaObject($storageType, $currentMediaObject);
        if ($mediaObject === null) {
            return;
        }
        $this->deleteMediaObject($storageType, $mediaObject);
    }

    public function setCompressedMediaObjectToDeleted(string $storageType, MediaObjectInterface $currentMediaObject): void
    {
        $compressedMediaObject = $this->getCompressedMediaObject($storageType, $currentMediaObject);
        if ($compressedMediaObject === null) {
            return;
        }

        $compressedMediaObject->setDeleted(true);
    }


    public function createCompressedMediaObject(string $storageType, MediaObjectInterface $mediaObject, array $options): ?MediaObjectInterface
    {
        $contents = $this->getFieldContents($storageType, $mediaObject);

        if ($contents === false) {
            $this->logger->warn('Could not get media object contents for id ' . $mediaObject?->getId());
            return null;
        }

        if ($this->getImagine() === null) {
            $this->logger->error('No image library available to create thumbnail.');
            return null;
        }

        [$uploadedFile, $path] = $this->createThumbnail($mediaObject, $contents, $options);

        if (empty($path)) {
            $this->logger->error('Could not create thumbnail for media object id ' . $mediaObject?->getId());
            return null;
        }

        $compressedMediaObjectAttributes = [
            'file' => $uploadedFile,
            'parent_field' => 'file',
            'parent_id' => $mediaObject->getId() ?? null,
            'parent_type' => 'MediaObject',
            'mime_type' => $mediaObject->getMimeType(),
            'name' => ($mediaObject->getName() ?? $mediaObject->getOriginalName()) . ' (compressed)',
            'original_name' => ($mediaObject->getOriginalName() ?? $mediaObject->getName()) . ' (compressed)',
            'temporary' => $options['temporary'] ?? false,
        ];

        $compressedMediaObject = $this->createMediaObjectFromAttributes($storageType, $compressedMediaObjectAttributes);

        $this->saveMediaObject($storageType, $compressedMediaObject);

        $contentUrl = $this->buildContentUrl($storageType, $compressedMediaObject);
        $compressedMediaObject->setContentUrl($contentUrl);

        unlink($path);

        return $compressedMediaObject;
    }

    protected function getFieldContents(string $storageType, MediaObjectInterface $mediaObject): bool|string
    {
        $stream = $this->storage->resolveStream($mediaObject, 'file', $this->getMediaObjectClassFromType($storageType) ?? null);
        rewind($stream);
        return stream_get_contents($stream);
    }

    protected function createThumbnail(MediaObjectInterface $mediaObject, string $contents, array $options): array
    {
        $height = $this->getThumbnailHeight($options['height'] ?? 0);
        $width = $this->getThumbnailWidth($options['width'] ?? 0);

        $id = create_guid();
        $imagine = $this->getImagine();

        $ext = $this->getExtension($mediaObject);
        $path = $this->projectDir . '/' . $this->getThumbnailTmpPath() . $id . $ext;

        $imagine->load($contents)->resize(new Box($width, $height))->save($path);
        return [new ReplacingFile($path), $path];
    }

    protected function getThumbnailHeight(int $height): int
    {
        $defaultHeight = $this->systemConfigProvider->getSystemConfig('image_thumbnail_height_default')->getValue() ?? 50;

        if (empty($height)) {
            return (int)$defaultHeight;
        }

        return $height;
    }

    protected function getThumbnailWidth(int $width): int
    {
        $defaultHeight = $this->systemConfigProvider->getSystemConfig('image_thumbnail_width_default')->getValue() ?? 50;

        if (empty($width)) {
            return (int)$defaultHeight;
        }

        return $width;
    }

    protected function getThumbnailTmpPath(): string
    {
        return $this->systemConfigProvider->getSystemConfig('image_thumbnail_tmp_path')->getValue() ?? 'tmp/';
    }

    public function getStorageTypeFromClass(string $className): ?string
    {
        return $this->objectTypeMap[$className] ?? null;
    }

    protected function getImagine(): ImagickImagine|Imagine|GmagickImagine|null
    {
        if (extension_loaded('gd')) {
            return new Imagine();
        }

        if (extension_loaded('imagick')) {
            return new ImagickImagine();
        }

        if (extension_loaded('gmagick')) {
            return new GmagickImagine();
        }

        return null;
    }
}
