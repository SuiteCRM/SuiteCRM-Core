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

namespace App\Module\Service\Fields\EventInvitees\SaveHandlers;

use App\Data\Entity\Record;
use App\Data\Service\Record\RecordSaveHandlers\RecordFieldTypeSaveHandlerInterface;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\Module\Service\Fields\EventInvitees\LegacyHandler\EventInviteesHandler;
use App\Module\Service\ModuleNameMapperInterface;

/**
 * Saves an event-invitees field, as the legacy edit view does: the field value
 * is taken out of the record attributes before the save, the invitees are saved
 * after it (EventInviteesHandler::saveInvitees), and the invitations sent when
 * the value asks for them ("Save and Send Invites" action).
 * A save without the field value (e.g. inline edit) leaves the invitees unchanged.
 */
class EventInviteesSaveHandler implements RecordFieldTypeSaveHandlerInterface
{
    /**
     * Value of the event being saved, null when it has no invitees value.
     */
    protected ?array $pendingValue = null;

    public function __construct(
        protected EventInviteesHandler $inviteesHandler,
        protected ModuleNameMapperInterface $moduleNameMapper
    ) {
    }

    public function getModule(): string
    {
        return 'default';
    }

    public function getFieldType(): string
    {
        return EventInviteesHandler::HANDLER_KEY;
    }

    public function getKey(): string
    {
        return EventInviteesHandler::HANDLER_KEY;
    }

    public function getModes(): array
    {
        return ['before-save', 'after-save'];
    }

    public function getOrder(): int
    {
        return 0;
    }

    public function run(?Record $previousVersion, Record $inputRecord, ?Record $savedRecord, FieldDefinition $fieldDefinition, string $field): void
    {
        if ($savedRecord === null) {
            $this->beforeSave($inputRecord, $field);
            return;
        }

        if ($this->pendingValue === null) {
            return;
        }

        $value = $this->pendingValue;
        $this->pendingValue = null;

        $this->inviteesHandler->saveInvitees(
            $this->moduleNameMapper->toLegacy($savedRecord->getModule()),
            (string)$savedRecord->getId(),
            is_array($value['invitees'] ?? null) ? $value['invitees'] : [],
            $previousVersion === null,
            !empty($value['sendInvites'])
        );
    }

    protected function beforeSave(Record $inputRecord, string $field): void
    {
        $this->pendingValue = null;

        $attributes = $inputRecord->getAttributes() ?? [];
        if (!array_key_exists($field, $attributes)) {
            return;
        }

        $value = $attributes[$field];
        unset($attributes[$field]);

        if (is_array($value) && is_array($value['invitees'] ?? null)) {
            $this->pendingValue = $value;
        }

        $inputRecord->setAttributes($attributes);
    }
}
