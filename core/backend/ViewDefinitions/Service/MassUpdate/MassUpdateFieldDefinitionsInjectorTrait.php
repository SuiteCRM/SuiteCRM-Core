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


namespace App\ViewDefinitions\Service\MassUpdate;

use App\ViewDefinitions\LegacyHandler\FieldDefinitionsInjectorTrait;
use App\ViewDefinitions\Service\FieldAliasMapper;

trait MassUpdateFieldDefinitionsInjectorTrait
{
    use FieldDefinitionsInjectorTrait;

    protected $defaultDefinition = [
        'name' => '',
        'label' => '',
    ];

    /**
     * Build columnMassUpdate
     * @param $field
     * @param $key
     * @param array|null $vardefs
     * @param FieldAliasMapper $fieldAliasMapper
     * @return array
     */
    protected function buildField($field, $key, ?array $vardefs, FieldAliasMapper $fieldAliasMapper): array
    {
        if (!empty($field)) {
            $field['label'] = $field['vname'] ?? '';
            $field['name'] = $field['name'] ?? $key;
        }

        $definition = $this->addFieldDefinition(
            $vardefs,
            $key,
            $field,
            $this->defaultDefinition,
            $fieldAliasMapper
        );

        $type = $definition['type'] ?? '';
        $this->addExtraEnumOptions($type, $definition);
        $this->handleBooleans($type, $definition);

        return $definition;
    }

    /**
     * @param string $type
     * @param array $definition
     */
    protected function addExtraEnumOptions(string $type, array &$definition): void
    {
        if ($type !== 'enum' && $type !== 'multienum') {
            return;
        }

        $metadata = $definition['metadata'] ?? [];
        $metadata['extraOptions'][] = [
            'value' => '__SugarMassUpdateClearField__',
            'labelKey' => 'LBL_EMPTY'
        ];

        $definition['metadata'] = $metadata;
    }

    /**
     * @param string $type
     * @param array $definition
     */
    protected function handleBooleans(string $type, array &$definition): void
    {
        if ($type !== 'bool' && $type !== 'boolean') {
            return;
        }
        $definition['type'] = 'enum';

        $fieldDefinition = $definition['fieldDefinition'] ?? [];
        $fieldDefinition['type'] = 'enum';
        $fieldDefinition['options'] = 'dom_int_bool';

        $definition['fieldDefinition'] = $fieldDefinition;
    }
}
