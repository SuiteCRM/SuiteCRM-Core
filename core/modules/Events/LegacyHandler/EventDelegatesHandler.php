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

namespace App\Module\Events\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * The delegates of an event (contacts, leads and targets linked to it, with their invitation
 * and acceptance status) for the delegates widget of the record view, as
 * the Delegates subpanel of the legacy page and its buttons (FP_eventsController): list, mark as
 * invited / not invited / attended / not attended / accepted / declined, add (records or the
 * members of target lists), remove, and send the invitations.
 */
class EventDelegatesHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'event-delegates';
    public const PAGE_SIZE = 20;

    public const INVITE_STATUSES = ['Invited', 'Not Invited', 'Attended', 'Not Attended'];
    public const ACCEPT_STATUSES = ['Accepted', 'Declined'];

    /**
     * Relationship of each delegate module: link of the event, table and columns, letter of the
     * accept and decline links of the invitations (responseEntryPoint).
     */
    protected const RELATIONSHIPS = [
        'Contacts' => [
            'link' => 'fp_events_contacts',
            'table' => 'fp_events_contacts_c',
            'event' => 'fp_events_contactsfp_events_ida',
            'delegate' => 'fp_events_contactscontacts_idb',
            'records' => 'contacts',
            'letter' => 'c',
        ],
        'Leads' => [
            'link' => 'fp_events_leads_1',
            'table' => 'fp_events_leads_1_c',
            'event' => 'fp_events_leads_1fp_events_ida',
            'delegate' => 'fp_events_leads_1leads_idb',
            'records' => 'leads',
            'letter' => 'l',
        ],
        'Prospects' => [
            'link' => 'fp_events_prospects_1',
            'table' => 'fp_events_prospects_1_c',
            'event' => 'fp_events_prospects_1fp_events_ida',
            'delegate' => 'fp_events_prospects_1prospects_idb',
            'records' => 'prospects',
            'letter' => 't',
        ],
    ];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * A page of the delegates of an event, by name, with the filters (search: name, account or
     * email; module; invite and accept status) and the counts by status of all the delegates;
     * null when the user cannot see the event.
     */
    public function list(string $eventId, array $filters, int $offset): ?array
    {
        return $this->inLegacy(function () use ($eventId, $filters, $offset) {
            $event = $this->getEvent($eventId, 'DetailView');
            if ($event === null) {
                return null;
            }

            $db = \DBManagerFactory::getInstance();
            $union = $this->unionQuery($event->id);
            $where = $this->filterWhere($filters);

            $total = (int)$db->getOne("SELECT COUNT(*) FROM ($union) d WHERE $where");
            $offset = max(0, min($offset, max(0, $total - 1)));
            $offset -= $offset % self::PAGE_SIZE;

            $rows = [];
            $result = $db->limitQuery("SELECT * FROM ($union) d WHERE $where ORDER BY d.last_name, d.first_name, d.id", $offset, self::PAGE_SIZE);
            while ($row = $db->fetchByAssoc($result, false)) {
                $rows[] = [
                    'module' => $row['module'],
                    'id' => $row['id'],
                    'name' => trim($row['first_name'] . ' ' . $row['last_name']),
                    'account' => (string)$row['account_name'],
                    'email' => (string)$row['email'],
                    'invite' => $row['invite_status'],
                    'accept' => $row['accept_status'],
                ];
            }

            $counts = ['all' => 0, 'invite' => [], 'accept' => []];
            $result = $db->query("SELECT d.invite_status, d.accept_status, COUNT(*) n FROM ($union) d GROUP BY d.invite_status, d.accept_status");
            while ($row = $db->fetchByAssoc($result, false)) {
                $counts['all'] += (int)$row['n'];
                $counts['invite'][$row['invite_status']] = ($counts['invite'][$row['invite_status']] ?? 0) + (int)$row['n'];
                $counts['accept'][$row['accept_status']] = ($counts['accept'][$row['accept_status']] ?? 0) + (int)$row['n'];
            }

            return [
                'rows' => $rows,
                'total' => $total,
                'offset' => $offset,
                'pageSize' => self::PAGE_SIZE,
                'counts' => $counts,
                'editable' => (bool)$event->ACLAccess('Save'),
                'template' => !empty($event->invite_templates),
            ];
        });
    }

    /**
     * Sets the invite (Invited, Not Invited, Attended, Not Attended) or accept (Accepted,
     * Declined) status of delegates: the given ones ([{module, id}]), or all the delegates
     * matching the filters.
     */
    public function setStatus(string $eventId, string $field, string $status, array $delegates, ?array $filters): bool
    {
        return $this->inLegacy(function () use ($eventId, $field, $status, $delegates, $filters) {
            $event = $this->getEvent($eventId, 'Save');
            $statuses = $field === 'accept' ? self::ACCEPT_STATUSES : self::INVITE_STATUSES;
            if ($event === null || !in_array($field, ['invite', 'accept'], true) || !in_array($status, $statuses, true)) {
                return false;
            }

            $db = \DBManagerFactory::getInstance();
            if ($filters !== null) {
                $delegates = [];
                $result = $db->query('SELECT d.module, d.id FROM (' . $this->unionQuery($event->id) . ') d WHERE ' . $this->filterWhere($filters));
                while ($row = $db->fetchByAssoc($result, false)) {
                    $delegates[] = $row;
                }
            }

            $ids = [];
            foreach ($delegates as $delegate) {
                $module = is_array($delegate) ? (string)($delegate['module'] ?? '') : '';
                if (isset(self::RELATIONSHIPS[$module]) && !empty($delegate['id'])) {
                    $ids[$module][] = $db->quoted((string)$delegate['id']);
                }
            }

            $column = $field === 'accept' ? 'accept_status' : 'invite_status';
            foreach ($ids as $module => $quoted) {
                $relationship = self::RELATIONSHIPS[$module];
                foreach (array_chunk($quoted, 500) as $chunk) {
                    $db->query("UPDATE {$relationship['table']} SET $column = " . $db->quoted($status)
                        . " WHERE {$relationship['event']} = " . $db->quoted($event->id)
                        . " AND {$relationship['delegate']} IN (" . implode(', ', $chunk) . ') AND deleted = 0');
                }
            }

            return true;
        });
    }

    /**
     * Adds delegates: records of a delegate module, or the contacts, leads and targets of target
     * lists (ProspectLists), as the legacy Select Delegates button. Delegates already linked
     * keep their status.
     */
    public function add(string $eventId, string $module, array $ids): bool
    {
        return $this->inLegacy(function () use ($eventId, $module, $ids) {
            $event = $this->getEvent($eventId, 'Save');
            if ($event === null || (!isset(self::RELATIONSHIPS[$module]) && $module !== 'ProspectLists')) {
                return false;
            }

            $records = [];
            foreach (array_filter(array_map('strval', $ids)) as $id) {
                if ($module !== 'ProspectLists') {
                    $records[$module][] = $id;
                    continue;
                }

                $list = \BeanFactory::getBean('ProspectLists', $id);
                if (!$list || empty($list->id) || !$list->ACLAccess('DetailView')) {
                    continue;
                }
                foreach (['contacts' => 'Contacts', 'leads' => 'Leads', 'prospects' => 'Prospects'] as $link => $listModule) {
                    if ($list->load_relationship($link)) {
                        $records[$listModule] = array_merge($records[$listModule] ?? [], $list->$link->get());
                    }
                }
            }

            foreach ($records as $recordModule => $recordIds) {
                $link = self::RELATIONSHIPS[$recordModule]['link'];
                if (!$event->load_relationship($link)) {
                    continue;
                }
                $linked = $event->$link->get();
                foreach (array_diff(array_unique($recordIds), $linked) as $recordId) {
                    $event->$link->add($recordId);
                }
            }

            return true;
        });
    }

    /**
     * Removes a delegate from the event.
     */
    public function remove(string $eventId, string $module, string $id): bool
    {
        return $this->inLegacy(function () use ($eventId, $module, $id) {
            $event = $this->getEvent($eventId, 'Save');
            if ($event === null || !isset(self::RELATIONSHIPS[$module]) || $id === '') {
                return false;
            }

            $link = self::RELATIONSHIPS[$module]['link'];

            return $event->load_relationship($link) && $event->$link->delete($event->id, $id) !== false;
        });
    }

    /**
     * Sends the invitation of the event (its email template, with the accept and decline links)
     * to the delegates not invited yet and marks them as invited, as the legacy Send Invites
     * button (FP_eventsController::action_sendinvitemails).
     *
     * @return array{sent: int, failed: int, error?: string}|null
     */
    public function sendInvitations(string $eventId): ?array
    {
        return $this->inLegacy(function () use ($eventId) {
            global $sugar_config, $current_language;

            $event = $this->getEvent($eventId, 'Save');
            if ($event === null) {
                return null;
            }

            $template = \BeanFactory::newBean('EmailTemplates');
            $template->disable_row_level_security = true;
            if (empty($event->invite_templates) || !$template->retrieve($event->invite_templates) || empty($template->id)) {
                return ['sent' => 0, 'failed' => 0, 'error' => 'LBL_ERROR_MSG_5'];
            }

            require_once 'modules/FP_events/controller.php';
            $controller = new \FP_eventsController();
            $strings = return_module_language($current_language, 'FP_events');
            $siteUrl = rtrim((string)$sugar_config['site_url'], " \n\r\t\v\x00/");
            $attachments = \BeanFactory::newBean('Notes')->get_full_list(
                '',
                "parent_type = 'Emails' AND parent_id = " . \DBManagerFactory::getInstance()->quoted($template->id)
            ) ?: [];

            $db = \DBManagerFactory::getInstance();
            $sent = 0;
            $failed = 0;
            foreach (self::RELATIONSHIPS as $relationship) {
                if (!$event->load_relationship($relationship['link'])) {
                    continue;
                }

                foreach ($event->{$relationship['link']}->getBeans() as $delegate) {
                    $where = " WHERE {$relationship['event']} = " . $db->quoted($event->id)
                        . " AND {$relationship['delegate']} = " . $db->quoted($delegate->id) . ' AND deleted = 0';
                    $status = $db->getOne("SELECT invite_status FROM {$relationship['table']}$where");
                    if (!in_array($status, [null, false, '', 'Not Invited'], true)) {
                        continue;
                    }

                    $response = $siteUrl . '/index.php?entryPoint=responseEntryPoint&event=' . $event->id
                        . '&delegate=' . $delegate->id . '&type=' . $relationship['letter'] . '&response=';
                    $event->link = "<a href='{$response}accept'>" . ($strings['LBL_ACCEPT_LINK'] ?? 'Accept') . '</a>';
                    $event->link_declined = "<a href='{$response}decline'>" . ($strings['LBL_DECLINE_LINK'] ?? 'Decline') . '</a>';

                    // As the legacy action: the delegate variables of the template are the contact ones.
                    $body = $template->parse_template_bean($template->body_html, 'Contacts', $delegate);
                    $subject = $template->parse_template_bean($template->subject, 'FP_events', $event);
                    $html = from_html($template->parse_template_bean($body, 'FP_events', $event));
                    $text = wordwrap($template->parse_template_bean($body, 'FP_events', $event), 900);
                    $name = trim($delegate->first_name . ' ' . $delegate->last_name);

                    if ($controller->sendEmail($delegate->email1, $subject, $name, $html, $text, $delegate, $attachments)) {
                        $db->query("UPDATE {$relationship['table']} SET invite_status = 'Invited'$where");
                        $sent++;
                    } else {
                        $GLOBALS['log']->fatal('Event invitation failed to send to: ' . $name . ' at ' . $delegate->email1);
                        $failed++;
                    }
                }
            }

            return ['sent' => $sent, 'failed' => $failed];
        });
    }

    /**
     * The email templates of the invitations (type event), as the legacy event views
     * (FP_events::email_templates).
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function getInviteTemplates(): array
    {
        return $this->inLegacy(static function () {
            $db = \DBManagerFactory::getInstance();
            $templates = [];
            $result = $db->query("SELECT id, name FROM email_templates WHERE deleted = 0 AND type = 'event' ORDER BY name");
            while ($row = $db->fetchByAssoc($result, false)) {
                $templates[] = ['value' => $row['id'], 'label' => html_entity_decode((string)$row['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8')];
            }

            return $templates;
        });
    }

    /**
     * The delegates of an event, one query per module: module, id, names, account, primary
     * email, invite status (Not Invited when empty) and accept status (No Response when empty).
     */
    protected function unionQuery(string $eventId): string
    {
        $db = \DBManagerFactory::getInstance();
        $queries = [];
        foreach (self::RELATIONSHIPS as $module => $relationship) {
            $records = $relationship['records'];
            $account = $module === 'Contacts'
                ? '(SELECT a.name FROM accounts_contacts ac INNER JOIN accounts a ON a.id = ac.account_id AND a.deleted = 0'
                . ' WHERE ac.contact_id = b.id AND ac.deleted = 0 ORDER BY ac.date_modified DESC LIMIT 1)'
                : 'b.account_name';
            $queries[] = 'SELECT ' . $db->quoted($module) . ' AS module, b.id, b.first_name, b.last_name, '
                . "$account AS account_name, "
                . '(SELECT ea.email_address FROM email_addr_bean_rel eabr INNER JOIN email_addresses ea ON ea.id = eabr.email_address_id'
                . ' AND ea.deleted = 0 WHERE eabr.bean_id = b.id AND eabr.bean_module = ' . $db->quoted($module)
                . ' AND eabr.deleted = 0 ORDER BY eabr.primary_address DESC LIMIT 1) AS email, '
                . "COALESCE(NULLIF(r.invite_status, ''), 'Not Invited') AS invite_status, "
                . "COALESCE(NULLIF(r.accept_status, ''), 'No Response') AS accept_status "
                . "FROM {$relationship['table']} r INNER JOIN $records b ON b.id = r.{$relationship['delegate']} AND b.deleted = 0 "
                . "WHERE r.{$relationship['event']} = " . $db->quoted($eventId) . ' AND r.deleted = 0';
        }

        return implode(' UNION ALL ', $queries);
    }

    protected function filterWhere(array $filters): string
    {
        $db = \DBManagerFactory::getInstance();
        $where = ['1 = 1'];

        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            $like = $db->quoted('%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%');
            $where[] = "(CONCAT(IFNULL(d.first_name, ''), ' ', IFNULL(d.last_name, '')) LIKE $like"
                . " OR d.account_name LIKE $like OR d.email LIKE $like)";
        }
        if (isset(self::RELATIONSHIPS[$filters['module'] ?? ''])) {
            $where[] = 'd.module = ' . $db->quoted($filters['module']);
        }
        if (in_array($filters['invite'] ?? '', self::INVITE_STATUSES, true)) {
            $where[] = 'd.invite_status = ' . $db->quoted($filters['invite']);
        }
        if (in_array($filters['accept'] ?? '', array_merge(self::ACCEPT_STATUSES, ['No Response']), true)) {
            $where[] = 'd.accept_status = ' . $db->quoted($filters['accept']);
        }

        return implode(' AND ', $where);
    }

    protected function getEvent(string $id, string $access): ?\SugarBean
    {
        $event = $id !== '' ? \BeanFactory::getBean('FP_events', $id) : null;

        return $event && !empty($event->id) && $event->ACLAccess($access) ? $event : null;
    }

    protected function inLegacy(callable $callback)
    {
        $this->init();
        $this->startLegacyApp();

        try {
            return $callback();
        } finally {
            $this->close();
        }
    }
}
