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


namespace App\FieldDefinitions\LegacyHandler;

use App\FieldDefinitions\Entity\FieldDefinition;

class GroupedFieldDefinitionMapper implements FieldDefinitionMapperInterface, GroupedFieldDefinitionMapperInterface
{
    /**
     * @var array
     */
    private $groupedFieldsTypeMap;

    /**
     * GroupedFieldDefinitionMapper constructor.
     * @param array $groupedFieldsTypeMap
     */
    public function __construct(array $groupedFieldsTypeMap)
    {
        $this->groupedFieldsTypeMap = $groupedFieldsTypeMap;
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return 'grouped-fields';
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
    public function map(FieldDefinition $definition): void
    {
        $vardefs = $definition->getVardef();

        if (empty($vardefs)) {
            return;
        }

        $typesConfig = $this->getGroupTypesConfig();

        $defs = [];
        foreach ($vardefs as $fieldName => $fieldDefinition) {
            $mappedDefinition = $fieldDefinition;
            $type = $mappedDefinition['type'] ?? '';
            $groupedType = $typesConfig[$type] ?? [];

            $this->replaceDynamicFields($groupedType, $mappedDefinition);

            if (isset($mappedDefinition['legacyGroup']) && $mappedDefinition['legacyGroup'] === true) {
                $defs[$fieldName] = $mappedDefinition;
                continue;
            }

            $groupedFields = $this->getGroupFields($mappedDefinition, $groupedType, $type);

            if (empty($groupedFields)) {
                $defs[$fieldName] = $mappedDefinition;
                continue;
            }

            $this->setLayout($groupedType, $mappedDefinition, $groupedFields, $type, $vardefs);
            $this->setDisplay($groupedType, $mappedDefinition);
            $this->setShowLabel($groupedType, $mappedDefinition);

            $mappedDefinition['groupFields'] = $this->getGroupedFieldDefinitions(
                $groupedFields,
                $vardefs,
                $groupedType,
                $fieldDefinition
            );

            $mappedDefinition['type'] = 'grouped-field';

            if (!empty($groupedType['name'])) {
                $mappedDefinition['name'] = $groupedType['name'];
                $defs[$groupedType['name']] = $mappedDefinition;

                $defs[$fieldName] = $fieldDefinition;

                continue;
            }

            $defs[$fieldName] = $mappedDefinition;
        }

        $definition->setVardef($defs);
    }

    /**
     * @inheritDoc
     */
    public function getGroupTypesConfig(): array
    {
        return $this->groupedFieldsTypeMap ?? [];
    }

    /**
     * @inheritDoc
     */
    public function setLayout(
        array $groupedType,
        array &$fieldDefinition,
        array $groupedFields,
        string $type,
        array $vardefs
    ): void {
        $layout = $groupedType['layout'] ?? [];

        if (!empty($fieldDefinition['layout'])) {
            $layout = $fieldDefinition['layout'];
        }

        if (empty($layout)) {
            $layout = $groupedFields;
        }

        $groupedKey = $fieldDefinition['groupKey'] ?? '';
        if ($groupedKey) {
            $layout = $this->getGroupKeyFields($layout, $type, $groupedKey);
        }

        $trimmedLayout = [];
        foreach ($layout as $item) {
            if (!in_array($item, $groupedFields, true)) {
                continue;
            }

            $definition = $vardefs[$item] ?? [];

            if (empty($definition)) {
                continue;
            }

            $trimmedLayout[] = $item;
        }

        $fieldDefinition['layout'] = $trimmedLayout;
    }

    /**
     * @inheritDoc
     */
    public function setDisplay(array $groupedType, array &$fieldDefinition): void
    {
        $display = $groupedType['display'] ?? '';

        if (!empty($fieldDefinition['display'])) {
            $display = $fieldDefinition['display'];
        }

        if (empty($display)) {
            $display = 'vertical';
        }

        $fieldDefinition['display'] = $display;
    }

    /**
     * @inheritDoc
     */
    public function setShowLabel(array $groupedType, array &$fieldDefinition): void
    {
        if (empty($groupedType['showLabel']) || !empty($fieldDefinition['showLabel'])) {
            return;
        }

        $fieldDefinition['showLabel'] = $groupedType['showLabel'];
    }

    /**
     * @inheritDoc
     */
    public function getGroupFields(array $fieldDefinition, array $groupedType, string $type): array
    {
        $groupedKey = $fieldDefinition['groupKey'] ?? '';
        $groupedFields = [];

        if (!empty($groupedType['groupFields']) && $groupedKey === '') {
            $groupedFields = $groupedType['groupFields'] ?? [];
        }

        if (!empty($groupedType['groupFields']) && $groupedKey !== '') {
            $groupedFields = $this->getGroupKeyFields($groupedType['groupFields'], $type, $groupedKey);
        }

        if (!empty($fieldDefinition['groupFields'])) {
            $groupedFields = $fieldDefinition['groupFields'];
        }

        return $groupedFields;
    }

    /**
     * @inheritDoc
     */
    public function getGroupKeyFields(array $groupedFields, string $type, string $groupedKey): array
    {
        $mapped = [];
        foreach ($groupedFields as $groupField) {
            $mapped[] = $groupedKey . '_' . $type . '_' . $groupField;
        }

        return $mapped;
    }

    /**
     * Get grouped field definitions
     *
     * @param array $groupedFields
     * @param array|null $vardefs
     * @param array $groupedType
     * @return array
     */
    public function getGroupedFieldDefinitions(array $groupedFields, ?array $vardefs, array $groupedType, array $groupFieldDefinition = []): array
    {
        $definitions = [];
        foreach ($groupedFields as $groupedField) {
            $definition = $vardefs[$groupedField] ?? [];

            if (empty($definition)) {
                continue;
            }

            $mappedDef = $this->injectOverrides($groupedType, $groupedField, $definition, $groupFieldDefinition);

            $definitions[$groupedField] = $mappedDef;
        }

        return $definitions;
    }

    /**
     * Inject definition overrides
     *
     * @param array $groupedType
     * @param $groupedField
     * @param array $definition
     * @return array
     */
    protected function injectOverrides(array $groupedType, $groupedField, array $definition, array $groupFieldDefinition = []): array
    {
        $definitionOverrides = $groupedType['definition'][$groupedField] ?? $groupFieldDefinition['definition'][$groupedField] ?? [];
        if (empty($definitionOverrides)) {
            return $definition;
        }
        $metadata = array_merge($definition['metadata'] ?? [], $definitionOverrides['metadata'] ?? []);
        $merged = array_merge($definition, $definitionOverrides);
        $merged['metadata'] = $metadata;

        return $merged;
    }

    /**
     * @param array $groupedType
     * @param array $field
     */
    public function replaceDynamicFields(
        array &$groupedType,
        array $field
    ): void {
        if (empty($groupedType) || empty($groupedType['replaceAttributes']) || empty($field)) {
            return;
        }

        if (!empty($groupedType['name'])) {
            $groupedType['name'] = $this->replaceInString(
                $field,
                $groupedType,
                $groupedType['name']
            );
        }

        if (!empty($groupedType['groupFields'])) {
            $this->replaceInArray(
                $field,
                $groupedType,
                $groupedType['groupFields']
            );
        }


        if (!empty($groupedType['layout'])) {
            $this->replaceInArray(
                $field,
                $groupedType,
                $groupedType['layout']
            );
        }


        if (!empty($groupedType['showLabel'])) {
            $groupedType['showLabel'] = $this->replaceMap(
                $field,
                $groupedType,
                $groupedType['showLabel']
            );
        }

        if (!empty($groupedType['definition'])) {
            $groupedType['definition'] = $this->replaceDefinitions(
                $field,
                $groupedType,
                $groupedType['definition']
            );
        }
    }

    /**
     * @param array $baseFieldDefinition
     * @param array $groupedType
     * @param array|null $values
     */
    protected function replaceInArray(
        array $baseFieldDefinition,
        array $groupedType,
        ?array &$values
    ): void {
        if (empty($values) || empty($groupedType['replaceAttributes'])) {
            return;
        }

        [$search, $replace] = $this->getReplacements($baseFieldDefinition, $groupedType['replaceAttributes']);

        foreach ($values as $key => $item) {
            $values[$key] = str_replace(
                $search,
                $replace,
                $item
            );
        }
    }

    /**
     * @param array $baseFieldDefinition
     * @param array $groupedType
     * @param string $value
     * @return string
     */
    protected function replaceInString(
        array $baseFieldDefinition,
        array $groupedType,
        ?string $value
    ): string {
        if (empty($value) || empty($groupedType['replaceAttributes'])) {
            return $value;
        }

        [$search, $replace] = $this->getReplacements($baseFieldDefinition, $groupedType['replaceAttributes']);

        return str_replace(
            $search,
            $replace,
            $value
        );
    }

    /**
     * @param array $baseFieldDefinition
     * @param array $groupedType
     * @param array|null $map
     * @return array
     */
    protected function replaceMap(
        array $baseFieldDefinition,
        array &$groupedType,
        ?array &$map
    ): array {
        if (empty($map) || empty($groupedType['replaceAttributes'])) {
            return [];
        }

        [$search, $replace] = $this->getReplacements($baseFieldDefinition, $groupedType['replaceAttributes']);

        $defs = [];
        foreach ($map as $key => $entry) {
            $this->replaceInArray(
                $baseFieldDefinition,
                $groupedType,
                $map[$key]
            );

            $key = str_replace(
                $search,
                $replace,
                $key
            );

            $defs[$key] = $map[$key];
        }

        return $defs;
    }

    /**
     * @param array $baseFieldDefinition
     * @param array $groupedType
     * @param array|null $definitions
     * @return array
     */
    protected function replaceDefinitions(
        array $baseFieldDefinition,
        array &$groupedType,
        ?array &$definitions
    ): array {
        if (empty($definitions) || empty($groupedType['replaceAttributes'])) {
            return [];
        }

        [$search, $replace] = $this->getReplacements($baseFieldDefinition, $groupedType['replaceAttributes']);

        $defs = [];
        foreach ($definitions as $definitionKey => $definition) {
            if (!empty($definitions[$definitionKey]['logic'])) {
                $this->replaceLogic($baseFieldDefinition, $groupedType, $definitions[$definitionKey]['logic']);
            }


            $key = str_replace(
                $search,
                $replace,
                $definitionKey
            );

            $defs[$key] = $definitions[$definitionKey];
        }

        return $defs;
    }

    /**
     * @param array $baseFieldDefinition
     * @param array $groupedType
     * @param array|null $logic
     */
    protected function replaceLogic(
        array $baseFieldDefinition,
        array $groupedType,
        ?array &$logic
    ): void {
        if (empty($logic)) {
            return;
        }

        foreach ($logic as $logicKey => $logicEntry) {
            $this->replaceInArray(
                $baseFieldDefinition,
                $groupedType,
                $logic[$logicKey]['params']['fieldDependencies']
            );
        }
    }

    /**
     * @param array $baseFieldDefinition
     * @param array $replaceAttributes
     * @return array
     */
    protected function getReplacements(array $baseFieldDefinition, array $replaceAttributes): array
    {
        $search = [];
        $replace = [];

        foreach ($replaceAttributes as $attribute) {
            if (!isset($baseFieldDefinition[$attribute])) {
                continue;
            }

            $search[] = '{' . $attribute . '}';
            $replace[] = $baseFieldDefinition[$attribute];
        }

        return [$search, $replace];
    }
}
