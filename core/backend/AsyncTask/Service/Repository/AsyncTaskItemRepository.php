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

namespace App\AsyncTask\Service\Repository;

use App\Data\LegacyHandler\PreparedStatementHandler;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class AsyncTaskItemRepository
{
    protected const TABLE = 'async_task_items';

    public function __construct(
        protected PreparedStatementHandler $preparedStatementHandler,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * Bulk insert items into the async_task_items table.
     *
     * @param string $taskId The parent task ID
     * @param array $items Array of items, each: ['item_key' => string, 'data' => array, 'sort_order' => int]
     * @return array The inserted items with their generated IDs
     */
    public function insertItems(string $taskId, array $items): array
    {
        $now = gmdate('Y-m-d H:i:s');
        $insertedItems = [];

        foreach ($items as $index => $item) {
            $id = Uuid::uuid4()->toString();
            $itemKey = $item['item_key'] ?? '';
            $data = !empty($item['data']) ? json_encode($item['data']) : null;
            $sortOrder = $item['sort_order'] ?? $index;

            try {
                $qb = $this->preparedStatementHandler->createQueryBuilder();
                $qb->insert(self::TABLE)
                    ->setValue('id', ':id')
                    ->setValue('name', ':name')
                    ->setValue('async_task_id', ':async_task_id')
                    ->setValue('item_key', ':item_key')
                    ->setValue('status', ':status')
                    ->setValue('data', ':data')
                    ->setValue('sort_order', ':sort_order')
                    ->setValue('date_entered', ':date_entered')
                    ->setValue('date_modified', ':date_modified')
                    ->setValue('deleted', '0')
                    ->setParameter('id', $id)
                    ->setParameter('name', $itemKey)
                    ->setParameter('async_task_id', $taskId)
                    ->setParameter('item_key', $itemKey)
                    ->setParameter('status', 'queued')
                    ->setParameter('data', $data)
                    ->setParameter('sort_order', $sortOrder)
                    ->setParameter('date_entered', $now)
                    ->setParameter('date_modified', $now);

                $qb->executeStatement();
            } catch (\Throwable $e) {
                $this->logger->error('Failed to insert async task item: ' . $e->getMessage(), [
                    'component' => 'async-task-item-repository',
                    'task_id' => $taskId,
                    'item_key' => $itemKey,
                ]);
                continue;
            }

            $item['id'] = $id;
            $insertedItems[] = $item;
        }

        return $insertedItems;
    }

    /**
     * Fetch a batch of items by status.
     *
     * @param string $taskId
     * @param string $status e.g. 'queued'
     * @param int $limit
     * @return array Array of associative arrays with decoded 'data' and 'result_data'
     */
    public function fetchBatch(string $taskId, string $status, int $limit): array
    {
        try {
            $qb = $this->preparedStatementHandler->createQueryBuilder();
            $qb->select('id', 'async_task_id', 'item_key', 'status', 'error_message', 'data', 'result_data', 'sort_order')
                ->from(self::TABLE)
                ->where('async_task_id = :async_task_id')
                ->andWhere('status = :status')
                ->andWhere('deleted = 0')
                ->orderBy('sort_order', 'ASC')
                ->addOrderBy('date_entered', 'ASC')
                ->setMaxResults($limit)
                ->setParameter('async_task_id', $taskId)
                ->setParameter('status', $status);

            $rows = $qb->fetchAllAssociative();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch async task items: ' . $e->getMessage(), [
                'component' => 'async-task-item-repository',
                'task_id' => $taskId,
            ]);
            return [];
        }

        return array_map([$this, 'decodeRow'], $rows ?: []);
    }

    /**
     * Update an item's status and optional error message.
     *
     * @param string $itemId
     * @param string $status
     * @param string|null $errorMessage
     */
    public function updateItemStatus(string $itemId, string $status, ?string $errorMessage = null): void
    {
        $now = gmdate('Y-m-d H:i:s');

        try {
            $qb = $this->preparedStatementHandler->createQueryBuilder();
            $qb->update(self::TABLE)
                ->set('status', ':status')
                ->set('error_message', ':error_message')
                ->set('date_modified', ':date_modified')
                ->where('id = :id')
                ->setParameter('status', $status)
                ->setParameter('error_message', $errorMessage)
                ->setParameter('date_modified', $now)
                ->setParameter('id', $itemId);

            $qb->executeStatement();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to update async task item status: ' . $e->getMessage(), [
                'component' => 'async-task-item-repository',
                'item_id' => $itemId,
            ]);
        }
    }

    /**
     * Update an item's status, result_data, and optional error message.
     *
     * @param string $itemId
     * @param string $status
     * @param array|null $resultData
     * @param string|null $errorMessage
     */
    public function updateItem(string $itemId, string $status, ?array $resultData = null, ?string $errorMessage = null): void
    {
        $now = gmdate('Y-m-d H:i:s');

        try {
            $qb = $this->preparedStatementHandler->createQueryBuilder();
            $qb->update(self::TABLE)
                ->set('status', ':status')
                ->set('result_data', ':result_data')
                ->set('error_message', ':error_message')
                ->set('date_modified', ':date_modified')
                ->where('id = :id')
                ->setParameter('status', $status)
                ->setParameter('result_data', $resultData !== null ? json_encode($resultData) : null)
                ->setParameter('error_message', $errorMessage)
                ->setParameter('date_modified', $now)
                ->setParameter('id', $itemId);

            $qb->executeStatement();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to update async task item: ' . $e->getMessage(), [
                'component' => 'async-task-item-repository',
                'item_id' => $itemId,
            ]);
        }
    }

    /**
     * Get counts grouped by status for a task.
     *
     * @param string $taskId
     * @return array e.g. ['queued' => 10, 'completed' => 5, 'failed' => 1]
     */
    public function countByStatus(string $taskId): array
    {
        try {
            $qb = $this->preparedStatementHandler->createQueryBuilder();
            $qb->select('status', 'COUNT(*) as cnt')
                ->from(self::TABLE)
                ->where('async_task_id = :async_task_id')
                ->andWhere('deleted = 0')
                ->groupBy('status')
                ->setParameter('async_task_id', $taskId);

            $rows = $qb->fetchAllAssociative();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to count async task items: ' . $e->getMessage(), [
                'component' => 'async-task-item-repository',
                'task_id' => $taskId,
            ]);
            return [];
        }

        $counts = [];
        foreach ($rows ?: [] as $row) {
            $counts[$row['status']] = (int)$row['cnt'];
        }

        return $counts;
    }

    /**
     * Get total item count for a task.
     *
     * @param string $taskId
     * @return int
     */
    public function getTotal(string $taskId): int
    {
        try {
            $qb = $this->preparedStatementHandler->createQueryBuilder();
            $qb->select('COUNT(*) as cnt')
                ->from(self::TABLE)
                ->where('async_task_id = :async_task_id')
                ->andWhere('deleted = 0')
                ->setParameter('async_task_id', $taskId);

            $result = $qb->fetchOne();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to get async task item total: ' . $e->getMessage(), [
                'component' => 'async-task-item-repository',
                'task_id' => $taskId,
            ]);
            return 0;
        }

        return (int)($result ?? 0);
    }

    /**
     * Fetch all items with a given status (useful for finalization).
     *
     * @param string $taskId
     * @param string $status
     * @return array
     */
    public function fetchAllByStatus(string $taskId, string $status): array
    {
        try {
            $qb = $this->preparedStatementHandler->createQueryBuilder();
            $qb->select('id', 'async_task_id', 'item_key', 'status', 'error_message', 'data', 'result_data', 'sort_order')
                ->from(self::TABLE)
                ->where('async_task_id = :async_task_id')
                ->andWhere('status = :status')
                ->andWhere('deleted = 0')
                ->orderBy('sort_order', 'ASC')
                ->addOrderBy('date_entered', 'ASC')
                ->setParameter('async_task_id', $taskId)
                ->setParameter('status', $status);

            $rows = $qb->fetchAllAssociative();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch all async task items by status: ' . $e->getMessage(), [
                'component' => 'async-task-item-repository',
                'task_id' => $taskId,
            ]);
            return [];
        }

        return array_map([$this, 'decodeRow'], $rows ?: []);
    }

    /**
     * Soft-delete all items for a task.
     *
     * @param string $taskId
     */
    public function deleteByTaskId(string $taskId): void
    {
        $now = gmdate('Y-m-d H:i:s');

        try {
            $qb = $this->preparedStatementHandler->createQueryBuilder();
            $qb->update(self::TABLE)
                ->set('deleted', '1')
                ->set('date_modified', ':date_modified')
                ->where('async_task_id = :async_task_id')
                ->setParameter('date_modified', $now)
                ->setParameter('async_task_id', $taskId);

            $qb->executeStatement();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to delete async task items: ' . $e->getMessage(), [
                'component' => 'async-task-item-repository',
                'task_id' => $taskId,
            ]);
        }
    }

    /**
     * Hard-delete all items for a task (permanent removal).
     *
     * @param string $taskId
     */
    public function purgeByTaskId(string $taskId): void
    {
        try {
            $qb = $this->preparedStatementHandler->createQueryBuilder();
            $qb->delete(self::TABLE)
                ->where('async_task_id = :async_task_id')
                ->setParameter('async_task_id', $taskId);

            $qb->executeStatement();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to purge async task items: ' . $e->getMessage(), [
                'component' => 'async-task-item-repository',
                'task_id' => $taskId,
            ]);
        }
    }

    /**
     * Reset failed items to 'queued' for retry, incrementing retry_count.
     * Only retries items that haven't exceeded the max retry limit.
     *
     * @param string $taskId
     * @param int $maxRetries
     * @return int Number of items reset for retry
     */
    public function retryFailedItems(string $taskId, int $maxRetries): int
    {
        $now = gmdate('Y-m-d H:i:s');

        try {
            $qb = $this->preparedStatementHandler->createQueryBuilder();
            $qb->update(self::TABLE)
                ->set('status', ':new_status')
                ->set('error_message', 'NULL')
                ->set('retry_count', 'retry_count + 1')
                ->set('date_modified', ':date_modified')
                ->where('async_task_id = :async_task_id')
                ->andWhere('status = :failed_status')
                ->andWhere('retry_count < :max_retries')
                ->andWhere('deleted = 0')
                ->setParameter('new_status', 'queued')
                ->setParameter('date_modified', $now)
                ->setParameter('async_task_id', $taskId)
                ->setParameter('failed_status', 'failed')
                ->setParameter('max_retries', $maxRetries);

            return (int)$qb->executeStatement();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to retry async task items: ' . $e->getMessage(), [
                'component' => 'async-task-item-repository',
                'task_id' => $taskId,
            ]);
            return 0;
        }
    }

    /**
     * Hard-delete all non-failed items for a task.
     * Failed items are preserved for user review.
     *
     * @param string $taskId
     */
    public function purgeCompletedItems(string $taskId): void
    {
        try {
            $qb = $this->preparedStatementHandler->createQueryBuilder();
            $qb->delete(self::TABLE)
                ->where('async_task_id = :async_task_id')
                ->andWhere('status != :failed_status')
                ->setParameter('async_task_id', $taskId)
                ->setParameter('failed_status', 'failed');

            $qb->executeStatement();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to purge completed async task items: ' . $e->getMessage(), [
                'component' => 'async-task-item-repository',
                'task_id' => $taskId,
            ]);
        }
    }

    /**
     * Decode JSON fields in a row.
     *
     * @param array $row
     * @return array
     */
    protected function decodeRow(array $row): array
    {
        if (!empty($row['data'])) {
            $row['data'] = json_decode($row['data'], true) ?? [];
        } else {
            $row['data'] = [];
        }

        if (!empty($row['result_data'])) {
            $row['result_data'] = json_decode($row['result_data'], true) ?? [];
        } else {
            $row['result_data'] = [];
        }

        return $row;
    }
}
