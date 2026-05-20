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

class LegacyGroupedFieldDefinitionMapper implements FieldDefinitionMapperInterface
{
    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return 'legacy-grouped-fields';
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

        $defs = [];
        foreach ($vardefs as $fieldName => $fieldDefinition) {
            $mapped = $fieldDefinition;
            $group = $mapped['group'] ?? '';
            $type = $mapped['type'] ?? '';

            if ($type === 'parent') {
                $defs[$fieldName] = $mapped;
                continue;
            }

            if (!empty($mapped['legacyGroup'])) {
                $defs[$fieldName] = $mapped;
                continue;
            }

            if (empty($group) || !empty($mapped['groupFields'])) {
                $defs[$fieldName] = $mapped;
                continue;
            }

            $groupedDefinition = $vardefs[$group] ?? [];
            $groupedDefinitionType = $groupedDefinition['type'] ?? '';
            if (empty($groupedDefinition)) {
                $groupedDefinition['type'] = 'grouped-field';
                $groupedDefinition['name'] = $group;
            }
            $groupedDefinition['legacyGroup'] = true;

            if ($groupedDefinitionType === 'parent') {
                $defs[$fieldName] = $mapped;
                continue;
            }

            $groupFields = $this->getGroupedFieldDefinitions($group, $vardefs);

            if ($group === $fieldName && count($groupFields) === 1) {
                $defs[$fieldName] = $mapped;
                continue;
            }

            $groupedDefinition['groupFields'] = $groupFields;

            $defs[$fieldName] = $fieldDefinition;
            $defs[$group] = $groupedDefinition;
        }

        $definition->setVardef($defs);
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

    /**
     * Set grouped fields display if not set
     *
     * @param $fieldDefinition
     */
    protected function setDefaultDisplay(&$fieldDefinition): void
    {
        if (empty($fieldDefinition['display'])) {
            $fieldDefinition['display'] = 'vertical';
        }
    }

    /**
     * Set grouped fields layout if not set
     *
     * @param $fieldDefinition
     * @param array $groupedFields
     */
    protected function setDefaultLayout(&$fieldDefinition, array $groupedFields): void
    {
        if (empty($fieldDefinition['layout'])) {
            $fieldDefinition['layout'] = $groupedFields;
        }
    }
}
