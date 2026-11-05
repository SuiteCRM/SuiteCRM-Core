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

namespace App\Module\Service\Fields\EventReminders\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use App\Module\Service\Fields\EventInvitees\LegacyHandler\EventInviteesHandler;

/**
 * Reminders of a call or a meeting for the event-reminders field (Reminders and
 * Reminders_Invitees), read and saved with the legacy Reminder class, as the
 * legacy edit view does (reminders_data).
 */
class EventRemindersHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'event-reminders';

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * Reminders of the event: [{id, popup, email, timer_popup, timer_email,
     * invitees: [{id, module, module_id, value}]}].
     */
    public function getReminders(string $module, string $id): array
    {
        $this->init();
        $this->startLegacyApp();

        $reminders = [];
        if (isset(EventInviteesHandler::EVENT_MODULES[$module]) && $id !== '') {
            $this->loadReminderClass();
            foreach (\Reminder::loadRemindersData($module, $id) as $reminder) {
                $reminders[] = $this->normalize($reminder);
            }
        }

        $this->close();

        return $reminders;
    }

    /**
     * Saves the reminders of the event (Reminder::saveRemindersDataJson): the
     * reminders and invitees not in $reminders are deleted. Only the invitees of
     * the event can be reminded, as Meeting / Call::removeUnInvitedFromReminders()
     * does. A new event ($copy, e.g. a duplicate) gets new reminders.
     */
    public function saveReminders(string $module, string $id, array $reminders, bool $copy): void
    {
        $this->init();
        $this->startLegacyApp();

        if (isset(EventInviteesHandler::EVENT_MODULES[$module]) && $id !== '') {
            $this->loadReminderClass();
            $invited = $this->readInvited($module, $id);

            $data = [];
            foreach ($reminders as $reminder) {
                if (!is_array($reminder)) {
                    continue;
                }
                $data[] = $this->toReminderData($reminder, $invited, $copy);
            }

            \Reminder::saveRemindersDataJson($module, $id, json_encode($data));
        }

        $this->close();
    }

    protected function toReminderData(array $reminder, array $invited, bool $copy): array
    {
        $invitees = [];
        foreach ($reminder['invitees'] ?? [] as $invitee) {
            $key = ($invitee['module'] ?? '') . ':' . ($invitee['module_id'] ?? '');
            if (!isset($invited[$key])) {
                continue;
            }
            $invitees[] = [
                'id' => $copy ? '' : (string)($invitee['id'] ?? ''),
                'module' => (string)$invitee['module'],
                'module_id' => (string)$invitee['module_id'],
            ];
        }

        return [
            'id' => $copy ? '' : (string)($reminder['id'] ?? ''),
            'popup' => !empty($reminder['popup']),
            'email' => !empty($reminder['email']),
            'timer_popup' => (string)($reminder['timer_popup'] ?? ''),
            'timer_email' => (string)($reminder['timer_email'] ?? ''),
            'invitees' => $invitees,
        ];
    }

    protected function normalize(array $reminder): array
    {
        $invitees = [];
        foreach ($reminder['invitees'] ?? [] as $invitee) {
            $invitees[] = [
                'id' => (string)($invitee['id'] ?? ''),
                'module' => (string)($invitee['module'] ?? ''),
                'module_id' => (string)($invitee['module_id'] ?? ''),
                'value' => html_entity_decode((string)($invitee['value'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            ];
        }

        return [
            'id' => (string)($reminder['id'] ?? ''),
            'popup' => !empty($reminder['popup']),
            'email' => !empty($reminder['email']),
            'timer_popup' => (string)($reminder['timer_popup'] ?? ''),
            'timer_email' => (string)($reminder['timer_email'] ?? ''),
            'invitees' => $invitees,
        ];
    }

    /**
     * "module:id" of the invitees of the event.
     *
     * @return array<string, bool>
     */
    protected function readInvited(string $module, string $id): array
    {
        $db = \DBManagerFactory::getInstance();
        $eventTable = EventInviteesHandler::EVENT_MODULES[$module];

        $invited = [];
        foreach (EventInviteesHandler::INVITEE_MODULES as $inviteeModule => $relation) {
            $result = $db->query(
                'SELECT ' . $relation['column'] . ' AS id FROM ' . $eventTable['table'] . '_' . strtolower($inviteeModule)
                . ' WHERE ' . $eventTable['column'] . ' = ' . $db->quoted($id) . ' AND deleted = 0'
            );
            while ($row = $db->fetchByAssoc($result, false)) {
                $invited[$inviteeModule . ':' . $row['id']] = true;
            }
        }

        return $invited;
    }

    protected function loadReminderClass(): void
    {
        require_once 'modules/Reminders/Reminder.php';
        require_once 'modules/Reminders_Invitees/Reminder_Invitee.php';
    }
}
