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

namespace App\ManualMigrations\Migrations\MigrateNotesFiles;

use App\AsyncTask\Service\LegacyBridge\AsyncTaskLegacyHandler;
use App\AsyncTask\Service\TaskHandler\AbstractAsyncTaskHandler;
use App\AsyncTask\Service\TaskHandler\AsyncTaskBatchItem;
use App\Data\Entity\Record;
use App\Data\LegacyHandler\PreparedStatementHandler;
use App\Engine\Model\Feedback;
use App\MediaObjects\Repository\MediaObjectManagerInterface;
use App\MediaObjects\Services\LocalFileMediaObjectMigratorInterface;
use Psr\Log\LoggerInterface;

class MigrateNotesFilesTaskHandler extends AbstractAsyncTaskHandler
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
        return 'migrate-notes-files';
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
            $qb->select('id', 'filename', 'file_mime_type')
                ->from('notes')
                ->where('filename IS NOT NULL')
                ->andWhere("filename != ''")
                ->andWhere('deleted = 0')
                ->setFirstResult($offset)
                ->setMaxResults($batchSize);

            $results = $qb->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            $this->logger->error('MigrateNotesFilesTaskHandler::enqueueItems failed: ' . $e->getMessage(), [
                'component' => 'migrate-notes-files',
            ]);

            return [];
        }

        $items = [];
        foreach ($results as $row) {
            $items[] = new AsyncTaskBatchItem(
                $row['id'],
                [
                    'record_id' => $row['id'],
                    'filename' => $row['filename'],
                    'file_mime_type' => $row['file_mime_type'] ?? 'application/octet-stream',
                ]
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

        if (empty($recordId) || empty($filename)) {
            return $feedback->setSuccess(false)->setMessages(['Missing record_id or filename']);
        }

        $this->legacyHandler->startLegacy();

        try {
            $legacyDir = $this->legacyHandler->getLegacyDir();
            $legacyPath = $legacyDir . '/upload/' . $recordId;

            $existing = $this->mediaObjectManager->getLinkedMediaObjects(
                'private-documents',
                'Notes',
                $recordId,
                'file'
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
                'Notes',
                $recordId,
                'file'
            );

            $feedback->setSuccess(true);
            $feedback->setData(['media_object_id' => $record?->getId()]);
        } catch (\Throwable $e) {
            $this->logger->error('MigrateNotesFilesTaskHandler::processItem failed: ' . $e->getMessage(), [
                'component' => 'migrate-notes-files',
                'record_id' => $recordId,
            ]);
            $feedback->setSuccess(false)->setMessages([$e->getMessage()]);
        } finally {
            $this->legacyHandler->stopLegacy();
        }

        return $feedback;
    }
}
