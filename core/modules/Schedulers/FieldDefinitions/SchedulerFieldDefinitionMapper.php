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

namespace App\Module\Schedulers\FieldDefinitions;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\LegacyHandler\FieldDefinitionMapperInterface;
use App\Module\Schedulers\LegacyHandler\SchedulerFieldsHandler;

/**
 * Fields of a scheduler in the record view, as the legacy edit view:
 *  - job_function lists the jobs (Scheduler::getJobsList fills it at display time) and the job URL
 *    is shown for a URL job only;
 *  - job_interval is shown in words and edited (basic or advanced) by the scheduler-interval
 *    field;
 *  - a new scheduler is active and starts on 2005-01-01, last_run is read-only.
 */
class SchedulerFieldDefinitionMapper implements FieldDefinitionMapperInterface
{
    public function __construct(protected SchedulerFieldsHandler $fields)
    {
    }

    public function getKey(): string
    {
        return 'scheduler-fields';
    }

    public function getModule(): string
    {
        return 'schedulers';
    }

    public function map(FieldDefinition $definition): void
    {
        $vardefs = $definition->getVardef();

        if (!empty($vardefs['job_function'])) {
            $metadata = $vardefs['job_function']['metadata'] ?? [];
            $metadata['extraOptions'] = $this->fields->getJobs();
            unset($vardefs['job_function']['function']);
            $vardefs['job_function'] = array_merge($vardefs['job_function'], [
                // The core enum field shows its extra options with a list only: a list without
                // options, the jobs being the extra options.
                'options' => 'scheduler_jobs_dom',
                'vname' => 'LBL_JOB',
                'required' => true,
                'inline_edit' => false,
                'metadata' => $metadata,
            ]);
        }

        if (!empty($vardefs['job_url'])) {
            $vardefs['job_url']['inline_edit'] = false;
            $vardefs['job_url']['displayLogic'] = [
                'hide_unless_url_job' => [
                    'key' => 'displayType',
                    'modes' => ['detail', 'edit', 'create'],
                    'params' => [
                        'fieldDependencies' => ['job_function'],
                        'activeOnFields' => [
                            'job_function' => [
                                ['operator' => 'not-equal', 'values' => [SchedulerFieldsHandler::URL_JOB]],
                            ],
                        ],
                    ],
                ],
            ];
        }

        if (!empty($vardefs['job_interval'])) {
            $vardefs['job_interval'] = array_merge($vardefs['job_interval'], [
                'type' => 'scheduler-interval',
                'inline_edit' => false,
                'default' => '*::*::*::*::*',
            ]);
        }

        if (!empty($vardefs['date_time_start']) && empty($vardefs['date_time_start']['default'])) {
            // The start the legacy edit view shows for a new scheduler (display_default).
            $vardefs['date_time_start']['default'] = '2005-01-01 00:00:00';
        }

        if (!empty($vardefs['status']) && ($vardefs['status']['default'] ?? '') === '') {
            $vardefs['status']['default'] = 'Active';
        }

        if (!empty($vardefs['last_run'])) {
            $vardefs['last_run']['display'] = 'readonly';
            $vardefs['last_run']['inline_edit'] = false;
        }

        $definition->setVardef($vardefs);
    }
}
