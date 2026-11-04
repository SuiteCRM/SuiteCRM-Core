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

namespace App\Module\SavedSearch\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A saved filter (SavedSearch) for the saved filter view: its name, user and Quick Filter, which
 * the view changes, and, read-only, its module, order and criteria. The criteria are read as the
 * filter panel reads them (FilterContentMapper): the filters are made in the filter panel of the
 * lists, not in this view.
 *
 * For the admins and for the user of the filter (the legacy pages show a user his own filters).
 */
class SavedSearchHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'saved-search';

    /** Operators of the filter panel shown with their label (the equality is not shown). */
    protected const OPERATORS = [
        'not_equal' => 'LBL_OPERATOR_NOT_EQUAL',
        'greater_than' => 'LBL_OPERATOR_AFTER',
        'less_than' => 'LBL_OPERATOR_BEFORE',
        'between' => 'LBL_OPERATOR_BETWEEN',
    ];

    /** Labels of the view, from the SavedSearch strings. */
    protected const MODULE_LABELS = [
        'LBL_SAVED_FILTER', 'LBL_CRITERIA', 'LBL_NO_CRITERIA', 'LBL_CRITERIA_HELP', 'LBL_ORDER',
        'LBL_OPERATOR_NOT_EQUAL', 'LBL_OPERATOR_AFTER', 'LBL_OPERATOR_BEFORE', 'LBL_OPERATOR_BETWEEN',
        'LBL_LIST_NAME', 'LBL_LIST_MODULE', 'LBL_QUICK_FILTER', 'LBL_DIRECTION', 'LBL_ASCENDING', 'LBL_DESCENDING',
    ];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The view data of a saved filter.
     */
    public function load(string $id): array
    {
        return $this->inLegacy(function () use ($id) {
            return $this->viewData($this->getSavedSearch($id));
        });
    }

    /**
     * Saves the name, the user and the Quick Filter of a saved filter (the bean keeps its contents).
     */
    public function save(string $id, string $name, string $userId, bool $quickFilter): array
    {
        return $this->inLegacy(function () use ($id, $name, $userId, $quickFilter) {
            $search = $this->getSavedSearch($id);
            $name = trim($name);
            if ($name === '') {
                throw new \InvalidArgumentException('The name of a saved filter is required');
            }

            $search->name = $name;
            $search->quick_filter = $quickFilter ? 1 : 0;
            if ($userId !== '' && $userId !== $search->assigned_user_id) {
                if (!$GLOBALS['current_user']->isAdmin()) {
                    throw new AccessDeniedHttpException('Only an admin gives a saved filter to another user');
                }
                $user = \BeanFactory::getBean('Users', $userId);
                if (empty($user->id)) {
                    throw new \InvalidArgumentException('Unknown user');
                }
                $search->assigned_user_id = $user->id;
            }
            // retrieve() HTML-encodes the JSON of the contents, which the bean would not decode and
            // would empty on save (FilterContentMapper decodes it too).
            $search->contents = html_entity_decode((string)$search->contents, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $search->save();

            return $this->viewData($this->getSavedSearch($id));
        });
    }

    /**
     * Deletes a saved filter, as Delete of the filter panel.
     */
    public function delete(string $id): void
    {
        $this->inLegacy(function () use ($id) {
            $search = $this->getSavedSearch($id);
            $search->mark_deleted($search->id);
        });
    }

    /**
     * The saved filter, for an admin or its user.
     */
    protected function getSavedSearch(string $id): \SavedSearch
    {
        global $current_user;

        $search = \BeanFactory::newBean('SavedSearch');
        $search->retrieve($id);
        if (empty($search->id) || !empty($search->deleted)) {
            throw new NotFoundHttpException('Saved filter not found');
        }
        if (!$current_user->isAdmin() && $search->assigned_user_id !== $current_user->id) {
            throw new AccessDeniedHttpException('The saved filter of another user');
        }

        return $search;
    }

    protected function viewData(\SavedSearch $search): array
    {
        global $app_list_strings, $current_language, $current_user;

        require_once 'include/portability/ApiBeanMapper/ModuleMappers/SavedSearch/FilterContentMapper.php';
        $contents = (new \FilterContentMapper())->parseContent((string)$search->name, (string)$search->contents);
        $module = (string)$search->search_module;
        $fieldDefs = $module !== '' ? (\BeanFactory::newBean($module)->field_defs ?? []) : [];
        $moduleStrings = return_module_language($current_language, 'SavedSearch');

        $criteria = [];
        foreach ($contents['filters'] ?? [] as $field => $filter) {
            $criteria[] = $this->criterion((string)$field, (array)$filter, $fieldDefs, $module);
        }

        $order = '';
        if (!empty($contents['orderBy'])) {
            $order = $this->fieldLabel((string)$contents['orderBy'], $fieldDefs, $module);
        }

        $labels = [];
        foreach (self::MODULE_LABELS as $label) {
            $labels[$label] = rtrim($this->decode((string)($moduleStrings[$label] ?? $label)), ': ');
        }

        return [
            'id' => $search->id,
            'name' => $this->decode((string)$search->name),
            'module' => $module,
            'moduleLabel' => $this->decode((string)($app_list_strings['moduleList'][$module] ?? $module)),
            'userId' => (string)$search->assigned_user_id,
            'userName' => $this->decode((string)get_assigned_user_name($search->assigned_user_id)),
            'quickFilter' => !empty($search->quick_filter),
            'order' => $order,
            'sortOrder' => strtolower((string)($contents['sortOrder'] ?? '')),
            'dateEntered' => (string)($search->fetched_row['date_entered'] ?? ''),
            'dateModified' => (string)($search->fetched_row['date_modified'] ?? ''),
            'criteria' => $criteria,
            'users' => $current_user->isAdmin() ? $this->users() : [],
            'canChangeUser' => $current_user->isAdmin(),
            'labels' => $labels,
        ];
    }

    /**
     * A criterion in words: the field label, the operator when it is not the equality, the values
     * (labels of the options of a dropdown), or the range or period of a date.
     */
    protected function criterion(string $field, array $filter, array $fieldDefs, string $module): array
    {
        global $app_list_strings;

        $def = $fieldDefs[$field] ?? [];
        $operator = (string)($filter['operator'] ?? '=');
        $values = $filter['values'] ?? [];
        $values = is_array($values) ? $values : [$values];
        $options = !empty($def['options']) ? ($app_list_strings[$def['options']] ?? []) : [];

        $words = [];
        foreach ($values as $value) {
            if (is_array($value)) {
                continue;
            }
            $value = (string)$value;
            $words[] = $this->decode((string)(is_array($options) && isset($options[$value]) ? $options[$value] : $value));
        }
        if ($operator === 'between') {
            $words = [trim(($filter['start'] ?? '') . ' – ' . ($filter['end'] ?? ''), ' –')];
        } elseif (!isset(self::OPERATORS[$operator]) && $operator !== '=' && $operator !== '') {
            // A period of a date (last_7_days, this_month...): its label in the date filter list.
            $periods = $app_list_strings['date_range_search_dom'] ?? [];
            $words = [$this->decode((string)($periods[$operator] ?? $operator))];
            $operator = '=';
        } elseif (in_array($operator, ['greater_than', 'less_than', 'not_equal'], true) && !empty($filter['target'])) {
            $words = [(string)$filter['target']];
        }

        return [
            'field' => $field,
            'label' => $this->fieldLabel($field, $fieldDefs, $module),
            'operator' => self::OPERATORS[$operator] ?? '',
            'value' => implode(', ', array_filter($words, static fn ($word) => $word !== '')),
        ];
    }

    protected function fieldLabel(string $field, array $fieldDefs, string $module): string
    {
        $vname = $fieldDefs[$field]['vname'] ?? '';
        $label = $vname !== '' ? translate($vname, $module) : $field;

        return rtrim($this->decode((string)(is_string($label) ? $label : $field)), ': ');
    }

    /**
     * The active users, for an admin who gives the filter to another user.
     */
    protected function users(): array
    {
        $db = \DBManagerFactory::getInstance();
        $result = $db->query(
            "SELECT id, user_name, first_name, last_name FROM users WHERE deleted = 0 AND status = 'Active'"
            . ' AND (is_group = 0 OR is_group IS NULL) AND (portal_only = 0 OR portal_only IS NULL)'
            . ' ORDER BY user_name'
        );

        $users = [];
        while ($row = $db->fetchByAssoc($result, false)) {
            $full = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            $users[] = [
                'id' => $row['id'],
                'name' => $this->decode($row['user_name'] . ($full !== '' ? ' (' . $full . ')' : '')),
            ];
        }

        return $users;
    }

    protected function decode(string $value): string
    {
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    protected function inLegacy(callable $callback)
    {
        $this->init();
        $this->startLegacyApp();

        try {
            return $callback();
        } finally {
            $this->close();
        }
    }
}
