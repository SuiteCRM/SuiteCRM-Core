<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2021 SuiteCRM Ltd.
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

namespace App\Currency\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use BeanFactory;
use Currency;

class CurrencyHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'currency';

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * Get currency info array
     * @param string $currencyId
     * @return array
     */
    public function getCurrency(?string $currencyId): array
    {
        $this->init();

        $id = -99;
        if (!empty($currencyId)) {
            $id = $currencyId;
        }

        $info = [];

        /** @var Currency $currency */
        $currency = BeanFactory::getBean('Currencies', $id);

        if (!$currency || $currency->id === null) {
            $this->close();
            return $info;
        }

        $info['id'] = $currency->id;
        $info['name'] = $currency->name;
        $info['symbol'] = html_entity_decode($currency->symbol);
        $info['iso4217'] = $currency->iso4217;
        $info['conversion_rate'] = $currency->conversion_rate;

        $this->close();

        return $info;
    }

    /**
     * Get currencies info array
     * @return array
     */
    public function getCurrencies(): array
    {
        $this->init();

        $currencies = [];

        $currency = $this->getCurrency('-99');
        if (!empty($currency)) {
            $currencies['-99'] = $currency;
        }


        $bean = BeanFactory::newBean('Currencies');

        if (!$bean) {
            return $currencies;
        }

        $list = $bean->get_full_list('name');

        if (!empty($list)) {
            foreach ($list as $item) {
                if (!$item->id){
                    continue;
                }
                $currencies[$item->id] = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'symbol' => html_entity_decode($item->symbol),
                    'iso4217' => $item->iso4217,
                    'conversion_rate' => $item->conversion_rate
                ];
            }
        }

        $this->close();

        return $currencies;
    }
}
