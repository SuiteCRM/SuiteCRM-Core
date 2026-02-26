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

namespace App\ManualMigrations\Migrations\MigratePersonPhotos;

use App\AsyncTask\Service\LegacyBridge\AsyncTaskLegacyHandler;
use App\AsyncTask\Service\TaskHandler\AbstractAsyncTaskHandler;
use App\AsyncTask\Service\TaskHandler\AsyncTaskBatchItem;
use App\Data\Entity\Record;
use App\Data\LegacyHandler\PreparedStatementHandler;
use App\Engine\Model\Feedback;
use App\MediaObjects\Repository\MediaObjectManagerInterface;
use App\MediaObjects\Services\LocalFileMediaObjectMigratorInterface;
use Psr\Log\LoggerInterface;

/**
 * Abstract base for Person photo migration handlers.
 *
 * In legacy SugarCRM, person photos are stored at upload/{record_id}_photo.
 * The photo field DB column holds the original uploaded filename.
 *
 * Concrete subclasses specify which module/table to migrate.
 */
abstract class MigratePersonPhotosTaskHandler extends AbstractAsyncTaskHandler
{
    public function __construct(
        protected AsyncTaskLegacyHandler $legacyHandler,
        protected PreparedStatementHandler $preparedStatementHandler,
        protected MediaObjectManagerInterface $mediaObjectManager,
        protected LocalFileMediaObjectMigratorInterface $migrator,
        protected LoggerInterface $logger
    ) {
    }

    abstract protected function getModule(): string;

    abstract protected function getTable(): string;

    public function getType(): string
    {
        return 'manual-migration-tasks';
    }

    /**
     * @inheritDoc
     */
    public function allowsFailureRetry(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function allowsFailureRerun(): bool
    {
        return true;
    }

    public function getNextBatchToQueue(Record $task, array $progress, int $batchSize): array
    {
        $offset = $progress['enqueue_offset'] ?? 0;
        $module = $this->getModule();
        $table = $this->getTable();

        try {
            $results = $this->preparedStatementHandler
                ->createQueryBuilder()
                ->select('t.id', 't.photo')
                ->from($table, 't')
                ->leftJoin(
                    't', 'private_images_media_objects', 'm',
                    'm.parent_type = :module AND m.parent_id = t.id AND m.parent_field = :field AND m.temporary = 0 AND m.deleted = 0'
                )
                ->where('t.photo IS NOT NULL')
                ->andWhere("t.photo != ''")
                ->andWhere('t.deleted = 0')
                ->andWhere('m.id IS NULL')
                ->setParameter('module', $module)
                ->setParameter('field', 'photo')
                ->setFirstResult($offset)
                ->setMaxResults($batchSize)
                ->executeQuery()
                ->fetchAllAssociative();
        } catch (\Throwable $e) {
            $this->logger->error(
                static::class . '::getNextBatchToQueue query failed: ' . $e->getMessage(), [
                    'component' => $this->getHandlerKey(),
                    'module' => $module,
                ]
            );

            return [];
        }

        $items = [];
        foreach ($results as $row) {
            $items[] = new AsyncTaskBatchItem(
                $row['id'],
                [
                    'record_id' => $row['id'],
                    'module' => $module,
                    'photo' => $row['photo'],
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
        $module = $data['module'] ?? '';
        $photo = $data['photo'] ?? '';

        if (empty($recordId) || empty($module)) {
            return $feedback->setSuccess(false)->setMessages(['Missing record_id or module']);
        }

        $this->legacyHandler->startLegacy();

        try {
            $legacyDir = $this->legacyHandler->getLegacyDir();
            $legacyPath = $legacyDir . '/upload/' . $recordId . '_photo';

            $existing = $this->mediaObjectManager->getLinkedMediaObjects(
                'private-images',
                $module,
                $recordId,
                'photo'
            );

            if (!empty($existing)) {
                if (file_exists($legacyPath)) {
                    unlink($legacyPath);
                }

                return $feedback->setSuccess(true)->setMessages(['Already migrated']);
            }

            if (!file_exists($legacyPath)) {
                return $feedback->setSuccess(false)->setMessages(['Legacy file not found: upload/' . $recordId . '_photo']);
            }

            $filename = !empty($photo) ? $photo : $recordId . '_photo';
            $mimeType = mime_content_type($legacyPath) ?: 'image/jpeg';

            $record = $this->migrator->migrate(
                $legacyPath,
                'private-images',
                $mimeType,
                $filename,
                $filename,
                $module,
                $recordId,
                'photo'
            );

            $feedback->setSuccess(true);
            $feedback->setData(['media_object_id' => $record?->getId()]);
        } catch (\Throwable $e) {
            $this->logger->error(
                static::class . '::processItem failed: ' . $e->getMessage(),
                [
                    'component' => $this->getHandlerKey(),
                    'module' => $module,
                    'record_id' => $recordId,
                ]
            );
            $feedback->setSuccess(false)->setMessages([$e->getMessage()]);
        } finally {
            $this->legacyHandler->stopLegacy();
        }

        return $feedback;
    }
}
