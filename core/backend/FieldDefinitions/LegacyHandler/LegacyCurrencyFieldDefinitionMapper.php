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

namespace App\FieldDefinitions\LegacyHandler;

use App\FieldDefinitions\Entity\FieldDefinition;

class LegacyCurrencyFieldDefinitionMapper implements FieldDefinitionMapperInterface
{
    /**
     * @inheritDoc
     */

    public function getKey(): string
    {
        return 'legacy-currency-fields';
    }

    /**
     * @inheritDoc
     */
    public function getModule(): string
    {
        return 'default';
    }

    /**
     * @inheritDoc
     * @param FieldDefinition $definition
     */
    public function map(FieldDefinition $definition): void
    {
        $vardefs = $definition->getVardef();
        foreach ($vardefs as $fieldName => $fieldDefinition) {

            $type = $fieldDefinition['type'] ?? '';

            if ($type !== 'currency') {
                continue;
            }

            $isBaseCurrency = $fieldDefinition['metadata']['isBaseCurrency'] ?? null;

            if ($isBaseCurrency !== null) {
                continue;
            }

            if (strpos($fieldName, '_usdollar') !== false) {
                $this->markAsBaseCurrency($fieldDefinition);
                $vardefs[$fieldName] = $fieldDefinition;

                continue;
            }

            if (empty($vardefs['currency_id'])) {
                $this->markAsBaseCurrency($fieldDefinition);
                $vardefs[$fieldName] = $fieldDefinition;
            }

        }

        $definition->setVardef($vardefs);
    }

    /**
     * Mark as base currency
     * @param $fieldDefinition
     */
    protected function markAsBaseCurrency(&$fieldDefinition): void
    {
        $fieldDefinition['metadata'] = array_merge(
            $fieldDefinition['metadata'] ?? [],
            [
                'isBaseCurrency' => true
            ]
        );
    }

}
