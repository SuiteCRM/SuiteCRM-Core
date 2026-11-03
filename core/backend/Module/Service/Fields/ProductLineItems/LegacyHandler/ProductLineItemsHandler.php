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

namespace App\Module\Service\Fields\ProductLineItems\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use App\Module\Service\Fields\ProductLineItems\ProductLineItemsCalculator;

/**
 * Line items of a quote, invoice or contract (AOS_Line_Item_Groups and their
 * AOS_Products_Quotes product and service lines) for the product-line-items
 * field: read them, save them, search the products to add.
 *
 * The legacy edit views post the lines and the record save() stores them from
 * $_POST (AOS_Line_Item_Groups::save_groups). The record views have no such
 * post, so the lines are saved here with the same beans and fields. Amounts are
 * given to the beans as numbers: perform_aos_save() unformats strings with the
 * user number format.
 */
class ProductLineItemsHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'product-line-items';

    public const GROUP_MODULE = 'AOS_Line_Item_Groups';
    public const LINE_MODULE = 'AOS_Products_Quotes';

    public const LINE_STRING_FIELDS = ['product_id', 'name', 'part_number', 'item_description', 'description', 'discount', 'vat'];
    public const LINE_NUMBER_FIELDS = [
        'product_qty',
        'product_cost_price',
        'product_list_price',
        'product_discount',
        'product_discount_amount',
        'product_unit_price',
        'vat_amt',
        'product_total_price',
    ];

    public const PRODUCT_SEARCH_LIMIT = 50;

    /**
     * The products listed in the empty product field (all of them in practice).
     */
    public const PRODUCT_LIST_LIMIT = 500;

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * aos.lineItems settings and the currency precision of the user.
     *
     * @return array{enableGroups: bool, totalTax: bool, precision: int}
     */
    public function getSettings(): array
    {
        $this->init();
        $this->startLegacyApp();

        $settings = $this->readSettings();

        $this->close();

        return $settings;
    }

    /**
     * Settings and groups of the record with their lines (amounts as numbers),
     * the lines without group first, as the legacy detail view lists them.
     */
    public function getLineItems(string $module, string $id): array
    {
        $this->init();
        $this->startLegacyApp();

        $settings = $this->readSettings();
        $groups = [];

        if ($id !== '') {
            $groups = $this->readGroups($module, $id, $settings['enableGroups']);
        }

        $this->close();

        return array_merge($settings, ['groups' => $groups]);
    }

    /**
     * Saves the calculated groups (ProductLineItemsCalculator) of the record:
     * updates the groups and lines it already has, creates the new ones and
     * deletes the removed ones. A new record ($copy, e.g. a duplicate) gets new
     * groups and lines.
     */
    public function saveLineItems(string $module, string $id, array $groups, bool $copy): void
    {
        $this->init();
        $this->startLegacyApp();

        $parent = \BeanFactory::getBean($module, $id);
        if ($parent && !empty($parent->id)) {
            $this->saveGroups($parent, $groups, $copy);
        }

        $this->close();
    }

    /**
     * Products whose name or part number contains $query, with their prices in
     * their own currency (the field converts them to the record currency); all the
     * products for an empty query, as the legacy product field lists them before
     * anything is typed.
     */
    public function searchProducts(string $query): array
    {
        $query = trim($query);

        $this->init();
        $this->startLegacyApp();

        $products = [];
        if (\ACLController::checkAccess('AOS_Products', 'list', true)) {
            $products = $this->readProducts($query, $query === '' ? self::PRODUCT_LIST_LIMIT : self::PRODUCT_SEARCH_LIMIT);
        }

        $this->close();

        return $products;
    }

    protected function saveGroups(\SugarBean $parent, array $groups, bool $copy): void
    {
        $settings = $this->readSettings();
        $existingGroups = $this->readIds('aos_line_item_groups', $parent->object_name, $parent->id);
        $existingLines = $this->readIds('aos_products_quotes', $parent->object_name, $parent->id);
        $savedGroups = [];
        $savedLines = [];
        $groupNumber = 0;
        $lineNumber = 0;

        foreach ($groups as $group) {
            $lines = $group['lines'] ?? [];
            $groupId = null;

            if ($settings['enableGroups']) {
                if (empty($lines) && ($group['name'] ?? '') === '') {
                    continue;
                }

                $groupBean = $this->getChildBean(self::GROUP_MODULE, $copy ? '' : (string)($group['id'] ?? ''), $existingGroups);
                $groupBean->name = (string)($group['name'] ?? '');
                foreach (ProductLineItemsCalculator::GROUP_TOTALS as $field) {
                    $groupBean->$field = (float)($group[$field] ?? 0);
                }
                $groupBean->number = ++$groupNumber;
                $this->setParent($groupBean, $parent);
                $groupBean->save();

                $groupId = $groupBean->id;
                $savedGroups[$groupId] = true;
            }

            foreach ($lines as $line) {
                $lineBean = $this->getChildBean(self::LINE_MODULE, $copy ? '' : (string)($line['id'] ?? ''), $existingLines);
                foreach (self::LINE_STRING_FIELDS as $field) {
                    $lineBean->$field = (string)($line[$field] ?? '');
                }
                foreach (self::LINE_NUMBER_FIELDS as $field) {
                    $lineBean->$field = (float)($line[$field] ?? 0);
                }
                $lineBean->group_id = $groupId;
                $lineBean->number = ++$lineNumber;
                $this->setParent($lineBean, $parent);
                $lineBean->save();

                $savedLines[$lineBean->id] = true;
            }
        }

        $this->deleteOthers(self::LINE_MODULE, $existingLines, $savedLines);
        $this->deleteOthers(self::GROUP_MODULE, $existingGroups, $savedGroups);
    }

    protected function readSettings(): array
    {
        global $sugar_config, $current_user;

        $lineItems = $sugar_config['aos']['lineItems'] ?? [];

        $precision = '';
        if (!empty($current_user->id)) {
            $precision = $current_user->getPreference('default_currency_significant_digits');
        }
        if ($precision === '' || $precision === null) {
            $precision = $sugar_config['default_currency_significant_digits'] ?? 2;
        }

        return [
            'enableGroups' => !empty($lineItems['enableGroups']),
            'totalTax' => !empty($lineItems['totalTax']),
            'precision' => (int)$precision,
        ];
    }

    protected function readGroups(string $module, string $id, bool $enableGroups): array
    {
        $db = \DBManagerFactory::getInstance();
        $parent = 'parent_type = ' . $db->quoted($module) . ' AND parent_id = ' . $db->quoted($id) . ' AND deleted = 0';

        $groups = [];
        if ($enableGroups) {
            $result = $db->query(
                'SELECT id, name, ' . implode(', ', ProductLineItemsCalculator::GROUP_TOTALS)
                . ' FROM aos_line_item_groups WHERE ' . $parent . ' ORDER BY number, date_entered'
            );
            while ($row = $db->fetchByAssoc($result, false)) {
                $group = ['id' => (string)$row['id'], 'name' => $this->decode($row['name'] ?? '')];
                foreach (ProductLineItemsCalculator::GROUP_TOTALS as $field) {
                    $group[$field] = (float)($row[$field] ?? 0);
                }
                $group['lines'] = [];
                $groups[$group['id']] = $group;
            }
        }

        $ungrouped = [];
        $result = $db->query(
            'SELECT id, group_id, ' . implode(', ', array_merge(self::LINE_STRING_FIELDS, self::LINE_NUMBER_FIELDS))
            . ' FROM aos_products_quotes WHERE ' . $parent . ' ORDER BY number, date_entered'
        );
        while ($row = $db->fetchByAssoc($result, false)) {
            $line = ['id' => (string)$row['id']];
            foreach (self::LINE_STRING_FIELDS as $field) {
                $line[$field] = $this->decode($row[$field] ?? '');
            }
            foreach (self::LINE_NUMBER_FIELDS as $field) {
                $line[$field] = (float)($row[$field] ?? 0);
            }

            $groupId = (string)($row['group_id'] ?? '');
            if (isset($groups[$groupId])) {
                $groups[$groupId]['lines'][] = $line;
                continue;
            }
            $ungrouped[] = $line;
        }

        $groups = array_values($groups);
        if (!empty($ungrouped)) {
            // Lines saved without group (groups disabled when they were saved): one
            // unnamed group, saved with them on the next save.
            array_unshift($groups, ['id' => '', 'name' => '', 'lines' => $ungrouped]);
        }

        return $groups;
    }

    protected function readProducts(string $query, int $limit): array
    {
        $db = \DBManagerFactory::getInstance();
        $where = 'deleted = 0';
        if ($query !== '') {
            $like = $db->quoted('%' . addcslashes($query, '%_\\') . '%');
            $where .= ' AND (name LIKE ' . $like . ' OR part_number LIKE ' . $like . ')';
        }
        $sql = 'SELECT id, name, part_number, price, cost, currency_id, description FROM aos_products '
            . 'WHERE ' . $where . ' ORDER BY name';

        $result = $db->limitQuery($sql, 0, $limit);
        $products = [];
        while ($row = $db->fetchByAssoc($result, false)) {
            $products[] = [
                'id' => (string)$row['id'],
                'name' => $this->decode($row['name']),
                'part_number' => $this->decode($row['part_number'] ?? ''),
                'price' => (float)($row['price'] ?? 0),
                'cost' => (float)($row['cost'] ?? 0),
                'currency_id' => (string)($row['currency_id'] ?? '') ?: '-99',
                'description' => $this->decode($row['description'] ?? ''),
            ];
        }

        return $products;
    }

    /**
     * Ids of the rows of $table that belong to the record.
     *
     * @return array<string, bool>
     */
    protected function readIds(string $table, string $module, string $id): array
    {
        $db = \DBManagerFactory::getInstance();
        $result = $db->query(
            'SELECT id FROM ' . $table . ' WHERE parent_type = ' . $db->quoted($module)
            . ' AND parent_id = ' . $db->quoted($id) . ' AND deleted = 0'
        );

        $ids = [];
        while ($row = $db->fetchByAssoc($result, false)) {
            $ids[(string)$row['id']] = true;
        }

        return $ids;
    }

    /**
     * The record's own group or line $id, or a new one: an id of another record
     * is never reused.
     */
    protected function getChildBean(string $module, string $id, array $existingIds): \SugarBean
    {
        if ($id !== '' && isset($existingIds[$id])) {
            $bean = \BeanFactory::getBean($module, $id);
            if ($bean && !empty($bean->id)) {
                return $bean;
            }
        }

        return \BeanFactory::newBean($module);
    }

    protected function setParent(\SugarBean $bean, \SugarBean $parent): void
    {
        $bean->parent_id = $parent->id;
        $bean->parent_type = $parent->object_name;
        $bean->currency_id = $parent->currency_id ?? null;
        $bean->assigned_user_id = $parent->assigned_user_id;
    }

    /**
     * Text as typed: the legacy save stores it HTML-encoded.
     */
    protected function decode($value): string
    {
        return html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    protected function deleteOthers(string $module, array $existingIds, array $savedIds): void
    {
        foreach (array_keys(array_diff_key($existingIds, $savedIds)) as $id) {
            $bean = \BeanFactory::newBean($module);
            $bean->mark_deleted($id);
        }
    }
}
