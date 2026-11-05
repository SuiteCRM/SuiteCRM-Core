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

namespace App\Module\Calls\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * Reschedules a call and reads its call attempt history, as the legacy call
 * "Reschedule" button (modules/Calls/Reschedule.php) and the Reschedule panel
 * (reschedule_history.php) do.
 */
class CallRescheduler extends LegacyHandler
{
    public const HANDLER_KEY = 'call-rescheduler';

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * Moves the call to $dateStart (database format, GMT; the end date follows
     * the duration) and adds a call attempt with $reason. False when the call is not
     * found, held or not editable.
     */
    public function reschedule(string $id, string $dateStart, string $reason): bool
    {
        $this->init();
        $this->startLegacyApp();

        // Dates in the database format, as the record views read and save them.
        global $disable_date_format;
        $disableOriginal = $disable_date_format;
        $disable_date_format = true;

        $rescheduled = false;
        $call = \BeanFactory::getBean('Calls', $id, ['encode' => false]);
        if ($call && !empty($call->id) && $call->status !== 'Held' && $call->ACLAccess('edit')) {
            $call->date_start = $dateStart;
            $call->save();

            $attempt = \BeanFactory::newBean('Calls_Reschedule');
            $attempt->reason = $reason;
            $attempt->call_id = $call->id;
            $attempt->save();

            $rescheduled = true;
        }

        $disable_date_format = $disableOriginal;

        $this->close();

        return $rescheduled;
    }

    /**
     * Call attempts, the last one first: "reason - date by user" lines.
     */
    public function getHistory(string $id): string
    {
        if ($id === '') {
            return '';
        }

        $this->init();
        $this->startLegacyApp();

        global $app_list_strings;

        $db = \DBManagerFactory::getInstance();
        $result = $db->query(
            'SELECT id FROM calls_reschedule WHERE call_id = ' . $db->quoted($id)
            . ' AND deleted = 0 ORDER BY date_entered DESC'
        );

        $mod = return_module_language($GLOBALS['current_language'] ?? 'en_us', 'Calls');
        $lines = [];
        while ($row = $db->fetchByAssoc($result, false)) {
            $attempt = \BeanFactory::getBean('Calls_Reschedule', $row['id']);
            if (!$attempt || empty($attempt->id)) {
                continue;
            }
            $reason = $app_list_strings['call_reschedule_dom'][$attempt->reason] ?? $attempt->reason;
            $lines[] = $reason . ' - ' . $attempt->date_entered . ' ' . ($mod['LBL_RESCHEDULED_BY'] ?? '') . ' ' . $attempt->created_by_name;
        }

        $this->close();

        return html_entity_decode(implode("\n", $lines), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
