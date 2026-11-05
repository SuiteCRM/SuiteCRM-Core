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

namespace App\Module\Service\Fields\EventInvitees\Mappers;

use App\Data\Entity\Record;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\Module\Service\Fields\EventInvitees\LegacyHandler\EventInviteesHandler;
use App\Module\Service\ModuleNameMapperInterface;

/**
 * Value of an event-invitees field: {invitees: [{module, id, name, email,
 * accept_status}]} (EventInviteesHandler::getInvitees).
 */
trait EventInviteesMapperTrait
{
    protected EventInviteesHandler $inviteesHandler;
    protected ModuleNameMapperInterface $moduleNameMapper;

    public function getFieldType(): string
    {
        return EventInviteesHandler::HANDLER_KEY;
    }

    public function getModule(): string
    {
        return 'default';
    }

    public function getKey(): string
    {
        return EventInviteesHandler::HANDLER_KEY;
    }

    public function getOrder(): int
    {
        return 0;
    }

    public function toInternal(Record $record, FieldDefinition $fieldDefinitions, string $field): void
    {
    }

    public function toExternal(Record $record, FieldDefinition $fieldDefinitions, string $field): void
    {
        $attributes = $record->getAttributes() ?? [];

        $attributes[$field] = [
            'invitees' => $this->inviteesHandler->getInvitees(
                $this->moduleNameMapper->toLegacy($record->getModule()),
                (string)($record->getId() ?? $attributes['id'] ?? '')
            ),
        ];

        $record->setAttributes($attributes);
    }
}
