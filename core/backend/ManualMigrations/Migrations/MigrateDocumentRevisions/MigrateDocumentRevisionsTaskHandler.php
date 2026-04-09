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

namespace App\ManualMigrations\Migrations\MigrateDocumentRevisions;

use App\AsyncTask\Service\LegacyBridge\AsyncTaskLegacyHandler;
use App\AsyncTask\Service\TaskHandler\AbstractAsyncTaskHandler;
use App\AsyncTask\Service\TaskHandler\AsyncTaskBatchItem;
use App\Data\Entity\Record;
use App\Data\LegacyHandler\PreparedStatementHandler;
use App\Engine\Model\Feedback;
use App\MediaObjects\Repository\MediaObjectManagerInterface;
use App\MediaObjects\Services\LocalFileMediaObjectMigratorInterface;
use Psr\Log\LoggerInterface;

class MigrateDocumentRevisionsTaskHandler extends AbstractAsyncTaskHandler
{
    public function __construct(
        protected AsyncTaskLegacyHandler $legacyHandler,
        protected PreparedStatementHandler $preparedStatementHandler,
        protected MediaObjectManagerInterface $mediaObjectManager,
        protected LocalFileMediaObjectMigratorInterface $migrator,
        protected LoggerInterface $logger
    ) {
    }

    public function getHandlerKey(): string
    {
        return 'migrate-document-revisions';
    }

    public function getType(): string
    {
        return 'manual-migration-tasks';
    }

    public function getNextBatchToQueue(Record $task, array $progress, int $batchSize): array
    {
        $offset = $progress['enqueue_offset'] ?? 0;

        try {
            $qb = $this->preparedStatementHandler->createQueryBuilder();
            $qb->select(
                'dr.id',
                'COALESCE(dr.file_mime_type, \'application/octet-stream\') AS file_mime_type',
                'COALESCE(dr.filename, \'\') AS filename',
                'COALESCE(dr.file_ext, \'\') AS file_ext',
                'COALESCE(d.document_name, \'\') AS document_name'
            )
                ->from('document_revisions', 'dr')
                ->leftJoin('dr', 'documents', 'd', 'dr.document_id = d.id AND d.deleted = 0')
                ->where('dr.deleted = 0')
                ->andWhere('(dr.filename IS NOT NULL AND dr.filename <> \'\') OR (dr.file_ext IS NOT NULL AND dr.file_ext <> \'\')')
                ->setFirstResult($offset)
                ->setMaxResults($batchSize);

            $results = $qb->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            $this->logger->error('MigrateDocumentRevisionsTaskHandler::enqueueItems failed: ' . $e->getMessage(), [
                'component' => 'migrate-document-revisions',
            ]);

            return [];
        }

        $items = [];
        foreach ($results as $row) {
            if ($row['filename'] !== '') {
                $filename = $row['filename'];
            } else {
                $ext = ltrim($row['file_ext'], '.');
                $documentName = $row['document_name'] !== '' ? $row['document_name'] : 'document';
                $filename = $documentName . ($ext !== '' ? '.' . $ext : '');
            }

            $items[] = new AsyncTaskBatchItem(
                $row['id'],
                [
                    'record_id' => $row['id'],
                    'filename' => $filename,
                    'file_mime_type' => $row['file_mime_type'],
                ],
                null,
                'DocumentRevisions',
                $filename
            );
        }

        return $items;
    }

    public function processItem(Record $task, array $item): Feedback
    {
        $feedback = new Feedback();
        $data = $item['data'] ?? [];
        $recordId = $data['record_id'] ?? '';
        $filename = $data['filename'] ?? '';
        $mimeType = $data['file_mime_type'] ?? 'application/octet-stream';

        if (empty($recordId)) {
            return $feedback->setSuccess(false)->setMessages(['Missing record_id']);
        }

        $this->legacyHandler->startLegacy();

        try {
            $legacyDir = $this->legacyHandler->getLegacyDir();
            $legacyPath = $legacyDir . '/upload/' . $recordId;

            $existing = $this->mediaObjectManager->getLinkedMediaObjects(
                'private-documents',
                'DocumentRevisions',
                $recordId,
                'filename'
            );

            if (!empty($existing)) {
                if (file_exists($legacyPath)) {
                    unlink($legacyPath);
                }

                return $feedback->setSuccess(true)->setMessages(['Already migrated']);
            }

            if (!file_exists($legacyPath)) {
                return $feedback->setSuccess(false)->setMessages(['Legacy file not found: upload/' . $recordId]);
            }

            $record = $this->migrator->migrate(
                $legacyPath,
                'private-documents',
                $mimeType,
                $filename,
                $filename,
                'DocumentRevisions',
                $recordId,
                'filename'
            );

            $feedback->setSuccess(true);
            $feedback->setData(['media_object_id' => $record?->getId()]);
        } catch (\Throwable $e) {
            $this->logger->error('MigrateDocumentRevisionsTaskHandler::processItem failed: ' . $e->getMessage(), [
                'component' => 'migrate-document-revisions',
                'record_id' => $recordId,
            ]);
            $feedback->setSuccess(false)->setMessages([$e->getMessage()]);
        } finally {
            $this->legacyHandler->stopLegacy();
        }

        return $feedback;
    }
}
