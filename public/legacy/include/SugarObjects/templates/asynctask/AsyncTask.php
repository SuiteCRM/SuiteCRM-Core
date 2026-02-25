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

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

/**
 * Abstract base class for async task modules (Processes, ManualMigrationTasks, etc.).
 *
 * Provides shared behaviour: ACL enforcement, write-only field preservation,
 * and the failed-items query helper. Subclasses must implement hasAccess() and
 * logAccessDenied() to supply module-specific access logic and log messages.
 */
#[\AllowDynamicProperties]
abstract class AsyncTask extends Basic
{
    /**
     * Determine whether the current user may access this record.
     */
    abstract public function hasAccess(): bool;

    /**
     * Write a fatal log entry describing the access denial.
     */
    abstract public function logAccessDenied(string $action): void;

    /**
     * @inheritDoc
     */
    public function retrieve($id = -1, $encode = true, $deleted = true)
    {
        $result = parent::retrieve($id, $encode, $deleted);

        if (!empty($result) && !$this->hasAccess()) {
            $this->logAccessDenied('retrieve');

            return null;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function save($check_notify = false)
    {
        if (!$this->hasAccess()) {
            $this->logAccessDenied('save');
            throw new RuntimeException('Access Denied');
        }

        $this->keepWriteOnlyFieldValues();

        return parent::save($check_notify);
    }

    /**
     * @inheritDoc
     */
    public function bean_implements($interface)
    {
        if ($interface === 'ACL') {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function ACLAccess($view, $is_owner = 'not_set', $in_group = 'not_set')
    {
        $isNotAllowAction = $this->isNotAllowedAction($view);
        if ($isNotAllowAction === true) {
            return false;
        }

        if (!$this->hasAccess()) {
            $this->logAccessDenied("ACLAccess-$view");

            return false;
        }

        return parent::ACLAccess($view, $is_owner, $in_group);
    }

    /**
     * Do not clear write-only fields when saving a previously-fetched record.
     */
    protected function keepWriteOnlyFieldValues(): void
    {
        if (empty($this->fetched_row)) {
            return;
        }

        foreach ($this->field_defs as $field => $field_def) {
            if (empty($field_def['display']) || $field_def['display'] !== 'writeonly') {
                continue;
            }

            if (empty($this->fetched_row[$field])) {
                continue;
            }

            if (!empty($this->$field)) {
                continue;
            }

            $this->$field = $this->fetched_row[$field];
        }
    }

    /**
     * Build the WHERE clause used by the failed async task items subpanel.
     */
    public function getFailedAsyncTaskItems(): array
    {
        $idQuoted = $this->db->quoted($this->id);

        return [
            'select' => '',
            'from' => '',
            'where' => "async_task_items.async_task_id = $idQuoted AND async_task_items.status = 'failed' ",
            'join' => '',
            'join_tables' => [],
        ];
    }

    /**
     * Return true when $view is an action that must never be allowed on async task records.
     */
    protected function isNotAllowedAction(string $view): bool
    {
        $notAllowed = [
            'export',
            'import',
            'massupdate',
            'duplicate',
            'edit',
            'editview',
            'create',
        ];

        return in_array(strtolower($view), $notAllowed);
    }
}
