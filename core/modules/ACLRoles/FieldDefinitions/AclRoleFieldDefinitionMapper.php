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

namespace App\Module\ACLRoles\FieldDefinitions;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\LegacyHandler\FieldDefinitionMapperInterface;
use App\Module\ACLRoles\LegacyHandler\AclRoleMatrixHandler;

/**
 * Fields of a role in the record view: the name is required, as in the legacy edit view, and the
 * access matrix (not a field of the bean: AclRoleMatrixHandler) is shown and edited by the
 * acl-matrix field.
 */
class AclRoleFieldDefinitionMapper implements FieldDefinitionMapperInterface
{
    public function getKey(): string
    {
        return 'acl-role-fields';
    }

    public function getModule(): string
    {
        return 'acl-roles';
    }

    public function map(FieldDefinition $definition): void
    {
        $vardefs = $definition->getVardef();

        if (!empty($vardefs['name'])) {
            $vardefs['name']['required'] = true;
        }

        $vardefs[AclRoleMatrixHandler::FIELD] = [
            'name' => AclRoleMatrixHandler::FIELD,
            'vname' => 'LBL_ACCESS_MATRIX',
            'type' => 'acl-matrix',
            'source' => 'non-db',
            'inline_edit' => false,
            'massupdate' => false,
            'importable' => 'false',
            'reportable' => false,
            'duplicate_merge' => 'disabled',
        ];

        $definition->setVardef($vardefs);
    }
}
