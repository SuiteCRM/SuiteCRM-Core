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

namespace App\Module\Service\Fields\Attachments;

use App\Data\LegacyHandler\PreparedStatementHandler;
use App\Module\Service\Fields\Attachments\AttachmentTypeHandlers\AttachmentTypeHandlers;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Log\LoggerInterface;

class AttachmentsManager implements AttachmentsManagerInterface
{
    public function __construct(
        protected PreparedStatementHandler $preparedStatementHandler,
        protected LoggerInterface $logger,
        protected AttachmentTypeHandlers $attachmentTypeHandlers,
    )
    {
    }

    public function getLinkedAttachments(string $parentType, string $parentId, string $parentField): array
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->select('ar.*')
            ->from('attachments_references', 'ar')
            ->where('ar.parent_type = :parent_type')
            ->andWhere('ar.parent_id = :parent_id')
            ->andWhere('ar.parent_field = :parent_field')
            ->andWhere('ar.deleted = :deleted')
            ->setParameter('parent_type', $parentType)
            ->setParameter('parent_id', $parentId)
            ->setParameter('parent_field', $parentField)
            ->setParameter('deleted', 0);

        $result = [];
        try {
            $result = $queryBuilder->fetchAllAssociative();
        } catch (Exception $e) {
            $this->logger->error('Unable to get linked attachments', [
                'exception' => $e,
                'parentType' => $parentType,
                'parentId' => $parentId,
            ]);
        }

        $attachments = [];

        foreach ($result as $row) {
            $sourceRecordId = $row['source_record_id'] ?? '';
            $type = $row['type'] ?? '';

            if (empty($sourceRecordId) || empty($type)) {
                $this->logger->warn('Skipping attachment with missing source_record_id or type.');
                continue;
            }

            $attachmentTypeHandler = $this->attachmentTypeHandlers->getHandler($type);
            if (!$attachmentTypeHandler) {
                $this->logger->warn('No attachment type handler found for type ' . $type);
                continue;
            }
            $loadedAttachments = $attachmentTypeHandler->getAttachments($type, $sourceRecordId);
            if (empty($loadedAttachments)) {
                continue;
            }
            $attachments = [...$attachments, ...$loadedAttachments];
        }

        return $attachments;
    }

    public function unlinkAttachment(string $storageType, $currentLinkedAttachment, string $parentField, string $parentType, string $parentId): void
    {
        $sourceRecordId = $currentLinkedAttachment['attributes']['source_record_id'] ?? '';
        $type = $currentLinkedAttachment['attributes']['attachmentType'] ?? '';
        if (empty($sourceRecordId) || empty($type)) {
            $this->logger->warn('Cannot unlink attachment. Missing source_record_id or attachmentType.');
            return;
        }

        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder
            ->update('attachments_references')
            ->set('deleted', ':deleted')
            ->where('source_record_id = :source_record_id')
            ->andWhere('parent_type = :parent_type')
            ->andWhere('type = :type')
            ->andWhere('parent_field = :parent_field')
            ->andWhere('parent_id = :parent_id')
            ->setParameter('deleted', 1)
            ->setParameter('source_record_id', $sourceRecordId)
            ->setParameter('parent_type', $parentType)
            ->setParameter('type', $type)
            ->setParameter('parent_field', $parentField)
            ->setParameter('parent_id', $parentId);

        try {
            $queryBuilder->executeStatement();
        } catch (Exception $e) {
            $this->logger->error('Unable to unlink attachment', [
                'exception' => $e,
                'source_record_id' => $sourceRecordId,
                'parentType' => $parentType,
                'parentField' => $parentField,
                'parentId' => $parentId,
                'type' => $type,
            ]);
        }
    }

    public function linkAttachmentByDocument(string $sourceId, string $type, string $parentType, string $parentId, string $parentField): void
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder
            ->insert('attachments_references')
            ->values([
                'source_record_id' => ':source_record_id',
                'parent_type' => ':parent_type',
                'parent_field' => ':parent_field',
                'parent_id' => ':parent_id',
                'type' => ':type',
                'deleted' => ':deleted',
            ])
            ->setParameter('parent_id', $parentId)
            ->setParameter('parent_type', $parentType)
            ->setParameter('parent_field', $parentField)
            ->setParameter('source_record_id', $sourceId)
            ->setParameter('type', $type)
            ->setParameter('deleted', 0);

        try {
            $queryBuilder->executeStatement();
        } catch (Exception $e) {
            $this->logger->error('Unable to link attachment', [
                'exception' => $e,
                'source_record_id' => $sourceId,
                'parentType' => $parentType,
                'parentField' => $parentField,
                'parentId' => $parentId,
                'type' => $type,
            ]);
        }
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        return $this->preparedStatementHandler->createQueryBuilder();
    }
}
