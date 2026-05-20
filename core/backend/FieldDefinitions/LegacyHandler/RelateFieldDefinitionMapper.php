<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
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

class RelateFieldDefinitionMapper implements FieldDefinitionMapperInterface {

    public function getKey(): string
    {
        return 'relate-field-definition-mapper';
    }

    public function getModule(): string
    {
        return 'default';
    }

    public function map(FieldDefinition $definition): void
    {
        $vardefs = $definition->getVardef();

        foreach ($vardefs as $fieldName => $fieldDefinition) {

            $type = $fieldDefinition['type'] ?? '';
            $rname = $fieldDefinition['rname'] ?? '';

            if ($fieldDefinition['name'] === 'assigned_user_name' ) {
                if ($this->showFullName()) {
                    $fieldDefinition['rname']  = 'full_name';
                } else {
                    $fieldDefinition['rname']  = 'user_name';
                }
                $vardefs[$fieldName] = $fieldDefinition;
            }

            if ($type !== 'relate') {
                continue;
            }

            if (empty($rname) || $rname !== 'last_name') {
                continue;
            }

            $metadata = $fieldDefinition['metadata'] ?? [];
            $metadata['relateSearchField'] = 'name';

            $fieldDefinition['metadata'] = $metadata;

            $vardefs[$fieldName] = $fieldDefinition;
        }

        $definition->setVardef($vardefs);
    }

    /**
     * based on user pref then system pref.
     */
    public function showFullName()
    {
        global $sugar_config;
        global $current_user;
        $showFullName = null;

        if (is_null($showFullName)) {
            $userPref = (is_object($current_user)) ? $current_user->getPreference('use_real_names') : null;
            if ($userPref != null && $userPref !== 'off') {
                $showFullName = 1;
            }
        }
        return $showFullName;
    }
}
