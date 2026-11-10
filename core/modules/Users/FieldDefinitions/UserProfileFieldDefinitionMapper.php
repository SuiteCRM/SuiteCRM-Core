<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2026 SuiteCRM Ltd.
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

namespace App\Module\Users\FieldDefinitions;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\LegacyHandler\FieldDefinitionMapperInterface;
use App\Module\Users\LegacyHandler\UserProfileHandler;

/**
 * Options of the user profile lists built from the configuration (date and time formats, time
 * zones, currencies, name formats, character sets, languages, first day of the week), and the
 * defaults of a new user, as the legacy Users edit view sets them.
 */
class UserProfileFieldDefinitionMapper implements FieldDefinitionMapperInterface
{
    public function __construct(protected UserProfileHandler $handler)
    {
    }

    public function getKey(): string
    {
        return 'user-profile';
    }

    public function getModule(): string
    {
        return 'users';
    }

    public function map(FieldDefinition $definition): void
    {
        $vardefs = $definition->getVardef();
        if (empty($vardefs['pref_datef'])) {
            return;
        }

        ['options' => $options, 'defaults' => $defaults] = $this->handler->getDefinitions();

        foreach ($options as $field => $list) {
            if (!isset($vardefs[$field])) {
                continue;
            }
            $extraOptions = [];
            foreach ($list as $value => $label) {
                $extraOptions[] = ['value' => (string)$value, 'label' => (string)$label];
            }
            $vardefs[$field]['metadata'] = ($vardefs[$field]['metadata'] ?? []) + ['extraOptions' => $extraOptions];
            // The front end enum builds its options (extraOptions included) only for a field
            // with a list name: a name that is not an application list leaves extraOptions only.
            $vardefs[$field]['options'] = $vardefs[$field]['options'] ?? 'user_profile_' . $field;
        }

        // A new user is active (status and employee status: User::isEnabled() needs both to log
        // in) and a regular user, as preselected by the legacy edit view.
        $defaults += ['status' => 'Active', 'employee_status' => 'Active', 'UserType' => 'RegularUser'];

        foreach ($defaults as $field => $default) {
            if (isset($vardefs[$field]) && !isset($vardefs[$field]['default'])) {
                $vardefs[$field]['default'] = is_bool($default) ? ($default ? 'true' : 'false') : (string)$default;
            }
        }

        $definition->setVardef($vardefs);
    }
}
