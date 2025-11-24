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

use App\Data\Service\Record\Repository\RecordEntityRepository;
use App\FieldDefinitions\Service\FieldDefinitionsProviderInterface;
use App\MediaObjects\Entity\LegacyDocumentMediaObject;
use App\MediaObjects\LegacyHandler\LegacyFileToMediaObjectAdapter;
use Doctrine\Persistence\ManagerRegistry;

class LegacyDocumentMediaObjectRepository extends RecordEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        protected FieldDefinitionsProviderInterface $fieldDefinitionsProvider,
        protected LegacyFileToMediaObjectAdapter $legacyFileAdapter
    ) {
        parent::__construct($registry, LegacyDocumentMediaObject::class);
    }

    public function find($id, $lockMode = null, $lockVersion = null): object|null
    {
        $idParts = explode('---', $id);
        $parentType = $idParts[0] ?? '';
        $parentId = $idParts[1] ?? '';
        $parentField = $idParts[2] ?? '';

        $filters = [
            'parentType' => $parentType,
            'parentId' => $parentId,
            'parentField' => $parentField,
            'temporary' => 0
        ];

        $filters['deleted'] = 0;
        $mediaObjects = $this->findBy($filters);
        if (empty($mediaObjects)) {
            return null;
        }

        return $mediaObjects[0];
    }

    /**
     * {@inheritDoc}
     */
    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null): array
    {
        ['parentType' => $parentType, 'parentId' => $parentId, 'parentField' => $parentField] = $criteria;

        if ($parentType === null || $parentId === null || $parentField === null) {
            return [];
        }

        if ($parentType === 'Notes') {
            return $this->getNoteFile($parentId, $parentField);
        }

        if ($parentType === 'Documents') {
            return $this->getDocumentFile($parentId, $parentField);
        }

        if ($parentType === 'DocumentRevisions') {
            return $this->getDocumentRevisionFile($parentId, $parentField);
        }

        if ($parentType === 'Products') {
            return $this->getProductsFile($parentId, $parentField);
        }

        return $this->getFileForFieldDefinition($parentType, $parentId, $parentField);
    }

    public function remove(object $entity, bool $flush = false): void
    {
        if (empty($entity) || !($entity instanceof LegacyDocumentMediaObject) || empty($entity->getFilePath()) ) {
            return;
        }
        $parentType = $entity->getParentType();
        $parentId = $entity->getParentId();
        $parentField = $entity->getParentField();

        if ($parentType === 'Notes') {
            $this->legacyFileAdapter->deleteNoteMediaObject($parentId, $parentField);
            return;
        }

        if ($parentType === 'DocumentRevisions') {
            $this->legacyFileAdapter->deleteDocumentRevisionMediaObject($parentId, $parentField);
            return;
        }

        if ($parentType === 'Products') {
            $this->legacyFileAdapter->deleteProductMediaObject($parentId, $parentField);
            return;
        }

        $this->legacyFileAdapter->deleteFileFieldMediaObject($parentType, $parentId, $parentField);
    }

    public function save(object $entity, bool $flush = false): void
    {
    }

    protected function getNoteFile(string $parentId, string $parentField): array
    {
        return $this->legacyFileAdapter->findMediaObjectsForNote($parentId, $parentField);
    }

    protected function getDocumentFile(string $parentId, string $parentField): array
    {
        return $this->legacyFileAdapter->findMediaObjectsForDocument($parentId, $parentField);
    }

    protected function getDocumentRevisionFile(string $parentId, string $parentField): array
    {
        return $this->legacyFileAdapter->findMediaObjectsForDocumentRevision($parentId, $parentField);
    }

    protected function getProductsFile(string $parentId, string $parentField): array
    {
        return $this->legacyFileAdapter->findMediaObjectsForProduct($parentId, $parentField);
    }

    protected function getFileForFieldDefinition(string $parentType, string $parentId, string $parentField): array
    {
        return $this->legacyFileAdapter->findMediaObjectsFileField($parentType, $parentId, $parentField);
    }
}
