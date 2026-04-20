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

namespace App\ManualMigrations\Migrations\MigrateGoogleCalendarSync;

use App\AsyncTask\Service\LegacyBridge\AsyncTaskLegacyHandler;
use App\AsyncTask\Service\TaskHandler\AbstractAsyncTaskHandler;
use App\AsyncTask\Service\TaskHandler\AsyncTaskBatchItem;
use App\Data\Entity\Record;
use App\Data\LegacyHandler\PreparedStatementHandler;
use App\Engine\Model\Feedback;
use Psr\Log\LoggerInterface;

class MigrateGoogleCalendarSyncTaskHandler extends AbstractAsyncTaskHandler
{
    protected const CONFIG_CATEGORY = 'calendar_sync';
    protected const CONFIG_NAME = 'google_sync_migration_status';

    public function __construct(
        protected AsyncTaskLegacyHandler $legacyHandler,
        protected PreparedStatementHandler $preparedStatementHandler,
        protected LoggerInterface $logger
    ) {
    }

    public function getHandlerKey(): string
    {
        return 'migrate-google-calendar-sync';
    }

    public function getType(): string
    {
        return 'manual-migration-tasks';
    }

    public function allowsFailureRetry(): bool
    {
        return true;
    }

    public function allowsFailureRerun(): bool
    {
        return false;
    }

    public function hasFinalization(): bool
    {
        return true;
    }

    public function getNextBatchToQueue(Record $task, array $progress, int $batchSize): array
    {
        $offset = $progress['enqueue_offset'] ?? 0;

        try {
            $qb = $this->preparedStatementHandler->createQueryBuilder();
            $qb->select('m.id', 'm.name', 'm.assigned_user_id', 'm.gsync_id', 'm.gsync_lastsync')
                ->from('meetings', 'm')
                ->leftJoin(
                    'm',
                    'calendar_account_meetings',
                    'cam',
                    'm.id = cam.meeting_id AND cam.deleted = 0'
                )
                ->where('m.gsync_id IS NOT NULL')
                ->andWhere("m.gsync_id != ''")
                ->andWhere('m.deleted = 0')
                ->andWhere('cam.id IS NULL')
                ->setFirstResult($offset)
                ->setMaxResults($batchSize);

            $results = $qb->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            $this->logger->error('MigrateGoogleCalendarSyncTaskHandler::getNextBatchToQueue failed: ' . $e->getMessage(), [
                'component' => 'migrate-google-calendar-sync',
            ]);

            return [];
        }

        $items = [];
        foreach ($results as $row) {
            $items[] = new AsyncTaskBatchItem(
                $row['id'],
                [
                    'meetingId' => $row['id'],
                    'userId' => $row['assigned_user_id'],
                    'gsyncId' => $row['gsync_id'],
                    'gsyncLastsync' => $row['gsync_lastsync'] ?? '',
                ]
            );
        }

        return $items;
    }

    public function processItem(Record $task, array $item): Feedback
    {
        $feedback = new Feedback();
        $data = $item['data'] ?? [];
        $meetingId = $data['meetingId'] ?? '';

        if (empty($meetingId)) {
            return $feedback->setSuccess(false)->setMessages(['Missing meetingId']);
        }

        $this->legacyHandler->startLegacy();

        try {
            require_once 'include/CalendarSync/migrations/Services/LegacyGoogleSyncMigrationService.php';

            $service = new \LegacyGoogleSyncMigrationService();
            $success = $service->migrateMeeting($data);

            if (!$success) {
                return $feedback->setSuccess(false)->setMessages(["Migration returned false for meeting $meetingId"]);
            }

            $feedback->setSuccess(true);
        } catch (\Throwable $e) {
            $this->logger->error('MigrateGoogleCalendarSyncTaskHandler::processItem failed: ' . $e->getMessage(), [
                'component' => 'migrate-google-calendar-sync',
                'meeting_id' => $meetingId,
            ]);
            $feedback->setSuccess(false)->setMessages([$e->getMessage()]);
        } finally {
            $this->legacyHandler->stopLegacy();
        }

        return $feedback;
    }

    public function finalize(Record $task): Feedback
    {
        $feedback = new Feedback();

        try {
            $failedCount = (int)$this->preparedStatementHandler->createQueryBuilder()
                ->getConnection()
                ->fetchOne(
                    "SELECT COUNT(*) FROM async_task_items WHERE async_task_id = ? AND status = 'failed' AND deleted = 0",
                    [$task->getId()]
                );

            $status = $failedCount > 0 ? 'failed' : 'completed';

            $this->preparedStatementHandler->createQueryBuilder()
                ->getConnection()
                ->executeStatement(
                    'REPLACE INTO config (category, name, value) VALUES (?, ?, ?)',
                    [self::CONFIG_CATEGORY, self::CONFIG_NAME, $status]
                );

            $this->logger->info('MigrateGoogleCalendarSyncTaskHandler::finalize: status set to ' . $status, [
                'component' => 'migrate-google-calendar-sync',
                'task_id' => $task->getId(),
            ]);

            $feedback->setSuccess(true);
        } catch (\Throwable $e) {
            $this->logger->error('MigrateGoogleCalendarSyncTaskHandler::finalize failed: ' . $e->getMessage(), [
                'component' => 'migrate-google-calendar-sync',
                'task_id' => $task->getId(),
            ]);
            $feedback->setSuccess(false)->setMessages([$e->getMessage()]);
        }

        return $feedback;
    }
}
