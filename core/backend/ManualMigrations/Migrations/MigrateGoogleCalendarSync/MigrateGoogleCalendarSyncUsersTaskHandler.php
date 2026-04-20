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

class MigrateGoogleCalendarSyncUsersTaskHandler extends AbstractAsyncTaskHandler
{
    protected const CONFIG_CATEGORY = 'calendar_sync';
    protected const CONFIG_NAME = 'google_sync_users_status';

    public function __construct(
        protected AsyncTaskLegacyHandler $legacyHandler,
        protected PreparedStatementHandler $preparedStatementHandler,
        protected LoggerInterface $logger
    ) {
    }

    public function getHandlerKey(): string
    {
        return 'migrate-google-calendar-sync-users';
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
            $qb->select('u.id', 'u.user_name')
                ->from('users', 'u')
                ->innerJoin('u', 'user_preferences', 'up', 'u.id = up.assigned_user_id')
                ->leftJoin(
                    'u',
                    'calendar_accounts',
                    'ca',
                    "u.id = ca.calendar_user_id AND ca.source = 'google' AND ca.deleted = 0"
                )
                ->where("u.deleted = '0'")
                ->andWhere("up.category = 'GoogleSync'")
                ->andWhere('up.contents IS NOT NULL')
                ->andWhere("from_base64(up.contents) LIKE '%GoogleApiToken%'")
                ->andWhere('ca.id IS NULL')
                ->setFirstResult($offset)
                ->setMaxResults($batchSize);

            $results = $qb->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            $this->logger->error(
                'MigrateGoogleCalendarSyncUsersTaskHandler::getNextBatchToQueue failed: ' . $e->getMessage(),
                ['component' => 'migrate-google-calendar-sync-users']
            );
            return [];
        }

        $items = [];
        foreach ($results as $row) {
            $items[] = new AsyncTaskBatchItem(
                $row['id'],
                [
                    'userId' => $row['id'],
                    'userName' => $row['user_name'],
                ]
            );
        }

        return $items;
    }

    public function processItem(Record $task, array $item): Feedback
    {
        $feedback = new Feedback();
        $data = $item['data'] ?? [];
        $userId = $data['userId'] ?? '';

        if (empty($userId)) {
            return $feedback->setSuccess(false)->setMessages(['Missing userId']);
        }

        $this->legacyHandler->startLegacy();

        try {
            require_once 'include/CalendarSync/migrations/Services/ProviderMigrationService.php';
            require_once 'include/CalendarSync/migrations/Services/UserMigrationService.php';

            $providerService = new \ProviderMigrationService();
            $providerId = $providerService->createOrGetExternalOAuthProvider(false);

            if ($providerId === null) {
                return $feedback->setSuccess(false)->setMessages(["Could not get/create OAuth provider for user $userId"]);
            }

            $userService = new \UserMigrationService();
            $userData = $userService->findUserForMigrationById($userId);

            if ($userData === null) {
                return $feedback->setSuccess(true);
            }

            $result = $userService->migrateUser($userData, $providerId, false);

            if ($result->type === \MigrationStatsDetailType::ERROR) {
                return $feedback->setSuccess(false)->setMessages([$result->message ?? 'Migration failed']);
            }

            $feedback->setSuccess(true);
        } catch (\Throwable $e) {
            $this->logger->error(
                'MigrateGoogleCalendarSyncUsersTaskHandler::processItem failed: ' . $e->getMessage(),
                [
                    'component' => 'migrate-google-calendar-sync-users',
                    'user_id' => $userId,
                ]
            );
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

            $this->logger->info(
                'MigrateGoogleCalendarSyncUsersTaskHandler::finalize: status set to ' . $status,
                [
                    'component' => 'migrate-google-calendar-sync-users',
                    'task_id' => $task->getId(),
                ]
            );

            $feedback->setSuccess(true);
        } catch (\Throwable $e) {
            $this->logger->error(
                'MigrateGoogleCalendarSyncUsersTaskHandler::finalize failed: ' . $e->getMessage(),
                [
                    'component' => 'migrate-google-calendar-sync-users',
                    'task_id' => $task->getId(),
                ]
            );
            $feedback->setSuccess(false)->setMessages([$e->getMessage()]);
        }

        return $feedback;
    }
}
