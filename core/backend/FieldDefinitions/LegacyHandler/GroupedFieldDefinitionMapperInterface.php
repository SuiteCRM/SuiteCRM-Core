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

interface GroupedFieldDefinitionMapperInterface
{
    /**
     * Get default configurations for grouped types
     *
     * @return array
     */
    public function getGroupTypesConfig(): array;

    /**
     * Calculate and set layout
     * @param array $groupedType
     * @param array $fieldDefinition
     * @param array $groupedFields
     * @param string $type
     * @param array $vardefs
     * @return void
     */
    public function setLayout(
        array $groupedType,
        array &$fieldDefinition,
        array $groupedFields,
        string $type,
        array $vardefs
    ): void;

    /**
     * Calculate and set display
     *
     * @param array $groupedType
     * @param array $fieldDefinition
     * @return void
     */
    public function setDisplay(array $groupedType, array &$fieldDefinition): void;

    /**
     * Show Label
     * @param array $groupedType
     * @param array $fieldDefinition
     */
    public function setShowLabel(array $groupedType, array &$fieldDefinition): void;

    /**
     * Get group fields
     *
     * @param $fieldDefinition
     * @param array $groupedType
     * @param string $type
     * @return array
     */
    public function getGroupFields(array $fieldDefinition, array $groupedType, string $type): array;

    /**
     * Map keys
     * @param array $groupedFields
     * @param string $type
     * @param string $groupedKey
     * @return array
     */
    public function getGroupKeyFields(array $groupedFields, string $type, string $groupedKey): array;

    /**
     * Replace Dynamic fields
     * @param array $groupedType
     * @param array $field
     */
    public function replaceDynamicFields(array &$groupedType, array $field): void;
}
