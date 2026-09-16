<?php
/**
 * SuiteCRM is a customer relationship management program developed by SalesAgility Ltd.
 * Copyright (C) 2024 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SALESAGILITY, SALESAGILITY DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */

require_once __DIR__ . '/TypeMapperInterface.php';

class NumericMapper implements TypeMapperInterface
{
    /**
     * @inheritDoc
     */
    public static function getType(): string
    {
        return 'float';
    }

    /**
     * @inheritDoc
     */
    public function toApi(SugarBean $bean, array &$container, string $name, string $alternativeName = ''): void
    {
        $newName = $name;

        if (!empty($alternativeName)) {
            $newName = $alternativeName;
        }

        $container[$newName] = html_entity_decode($bean->$name ?? '', ENT_QUOTES);
    }

    /**
     * API values are internal ("1000.50"). SugarBean::fixUpFormatting runs unformat_number()
     * with the user's separators on string values, so format internal numbers to the user's
     * format first (same as CurrencyMapper::toBean); unformat_number() then converts them back.
     * int uses no decimals, float and decimal use the field's precision (null = user precision).
     * @inheritDoc
     */
    public function toBean(SugarBean $bean, array &$container, string $name, string $alternativeName = ''): void
    {
        $newName = $name;

        if (!empty($alternativeName)) {
            $newName = $alternativeName;
        }

        $value = $container[$newName] ?? '';

        if (empty($value) || !is_numeric($value)) {
            return;
        }

        $def = $bean->field_defs[$name] ?? [];

        if (($def['type'] ?? '') === 'int') {
            $container[$newName] = (string)format_number($value, 0, 0);

            return;
        }

        $precision = isset($def['precision']) && is_numeric($def['precision']) ? (int)$def['precision'] : null;

        $container[$newName] = (string)format_number($value, $precision, $precision);
    }
}
