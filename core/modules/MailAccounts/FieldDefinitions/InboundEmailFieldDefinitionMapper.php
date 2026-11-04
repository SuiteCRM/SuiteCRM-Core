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

namespace App\Module\MailAccounts\FieldDefinitions;

/**
 * The fields of an inbound email account in the SuiteCRM 8 record view, shown as the
 * scripts of the legacy detail view show them (js/fields_toggle.js, owner_toggle.js,
 * auth_type_fields_toggle.js, case_create_toggle.js, distribution_toggle.js).
 */
class InboundEmailFieldDefinitionMapper extends MailAccountFieldDefinitionMapper
{
    protected const NO_CASE = ['is_create_case' => ['false', '0', '']];

    public function getKey(): string
    {
        return 'inbound-email-fields';
    }

    public function getModule(): string
    {
        return 'inbound-email';
    }

    protected function hiddenOn(): array
    {
        return [
            'is_default' => [['type' => ['group', 'bounce']]],
            'owner_name' => [['type' => ['group', 'bounce']]],
            'outbound_email_name' => [['type' => ['group', 'bounce']]],
            'account_signature_id' => [['type' => ['group', 'bounce']]],
            'allow_outbound_group_usage' => [['type' => ['personal', 'bounce']]],
            'external_oauth_connection_name' => [['auth_type' => ['basic']]],
            'create_case_email_template_name' => [self::NO_CASE],
            'distrib_method' => [self::NO_CASE],
            'distribution_options' => [self::NO_CASE, ['distrib_method' => ['', 'AOPDefault', 'singleUser']]],
            'distribution_user_name' => [self::NO_CASE, ['distrib_method' => ['', 'AOPDefault', 'roundRobin', 'leastBusy', 'random']]],
        ];
    }
}
