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

namespace App\Module\Emails\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use BeanFactory;
use Email;

/**
 * Emails in the list and record views: the sender, recipients (emails_email_addr_rel, else
 * emails_text) and body (emails_text) of an email, and the compose modal of a reply or a
 * forward, prepared as the legacy mail client (EmailsController::composeBean()).
 */
class EmailViewHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'email-view';

    public const REPLY = 'reply';
    public const REPLY_ALL = 'reply-all';
    public const FORWARD = 'forward';

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * Sender, recipients and body of an email, as text (email_from, email_to, email_cc,
     * email_bcc, email_body).
     */
    public function getTexts(string $emailId): array
    {
        $this->init();
        $this->startLegacyApp();

        $row = $this->getText($emailId);
        $addresses = $this->getAddresses($emailId, $row);
        $texts = [
            'email_from' => implode(', ', $addresses['from']),
            'email_to' => implode(', ', $addresses['to']),
            'email_cc' => implode(', ', $addresses['cc']),
            'email_bcc' => implode(', ', $addresses['bcc']),
            'email_body' => $this->getBody($row),
        ];

        $this->close();

        return $texts;
    }

    /**
     * Options of the compose modal (front end record-modal handler) for a reply, a reply to
     * all or a forward of an email; null when the email cannot be read.
     */
    public function getComposeModal(string $emailId, string $mode): ?array
    {
        $this->init();
        $this->startLegacyApp();

        global $current_user;

        $email = BeanFactory::getBean('Emails', $emailId);
        if (!$email instanceof Email || empty($email->id) || !$email->ACLAccess('view')) {
            $this->close();

            return null;
        }

        $row = $this->getText($email->id);
        $addresses = $this->getAddresses($email->id, $row);
        $strings = return_module_language($GLOBALS['current_language'] ?? 'en_us', 'Emails');

        $from = $this->addresses($row['reply_to_addr'] ?? '') ?: $addresses['from'];
        $to = [];
        $cc = [];
        if ($mode === self::REPLY || $mode === self::REPLY_ALL) {
            $to = $from;
        }
        if ($mode === self::REPLY_ALL) {
            // The other recipients, without the user's own addresses (legacy: without the mailbox).
            $own = array_map('strtolower', array_column($current_user->emailAddress->getAddressesByGUID($current_user->id, 'Users') ?: [], 'email_address'));
            $others = array_merge($addresses['to'], $addresses['cc']);
            $cc = array_values(array_filter(array_unique($others), static fn($address) => !in_array(strtolower($address), $own, true) && !in_array($address, $to, true)));
        }

        $prefix = $mode === self::FORWARD ? ($strings['LBL_FW'] ?? 'FW:') : ($strings['LBL_RE'] ?? 'RE:');
        $subject = trim(html_entity_decode((string)$email->name, ENT_QUOTES));
        if (stripos($subject, $prefix) !== 0) {
            $subject = trim($prefix . ' ' . $subject);
        }

        $separator = $strings['LBL_ORIGINAL_MESSAGE_SEPARATOR'] ?? '---';
        $fields = [
            'name' => $subject,
            'description_html' => '<br>' . $separator . '<br>' . $this->getBody($row),
            'to_addrs_names' => array_map([$this, 'recipient'], $to),
            'cc_addrs_names' => array_map([$this, 'recipient'], $cc),
            'bcc_addrs_names' => [],
        ];
        if (!empty($email->parent_type) && !empty($email->parent_id)) {
            $parent = BeanFactory::getBean($email->parent_type, $email->parent_id);
            if ($parent && !empty($parent->id)) {
                $fields['parent_type'] = $email->parent_type;
                $fields['parent_id'] = $email->parent_id;
                $fields['parent_name'] = html_entity_decode((string)$parent->get_summary_text(), ENT_QUOTES);
            }
        }

        $this->close();

        return [
            'module' => 'emails',
            'metadataView' => 'modalComposeView',
            'detached' => true,
            'closable' => false,
            'headerActionsKlass' => 'draft-modal-action',
            'headerClass' => 'left-aligned-title',
            'dynamicTitleKey' => 'LBL_EMAIL_MODAL_DYNAMIC_TITLE',
            'modalOptions' => ['size' => 'lg', 'scrollable' => false],
            // The modal reads the values through a record: the values are not paths of it.
            'record' => ['id' => '', 'module' => 'emails', 'attributes' => []],
            'mapFields' => ['default' => $fields],
        ];
    }

    protected function getText(string $emailId): array
    {
        $db = \DBManagerFactory::getInstance();
        $row = $db->fetchOne(
            'SELECT from_addr, reply_to_addr, to_addrs, cc_addrs, bcc_addrs, description, description_html FROM emails_text WHERE email_id = '
            . $db->quoted($emailId) . ' AND deleted = 0'
        );

        return is_array($row) ? $row : [];
    }

    /**
     * Addresses of an email by type (from, to, cc, bcc): the email address links of the email,
     * else the recipients of a SuiteCRM 8 draft (addrs_metadata), else the address lists of
     * emails_text.
     */
    protected function getAddresses(string $emailId, array $row): array
    {
        $db = \DBManagerFactory::getInstance();
        $addresses = ['from' => [], 'to' => [], 'cc' => [], 'bcc' => []];
        $result = $db->query(
            'SELECT r.address_type, ea.email_address FROM emails_email_addr_rel r'
            . ' JOIN email_addresses ea ON ea.id = r.email_address_id AND ea.deleted = 0'
            . ' WHERE r.email_id = ' . $db->quoted($emailId) . ' AND r.deleted = 0'
        );
        while ($link = $db->fetchByAssoc($result, false)) {
            $type = (string)$link['address_type'];
            if (isset($addresses[$type]) && !in_array($link['email_address'], $addresses[$type], true)) {
                $addresses[$type][] = (string)$link['email_address'];
            }
        }

        // Not html encoded (getOne() encodes): the column holds json.
        $email = $db->fetchByAssoc(
            $db->query('SELECT addrs_metadata FROM emails WHERE id = ' . $db->quoted($emailId)),
            false
        );
        $metadata = json_decode((string)($email['addrs_metadata'] ?? ''), true);
        $columns = ['from' => 'from_addr', 'to' => 'to_addrs', 'cc' => 'cc_addrs', 'bcc' => 'bcc_addrs'];
        foreach ($columns as $type => $column) {
            if ($addresses[$type] === [] && is_array($metadata[$type . '_addrs_names'] ?? null)) {
                foreach ($metadata[$type . '_addrs_names'] as $recipient) {
                    $address = (string)($recipient['email'] ?? $recipient['email1'] ?? $recipient['name'] ?? '');
                    if ($address !== '') {
                        $addresses[$type][] = $address;
                    }
                }
            }
            if ($addresses[$type] === []) {
                $addresses[$type] = $this->addresses($row[$column] ?? '');
            }
        }

        return $addresses;
    }

    /**
     * Body of an email as html: the html body, else the text body.
     */
    protected function getBody(array $row): string
    {
        // Stored html encoded.
        $html = html_entity_decode((string)($row['description_html'] ?? ''), ENT_QUOTES);
        if (trim(strip_tags($html)) !== '' || stripos($html, '<img') !== false) {
            return $html;
        }

        return nl2br(htmlspecialchars(html_entity_decode((string)($row['description'] ?? ''), ENT_QUOTES), ENT_QUOTES));
    }

    /**
     * Email addresses of an address list ("Name <a@b.org>, c@d.org").
     */
    protected function addresses(string $list): array
    {
        preg_match_all('/[^\s<>,;"\']+@[^\s<>,;"\']+/', html_entity_decode($list, ENT_QUOTES), $matches);

        return array_values(array_unique($matches[0] ?? []));
    }

    /**
     * A recipient of the compose modal given by its email address.
     */
    protected function recipient(string $address): array
    {
        return ['id' => $address, 'name' => $address, 'module_name' => 'Emails', 'email' => $address];
    }
}
