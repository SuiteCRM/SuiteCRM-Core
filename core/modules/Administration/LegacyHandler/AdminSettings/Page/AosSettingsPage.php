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

namespace App\Module\Administration\LegacyHandler\AdminSettings\Page;

use App\Module\Administration\LegacyHandler\AdminSettings\AdminSettingsPage;

/**
 * The Sales Module Settings (legacy Administration&action=AOSAdmin): contract renewal
 * reminder, initial invoice and quote numbers, line item groups and total tax, saved as the
 * legacy page (Configurator::saveConfig over its form fields).
 */
class AosSettingsPage extends AdminSettingsPage
{
    protected const NUMBERS = [
        'aos_contracts_renewalReminderPeriod' => ['contracts', 'renewalReminderPeriod'],
        'aos_invoices_initialNumber' => ['invoices', 'initialNumber'],
        'aos_quotes_initialNumber' => ['quotes', 'initialNumber'],
    ];

    protected const BOOLEANS = [
        'aos_lineItems_enableGroups' => 'enableGroups',
        'aos_lineItems_totalTax' => 'totalTax',
    ];

    /**
     * The highest initial invoice number of the legacy page.
     */
    protected const MAX_NUMBER = 9999999999;

    public function getPageKey(): string
    {
        return 'aos';
    }

    protected function getPage(): array
    {
        global $sugar_config;

        $aos = is_array($sugar_config['aos'] ?? null) ? $sugar_config['aos'] : [];
        $values = [];
        foreach (self::NUMBERS as $name => [$group, $key]) {
            $values[$name] = (string)($aos[$group][$key] ?? '');
        }
        foreach (self::BOOLEANS as $name => $key) {
            $values[$name] = $this->isCheckedByDefault($aos['lineItems'][$key] ?? null);
        }

        return [
            'title' => $this->text('LBL_AOS_ADMIN_MANAGE_AOS'),
            'sections' => [
                $this->section('LBL_AOS_ADMIN_CONTRACT_SETTINGS', [
                    $this->field('aos_contracts_renewalReminderPeriod', 'LBL_AOS_ADMIN_CONTRACT_RENEWAL_REMINDER', 'number', ['suffix' => $this->text('LBL_AOS_DAYS')]),
                ]),
                $this->section('LBL_AOS_ADMIN_INVOICE_SETTINGS', [
                    $this->field('aos_invoices_initialNumber', 'LBL_AOS_ADMIN_INITIAL_INVOICE_NUMBER', 'number', ['max' => self::MAX_NUMBER]),
                ]),
                $this->section('LBL_AOS_ADMIN_QUOTE_SETTINGS', [
                    $this->field('aos_quotes_initialNumber', 'LBL_AOS_ADMIN_INITIAL_QUOTE_NUMBER', 'number'),
                ]),
                $this->section('LBL_AOS_ADMIN_LINE_ITEM_SETTINGS', [
                    $this->field('aos_lineItems_enableGroups', 'LBL_AOS_ADMIN_ENABLE_LINE_ITEM_GROUPS', 'bool'),
                    $this->field('aos_lineItems_totalTax', 'LBL_AOS_ADMIN_ENABLE_LINE_ITEM_TOTAL_TAX', 'bool'),
                ]),
            ],
            'values' => $values,
        ];
    }

    protected function saveValues(array $values): ?string
    {
        $fields = [];
        foreach (array_keys(self::NUMBERS) as $name) {
            $value = trim((string)($values[$name] ?? ''));
            if ($value !== '' && (!ctype_digit($value) || (float)$value > self::MAX_NUMBER)) {
                return $this->text('LBL_VALIDATION_ERRORS', 'Administration', false);
            }
            $fields[$name] = $value;
        }
        foreach (array_keys(self::BOOLEANS) as $name) {
            $fields[$name] = !empty($values[$name]);
        }

        $this->withPost($fields, static fn () => (new \Configurator())->saveConfig());

        return null;
    }
}
