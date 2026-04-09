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
 * Default implementation of AsyncTaskBatchItemInterface.
 *
 * Handlers construct one instance per record to enqueue:
 *
 *   return [
 *       new AsyncTaskBatchItem($recordId, ['record_id' => $recordId, 'module' => $module]),
 *       new AsyncTaskBatchItem($otherId,  ['record_id' => $otherId,  'module' => $module]),
 *   ];
 *
 * The item_key is typically the record's ID.  The data array is stored as JSON and
 * decoded back into $item['data'] when processItem() is called.
 */
class AsyncTaskBatchItem implements AsyncTaskBatchItemInterface
{
    public function __construct(
        private string $itemKey,
        private array $data = [],
        private ?int $sortOrder = null,
        private string $module = '',
        private string $name = ''
    ) {
    }

    public function getItemKey(): string
    {
        return $this->itemKey;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getSortOrder(): ?int
    {
        return $this->sortOrder;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getModule(): string
    {
        return $this->module;
    }
}
