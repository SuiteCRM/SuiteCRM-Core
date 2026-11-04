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

namespace App\Module\CalendarAccounts\FieldDefinitions;

use App\Module\MailAccounts\FieldDefinitions\MailAccountFieldDefinitionMapper;
use CalendarSync;
use Throwable;

/**
 * The fields of a calendar account in the SuiteCRM 8 record view: not edited inline
 * (edited in the account editor), and the authentication fields shown for the
 * sources of their method only, as the legacy detail view (js/auth_fields_visibility.js).
 */
class CalendarAccountFieldDefinitionMapper extends MailAccountFieldDefinitionMapper
{
    /** The authentication fields of each method (CalendarSync::getFieldsToHide()). */
    protected const AUTH_FIELDS = [
        'oauth2' => ['oauth_connection_name'],
        'basic' => ['username', 'password', 'server_url'],
        'api_key' => ['api_key', 'api_endpoint'],
    ];

    public function getKey(): string
    {
        return 'calendar-account-fields';
    }

    public function getModule(): string
    {
        return 'calendar-account';
    }

    protected function hiddenOn(): array
    {
        $methods = $this->sourceAuthMethods();
        $hidden = [];
        foreach (self::AUTH_FIELDS as $method => $fields) {
            // The sources of the other methods (and no source).
            $otherSources = array_merge([''], array_keys(array_filter($methods, static fn ($other) => $other !== $method)));
            foreach ($fields as $field) {
                $hidden[$field] = [['source' => array_map('strval', $otherSources)]];
            }
        }

        return $hidden;
    }

    /**
     * The authentication method of each calendar source (the field definitions are mapped in the
     * legacy application); none when the calendar sync is not available.
     *
     * @return array<string, string>
     */
    protected function sourceAuthMethods(): array
    {
        try {
            require_once 'include/CalendarSync/CalendarSync.php';
            $calendarSync = CalendarSync::getInstance();
            $methods = [];
            foreach (array_keys($calendarSync->getCalendarSourceTypes()) as $source) {
                $methods[(string)$source] = $calendarSync->getProviderAuthMethodWithValidation((string)$source);
            }

            return $methods;
        } catch (Throwable) {
            return [];
        }
    }
}
