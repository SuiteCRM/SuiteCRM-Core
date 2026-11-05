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

namespace App\Module\Activities\FieldDefinitions;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\LegacyHandler\FieldDefinitionMapperInterface;

/**
 * Duration of a new call or meeting, as the legacy edit view sets it
 * (Call / Meeting::fill_in_additional_detail_fields(): 0 hours and
 * minutes_value_default minutes). Only the front field definitions get it: a
 * vardef default would also fill the beans created by the API or an import.
 */
abstract class EventDurationDefaultsFieldDefinitionMapper implements FieldDefinitionMapperInterface
{
    public const DEFAULTS = [
        'duration_hours' => '0',
        'duration_minutes' => '15',
    ];

    public function map(FieldDefinition $definition): void
    {
        $vardefs = $definition->getVardef();

        foreach (self::DEFAULTS as $field => $default) {
            $this->setDefault($vardefs, $field, $default);

            // Calls group the minutes with the hours: the record view builds them from the group.
            foreach (array_keys($vardefs) as $name) {
                if (isset($vardefs[$name]['groupFields']) && is_array($vardefs[$name]['groupFields'])) {
                    $this->setDefault($vardefs[$name]['groupFields'], $field, $default);
                }
            }
        }

        $definition->setVardef($vardefs);
    }

    protected function setDefault(array &$vardefs, string $field, string $default): void
    {
        if (isset($vardefs[$field]) && (string)($vardefs[$field]['default'] ?? '') === '') {
            $vardefs[$field]['default'] = $default;
        }
    }
}
