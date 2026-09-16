<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2014 - 2026 SuiteCRM Ltd.
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

class NumericFilterFormatter
{
    private const TYPES = ['float', 'decimal', 'currency'];
    private const RANGE_KEYS = ['start', 'end', 'target'];

    /**
     * Convert the internal numbers ("1000.50") in the criteria filters to the user's format.
     * @param array $criteria
     * @return array
     */
    public function toUserFormat(array $criteria): array
    {
        foreach ($criteria['filters'] ?? [] as $key => $item) {
            if (!in_array($item['fieldType'] ?? '', self::TYPES, true)) {
                continue;
            }

            if (!empty($item['values']) && is_array($item['values'])) {
                $item['values'] = array_map([$this, 'formatValue'], $item['values']);
            }

            foreach (self::RANGE_KEYS as $rangeKey) {
                if (isset($item[$rangeKey])) {
                    $item[$rangeKey] = $this->formatValue($item[$rangeKey]);
                }
            }

            $criteria['filters'][$key] = $item;
        }

        return $criteria;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function formatValue($value)
    {
        if (empty($value) || !is_numeric($value)) {
            return $value;
        }

        require_once 'modules/Currencies/Currency.php';

        // By default format_number() rounds to the user's currency precision, which would
        // change the number being filtered on.
        $fraction = strrchr((string)$value, '.');
        $decimals = $fraction === false ? 0 : strlen($fraction) - 1;

        return (string)format_number($value, $decimals, $decimals);
    }
}
