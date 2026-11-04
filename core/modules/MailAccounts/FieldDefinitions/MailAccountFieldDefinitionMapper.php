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

use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\LegacyHandler\FieldDefinitionMapperInterface;

/**
 * The fields of a mail account in the SuiteCRM 8 record view: not edited inline (the
 * account is saved by the legacy code in the mail account editor), and shown as the
 * scripts of the legacy detail view show them.
 */
abstract class MailAccountFieldDefinitionMapper implements FieldDefinitionMapperInterface
{
    /**
     * Each field hidden when one of these fields has one of these values (any of the rules).
     *
     * @return array<string, array<int, array<string, string[]>>>
     */
    abstract protected function hiddenOn(): array;

    /**
     * The SuiteCRM 8 type of fields shown as another type (a legacy type unknown to SuiteCRM 8).
     *
     * @return array<string, string>
     */
    protected function fieldTypes(): array
    {
        return [];
    }

    public function map(FieldDefinition $definition): void
    {
        $vardefs = $definition->getVardef();
        foreach ($vardefs as $name => $vardef) {
            if (is_array($vardef)) {
                $vardefs[$name]['inline_edit'] = false;
            }
        }

        foreach ($this->fieldTypes() as $name => $type) {
            if (isset($vardefs[$name])) {
                $vardefs[$name]['type'] = $type;
            }
        }

        foreach ($this->hiddenOn() as $name => $rules) {
            if (!isset($vardefs[$name])) {
                continue;
            }
            foreach ($rules as $index => $activeOnFields) {
                $vardefs[$name]['displayLogic']['mail-account-hide-' . $index] = [
                    'key' => 'displayType',
                    'modes' => ['detail'],
                    'params' => [
                        'fieldDependencies' => array_keys($activeOnFields),
                        // One of the values (a list of plain values must match all of them).
                        'activeOnFields' => array_map(
                            static fn(array $values) => [['operator' => 'is-equal', 'values' => $values]],
                            $activeOnFields
                        ),
                    ],
                ];
            }
        }

        $definition->setVardef($vardefs);
    }
}
