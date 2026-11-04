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

namespace App\Module\Administration\LegacyHandler\AdminSettings\Page;

/**
 * Map Connector Fields (legacy Connectors&action=ModifyMapping): for each connector and
 * each module it is enabled for, the module field of each connector field, saved by
 * ConnectorsController::action_SaveModifyMapping().
 */
class ConnectorMappingPage extends ConnectorSettingsPage
{
    public function getPageKey(): string
    {
        return 'connector-mapping';
    }

    protected function getPage(): array
    {
        global $app_list_strings;

        $sections = [];
        $values = [];
        foreach ($this->mappedConnectors() as $id => $connector) {
            $source = $connector['source'];
            $fieldDefs = $source->getFieldDefs();
            foreach ($source->getMapping()['beans'] ?? [] as $module => $mapping) {
                $options = ['' => ''] + $this->moduleFields((string)$module);
                $fields = [];
                foreach ($fieldDefs as $field => $def) {
                    if ($field === 'id') {
                        continue;
                    }
                    $name = $this->name($id, (string)$module, (string)$field);
                    $fields[] = [
                        'name' => $name,
                        'label' => $this->decode((string)($this->connectorStrings($id)[$def['vname'] ?? ''] ?? $field)),
                        'type' => 'select',
                        'options' => $this->options($options),
                    ];
                    $values[$name] = (string)($mapping[strtolower((string)$field)] ?? $mapping[$field] ?? '');
                }
                $section = $this->section('', $fields, 'Connectors');
                $section['title'] = $this->connectorName($id, $connector['entry']) . ' — '
                    . $this->decode((string)($app_list_strings['moduleList'][$module] ?? $module));
                $sections[] = $section;
            }
        }

        return [
            'title' => $this->text('LBL_MODIFY_MAPPING_TITLE', 'Connectors'),
            'sections' => $sections ?: [$this->section('LBL_MODIFY_MAPPING_TITLE', [], 'Connectors', 'LBL_MODIFY_MAPPING_DESC')],
            'values' => $values,
            'back' => self::BACK,
        ];
    }

    protected function saveValues(array $values): ?string
    {
        $connectors = $this->mappedConnectors();
        if (!$connectors) {
            return null;
        }

        // The request of the legacy form: the connectors, and <connector>:<module>:<field>=<module field>.
        $mapping = [];
        foreach ($connectors as $id => $connector) {
            foreach (array_keys($connector['source']->getMapping()['beans'] ?? []) as $module) {
                $moduleFields = $this->moduleFields((string)$module);
                foreach (array_keys($connector['source']->getFieldDefs()) as $field) {
                    $value = (string)($values[$this->name($id, (string)$module, (string)$field)] ?? '');
                    if ($field !== 'id' && $value !== '' && array_key_exists($value, $moduleFields)) {
                        $mapping[] = $id . ':' . $module . ':' . $field . '=' . $value;
                    }
                }
            }
        }

        $this->runController('SaveModifyMapping', [
            'mapping_sources' => implode(',', array_keys($connectors)),
            'mapping_values' => implode(',', $mapping),
        ]);

        return null;
    }

    /** The connectors with a mapping (legacy view.modifymapping.php). */
    protected function mappedConnectors(): array
    {
        return $this->connectors(static fn (\source $source) => $source->isEnabledInAdminMapping() && !empty($source->getMapping()));
    }

    /** The fields of a module that a connector field can fill (legacy view.mappingproperties.php). */
    protected function moduleFields(string $module): array
    {
        $bean = \BeanFactory::newBean($module);
        $fields = [];
        foreach ($bean instanceof \SugarBean ? $bean->field_defs : [] as $name => $def) {
            if (!empty($def['source']) && in_array($def['source'], ['non-db', 'function'], true)) {
                continue;
            }
            if (in_array($def['type'] ?? '', ['id', 'link', 'relate'], true)) {
                continue;
            }
            $fields[$name] = rtrim($this->decode((string)translate($def['vname'] ?? $name, $module)), ': ');
        }
        asort($fields);

        return $fields;
    }

    protected function connectorStrings(string $id): array
    {
        require_once 'include/connectors/utils/ConnectorUtils.php';

        return \ConnectorUtils::getConnectorStrings($id) ?: [];
    }

    protected function name(string $id, string $module, string $field): string
    {
        return 'mapping__' . $id . '__' . $module . '__' . $field;
    }
}
