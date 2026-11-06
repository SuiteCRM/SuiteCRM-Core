<?php
/**
 * SuiteCRM is a customer relationship management program developed by SalesAgility Ltd.
 * Copyright (C) 2026 SalesAgility Ltd.
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
require_once __DIR__ . '/../../../../modules/Currencies/Currency.php';

/**
 * Float fields saved from the SuiteCRM 8 views.
 *
 * The front end sends a float in the internal format ('.' decimal), but SugarBean::save()
 * (fixUpFormatting) reads every float string in the number format of the user: for a user whose
 * group symbol is the dot, 46.2044 becomes 462044. The currency fields have a mapper giving the
 * bean the user format (CurrencyMapper); this one does the same for floats, without group symbol
 * and without rounding them to the precision of the user, which the bean reads back to the same
 * number.
 */
class FloatMapper implements TypeMapperInterface
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
        $newName = $alternativeName !== '' ? $alternativeName : $name;

        // As the default mapping of ApiBeanMapper (no type mapper).
        $container[$newName] = html_entity_decode((string)($bean->$name ?? ''), ENT_QUOTES);
    }

    /**
     * @inheritDoc
     */
    public function toBean(SugarBean $bean, array &$container, string $name, string $alternativeName = ''): void
    {
        $newName = $alternativeName !== '' ? $alternativeName : $name;
        $value = $container[$newName] ?? null;

        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            return;
        }

        $value = trim((string)$value);
        if (!preg_match('/^[-+]?\d+(\.\d+)?$/', $value)) {
            // Not an internal number (empty or in another format): left as it is.
            return;
        }

        [, $decimalSymbol] = get_number_separators();
        $container[$newName] = str_replace('.', $decimalSymbol ?: '.', $value);
    }
}
