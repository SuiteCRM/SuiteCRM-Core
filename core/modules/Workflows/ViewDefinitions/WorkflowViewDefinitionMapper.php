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

namespace App\Module\Workflows\ViewDefinitions;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\ViewDefinitions\Entity\ViewDefinition;
use App\ViewDefinitions\LegacyHandler\ViewDefinitionMapperInterface;

/**
 * SuiteCRM 8 record view of the workflows (legacy layout of the module): without the
 * Conditions and Actions panels, whose fields are the HTML of the legacy page
 * (display_condition_lines, display_action_lines). The record view shows the conditions and the
 * actions in the workflow lines widget, and the workflow editor edits them.
 */
class WorkflowViewDefinitionMapper implements ViewDefinitionMapperInterface
{
    protected const LINE_FIELDS = ['condition_lines', 'action_lines'];

    public function getKey(): string
    {
        return 'workflow-view';
    }

    public function getModule(): string
    {
        return 'workflow';
    }

    public function map(ViewDefinition $definition, FieldDefinition $fieldDefinition): void
    {
        $recordView = $definition->getRecordView() ?? [];

        $panels = [];
        foreach ($recordView['panels'] ?? [] as $panel) {
            $names = [];
            foreach ($panel['rows'] ?? [] as $row) {
                foreach ($row['cols'] ?? [] as $cell) {
                    $names[] = (string)($cell['name'] ?? '');
                }
            }
            if ($names && !array_diff($names, self::LINE_FIELDS)) {
                continue;
            }
            $panels[] = $panel;
        }
        $recordView['panels'] = $panels;

        $definition->setRecordView($recordView);
    }
}
