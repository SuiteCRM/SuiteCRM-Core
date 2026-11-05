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

namespace App\Module\MergeRecords\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * Merge of duplicate records for the merge records view, as the legacy
 * MergeRecords module does (modules/MergeRecords/Step3.php, SaveMerge.php):
 *  - load: the fields that take part in a merge (Step3 show_field()) with the
 *    value of each record, and the options of the dropdown lists;
 *  - merge: the primary record gets the value chosen for each field (a record's
 *    value, or an option of a dropdown list) and is saved,
 *    the related records of the other records are moved to it (email
 *    addresses without duplicates), then the other records are deleted.
 * Those legacy scripts read the request and end with a redirect, so their logic
 * is reproduced here. Values are copied between retrieved beans, in the bean
 * format, as the legacy edit form posted them.
 */
class RecordMerger extends LegacyHandler
{
    public const HANDLER_KEY = 'record-merger';

    /**
     * Fields of Step3 $filter_for_valid_editable_attributes: a field takes part in
     * a merge when it matches one of these attribute sets.
     */
    protected const MERGE_ATTRIBUTES = [
        ['type' => 'datetimecombo', 'source' => 'db'],
        ['type' => 'datetime', 'source' => 'db'],
        ['type' => 'varchar', 'source' => 'db'],
        ['type' => 'enum', 'source' => 'db'],
        ['type' => 'multienum', 'source' => 'db'],
        ['type' => 'text', 'source' => 'db'],
        ['type' => 'date', 'source' => 'db'],
        ['type' => 'time', 'source' => 'db'],
        ['type' => 'bool', 'source' => 'db'],
        ['type' => 'int', 'source' => 'db'],
        ['type' => 'long', 'source' => 'db'],
        ['type' => 'double', 'source' => 'db'],
        ['type' => 'float', 'source' => 'db'],
        ['type' => 'short', 'source' => 'db'],
        ['dbType' => 'varchar', 'source' => 'db'],
        ['dbType' => 'double', 'source' => 'db'],
        ['type' => 'relate'],
    ];

    protected const EXCLUDED_FIELDS = ['date_entered', 'date_modified', 'modified_user_id', 'created_by', 'deleted'];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The records and their merge fields, null when a record is missing, not
     * readable, or the module cannot be merged.
     *
     * @return array{records: array, fields: array}|null
     */
    public function load(string $module, array $ids): ?array
    {
        $this->init();
        $this->startLegacyApp();

        try {
            $beans = [];
            foreach ($this->cleanIds($ids) as $id) {
                $bean = \BeanFactory::getBean($module, $id);
                if (!$bean || empty($bean->id) || !$bean->ACLAccess('view')) {
                    return null;
                }
                $beans[$bean->id] = $bean;
            }

            if (count($beans) < 2 || !$this->canMerge(reset($beans))) {
                return null;
            }

            $records = [];
            foreach ($beans as $id => $bean) {
                $records[] = [
                    'id' => $id,
                    'name' => $this->decode($bean->get_summary_text()),
                    'editable' => $bean->ACLAccess('edit') && $bean->ACLAccess('delete'),
                ];
            }

            $fields = [];
            foreach ($this->getMergeFields(reset($beans)) as $name => $definition) {
                $values = [];
                $raw = [];
                foreach ($beans as $id => $bean) {
                    $values[$id] = $this->displayValue($bean, $name, $definition);
                    $raw[$id] = $this->rawValue($bean, $name, $definition);
                }

                $field = [
                    'name' => $name,
                    'label' => $this->label(reset($beans), $definition),
                    'required' => !empty($definition['required']),
                    'values' => $values,
                    'raw' => $raw,
                ];

                // A dropdown (or multi-select) list, as in the legacy Step3.
                $options = $this->getListOptions($definition);
                if ($options !== null) {
                    $field['type'] = $this->getListType($definition);
                    $field['options'] = [];
                    foreach ($options as $value => $optionLabel) {
                        $field['options'][] = ['value' => (string)$value, 'label' => $this->decode((string)$optionLabel)];
                    }
                }

                $fields[] = $field;
            }

            return ['records' => $records, 'fields' => $fields];
        } finally {
            $this->close();
        }
    }

