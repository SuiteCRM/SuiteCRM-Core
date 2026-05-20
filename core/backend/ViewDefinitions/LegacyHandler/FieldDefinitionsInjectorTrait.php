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


namespace App\ViewDefinitions\LegacyHandler;


use App\ViewDefinitions\Service\FieldAliasMapper;

trait FieldDefinitionsInjectorTrait
{
    private $defaultFields = [
        'type' => 'type',
        'label' => 'vname',
    ];

    /**
     * Add field definition to current view field metadata
     * @param array|null $vardefs
     * @param $name
     * @param $field
     * @param array $baseViewFieldDefinition
     * @param FieldAliasMapper $fieldAliasMapper
     * @return array
     */
    protected function addFieldDefinition(
        array &$vardefs,
        $name,
        $field,
        array $baseViewFieldDefinition,
        FieldAliasMapper $fieldAliasMapper
    ): array {
        $baseField = $this->getField($field);

        $field = array_merge($baseViewFieldDefinition, $baseField);

        if (!isset($vardefs[$name])) {
            return $field;
        }

        $aliasDefs = $this->getAliasDefinitions($fieldAliasMapper, $vardefs, $name);

        $field['fieldDefinition'] = $aliasDefs;
        $field['name'] = $aliasDefs['name'] ?? $field['name'];

        $field = $this->applyDefaults($field);

        if ($field['name'] === 'email1') {
            $field['type'] = 'email';
            $column['link'] = false;
        }

        return $field;
    }

    /**
     * Get base field structure
     * @param $field
     * @return array
     */
    protected function getField($field): array
    {
        $baseField = $field;

        if (is_string($field)) {
            $baseField = [
                'name' => $field,
            ];
        }

        return $baseField;
    }

    /**
     * Apply defaults
     * @param array $field
     * @return array
     */
    protected function applyDefaults(array $field): array
    {
        foreach ($this->defaultFields as $attribute => $default) {
            if (empty($field[$attribute])) {
                $defaultValue = $field['fieldDefinition'][$default] ?? '';
                $field[$attribute] = $defaultValue;
            }
        }

        return $field;
    }

    /**
     * @param FieldAliasMapper $fieldAliasMapper
     * @param array $vardefs
     * @param string $name
     * @return mixed
     */
    protected function getAliasDefinitions(FieldAliasMapper $fieldAliasMapper, array $vardefs, string $name)
    {
        if (empty($vardefs[$name])) {
            return [];
        }

        $alias = $fieldAliasMapper->map($vardefs[$name]);
        return $vardefs[$alias] ?? $vardefs[$name];
    }
}
