<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2021 SuiteCRM Ltd.
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

namespace App\ViewDefinitions\LegacyHandler\SearchDefs;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\ViewDefinitions\Entity\ViewDefinition;
use App\ViewDefinitions\LegacyHandler\ViewDefinitionMapperInterface;

class SearchDefsEnumToRelateTypeMapper implements ViewDefinitionMapperInterface
{

    /**
     * SearchDefsEnumToRelateTypeMapper constructor.
     */
    public function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return 'search-defs-enum-to-relate-mapper';
    }

    /**
     * @inheritDoc
     */
    public function getModule(): string
    {
        return 'default';
    }

    /**
     * @inheritDoc
     */
    public function map(ViewDefinition $definition, FieldDefinition $fieldDefinition): void
    {
        $search = $definition->getSearch() ?? [];
        $layout = $search['layout'] ?? [];
        $advanced = $layout['advanced'] ?? [];

        if (empty($advanced)) {
            return;
        }

        $vardefs = $fieldDefinition->getVardef();

        $mapped = [];
        foreach ($advanced as $fieldKey => $field) {


            if (empty($field['function'])) {
                $mapped[$fieldKey] = $field;

                continue;
            }

            if (empty($field['function']['name'])) {
                $mapped[$fieldKey] = $field;

                continue;
            }

            if ($field['function']['name'] !== 'get_user_array') {
                $mapped[$fieldKey] = $field;

                continue;
            }

            if ($field['type'] !== 'enum') {
                $mapped[$fieldKey] = $field;

                continue;
            }

            $field['type'] = 'grouped-field';
            $field['name'] .= '-group';
            $field['fieldDefinition']['layout'] = ['assigned_user_name'];
            $field['fieldDefinition']['display'] = 'inline';
            $field['fieldDefinition']['type'] = 'grouped-field';
            $field['fieldDefinition']['groupKey'] = 'assigned_user';

            $field['fieldDefinition']['groupFields'] = [
                'assigned_user_id' => array_merge($vardefs['assigned_user_id'] ?? [], [
                    'name' => 'assigned_user_id',
                    'type' => 'id',
                    'showLabel' => [],
                    'display' => 'none',
                    'group' => 'assigned_user'
                ]),
                'assigned_user_name' => array_merge($vardefs['assigned_user_name'] ?? [], [
                    'name' => 'assigned_user_name',
                    'type' => 'relate',
                    'showLabel' => ['*'],
                    'display' => 'inline',
                    'group' => 'assigned_user'
                ]),
            ];

            $mapped[$field['name']] = $field;
        }

        $search['layout']['advanced'] = $mapped;
        $definition->setSearch($search);
    }

    /**
     * Get grouped field definitions
     *
     * @param string $group
     * @param array|null $vardefs
     * @return array
     */
    protected function getGroupedFieldDefinitions(string $group, ?array $vardefs): array
    {
        $definitions = [];
        foreach ($vardefs as $vardef) {
            $fieldGroup = $vardef['group'] ?? '';
            if (empty($fieldGroup) || $fieldGroup !== $group) {
                continue;
            }

            $name = $vardef['name'] ?? '';

            $definitions[$name] = $vardef;
        }

        return $definitions;
    }
}
