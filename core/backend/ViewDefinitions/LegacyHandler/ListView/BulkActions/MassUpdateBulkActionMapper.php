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

namespace App\ViewDefinitions\LegacyHandler\ListView\BulkActions;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\ViewDefinitions\Entity\ViewDefinition;
use App\ViewDefinitions\LegacyHandler\ViewDefinitionMapperInterface;

class MassUpdateBulkActionMapper implements ViewDefinitionMapperInterface
{
    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return 'mass-update-definition';
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
     */
    public function map(ViewDefinition $definition, FieldDefinition $fieldDefinition): void
    {
        $listView = $definition->getListView() ?? [];
        $massUpdateDefinitions = $definition->getMassUpdate() ?? [];
        $bulkActions = $listView['bulkActions'] ?? [];
        $massUpdateAction = $bulkActions['massupdate'] ?? [];

        if (empty($listView) || empty($bulkActions) || empty($massUpdateAction) || empty($massUpdateDefinitions)) {
            return;
        }

        if (empty($massUpdateAction['params']) || empty($massUpdateAction['params']['recordPanel'])) {
            return;
        }

        $fields = $massUpdateAction['params']['recordPanel']['fields'] ?? [];

        if (!empty($fields)) {
            return;
        }

        $massUpdateAction['params']['recordPanel']['fields'] = $massUpdateDefinitions['fields'];

        $listView['bulkActions']['massupdate'] = $massUpdateAction;

        $definition->setListView($listView);
    }
}
