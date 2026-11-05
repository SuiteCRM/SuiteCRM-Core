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

namespace App\Module\Schedulers\ViewDefinitions;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\ViewDefinitions\Entity\ViewDefinition;
use App\ViewDefinitions\LegacyHandler\ViewDefinitionMapperInterface;

/**
 * Record view of the schedulers (metadata/detailviewdefs.php, also the legacy detail view):
 *  - the job, interval and active hours are edited: the custom code of the legacy detail view
 *    (which makes the record view show them read-only) is for the legacy page only;
 *  - the Job Log subpanel is replaced by the job log widget under the details (the record view
 *    shows no row in it: its record list only reads the modules of the user, and job logs are
 *    not one).
 */
class SchedulerViewDefinitionMapper implements ViewDefinitionMapperInterface
{
    protected const EDITABLE = ['job_function', 'job_interval', 'time_from', 'time_to'];

    public function getKey(): string
    {
        return 'scheduler-view';
    }

    public function getModule(): string
    {
        return 'schedulers';
    }

    public function map(ViewDefinition $definition, FieldDefinition $fieldDefinition): void
    {
        $vardefs = $fieldDefinition->getVardef();
        $recordView = $definition->getRecordView() ?? [];

        foreach ($recordView['panels'] ?? [] as $panelKey => $panel) {
            foreach ($panel['rows'] ?? [] as $rowKey => $row) {
                foreach ($row['cols'] ?? [] as $colKey => $cell) {
                    $name = $cell['name'] ?? '';
                    if (!in_array($name, self::EDITABLE, true)) {
                        continue;
                    }

                    unset($cell['customCode'], $cell['readonly']);
                    if (!empty($vardefs[$name]['required'])) {
                        $cell['fieldDefinition']['required'] = true;
                    }
                    $recordView['panels'][$panelKey]['rows'][$rowKey]['cols'][$colKey] = $cell;
                }
            }
        }
        $definition->setRecordView($recordView);

        $subPanel = $definition->getSubPanel() ?? [];
        if (isset($subPanel['times'])) {
            unset($subPanel['times']);
            $definition->setSubPanel($subPanel);
        }
    }
}
