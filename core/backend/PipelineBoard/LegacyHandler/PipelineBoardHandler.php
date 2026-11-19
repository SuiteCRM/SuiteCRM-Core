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

namespace App\PipelineBoard\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\PipelineBoard\Model\BoardDefinition;
use App\PipelineBoard\Service\BoardColumns;
use App\PipelineBoard\Service\BoardDefinitions;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * The pipeline board of a module (BoardDefinition), for the SuiteCRM 8 board
 * page: its records in columns, one per value of the stage dropdown.
 *  - The records are those of the list view of the user: the list query of the module
 *    (SugarBean::create_new_list_query()) applies the roles and the security groups.
 *  - Each column has the number of its records and the sum of their amounts in the
 *    system currency; its cards are read by pages, the last changed first.
 *  - A card moved to another column changes the stage of its record through its bean
 *    (audit, logic hooks), when the user can edit the record.
 *  - The folded columns are kept in the preferences of the user.
 */
class PipelineBoardHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'pipeline-board';

    public const MOVED = 'moved';
    public const DENIED = 'denied';
    public const INVALID = 'invalid';

    protected const PREFERENCE_CATEGORY = 'pipeline_board';
    protected const ENUM_TYPES = ['enum', 'radioenum', 'dynamicenum'];
    protected const USER_FILTER = 'assigned_user_id';
    protected const MAX_FOLDED = 200;

    /**
     * @var array<string, string> names of the users read so far, by id
     */
    protected array $userNames = [];

    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        protected BoardDefinitions $definitions,
        protected BoardColumns $columns
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $requestStack
        );
    }

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The board: its filters, its columns with their totals, and the first cards of the
     * columns that are not folded. Null when the user cannot list the module.
     *
     * @param array<string, string> $filters values of the filters, by field
     */
    public function load(string $board, array $filters): ?array
    {
        return $this->inLegacy(function () use ($board, $filters) {
            global $app_list_strings;

            $context = $this->context($board);
            if ($context === null) {
                return null;
            }
            [$definition, $seed, $stages] = $context;

            $boardFilters = $this->filters($definition, $seed, $filters);
            $where = $this->filterWhere($seed, $boardFilters);

            $columns = $this->columns->build(
                $stages,
                $this->totals($definition, $seed, $where),
                $this->foldedStages($definition),
                $definition->closedStages
            );
            foreach ($columns as &$column) {
                if ($column['folded']) {
                    continue;
                }
                $column['loaded'] = true;
                if ($column['count'] > 0) {
                    $column['cards'] = $this->cards($definition, $seed, $where, $column['stage'], 0);
                }
            }
            unset($column);

            $stageDef = $seed->field_defs[$definition->stageField];

            return [
                'board' => $definition->board,
                'module' => $definition->module,
                'title' => (string)($app_list_strings['moduleList'][$definition->module] ?? $definition->module),
                'stageLabel' => $this->fieldLabel($seed, $stageDef),
                'hasAmount' => $definition->amountField !== '',
                'canCreate' => \ACLController::checkAccess($definition->module, 'edit', true),
                'staleDays' => $definition->staleDays,
                'pageSize' => $definition->pageSize,
                'filters' => $boardFilters,
                'columns' => $columns,
            ];
        });
    }

    /**
     * The cards of a column from $offset. Null when the user cannot list the module.
     *
     * @param array<string, string> $filters values of the filters, by field
     */
    public function more(string $board, array $filters, string $stage, int $offset): ?array
    {
        return $this->inLegacy(function () use ($board, $filters, $stage, $offset) {
            $context = $this->context($board);
            if ($context === null) {
                return null;
            }
            [$definition, $seed] = $context;

            $where = $this->filterWhere($seed, $this->filters($definition, $seed, $filters));

            return ['cards' => $this->cards($definition, $seed, $where, $stage, max(0, $offset))];
        });
    }

    /**
     * Moves a record to the column of $stage: MOVED with its card, DENIED when the user
     * cannot edit it, INVALID when the record or the stage does not exist.
     *
     * @return array{result: string, card?: array<string, mixed>}
     */
    public function move(string $board, string $id, string $stage): array
    {
        return $this->inLegacy(function () use ($board, $id, $stage) {
            $context = $this->context($board);
            if ($context === null) {
                return ['result' => self::DENIED];
            }
            [$definition, $seed, $stages] = $context;

            if ($stage === '' || !isset($stages[$stage]) || $id === '') {
                return ['result' => self::INVALID];
            }

            $bean = \BeanFactory::getBean($definition->module, $id);
            if (!$bean || empty($bean->id)) {
                return ['result' => self::INVALID];
            }
            if (!$bean->ACLAccess('Save')) {
                return ['result' => self::DENIED];
            }

            $field = $definition->stageField;
            if ((string)($bean->$field ?? '') !== $stage) {
                $bean->$field = $stage;
                $bean->save();
            }

            $db = \DBManagerFactory::getInstance();
            $rows = $this->rows($definition, $seed, $seed->table_name . '.id = ' . $db->quoted($bean->id), 0, 1);

            return ['result' => self::MOVED, 'card' => $this->describe($definition, $seed, $rows)[0] ?? null];
        });
    }

    /**
     * Keeps a column folded or unfolded for the user.
     */
    public function fold(string $board, string $stage, bool $folded): bool
    {
        return $this->inLegacy(function () use ($board, $stage, $folded) {
            global $current_user;

            $definition = $this->definitions->get($board);
            if ($definition === null || strlen($stage) > 255) {
                return false;
            }

            $stages = array_values(array_diff($this->foldedStages($definition), [$stage]));
            if ($folded) {
                $stages[] = $stage;
            }
            // A board has a few stages: the preference never grows beyond that.
            $stages = array_slice($stages, -self::MAX_FOLDED);

            $current_user->setPreference($definition->board, $stages, 0, self::PREFERENCE_CATEGORY);
            // setPreference() only changes the session: the legacy application writes the
            // preferences at the end of its requests, which the SuiteCRM 8 requests do not run.
            $current_user->savePreferencesToDB();

            return true;
        });
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

    /**
     * The definition of the board, a bean of its module and the values of its stage
     * dropdown; null when there is no such board or the user cannot list the module.
     *
     * @return array{0: BoardDefinition, 1: \SugarBean, 2: array<string, string>}|null
     */
    protected function context(string $board): ?array
    {
        global $app_list_strings;

        $definition = $this->definitions->get($board);
        if ($definition === null) {
            return null;
        }

        $seed = \BeanFactory::newBean($definition->module);
        if (!$seed || !\ACLController::checkAccess($definition->module, 'list', true)) {
            return null;
        }

        $stageDef = $seed->field_defs[$definition->stageField] ?? null;
        $stages = is_array($stageDef) ? ($app_list_strings[$stageDef['options'] ?? ''] ?? null) : null;
        if (!is_array($stages) || !$this->isStored($stageDef) || !in_array($stageDef['type'] ?? '', self::ENUM_TYPES, true)) {
            throw new \RuntimeException(sprintf(
                'Pipeline board "%s": %s.%s is not a dropdown field',
                $definition->board,
                $definition->module,
                $definition->stageField
            ));
        }

        $values = [];
        foreach ($stages as $value => $label) {
            $values[(string)$value] = (string)$label;
        }

        return [$definition, $seed, $values];
    }

    /**
     * The filters of the board with their options and their value, '' when $values has
     * none or one that is not an option.
     *
     * @param array<string, string> $values
     * @return array<int, array{field: string, label: string, value: string, options: array<int, array{value: string, label: string}>}>
     */
    protected function filters(BoardDefinition $definition, \SugarBean $seed, array $values): array
    {
        global $app_list_strings;

        $filters = [];
        foreach ($definition->filters as $field) {
            $def = $seed->field_defs[$field] ?? null;
            if (!is_array($def) || !$this->isStored($def)) {
                continue;
            }

            $options = [];
            if ($field === self::USER_FILTER) {
                $options = $this->userOptions($seed);
                // The label of the user name field ("Assigned to"), not of its id.
                $def = $seed->field_defs['assigned_user_name'] ?? $def;
            } elseif (in_array($def['type'] ?? '', self::ENUM_TYPES, true)) {
                foreach ((array)($app_list_strings[$def['options'] ?? ''] ?? []) as $value => $label) {
                    if ((string)$value !== '') {
                        $options[] = ['value' => (string)$value, 'label' => (string)$label];
                    }
                }
            } else {
                continue;
            }

            $value = (string)($values[$field] ?? '');

            $filters[] = [
                'field' => $field,
                'label' => $this->fieldLabel($seed, $def),
                'value' => in_array($value, array_column($options, 'value'), true) ? $value : '',
                'options' => $options,
            ];
        }

        return $filters;
    }

    /**
     * The users the records of the list of the user are assigned to, by name.
     *
     * @return array<int, array{value: string, label: string}>
     */
    protected function userOptions(\SugarBean $seed): array
    {
        $db = \DBManagerFactory::getInstance();
        $table = $seed->table_name;

        $where = "{$table}.deleted = 0";
        $access = $seed->buildAccessWhere('list');
        if ($access !== '') {
            $where .= " AND ({$access})";
        }

        $ids = [];
        $result = $db->query("SELECT DISTINCT {$table}.assigned_user_id AS id FROM {$table} WHERE {$where}");
        while ($row = $db->fetchByAssoc($result, false)) {
            $ids[] = (string)($row['id'] ?? '');
        }

        $options = [];
        foreach ($this->userNames($ids) as $id => $name) {
            $options[] = ['value' => (string)$id, 'label' => $name];
        }
        usort($options, static fn (array $a, array $b): int => strcasecmp($a['label'], $b['label']));

        return $options;
    }

    /**
     * The names of users, as the user reads names (name format of his locale settings);
     * the users that no longer exist are left out.
     *
     * @param string[] $ids
     * @return array<string, string> by user id
     */
    protected function userNames(array $ids): array
    {
        global $locale;

        $db = \DBManagerFactory::getInstance();

        $missing = array_diff(array_unique(array_filter($ids)), array_keys($this->userNames));
        if ($missing) {
            $in = implode(',', array_map(static fn (string $id): string => $db->quoted($id), $missing));
            $result = $db->query("SELECT id, first_name, last_name, user_name FROM users WHERE id IN ({$in})");
            while ($row = $db->fetchByAssoc($result, false)) {
                $name = trim((string)$locale->getLocaleFormattedName($row['first_name'] ?? '', $row['last_name'] ?? ''));
                $this->userNames[(string)$row['id']] = $this->decode($name !== '' ? $name : (string)$row['user_name']);
            }
        }

        return array_intersect_key($this->userNames, array_flip($ids));
    }

    /**
     * The condition of the chosen filters.
     */
    protected function filterWhere(\SugarBean $seed, array $filters): string
    {
        $db = \DBManagerFactory::getInstance();

        $conditions = [];
        foreach ($filters as $filter) {
            if ($filter['value'] !== '') {
                $conditions[] = $this->column($seed, $filter['field']) . ' = ' . $db->quoted($filter['value']);
            }
        }

        return implode(' AND ', $conditions);
    }

    /**
     * Number of records and sum of their amounts (system currency), by stage.
     *
     * @return array<string, array{count: int, amount: float}>
     */
    protected function totals(BoardDefinition $definition, \SugarBean $seed, string $where): array
    {
        $db = \DBManagerFactory::getInstance();

        $stage = $this->column($seed, $definition->stageField);
        $amountField = $this->baseAmountField($definition, $seed);
        $sum = $amountField !== '' ? 'SUM(' . $this->column($seed, $amountField) . ')' : '0';

        // Only the stage field: no join that could count a record twice.
        $parts = $seed->create_new_list_query('', $where, [$definition->stageField], [], 0, '', true, null, true);

        $totals = [];
        $result = $db->query(
            "SELECT {$stage} AS stage, COUNT(*) AS n, {$sum} AS amount" . $parts['from'] . $parts['where'] . " GROUP BY {$stage}"
        );
        while ($row = $db->fetchByAssoc($result, false)) {
            // No stage: NULL and '' are one column.
            $key = (string)($row['stage'] ?? '');
            $totals[$key] = [
                'count' => ($totals[$key]['count'] ?? 0) + (int)$row['n'],
                'amount' => ($totals[$key]['amount'] ?? 0.0) + (float)$row['amount'],
            ];
        }

        return $totals;
    }

    /**
     * A page of the cards of a column.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function cards(BoardDefinition $definition, \SugarBean $seed, string $where, string $stage, int $offset): array
    {
        $db = \DBManagerFactory::getInstance();

        $column = $this->column($seed, $definition->stageField);
        $conditions = $where !== '' ? [$where] : [];
        $conditions[] = $stage === '' ? "({$column} IS NULL OR {$column} = '')" : $column . ' = ' . $db->quoted($stage);

        $rows = $this->rows($definition, $seed, implode(' AND ', $conditions), $offset, $definition->pageSize);

        return $this->describe($definition, $seed, $rows);
    }

    /**
     * The records of the list of the user matching $where, the last changed first.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function rows(BoardDefinition $definition, \SugarBean $seed, string $where, int $offset, int $limit): array
    {
        $db = \DBManagerFactory::getInstance();
        $table = $seed->table_name;

        $fields = ['name', 'date_modified', 'assigned_user_id', 'currency_id', $definition->stageField];
        array_push(
            $fields,
            $definition->amountField,
            $this->baseAmountField($definition, $seed),
            $definition->subtitleField,
            ...$definition->filters
        );
        $fields = array_values(array_unique(array_filter(
            $fields,
            static fn (string $field): bool => $field !== '' && isset($seed->field_defs[$field])
        )));

        $parts = $seed->create_new_list_query('', $where, $fields, [], 0, '', true, null, true);
        $result = $db->limitQuery(
            $parts['select'] . $parts['from'] . $parts['where'] . " ORDER BY {$table}.date_modified DESC, {$table}.id ASC",
            $offset,
            $limit
        );

        $rows = [];
        while ($row = $db->fetchByAssoc($result, false)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * The cards of records.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function describe(BoardDefinition $definition, \SugarBean $seed, array $rows): array
    {
        global $app_list_strings;

        $editable = $this->editableIds($seed, $rows);
        $baseAmountField = $this->baseAmountField($definition, $seed);
        $userNames = $this->userNames(array_map('strval', array_column($rows, 'assigned_user_id')));
        $now = time();

        $cards = [];
        foreach ($rows as $row) {
            $stage = (string)($row[$definition->stageField] ?? '');
            $idleDays = $this->columns->idleDays($row['date_modified'] ?? null, $now);

            // The labels of the dropdown filters (the opportunity type), as tags.
            $tags = [];
            foreach ($definition->filters as $field) {
                $def = $seed->field_defs[$field] ?? [];
                $value = (string)($row[$field] ?? '');
                if ($field !== self::USER_FILTER && $value !== '' && in_array($def['type'] ?? '', self::ENUM_TYPES, true)) {
                    $tags[] = (string)($app_list_strings[$def['options'] ?? ''][$value] ?? $value);
                }
            }

            $amount = $definition->amountField !== '' ? ($row[$definition->amountField] ?? null) : null;

            $cards[] = [
                'id' => (string)$row['id'],
                'name' => $this->decode(trim((string)($row['name'] ?? ''))),
                'subtitle' => $definition->subtitleField !== ''
                    ? $this->decode(trim((string)($row[$definition->subtitleField] ?? '')))
                    : '',
                'amount' => is_numeric($amount) ? (float)$amount : null,
                'baseAmount' => $baseAmountField !== '' ? (float)($row[$baseAmountField] ?? 0) : 0.0,
                'currencyId' => (string)($row['currency_id'] ?? ''),
                'userName' => $userNames[(string)($row['assigned_user_id'] ?? '')] ?? '',
                'tags' => $tags,
                'modified' => (string)($row['date_modified'] ?? ''),
                'idleDays' => $idleDays,
                'stale' => $this->columns->isStale($idleDays, $definition->staleDays, $definition->isClosed($stage)),
                'canEdit' => $editable === null || in_array($row['id'], $editable, true),
            ];
        }

        return $cards;
    }

    /**
     * The records the user can edit among $rows, as SugarBean::ACLAccess('Save') decides
     * for each of them: all (null), his own, or his own and those of his security groups.
     *
     * @return string[]|null
     */
    protected function editableIds(\SugarBean $seed, array $rows): ?array
    {
        global $current_user;

        $module = $seed->module_dir;
        if (is_admin($current_user) || !$seed->bean_implements('ACL')) {
            return null;
        }
        if (!$rows || !\ACLController::checkAccess($module, 'edit', true, $seed->acltype, true)) {
            return [];
        }

        $own = [];
        foreach ($rows as $row) {
            if (($row['assigned_user_id'] ?? $row['created_by'] ?? null) === $current_user->id) {
                $own[] = $row['id'];
            }
        }

        if (\ACLController::requireOwner($module, 'edit', $seed->acltype)) {
            return $own;
        }

        if (\ACLController::requireSecurityGroup($module, 'edit', $seed->acltype)) {
            $db = \DBManagerFactory::getInstance();
            $table = $seed->table_name;
            $ids = implode(',', array_map(static fn (array $row): string => $db->quoted($row['id']), $rows));
            $groupWhere = \SecurityGroup::getGroupWhere($table, $module, $current_user->id);

            $result = $db->query("SELECT {$table}.id FROM {$table} WHERE {$table}.id IN ({$ids}) AND ({$groupWhere})");
            while ($row = $db->fetchByAssoc($result, false)) {
                $own[] = $row['id'];
            }

            return array_values(array_unique($own));
        }

        return null;
    }

    /**
     * The stages folded for the user: those he chose, else the closed stages.
     *
     * @return string[]
     */
    protected function foldedStages(BoardDefinition $definition): array
    {
        global $current_user;

        $saved = $current_user->getPreference($definition->board, self::PREFERENCE_CATEGORY);

        return is_array($saved) ? array_values(array_map('strval', $saved)) : $definition->closedStages;
    }

    /**
     * The field holding the amounts in the system currency: the *_usdollar field of the
     * amount field when the module has one, else the amount field itself.
     */
    protected function baseAmountField(BoardDefinition $definition, \SugarBean $seed): string
    {
        if ($definition->amountField === '' || !$this->isStored($seed->field_defs[$definition->amountField] ?? null)) {
            return '';
        }

        $converted = $definition->amountField . '_usdollar';

        return $this->isStored($seed->field_defs[$converted] ?? null) ? $converted : $definition->amountField;
    }

    /**
     * Whether a field is a column of the module table or of its custom fields table.
     */
    protected function isStored(?array $def): bool
    {
        return $def !== null && in_array($def['source'] ?? 'db', ['db', 'custom_fields'], true);
    }

    protected function column(\SugarBean $seed, string $field): string
    {
        $custom = ($seed->field_defs[$field]['source'] ?? '') === 'custom_fields';

        return $seed->table_name . ($custom ? '_cstm.' : '.') . $field;
    }

    protected function fieldLabel(\SugarBean $seed, array $def): string
    {
        $label = (string)translate($def['vname'] ?? '', $seed->module_dir);

        return rtrim(trim($label), ':');
    }

    protected function decode(string $value): string
    {
        return html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    }
}
