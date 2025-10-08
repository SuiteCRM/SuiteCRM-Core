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
use App\ViewDefinitions\Entity\ViewDefinition;
use App\FieldDefinitions\LegacyHandler\GroupedFieldDefinitionMapperInterface;
use App\ViewDefinitions\LegacyHandler\ViewDefinitionMapperInterface;

class RecordViewGroupTypeMapper implements ViewDefinitionMapperInterface
{
    /**
     * @var GroupedFieldDefinitionMapperInterface
     */
    private $mapper;

    /**
     * RecordViewAddressMapper constructor.
     * @param GroupedFieldDefinitionMapperInterface $mapper
     */
    public function __construct(GroupedFieldDefinitionMapperInterface $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return 'record-view-address';
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

        $typesConfig = $this->mapper->getGroupTypesConfig();

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
     * @param $row
     * @param array $typesConfig
     * @param array|null $vardefs
     * @return array cols
     */
    public function mapCols(
        $row,
        array $typesConfig,
        ?array &$vardefs
    ): array {
        $cols = [];

        foreach ($row['cols'] as $cellKey => $cell) {

            $type = $cell['type'] ?? '';
            $groupedType = $typesConfig[$type] ?? [];

            if (empty($groupedType)) {
                $cols[$cellKey] = $cell;
                continue;
            }

            $cellDefinition = $cell['fieldDefinition'] ?? [];
            $group = $cellDefinition['group'] ?? '';
            $groupDefinition = $vardefs[$group] ?? [];

            if (!empty($groupDefinition)) {
                $cell['name'] = $groupDefinition['name'];
                $cell['type'] = 'grouped-field';

                $cellDefinition = $groupDefinition;
            }

            if (isset($cell['displayParams']['key'])) {
                $cellDefinition['groupKey'] = $cell['displayParams']['key'];
            }

            $this->mapper->replaceDynamicFields($groupedType, $cellDefinition);

            $groupFields = $cellDefinition['groupFields'] ?? [];

            if (empty($groupFields)) {
                $groupFields = $this->mapper->getGroupFields($cellDefinition, $groupedType, $type);
            }

            if (!empty($groupFields)) {
                $this->mapper->setLayout(
                    $groupedType,
                    $cellDefinition,
                    array_keys($groupFields),
                    $type,
                    $vardefs
                );
                $this->mapper->setDisplay($groupedType, $cellDefinition);
                $this->mapper->setShowLabel($groupedType, $cellDefinition);

                $cellDefinition['groupFields'] = $this->mapper->getGroupedFieldDefinitions(
                    array_keys($groupFields),
                    $vardefs,
                    $groupedType
                );
            }

            $cell['fieldDefinition'] = $cellDefinition;
            $cols[$cellKey] = $cell;
        }

        return $cols;
    }
}
