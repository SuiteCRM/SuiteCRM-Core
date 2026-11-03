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

namespace App\Module\ScheduledReports\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * The recipients of a scheduled report (email_recipients) for the record view. The legacy page stores them as the base64 of a serialized array, one line per
 * recipient, as AOR_Scheduled_Reports::get_email_recipients reads them:
 *  - email_target_type: 'Email Address', 'Specify User' or 'Users';
 *  - email: the address, the user id, or [scope (all, role, security_group), group id, role id].
 * The record view works with a list of recipients:
 * {type, email} | {type, userId, userName} | {type, scope, groupId, groupName, roleId, roleName}.
 */
class ScheduledReportRecipientsHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'scheduled-report-recipients';

    public const EMAIL = 'Email Address';
    public const USER = 'Specify User';
    public const USERS = 'Users';

    protected const SCOPES = ['all', 'role', 'security_group'];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The recipients of a stored value.
     *
     * @return array<int, array<string, string>>
     */
    public function toList(?string $stored): array
    {
        return $this->inLegacy(function () use ($stored) {
            $params = $stored ? unserialize(base64_decode($stored), ['allowed_classes' => false]) : [];
            $recipients = [];

            foreach ((is_array($params) ? $params['email_target_type'] ?? [] : []) as $key => $type) {
                $value = $params['email'][$key] ?? '';
                if ($type === self::EMAIL) {
                    $recipients[] = ['type' => $type, 'email' => is_string($value) ? $value : ''];
                } elseif ($type === self::USER) {
                    $recipients[] = ['type' => $type, 'userId' => (string)$value, 'userName' => $this->name('Users', (string)$value)];
                } elseif ($type === self::USERS) {
                    $value = is_array($value) ? array_values($value) : [];
                    $recipients[] = [
                        'type' => $type,
                        'scope' => in_array($value[0] ?? '', self::SCOPES, true) ? $value[0] : 'all',
                        'groupId' => (string)($value[1] ?? ''),
                        'groupName' => $this->name('SecurityGroups', (string)($value[1] ?? '')),
                        'roleId' => (string)($value[2] ?? ''),
                        'roleName' => $this->name('ACLRoles', (string)($value[2] ?? '')),
                    ];
                }
            }

            return $recipients;
        });
    }

    /**
     * The stored value of a list of recipients, as the legacy edit view posts them (null
     * without recipient). Incomplete lines are left out.
     */
    public function toStored(array $recipients): ?string
    {
        $types = [];
        $values = [];
        foreach ($recipients as $recipient) {
            $type = is_array($recipient) ? (string)($recipient['type'] ?? '') : '';
            if ($type === self::EMAIL && trim((string)($recipient['email'] ?? '')) !== '') {
                $value = trim((string)$recipient['email']);
            } elseif ($type === self::USER && (string)($recipient['userId'] ?? '') !== '') {
                $value = (string)$recipient['userId'];
            } elseif ($type === self::USERS) {
                $scope = in_array($recipient['scope'] ?? '', self::SCOPES, true) ? $recipient['scope'] : 'all';
                $value = [
                    $scope,
                    $scope === 'security_group' ? (string)($recipient['groupId'] ?? '') : '',
                    $scope !== 'all' ? (string)($recipient['roleId'] ?? '') : '',
                ];
            } else {
                continue;
            }

            $types[] = $type;
            $values[] = $value;
        }

        return $types ? base64_encode(serialize(['email_target_type' => $types, 'email' => $values])) : null;
    }

    /**
     * The stored recipients of a scheduled report.
     */
    public function load(string $id): ?string
    {
        return $this->inLegacy(static function () use ($id) {
            $db = \DBManagerFactory::getInstance();
            $row = $db->fetchOne('SELECT email_recipients FROM aor_scheduled_reports WHERE id = ' . $db->quoted($id));

            return $row['email_recipients'] ?? null;
        });
    }

    /**
     * Stores the recipients of a scheduled report, after its save (the legacy bean takes
     * them from the edit view post only).
     */
    public function store(string $id, ?string $stored): void
    {
        $this->inLegacy(static function () use ($id, $stored) {
            $db = \DBManagerFactory::getInstance();
            $db->query('UPDATE aor_scheduled_reports SET email_recipients = '
                . ($stored === null ? 'NULL' : $db->quoted($stored)) . ' WHERE id = ' . $db->quoted($id));
        });
    }

    /**
     * Choices of the recipient editor: active users, roles and security groups, by name; null
     * when the user cannot edit scheduled reports.
     *
     * @return array<string, array<int, array{id: string, name: string}>>|null
     */
    public function getOptions(): ?array
    {
        return $this->inLegacy(function () {
            if (!\ACLController::checkAccess('AOR_Scheduled_Reports', 'edit', true)) {
                return null;
            }

            $db = \DBManagerFactory::getInstance();
            $users = [];
            $result = $db->query("SELECT id, first_name, last_name, user_name FROM users WHERE deleted = 0 AND status = 'Active'"
                . " AND portal_only = 0 AND is_group = 0 ORDER BY last_name, first_name");
            while ($row = $db->fetchByAssoc($result, false)) {
                $name = trim($row['first_name'] . ' ' . $row['last_name']);
                $users[] = ['id' => $row['id'], 'name' => $name !== '' ? $name : $row['user_name']];
            }

            return [
                'users' => $users,
                'roles' => $this->list('acl_roles'),
                'groups' => $this->list('securitygroups'),
            ];
        });
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    protected function list(string $table): array
    {
        $db = \DBManagerFactory::getInstance();
        $items = [];
        $result = $db->query("SELECT id, name FROM $table WHERE deleted = 0 ORDER BY name");
        while ($row = $db->fetchByAssoc($result, false)) {
            $items[] = ['id' => $row['id'], 'name' => (string)$row['name']];
        }

        return $items;
    }

    protected function name(string $module, string $id): string
    {
        if ($id === '') {
            return '';
        }

        $bean = \BeanFactory::getBean($module, $id);

        return $bean && !empty($bean->id) ? html_entity_decode((string)$bean->name, ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
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
