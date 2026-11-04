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

namespace App\Module\ProjectTasks\ViewDefinitions;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\ViewDefinitions\Entity\ViewDefinition;
use App\ViewDefinitions\LegacyHandler\ViewDefinitionMapperInterface;

/**
 * SuiteCRM 8 record view of the project tasks (legacy layout of the module): the project
 * is chosen in the edit and create views, as in the legacy edit view. Its custom code (a link to
 * the legacy project page) is for the legacy detail view only: the core shows a field with custom
 * code read-only.
 */
class ProjectTaskViewDefinitionMapper implements ViewDefinitionMapperInterface
{
    protected const EDITABLE = ['project_name'];

    public function getKey(): string
    {
        return 'project-task-view';
    }

    public function getModule(): string
    {
        return 'project-task';
    }

    public function map(ViewDefinition $definition, FieldDefinition $fieldDefinition): void
    {
        $recordView = $definition->getRecordView() ?? [];

        foreach ($recordView['panels'] ?? [] as $panelKey => $panel) {
            foreach ($panel['rows'] ?? [] as $rowKey => $row) {
                foreach ($row['cols'] ?? [] as $colKey => $cell) {
                    if (in_array($cell['name'] ?? '', self::EDITABLE, true)) {
                        unset($cell['customCode'], $cell['readonly']);
                        $recordView['panels'][$panelKey]['rows'][$rowKey]['cols'][$colKey] = $cell;
                    }
                }
            }
        }

        $definition->setRecordView($recordView);
    }
}
