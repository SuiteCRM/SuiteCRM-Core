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

namespace App\Module\Events\FieldDefinitions;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\LegacyHandler\FieldDefinitionMapperInterface;
use App\Module\Events\LegacyHandler\EventDelegatesHandler;

/**
 * Fields of an event in the record view:
 *  - invite_templates lists the email templates of type event, as the legacy views
 *    (FP_events::email_templates fills its list at display time);
 *  - duration is the duration of the event in hours and minutes, as the legacy detail view
 *    (EventDurationEntityMapper), calculated from the start and end at the save
 *    (EventDurationSaveHandler) as the legacy edit view did.
 */
class EventFieldDefinitionMapper implements FieldDefinitionMapperInterface
{
    public function __construct(protected EventDelegatesHandler $delegates)
    {
    }

    public function getKey(): string
    {
        return 'event-fields';
    }

    public function getModule(): string
    {
        return 'events';
    }

    public function map(FieldDefinition $definition): void
    {
        $vardefs = $definition->getVardef();

        if (!empty($vardefs['invite_templates'])) {
            $metadata = $vardefs['invite_templates']['metadata'] ?? [];
            $metadata['extraOptions'] = array_merge([['value' => '', 'label' => '']], $this->delegates->getInviteTemplates());
            $vardefs['invite_templates']['metadata'] = $metadata;
            // Its list (emailTemplates_type_list) is the template types until the legacy views
            // replace it: only the templates. The core enum field shows its extra options with a
            // list only: a list without options.
            $vardefs['invite_templates']['options'] = 'event_invite_templates_dom';
        }

        if (!empty($vardefs['duration'])) {
            $vardefs['duration'] = array_merge($vardefs['duration'], [
                'type' => 'varchar',
                'display' => 'readonly',
                'inline_edit' => false,
            ]);
            unset($vardefs['duration']['options']);
        }

        $definition->setVardef($vardefs);
    }
}
