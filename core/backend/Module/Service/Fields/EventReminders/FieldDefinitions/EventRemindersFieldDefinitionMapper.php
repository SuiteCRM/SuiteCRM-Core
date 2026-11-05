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

namespace App\Module\Service\Fields\EventReminders\FieldDefinitions;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\LegacyHandler\FieldDefinitionMapperInterface;
use App\Module\Service\Fields\EventReminders\LegacyHandler\EventRemindersHandler;

/**
 * The legacy reminders function field of calls and meetings (`reminders`,
 * Reminder::getRemindersListView) is an event-reminders field in the record views.
 */
class EventRemindersFieldDefinitionMapper implements FieldDefinitionMapperInterface
{
    public const LEGACY_FUNCTION = 'Reminder::getRemindersListView';

    public function getKey(): string
    {
        return 'event-reminders-field-definition';
    }

    public function getModule(): string
    {
        return 'default';
    }

    public function map(FieldDefinition $definition): void
    {
        $vardefs = $definition->getVardef();
        if (empty($vardefs)) {
            return;
        }

        $mapped = false;
        foreach ($vardefs as $name => $vardef) {
            $function = $vardef['function'] ?? '';
            if (is_array($function)) {
                $function = $function['name'] ?? '';
            }

            if (($vardef['type'] ?? '') !== 'function' || $function !== self::LEGACY_FUNCTION) {
                continue;
            }

            $vardefs[$name]['type'] = EventRemindersHandler::HANDLER_KEY;
            $vardefs[$name]['inline_edit'] = false;
            $mapped = true;
        }

        if ($mapped) {
            $definition->setVardef($vardefs);
        }
    }
}
