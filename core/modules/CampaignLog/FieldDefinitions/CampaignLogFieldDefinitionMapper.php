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

namespace App\Module\CampaignLog\FieldDefinitions;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\LegacyHandler\FieldDefinitionMapperInterface;

/**
 * The target and related types of a campaign log entry shown as module names in the list and
 * record views (the legacy subpanels show the type as stored).
 */
class CampaignLogFieldDefinitionMapper implements FieldDefinitionMapperInterface
{
    protected const MODULE_TYPES = ['target_type', 'related_type'];

    public function getKey(): string
    {
        return 'campaign-log-fields';
    }

    public function getModule(): string
    {
        return 'campaign-log';
    }

    public function map(FieldDefinition $definition): void
    {
        $vardefs = $definition->getVardef();

        foreach (self::MODULE_TYPES as $field) {
            if (empty($vardefs[$field])) {
                continue;
            }

            unset($vardefs[$field]['dbType'], $vardefs[$field]['group']);
            $vardefs[$field]['type'] = 'enum';
            $vardefs[$field]['options'] = 'moduleList';
        }

        $definition->setVardef($vardefs);
    }
}
