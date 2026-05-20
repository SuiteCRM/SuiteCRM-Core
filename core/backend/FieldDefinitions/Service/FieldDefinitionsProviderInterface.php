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



namespace App\FieldDefinitions\Service;


use App\FieldDefinitions\Entity\FieldDefinition;

interface FieldDefinitionsProviderInterface
{
    /**
     * Get all exposed user preferences
     * @param string $moduleName
     * @return FieldDefinition
     */
    public function getVardef(string $moduleName): FieldDefinition;


    /**
     * Retrieves the name of the list options corresponding to a specific field within a module.
     * Supports enum, multienum, radioenum, and dynamicenum field types.
     *
     * @param string $module The name of the module to search.
     * @param string $fieldName The name of the field for which to get the list options name.
     * @return string|null The name of the list options if found, or null if not available.
     */
    public function getOptionsKey(string $module, string $fieldName): ?string;

    /**
     * Get definition for a single field
     * @param string $moduleName
     * @param string $field
     * @return array|null
     */
    public function getFieldDefinition(string $moduleName, string $field): ?array;
}
