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

namespace App\Module\ScheduledReports\FieldDefinitions;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\LegacyHandler\FieldDefinitionMapperInterface;

/**
 * Fields of a scheduled report in the record view:
 *  - schedule (legacy CronSchedule field) and email_recipients (legacy function field) are
 *    shown and edited by the cron-schedule and report-recipients field components, not inline;
 *  - last_run, of the legacy type readonly, is a read-only date and time;
 *  - a new scheduled report is active, as shown first by the legacy edit view.
 */
class ScheduledReportFieldDefinitionMapper implements FieldDefinitionMapperInterface
{
    public function getKey(): string
    {
        return 'scheduled-report-fields';
    }

    public function getModule(): string
    {
        return 'scheduled-reports';
    }

    public function map(FieldDefinition $definition): void
    {
        $vardefs = $definition->getVardef();

        if (!empty($vardefs['schedule'])) {
            $vardefs['schedule']['type'] = 'cron-schedule';
            $vardefs['schedule']['inline_edit'] = false;
        }

        if (!empty($vardefs['email_recipients'])) {
            // A list of recipients (RecipientsEntityMapper), not text.
            $vardefs['email_recipients']['type'] = 'report-recipients';
            $vardefs['email_recipients']['inline_edit'] = false;
            unset($vardefs['email_recipients']['function']);
        }

        if (!empty($vardefs['status']) && ($vardefs['status']['default'] ?? '') === '') {
            $vardefs['status']['default'] = 'active';
        }

        if (!empty($vardefs['last_run'])) {
            $vardefs['last_run'] = array_merge($vardefs['last_run'], [
                'type' => 'datetime',
                'display' => 'readonly',
                'inline_edit' => false,
            ]);
        }

        $definition->setVardef($vardefs);
    }
}
