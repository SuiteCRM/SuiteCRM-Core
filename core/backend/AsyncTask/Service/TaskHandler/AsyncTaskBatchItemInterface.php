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

/**
 * Represents a single work item returned by AsyncTaskHandlerInterface::getNextBatchToQueue().
 *
 * Implementations are constructed by the handler during the queueing phase and handed
 * to the runner, which persists them to the async_task_items table.  Once stored, each
 * item is retrieved as a plain array and passed to processItem() — at that point the
 * row will have additional DB-generated fields (id, status, result_data, etc.).
 *
 * Minimal usage:
 *   return [new AsyncTaskBatchItem($recordId, ['record_id' => $recordId, 'module' => $module])];
 */
interface AsyncTaskBatchItemInterface
{
    /**
     * Unique key for this item within the task — usually the record ID being processed.
     * Stored in the item_key column and available in processItem() as $item['item_key'].
     */
    public function getItemKey(): string;

    /**
     * Arbitrary payload passed through to processItem() as $item['data'].
     * Keep only what is needed to process one record; avoid storing large blobs.
     */
    public function getData(): array;

    /**
     * Optional processing order hint.  Items are processed in ascending sort_order
     * within each batch.  Return null to use insertion order (default).
     */
    public function getSortOrder(): ?int;

    /**
     * Optional display name for this item (e.g. record name, filename).
     * When non-empty, the subpanel shows this instead of the raw item key.
     * Return empty string to fall back to item_key as the display value.
     */
    public function getName(): string;

    /**
     * Legacy module name for the record identified by getItemKey(), or empty string
     * when the item key is not a record ID (e.g. CSV row number, filename).
     * Stored in the item_module column and used by the frontend to build record links.
     */
    public function getModule(): string;
}