    /**
     * Merges $mergedIds into $primaryId with the value of the record chosen for
     * each field ($choices: field => record id, the primary value otherwise).
     * Returns false when a record is missing or the user cannot edit the primary
     * record, or edit and delete the merged ones (nothing is changed then).
     */
    public function merge(string $module, string $primaryId, array $mergedIds, array $choices, array $values = []): bool
    {
        $this->init();
        $this->startLegacyApp();

        try {
            $primary = \BeanFactory::getBean($module, $primaryId);
            if (!$primary || empty($primary->id) || !$this->canMerge($primary) || !$primary->ACLAccess('edit')) {
                return false;
            }

            $sources = [];
            foreach ($this->cleanIds($mergedIds) as $id) {
                if ($id === $primary->id) {
                    continue;
                }
                $source = \BeanFactory::getBean($module, $id);
                if (!$source || empty($source->id) || !$source->ACLAccess('edit') || !$source->ACLAccess('delete')) {
                    return false;
                }
                $sources[$source->id] = $source;
            }
            if (empty($sources)) {
                return false;
            }

            $fields = $this->getMergeFields($primary);
            foreach ($fields as $name => $definition) {
                $source = $sources[(string)($choices[$name] ?? '')] ?? null;
                if ($source === null) {
                    continue;
                }
                $primary->$name = $source->$name;
                if (($definition['type'] ?? '') === 'relate' && !empty($definition['id_name'])) {
                    $idName = $definition['id_name'];
                    $primary->$idName = $source->$idName;
                }
            }
            $this->setListValues($primary, $fields, $values);
            $primary->save();

            $excludedLinks = $this->getExcludedLinks($fields);
            foreach ($sources as $source) {
                $this->moveRelatedRecords($primary, $source, $excludedLinks);
                $source->mark_deleted($source->id);
            }

            return true;
        } finally {
            $this->close();
        }
    }

    protected function canMerge(\SugarBean $bean): bool
    {
        global $dictionary;

        return !empty($dictionary[$bean->object_name]['duplicate_merge']);
    }

    /**
     * Step3 show_field(): fields with duplicate_merge enabled, or of a mergeable
     * type, without the system ones.
     */
    protected function getMergeFields(\SugarBean $bean): array
    {
        $fields = [];
        foreach ($bean->field_defs as $name => $definition) {
            if ($this->isMergeField($definition)) {
                $fields[$name] = $definition;
            }
        }

        return $fields;
    }

    protected function isMergeField(array $definition): bool
    {
        if (in_array($definition['name'] ?? '', self::EXCLUDED_FIELDS, true)) {
            return false;
        }

        if (isset($definition['duplicate_merge'])) {
            if ($definition['duplicate_merge'] === 'disabled' || $definition['duplicate_merge'] == false) {
                return false;
            }
            if ($definition['duplicate_merge'] === 'enabled' || $definition['duplicate_merge'] == true) {
                return true;
            }
        }

        if (!empty($definition['auto_increment'])) {
            return false;
        }

        if (empty($definition['source'])) {
            $definition['source'] = 'db';
        }
        if (empty($definition['dbType']) && isset($definition['type'])) {
            $definition['dbType'] = $definition['type'];
        }

        foreach (self::MERGE_ATTRIBUTES as $attributes) {
            $matches = true;
            foreach ($attributes as $attribute => $value) {
                if (($definition[$attribute] ?? null) !== $value) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                return true;
            }
        }

        return false;
    }

    /**
     * Links not moved from the merged records (SaveMerge merged_links): the ones
     * of the relate fields of the merge, whose chosen value wins.
     */
    protected function getExcludedLinks(array $fields): array
    {
        $links = [];
        foreach ($fields as $name => $definition) {
            if (($definition['type'] ?? '') === 'relate' && !empty($definition['link'])) {
                $links[] = $definition['link'];
            }
            if (($definition['type'] ?? '') === 'link') {
                $links[] = $name;
            }
        }

        return $links;
    }

    /**
     * SaveMerge: the related records of $source linked to $primary.
     */
    protected function moveRelatedRecords(\SugarBean $primary, \SugarBean $source, array $excludedLinks): void
    {
        foreach ($primary->get_linked_fields() as $name => $properties) {
            if (in_array($properties['name'], ['modified_user_link', 'created_by_link', 'assigned_user_link'], true)
                || in_array($properties['name'], $excludedLinks, true)) {
                continue;
            }
            if (isset($properties['duplicate_merge'])
                && ($properties['duplicate_merge'] === 'disabled' || $properties['duplicate_merge'] === 'false')) {
                continue;
            }
            if ($name === 'accounts' && $primary->module_dir === 'Opportunities') {
                continue;
            }
            if (!$source->load_relationship($name)) {
                continue;
            }

            $relatedIds = $source->$name->get();
            if (!is_array($relatedIds) || empty($relatedIds) || !$primary->load_relationship($name)) {
                continue;
            }

            $relatedModule = (string)$source->$name->getRelatedModuleName();
            if (strtolower($relatedModule) === 'emailaddresses') {
                $relatedIds = $this->getNewEmailAddressIds($primary, $name, $relatedIds);
            }

            foreach ($relatedIds as $relatedId) {
                $primary->$name->add($relatedId);
            }
        }
    }

