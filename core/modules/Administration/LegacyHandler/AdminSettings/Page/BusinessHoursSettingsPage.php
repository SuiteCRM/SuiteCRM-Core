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

namespace App\Module\Administration\LegacyHandler\AdminSettings\Page;

use App\Module\Administration\LegacyHandler\AdminSettings\AdminSettingsPage;

/**
 * The Business Hours page (legacy Administration&action=BusinessHours): by day of the
 * week, whether it is open and its hours from / to; saved as the legacy page, one business hours
 * record by day (AOBH_BusinessHours::getOrCreate). Without a record, a day is open from 9am to 5pm
 * from Monday to Friday, as the legacy page shows it.
 */
class BusinessHoursSettingsPage extends AdminSettingsPage
{
    protected const DAYS = [
        'Monday' => 'LBL_MONDAY',
        'Tuesday' => 'LBL_TUESDAY',
        'Wednesday' => 'LBL_WEDNESDAY',
        'Thursday' => 'LBL_THURSDAY',
        'Friday' => 'LBL_FRIDAY',
        'Saturday' => 'LBL_SATURDAY',
        'Sunday' => 'LBL_SUNDAY',
    ];

    public function getPageKey(): string
    {
        return 'business-hours';
    }

    protected function getPage(): array
    {
        global $app_list_strings;

        $hours = $this->options($app_list_strings['business_hours_list'] ?? []);
        $businessHours = \BeanFactory::newBean('AOBH_BusinessHours');

        $sections = [];
        $values = [];
        foreach (self::DAYS as $day => $label) {
            $sections[] = $this->section($label, [
                $this->field('open_status_' . $day, 'LBL_BUSINESS_HOURS_OPEN', 'bool'),
                $this->field('opening_time_' . $day, 'LBL_BUSINESS_HOURS_FROM', 'select', ['options' => $hours]),
                $this->field('closing_time_' . $day, 'LBL_BUSINESS_HOURS_TO', 'select', ['options' => $hours]),
            ]);
            $record = $businessHours->getBusinessHoursForDay($day)[0] ?? null;
            $values['open_status_' . $day] = $record ? !empty($record->open_status) : !in_array($day, ['Saturday', 'Sunday'], true);
            $values['opening_time_' . $day] = (string)($record ? $record->opening_hours : 9);
            $values['closing_time_' . $day] = (string)($record ? $record->closing_hours : 17);
        }

        return [
            'title' => $this->text('LBL_BUSINESS_HOURS_DESC'),
            'sections' => $sections,
            'values' => $values,
        ];
    }

    protected function saveValues(array $values): ?string
    {
        global $app_list_strings;

        $hours = $app_list_strings['business_hours_list'] ?? [];
        foreach (array_keys(self::DAYS) as $day) {
            foreach (['opening_time_', 'closing_time_'] as $prefix) {
                if (!$this->isOption($values[$prefix . $day] ?? null, $hours)) {
                    return $this->text('LBL_VALIDATION_ERRORS', 'Administration', false);
                }
            }
        }

        $businessHours = \BeanFactory::newBean('AOBH_BusinessHours');
        foreach (array_keys(self::DAYS) as $day) {
            $record = $businessHours->getOrCreate($day);
            $record->day = $day;
            $record->open_status = !empty($values['open_status_' . $day]) ? 1 : 0;
            $record->opening_hours = (string)$values['opening_time_' . $day];
            $record->closing_hours = (string)$values['closing_time_' . $day];
            $record->save();
        }

        return null;
    }
}
