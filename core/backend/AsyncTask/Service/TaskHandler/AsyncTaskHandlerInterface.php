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

namespace App\AsyncTask\Service\TaskHandler;

use App\Data\Entity\Record;
use App\Engine\Model\Feedback;

interface AsyncTaskHandlerInterface
{
    public function getHandlerKey(): string;

    public function getType(): string;

    /**
     * Return the next batch of items to enqueue.
     *
     * Each item is an AsyncTaskBatchItemInterface — construct one per record:
     *   new AsyncTaskBatchItem($recordId, ['record_id' => $recordId, ...])
     *
     * Use $progress['enqueue_offset'] for pagination.
     * Return an empty array when there are no more items to enqueue.
     *
     * @param Record $task The parent async task record
     * @param array $progress Current progress state (contains 'enqueue_offset' for pagination)
     * @param int $batchSize Max items to return in this call
     * @return AsyncTaskBatchItemInterface[]
     */
    public function getNextBatchToQueue(Record $task, array $progress, int $batchSize): array;

    /**
     * Process a single item.
     * $item contains: 'id', 'item_key', 'data' (decoded), 'sort_order', 'result_data'.
     * Return Feedback with success/failure. Set Feedback::data for result_data storage.
     *
     * @param Record $task The parent async task record
     * @param array $item The item row from async_task_items (with decoded 'data')
     * @return Feedback Result of processing
     */
    public function processItem(Record $task, array $item): Feedback;

    /**
     * Post-processing after all items are done (merge PDFs, generate summary, etc.).
     * Only called if hasFinalization() returns true.
     *
     * @param Record $task The parent async task record
     * @return Feedback Result of finalization
     */
    public function finalize(Record $task): Feedback;

    /**
     * Whether this handler has a finalization phase.
     *
     * @return bool
     */
    public function hasFinalization(): bool;

    /**
     * Maximum number of retries for failed items. Return 0 for no retry.
     *
     * @return int
     */
    public function getMaxItemRetries(): int;

    /**
     * Whether this handler supports the retry-failed action (re-queues only failed items).
     * When true, the retry-failed button is shown on the task record view after a failure.
     *
     * @return bool
     */
    public function allowsFailureRetry(): bool;

    /**
     * Whether this handler supports the rerun action (purges all items and restarts from scratch).
     * When true, the rerun button is shown on the task record view after a failure.
     *
     * @return bool
     */
    public function allowsFailureRerun(): bool;
}