    /**
     * SaveMerge handleEmailMerge(): the email addresses of the merged record that
     * the primary record does not have yet.
     */
    protected function getNewEmailAddressIds(\SugarBean $primary, string $link, array $ids): array
    {
        $existing = $this->getEmailAddresses($primary->$link->get());
        $new = [];
        foreach ($this->getEmailAddresses($ids) as $id => $address) {
            if (!in_array($address, $existing, true)) {
                $new[] = $id;
            }
        }

        return $new;
    }

    protected function getEmailAddresses(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $db = \DBManagerFactory::getInstance();
        $quoted = implode(', ', array_map([$db, 'quoted'], $ids));
        $result = $db->query('SELECT id, email_address FROM email_addresses WHERE id IN (' . $quoted . ')');

        $addresses = [];
        while ($row = $db->fetchByAssoc($result, false)) {
            $addresses[(string)$row['id']] = (string)$row['email_address'];
        }

        return $addresses;
    }

    protected function label(\SugarBean $bean, array $definition): string
    {
        $vname = (string)($definition['vname'] ?? '');
        $label = $vname !== '' ? translate($vname, $bean->module_dir) : '';
        if (!is_string($label) || $label === '' || $label === $vname) {
            $label = (string)($definition['name'] ?? '');
        }

        return rtrim($this->decode($label), ': ');
    }

    /**
     * Value as the user reads it: list labels, Yes / No, related record name.
     */
    protected function displayValue(\SugarBean $bean, string $name, array $definition): string
    {
        global $app_list_strings, $app_strings;

        $value = $bean->$name ?? '';
        $type = $definition['type'] ?? '';
        $options = $app_list_strings[$definition['options'] ?? ''] ?? [];

        if (in_array($type, ['enum', 'radioenum', 'dynamicenum'], true)) {
            return $this->decode(is_array($options) ? ($options[$value] ?? $value) : $value);
        }
        if ($type === 'multienum') {
            $labels = [];
            foreach (unencodeMultienum($value) as $item) {
                if ($item !== '') {
                    $labels[] = is_array($options) ? ($options[$item] ?? $item) : $item;
                }
            }

            return $this->decode(implode(', ', $labels));
        }
        if ($type === 'bool') {
            if ($value === '' || $value === null) {
                return '';
            }

            return in_array($value, [true, 1, '1', 'on', 'yes'], true) ? $app_strings['LBL_YES'] : $app_strings['LBL_NO'];
        }

        return $this->decode(is_scalar($value) ? (string)$value : '');
    }

    /**
     * Value compared between the records (identical fields are folded).
     */
    protected function rawValue(\SugarBean $bean, string $name, array $definition): string
    {
        if (($definition['type'] ?? '') === 'relate' && !empty($definition['id_name'])) {
            $idName = $definition['id_name'];

            return trim((string)($bean->$idName ?? ''));
        }

        $value = $bean->$name ?? '';

        return trim($this->decode(is_scalar($value) ? (string)$value : ''));
    }

    /**
     * The values chosen in the dropdown lists (the list of the column chosen for
     * the field), when they are options of the list (several for a multi-select list).
     */
    protected function setListValues(\SugarBean $primary, array $fields, array $values): void
    {
        foreach ($values as $name => $value) {
            $definition = $fields[$name] ?? null;
            $options = $definition !== null ? $this->getListOptions($definition) : null;
            if ($options === null) {
                continue;
            }

            if ($this->getListType($definition) === 'multienum') {
                $items = array_values(array_filter(
                    array_map('strval', is_array($value) ? $value : []),
                    static fn (string $item): bool => array_key_exists($item, $options)
                ));
                $primary->$name = encodeMultienumValue($items);
                continue;
            }

            if (is_scalar($value) && array_key_exists((string)$value, $options)) {
                $primary->$name = (string)$value;
            }
        }
    }

    /**
     * Options of a dropdown or multi-select list field, null for the other fields.
     */
    protected function getListOptions(array $definition): ?array
    {
        global $app_list_strings;

        if (!in_array($definition['type'] ?? '', ['enum', 'radioenum', 'dynamicenum', 'multienum'], true)) {
            return null;
        }

        $options = $app_list_strings[$definition['options'] ?? ''] ?? null;

        return is_array($options) && $options ? $options : null;
    }

    protected function getListType(array $definition): string
    {
        return ($definition['type'] ?? '') === 'multienum' ? 'multienum' : 'enum';
    }

    protected function cleanIds(array $ids): array
    {
        $clean = [];
        foreach ($ids as $id) {
            $id = trim((string)$id);
            if ($id !== '' && preg_match('/^[A-Za-z0-9-]+$/', $id)) {
                $clean[$id] = $id;
            }
        }

        return array_values($clean);
    }

    protected function decode($value): string
    {
        return html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
