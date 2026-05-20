<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2023 SuiteCRM Ltd.
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

use App\Engine\LegacyHandler\LegacyHandler;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\LegacyHandler\FieldDefinitionMapperInterface;

class LegacyDisableNumFormatFieldDefinitionMapper extends LegacyHandler implements FieldDefinitionMapperInterface
{
    /**
     * @inheritDoc
     */
    public function getModule(): string
    {
        return 'default';
    }

    public function getHandlerKey(): string
    {
        return $this->getKey();
    }

    /**
     * @inheritDoc
     */

    public function getKey(): string
    {
        return 'legacy-disable-num-format-fields';
    }

    /**
     * @inheritDoc
     * @param FieldDefinition $definition
     */
    public function map(FieldDefinition $definition): void
    {
        $this->init();

        $vardefs = $definition->getVardef();
        foreach ($vardefs as $fieldName => $fieldDefinition) {

            if (!isset($fieldDefinition['metadata']['format']) && !isset($fieldDefinition['disable_num_format'])) {
                continue;
            }

            $format = isTrue($fieldDefinition['metadata']['format'] ?? true);
            $disable_num_format = isTrue($fieldDefinition['disable_num_format'] ?? false);

            $fieldDefinition['metadata']['format'] = $format && !$disable_num_format;

            $vardefs[$fieldName] = $fieldDefinition;
        }
        $definition->setVardef($vardefs);

        $this->close();
    }
}
