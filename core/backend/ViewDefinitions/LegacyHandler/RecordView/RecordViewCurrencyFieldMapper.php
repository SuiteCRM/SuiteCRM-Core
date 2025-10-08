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

namespace App\ViewDefinitions\LegacyHandler\RecordView;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\LegacyHandler\GroupedFieldDefinitionMapperInterface;
use App\ViewDefinitions\Entity\ViewDefinition;
use App\ViewDefinitions\LegacyHandler\ViewDefinitionMapperInterface;

class RecordViewCurrencyFieldMapper implements ViewDefinitionMapperInterface
{
    /**
     * @var GroupedFieldDefinitionMapperInterface
     */
    private $mapper;
    /**
     * @var array
     */
    private $currencyFieldsTypeMap;

    /**
     * RecordViewCurrencyFieldMapper constructor.
     * @param GroupedFieldDefinitionMapperInterface $mapper
     * @param array $currencyFieldsTypeMap
     */
    public function __construct(GroupedFieldDefinitionMapperInterface $mapper, array $currencyFieldsTypeMap)
    {
        $this->mapper = $mapper;
        $this->currencyFieldsTypeMap = $currencyFieldsTypeMap;
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return 'record-currency-field-mapper';
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
        $recordView = $definition->getRecordView() ?? [];
        $panels = $recordView['panels'] ?? [];
        $vardefs = $fieldDefinition->getVardef();

        if (empty($recordView) || empty($panels)) {
            return;
        }

        $typesConfig = $this->getConfig($definition->getId() ?? 'default');

        if (empty($typesConfig)) {
            return;
        }

        foreach ($panels as $panelKey => $panel) {
            foreach ($panel['rows'] as $rowKey => $row) {
                $cols = $this->mapCols($row, $typesConfig, $vardefs);
                $panels[$panelKey]['rows'][$rowKey]['cols'] = $cols;
            }
        }

        $recordView['panels'] = $panels;
        $definition->setRecordView($recordView);
    }

    /**
     * @param string $module
     * @return array
     */
    protected function getConfig(string $module): array
    {
        $registry = $this->currencyFieldsTypeMap;

        $defaultDefinitions = $registry['default'] ?? [];
        $moduleDefinitions = $registry[$module] ?? [];

        return array_merge($defaultDefinitions, $moduleDefinitions);
    }

    /**
     * @param $row
     * @param array $config
     * @param array|null $vardefs
     * @return array cols
     */
    public function mapCols(
        $row,
        array $config,
        ?array &$vardefs
    ): array {
        $cols = [];

        foreach ($row['cols'] as $cellKey => $cell) {
            $type = $cell['type'] ?? '';

            if (empty($config) || empty($config[$type])) {
                $cols[$cellKey] = $cell;
                continue;
            }

            $typeConfig = $config[$type];

            $name = $cell['name'] ?? '';

            $logic = $cell['logic'] ?? [];

            $currencyName = $name;
            $currencyIdName = 'currency_id';
            $baseName = $name . '_usdollar';
            $groupFields = [$currencyName];

            if (!empty($vardefs[$baseName])) {
                $groupFields[] = $baseName;
            }

            if (strpos($name, '_usdollar') !== false) {
                $baseName = $name;
                $currencyName = str_replace('_usdollar', '', $name);
                $groupFields = [$baseName];
            }

            if (!empty($vardefs[$currencyIdName])) {
                array_unshift($groupFields, $currencyIdName);
            }

            $vname = $cell['vname'] ?? '';
            $label = $cell['label'] ?? '';
            $groupName = $currencyName . '-group';

            $cell = [
                'name' => $groupName,
                'type' => 'grouped-field',
                'label' => $label,
                'vname' => $vname,
                'fieldDefinition' => []
            ];

            $cellDefinition = $cell['fieldDefinition'] ?? [];
            $cellDefinition['type'] = 'grouped-field';

            $this->replaceDynamicNames($typeConfig, $currencyName, $baseName, $currencyIdName);

            $this->mapper->setLayout(
                $typeConfig,
                $cellDefinition,
                $groupFields,
                $type,
                $vardefs
            );
            $this->mapper->setDisplay($typeConfig, $cellDefinition);
            $this->mapper->setShowLabel($typeConfig, $cellDefinition);

            $cellDefinition['groupFields'] = $this->mapper->getGroupedFieldDefinitions(
                $groupFields,
                $vardefs,
                $typeConfig
            );

            foreach ($logic as $key => $def){
                $cellDefinition['groupFields'][$name]['logic'][$key] = $def;
            }

            $cell['fieldDefinition'] = $cellDefinition;
            $cols[$cellKey] = $cell;
        }

        return $cols;
    }

    /**
     * @param array $groupedType
     * @param string $currencyName
     * @param string $baseName
     * @param string $currencyIdName
     */
    protected function replaceDynamicNames(
        array &$groupedType,
        string $currencyName,
        string $baseName,
        string $currencyIdName
    ): void {
        $this->replaceInArray($currencyName, $baseName, $currencyIdName, $groupedType['showLabel']);

        $groupedType['definition'] = $this->replaceDefinitions(
            $currencyName,
            $baseName,
            $currencyIdName,
            $groupedType['definition']
        );
    }

    /**
     * @param string $currencyName
     * @param string $baseName
     * @param string $currencyIdName
     * @param array|null $values
     */
    protected function replaceInArray(
        string $currencyName,
        string $baseName,
        string $currencyIdName,
        ?array &$values
    ): void {

        if (empty($values)) {
            return;
        }

        foreach ($values as $key => $item) {

            $values[$key] = str_replace(
                ['{currency}', '{base_currency}', '{currency_id}'],
                [$currencyName, $baseName, $currencyIdName],
                $item
            );

        }
    }

    /**
     * @param string $currencyName
     * @param string $baseName
     * @param string $currencyIdName
     * @param array|null $definitions
     * @return array
     */
    protected function replaceDefinitions(
        string $currencyName,
        string $baseName,
        string $currencyIdName,
        ?array &$definitions
    ): array {

        if (empty($definitions)) {
            return [];
        }

        $defs = [];
        foreach ($definitions as $definitionKey => $definition) {
            $this->replaceLogic($currencyName, $baseName, $currencyIdName, $definition['logic']);

            $key = str_replace(
                ['{currency}', '{base_currency}', '{currency_id}'],
                [$currencyName, $baseName, $currencyIdName],
                $definitionKey
            );

            $defs[$key] = $definition;
        }

        return $defs;
    }

    /**
     * @param string $currencyName
     * @param string $baseName
     * @param string $currencyIdName
     * @param array|null $logic
     */
    protected function replaceLogic(
        string $currencyName,
        string $baseName,
        string $currencyIdName,
        ?array &$logic
    ): void {
        if (empty($logic)) {
            return;
        }

        foreach ($logic as $logicKey => $logicEntry) {
            $this->replaceInArray(
                $currencyName,
                $baseName,
                $currencyIdName,
                $logic[$logicKey]['params']
            );
        }
    }
}
