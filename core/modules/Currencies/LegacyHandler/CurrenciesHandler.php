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

namespace App\Module\Currencies\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * The currencies of the administration (legacy Currencies page: ListCurrency and its edit form)
 * for the SuiteCRM 8 view: the list (the system currency first, read-only: it is set
 * in the system settings), the creation and the edit of a currency, its deletion, and the merge of
 * currencies into another one (the opportunities of the merged currencies move to it, as the
 * legacy page).
 */
class CurrenciesHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'currencies';

    /** The system currency (config), not stored in the table. */
    public const SYSTEM_ID = '-99';

    protected const LABELS = [
        'LBL_MODULE_NAME', 'LBL_LIST_NAME', 'LBL_LIST_ISO4217', 'LBL_LIST_ISO4217_HELP', 'LBL_LIST_SYMBOL', 'LBL_LIST_RATE',
        'LBL_LIST_RATE_HELP', 'LBL_LIST_STATUS', 'LBL_MERGE', 'LBL_MERGE_TXT', 'LBL_DELETE', 'LBL_BELOW_MIN',
        'NTC_DELETE_CONFIRMATION', 'LBL_CURRENCY_SYSTEM_NOTE', 'LBL_CURRENCY_CREATE', 'LBL_CURRENCY_EDIT', 'LBL_CURRENCY_REQUIRED',
    ];


    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The currencies, the ISO 4217 codes (name and symbol of each) and the labels.
     */
    public function load(): array
    {
        return $this->inLegacy(fn () => $this->pageData());
    }

    /**
     * Creates (no id) or saves a currency, as the legacy form (ListCurrency::handleAdd).
     *
     * @param array<string, mixed> $values name, iso4217, symbol, conversion_rate, status
     */
    public function save(string $id, array $values): array
    {
        return $this->inLegacy(function () use ($id, $values) {
            $name = trim((string)($values['name'] ?? ''));
            $iso = strtoupper(trim((string)($values['iso4217'] ?? '')));
            $symbol = trim((string)($values['symbol'] ?? ''));
            $rate = (float)($values['conversion_rate'] ?? 0);
            $status = (string)($values['status'] ?? 'Active');

            if ($name === '' || $iso === '' || $symbol === '') {
                throw new \InvalidArgumentException('LBL_CURRENCY_REQUIRED');
            }
            if ($rate < 0.000001) {
                throw new \InvalidArgumentException('LBL_BELOW_MIN');
            }

            $currency = \BeanFactory::newBean('Currencies');
            if ($id !== '') {
                if ($id === self::SYSTEM_ID) {
                    throw new \InvalidArgumentException('The system currency is set in the system settings');
                }
                $currency->retrieve($id);
                if (empty($currency->id) || $currency->id === self::SYSTEM_ID) {
                    throw new \InvalidArgumentException('Unknown currency');
                }
            }

            $currency->name = $name;
            $currency->iso4217 = $iso;
            $currency->symbol = $symbol;
            $currency->conversion_rate = $rate;
            $currency->status = in_array($status, ['Active', 'Inactive'], true) ? $status : 'Active';
            $currency->save();

            return $this->pageData();
        });
    }

    /**
     * Deletes a currency (legacy Delete link of the list).
     */
    public function delete(string $id): array
    {
        return $this->inLegacy(function () use ($id) {
            if ($id === '' || $id === self::SYSTEM_ID) {
                throw new \InvalidArgumentException('The system currency cannot be deleted');
            }
            $currency = \BeanFactory::newBean('Currencies');
            $currency->mark_deleted($id);
            sugar_cache_clear('currency_list');

            return $this->pageData();
        });
    }

    /**
     * Merges currencies into another one, as the legacy page: the opportunities of the merged
     * currencies move to it, and the merged currencies are deleted.
     *
     * @param string[] $ids
     */
    public function merge(array $ids, string $mergeTo): array
    {
        return $this->inLegacy(function () use ($ids, $mergeTo) {
            $ids = array_values(array_filter(array_map('strval', $ids), static fn ($id) => $id !== ''));
            if ($ids === [] || $mergeTo === '') {
                throw new \InvalidArgumentException('Nothing to merge');
            }

            $opportunity = \BeanFactory::newBean('Opportunities');
            $opportunity->update_currency_id($ids, $mergeTo);
            $currency = \BeanFactory::newBean('Currencies');
            foreach ($ids as $id) {
                if ($id !== $mergeTo && $id !== self::SYSTEM_ID) {
                    $currency->mark_deleted($id);
                }
            }
            sugar_cache_clear('currency_list');

            return $this->pageData();
        });
    }

    protected function pageData(): array
    {
        global $current_language;

        $strings = return_module_language($current_language, 'Currencies');

        $system = \BeanFactory::newBean('Currencies');
        $system->retrieve(self::SYSTEM_ID);
        $currencies = [$this->currency($system, true)];
        foreach (\BeanFactory::newBean('Currencies')->get_full_list('name') ?: [] as $currency) {
            $currencies[] = $this->currency($currency, false);
        }

        $labels = [];
        foreach (self::LABELS as $label) {
            $labels[$label] = rtrim($this->decode((string)($strings[$label] ?? $label)), ': ');
        }

        $statuses = [];
        foreach ($strings['currency_status_dom'] ?? ['Active' => 'Active', 'Inactive' => 'Inactive'] as $value => $label) {
            $statuses[] = ['value' => (string)$value, 'label' => $this->decode((string)$label)];
        }

        return [
            'currencies' => $currencies,
            'statuses' => $statuses,
            'iso' => $this->isoCodes(),
            'labels' => $labels,
        ];
    }

    protected function currency(\Currency $currency, bool $system): array
    {
        return [
            'id' => (string)$currency->id,
            'name' => $this->decode((string)$currency->name),
            'iso4217' => $this->decode((string)$currency->iso4217),
            'symbol' => $this->decode((string)$currency->symbol),
            'conversionRate' => (float)$currency->conversion_rate,
            'status' => (string)$currency->status,
            'system' => $system,
        ];
    }

    /**
     * The ISO 4217 codes with their name and symbol, to fill a new currency (legacy EditView.js).
     */
    protected function isoCodes(): array
    {
        require 'modules/Currencies/iso4217.php';

        $codes = [];
        foreach ($fullIsoList ?? [] as $code => $iso) {
            $symbol = '';
            foreach ($iso['unicode'] ?? [] as $codePoint) {
                $symbol .= mb_chr((int)$codePoint, 'UTF-8');
            }
            $codes[(string)$code] = ['name' => (string)($iso['name'] ?? ''), 'symbol' => $symbol];
        }

        return $codes;
    }

    protected function decode(string $value): string
    {
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
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
