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

namespace App\Module\Service\Fields\EventInvitees\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * Invitees (users, contacts and leads) of a call or a meeting for the
 * event-invitees field: read them with their accept status, save them as the
 * legacy edit view does (MeetingFormBase / CallFormBase::handleSave), search the
 * people to invite and send the invitations.
 */
class EventInviteesHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'event-invitees';

    /**
     * Invitee module => link of the event and id column of its relationship table.
     */
    public const INVITEE_MODULES = [
        'Users' => ['link' => 'users', 'column' => 'user_id'],
        'Contacts' => ['link' => 'contacts', 'column' => 'contact_id'],
        'Leads' => ['link' => 'leads', 'column' => 'lead_id'],
    ];

    /**
     * Event module => relationship table prefix and event id column.
     */
    public const EVENT_MODULES = [
        'Calls' => ['table' => 'calls', 'column' => 'call_id'],
        'Meetings' => ['table' => 'meetings', 'column' => 'meeting_id'],
    ];

    public const SEARCH_LIMIT = 10;

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * Invitees of the event: [{module, id, name, email, accept_status}], users
     * first, as the legacy scheduler lists them.
     */
    public function getInvitees(string $module, string $id): array
    {
        $this->init();
        $this->startLegacyApp();

        $invitees = [];
        if (isset(self::EVENT_MODULES[$module]) && $id !== '') {
            $invitees = $this->readInvitees($module, $id);
        }

        $this->close();

        return $invitees;
    }

    /**
     * Invitees of a new event: the current user, as the legacy edit view adds him.
     */
    public function getDefaultInvitees(): array
    {
        $this->init();
        $this->startLegacyApp();

        global $current_user;

        $invitees = [];
        if (!empty($current_user->id)) {
            $invitees[] = $this->toInvitee('Users', $current_user, 'accept');
        }

        $this->close();

        return $invitees;
    }

    /**
     * Saves the invitees of the event: removed ones are taken out, new ones added,
     * the others keep their accept status. On a new event, the assigned user and
     * the related contact or lead are invited and the organizer accepts. The
     * invitations are sent when asked.
     *
     * @param array $invitees [{module, id}]
     */
    public function saveInvitees(string $module, string $id, array $invitees, bool $newEvent, bool $sendInvites): void
    {
        $this->init();
        $this->startLegacyApp();

        $event = isset(self::EVENT_MODULES[$module]) ? \BeanFactory::getBean($module, $id) : null;
        if ($event && !empty($event->id)) {
            $this->saveEventInvitees($event, $this->groupIds($invitees), $newEvent, $sendInvites);
        }

        $this->close();
    }

    /**
     * Users, contacts and leads whose name or email address starts with $query,
     * the ones the user may list.
     */
    public function search(string $query, int $limit = self::SEARCH_LIMIT): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $this->init();
        $this->startLegacyApp();

        $results = [];
        foreach (array_keys(self::INVITEE_MODULES) as $inviteeModule) {
            if (!\ACLController::checkAccess($inviteeModule, 'list', true)) {
                continue;
            }
            array_push($results, ...$this->searchModule($inviteeModule, $query, $limit));
        }

        $this->close();

        return $results;
    }

    protected function saveEventInvitees(\SugarBean $event, array $ids, bool $newEvent, bool $sendInvites): void
    {
        global $current_user;

        if ($newEvent && !empty($event->assigned_user_id)) {
            $ids['Users'][] = $event->assigned_user_id;
        }
        if ($newEvent && !empty($event->parent_id) && in_array($event->parent_type, ['Contacts', 'Leads'], true)) {
            $ids[$event->parent_type][] = $event->parent_id;
        }

        $eventTable = self::EVENT_MODULES[$event->module_dir];
        foreach (self::INVITEE_MODULES as $inviteeModule => $relation) {
            $wanted = array_values(array_unique(array_filter($ids[$inviteeModule] ?? [])));
            $table = $eventTable['table'] . '_' . strtolower($inviteeModule);
            $existing = $this->readAcceptStatus($event, $table, $eventTable['column'], $relation['column']);

            $event->load_relationship($relation['link']);
            foreach (array_keys(array_diff_key($existing, array_flip($wanted))) as $removedId) {
                $event->{$relation['link']}->delete($event->id, $removedId);
            }

            foreach ($wanted as $inviteeId) {
                if (!isset($existing[$inviteeId])) {
                    $event->{$relation['link']}->add($inviteeId);
                }
            }

            $event->{strtolower($inviteeModule) . '_arr'} = $wanted;
        }

        if ($newEvent && $event->assigned_user_id === $current_user->id) {
            $event->set_accept_status($current_user, 'accept');
        }

        require_once 'modules/vCals/vCal.php';
        \vCal::cache_sugar_vcal($current_user);

        if ($sendInvites) {
            $this->sendInvites($event);
        }
    }

    /**
     * The invitations of the legacy "Send Invites" button (SugarBean save with
     * notifications to Meeting / Call::get_notification_recipients()).
     */
    protected function sendInvites(\SugarBean $event): void
    {
        $admin = \BeanFactory::newBean('Administration');
        $admin->retrieveSettings();

        $event->special_notification = false;
        foreach ($event->get_notification_recipients() as $recipient) {
            $event->send_assignment_notifications($recipient, $admin);
        }
    }

    protected function readInvitees(string $module, string $id): array
    {
        $db = \DBManagerFactory::getInstance();
        $eventTable = self::EVENT_MODULES[$module];

        $invitees = [];
        foreach (self::INVITEE_MODULES as $inviteeModule => $relation) {
            $table = $eventTable['table'] . '_' . strtolower($inviteeModule);
            $result = $db->query(
                'SELECT ' . $relation['column'] . ' AS id, accept_status FROM ' . $table
                . ' WHERE ' . $eventTable['column'] . ' = ' . $db->quoted($id) . ' AND deleted = 0'
            );
            while ($row = $db->fetchByAssoc($result, false)) {
                $bean = \BeanFactory::getBean($inviteeModule, $row['id']);
                if (!$bean || empty($bean->id)) {
                    continue;
                }
                $invitees[] = $this->toInvitee($inviteeModule, $bean, (string)($row['accept_status'] ?? 'none'));
            }
        }

        return $invitees;
    }

    protected function searchModule(string $module, string $query, int $limit): array
    {
        $db = \DBManagerFactory::getInstance();
        $bean = \BeanFactory::newBean($module);
        $table = $bean->table_name;
        $like = $db->quoted(addcslashes($query, '%_\\') . '%');

        $nameMatch = '(' . $table . '.first_name LIKE ' . $like . ' OR ' . $table . '.last_name LIKE ' . $like
            . ' OR CONCAT(' . $table . '.first_name, \' \', ' . $table . '.last_name) LIKE ' . $like
            . ' OR ' . $table . '.id IN (SELECT eabr.bean_id FROM email_addr_bean_rel eabr'
            . ' JOIN email_addresses ea ON ea.id = eabr.email_address_id AND ea.deleted = 0'
            . ' WHERE eabr.deleted = 0 AND eabr.bean_module = ' . $db->quoted($module)
            . ' AND ea.email_address_caps LIKE ' . $db->quoted(strtoupper(addcslashes($query, '%_\\')) . '%') . '))';

        $where = $table . '.deleted = 0 AND ' . $nameMatch;
        if ($module === 'Users') {
            $where .= " AND users.status = 'Active' AND users.is_group = 0 AND users.portal_only = 0";
        }

        $result = $db->limitQuery(
            'SELECT ' . $table . '.id FROM ' . $table . ' WHERE ' . $where . ' ORDER BY ' . $table . '.last_name, ' . $table . '.first_name',
            0,
            $limit
        );

        $invitees = [];
        while ($row = $db->fetchByAssoc($result, false)) {
            $person = \BeanFactory::getBean($module, $row['id']);
            if ($person && !empty($person->id) && ($module === 'Users' || $person->ACLAccess('view'))) {
                $invitees[] = $this->toInvitee($module, $person, 'none');
            }
        }

        return $invitees;
    }

    protected function toInvitee(string $module, \SugarBean $person, string $acceptStatus): array
    {
        $name = $person->full_name ?? '';
        if ($name === '' || $name === null) {
            $name = trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? ''));
        }

        return [
            'module' => $module,
            'id' => (string)$person->id,
            'name' => html_entity_decode((string)$name, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'email' => (string)($person->email1 ?? ''),
            'accept_status' => $acceptStatus !== '' ? $acceptStatus : 'none',
        ];
    }

    /**
     * Ids by invitee module.
     *
     * @return array<string, string[]>
     */
    protected function groupIds(array $invitees): array
    {
        $ids = [];
        foreach ($invitees as $invitee) {
            $module = (string)($invitee['module'] ?? '');
            $id = (string)($invitee['id'] ?? '');
            if ($id !== '' && isset(self::INVITEE_MODULES[$module])) {
                $ids[$module][] = $id;
            }
        }

        return $ids;
    }

    /**
     * @return array<string, string> invitee id => accept status
     */
    protected function readAcceptStatus(\SugarBean $event, string $table, string $eventColumn, string $inviteeColumn): array
    {
        $db = $event->db;
        $result = $db->query(
            'SELECT ' . $inviteeColumn . ' AS id, accept_status FROM ' . $table
            . ' WHERE ' . $eventColumn . ' = ' . $db->quoted($event->id) . ' AND deleted = 0'
        );

        $statuses = [];
        while ($row = $db->fetchByAssoc($result, false)) {
            $statuses[(string)$row['id']] = (string)($row['accept_status'] ?? '');
        }

        return $statuses;
    }
}
