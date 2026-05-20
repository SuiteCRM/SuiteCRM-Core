<?php
/**
 * SuiteCRM is a customer relationship management program developed by SalesAgility Ltd.
 * Copyright (C) 2023 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SALESAGILITY, SALESAGILITY DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */

require_once __DIR__ . '/AddAlert.php';

class AddScheduledReminderAlerts
{

    /**
     * @var AddAlert
     */
    protected $addAlertService;

    public function __construct()
    {
        $this->addAlertService = new AddAlert();
    }

    /**
     * @param $checkDecline bool
     * @return void
     */
    public function run($checkDecline = true): void
    {
        global $current_user, $timedate, $app_strings;

        if (empty($current_user->id)) {
            return;
        }

        [$dateTimeMaxStamp, $dateTimeNowStamp] = $this->getDateRange();

        // Original jsAlert used to a meeting integration.
        $meetingIntegration = $this->getMeetingIntegration();

        $popupReminders = $this->getScheduledReminders($dateTimeNowStamp, $dateTimeMaxStamp);

        if (empty($popupReminders)) {
            return;
        }

        $i_runs = 0;
        foreach ($popupReminders as $popupReminder) {

            $relatedEventModule = $popupReminder->related_event_module ?? '';

            $relatedEvent = BeanFactory::getBean(
                $relatedEventModule,
                $popupReminder->related_event_module_id
            );

            if (!$relatedEvent) {
                continue;
            }

            $dateTime = DateTime::createFromFormat($timedate->get_date_time_format(), $relatedEvent->date_start);
            $relatedEventStart = $dateTime ? $dateTime->getTimestamp() : $dateTime;

            if ($this->shouldUpdateExecutionTime($i_runs, $popupReminder)) {
                $this->updateReminderExecutionTime($popupReminder, $relatedEventStart);
                $i_runs++;
            }

            $status = $relatedEvent->status ?? '';
            if ($status !== '' && $relatedEvent->status !== 'Planned') {
                continue;
            }

            $relatedEventStart -= $popupReminder->timer_popup;

            if (!$this->isWithinExecutionDateRange($relatedEventStart, $dateTimeNowStamp, $dateTimeMaxStamp)) {
                continue;
            }

            $user = BeanFactory::getBean('Users', $current_user->id);
            if ($checkDecline && Reminder::isDecline($relatedEvent, $user)) {
                continue;
            }

            // The original popup/alert reminders check the accept_status field in related users/leads/contacts etc. and filtered these users who not decline this event.
            $invitees = $this->getInvitees($popupReminder, $current_user);
            if (!$invitees) {
                continue;
            }

            $url = $this->getURL($popupReminder);
            $instructions = $this->getInstructions($app_strings['MSG_JS_ALERT_MTG_REMINDER_MEETING_MSG']);

            if ($popupReminder->related_event_module === 'Meetings') {
                [$url, $instructions] = $this->addMeetingOverrides($meetingIntegration, $popupReminder, $url,
                    $instructions);
            }

            $alertTitle = $this->getMeetingName($relatedEvent);
            $location = $this->getLocation($relatedEvent);

            $relatedToMeeting = $this->getRelatedName($popupReminder);

            $description = $this->getDescription($relatedEvent, $app_strings, $relatedToMeeting);


            $time = $this->getAlertTime($relatedEvent);

            $alertSubtitle = $app_strings['MSG_JS_ALERT_MTG_REMINDER_TIME'] . $time;
            $alertDescription = $app_strings['MSG_JS_ALERT_MTG_REMINDER_LOC'] . $location . $description . $instructions;
            $this->addAlertService->run(
                BeanFactory::getBeanName($relatedEvent->module_name) ?? '',
                $alertTitle,
                $alertDescription,
                $url,
                $time,
                $popupReminder->id
            );
        }
    }

    /**
     * @return array|null
     */
    protected function getMeetingIntegration(): ?array
    {
        global $sugar_config;
        $meetingIntegration = null;
        if (isset($sugar_config['meeting_integration']) && !empty($sugar_config['meeting_integration'])) {
            if (!class_exists($sugar_config['meeting_integration'])) {
                require_once("modules/{$sugar_config['meeting_integration']}/{$sugar_config['meeting_integration']}.php");
            }
            $meetingIntegration = new $sugar_config['meeting_integration']();
        }

        return $meetingIntegration;
    }

    /**
     * @param $dateTimeNowStamp
     * @param $dateTimeMaxStamp
     * @return SugarBean[]|null
     */
    protected function getScheduledReminders($dateTimeNowStamp, $dateTimeMaxStamp): ?array
    {
        $popupReminders = BeanFactory::getBean('Reminders')->get_full_list(
            '',
            "reminders.popup = 1 AND
                    reminders.popup_viewed = 0 AND
                    (
                        reminders.date_willexecute = -1 OR
                        reminders.date_willexecute BETWEEN " . $dateTimeNowStamp . " AND " . $dateTimeMaxStamp . "
                    )"
        );

        return $popupReminders;
    }

    /**
     * @param $relatedEvent
     * @return string
     */
    protected function getMeetingName($relatedEvent): string
    {
        global $app_strings;
        $default = $app_strings['MSG_JS_ALERT_MTG_REMINDER_NO_EVENT_NAME'];

        return from_html($relatedEvent->name ?? $default);
    }

    /**
     * @param $relatedEvent
     * @param array $app_strings
     * @param string $relatedToMeeting
     * @return string
     */
    protected function getDescription($relatedEvent, array $app_strings, string $relatedToMeeting): string
    {
        $desc1 = from_html($relatedEvent->description ?? $app_strings['MSG_JS_ALERT_MTG_REMINDER_NO_DESCRIPTION']);
        $description = empty($desc1) ? '' : $app_strings['MSG_JS_ALERT_MTG_REMINDER_AGENDA'] . $desc1 . "\n";
        $description .= "\n" . $app_strings['MSG_JS_ALERT_MTG_REMINDER_STATUS'] . (isset($relatedEvent->status) ? $relatedEvent->status : '') . "\n" . $app_strings['MSG_JS_ALERT_MTG_REMINDER_RELATED_TO'] . $relatedToMeeting;

        return $description;
    }

    /**
     * @param $relatedEvent
     * @return string
     */
    protected function getLocation($relatedEvent): string
    {
        global $app_strings;
        $default = $app_strings['MSG_JS_ALERT_MTG_REMINDER_NO_LOCATION'];

        return from_html($relatedEvent->location ?? $default);
    }

    /**
     * @param SugarBean $popupReminder
     * @param $current_user
     * @return SugarBean[]|null
     */
    protected function getInvitees(SugarBean $popupReminder, $current_user): ?array
    {
        $invitees = BeanFactory::getBean('Reminders_Invitees')->get_full_list(
            '',
            "reminders_invitees.reminder_id = '{$popupReminder->id}' AND reminders_invitees.related_invitee_module_id = '{$current_user->id}'"
        );

        return $invitees;
    }

    /**
     * To return the name of parent bean.
     * @param $popupReminder
     * @return string
     */
    protected function getRelatedName($popupReminder): string
    {
        $parentType = $popupReminder->related_event_module;
        $parentId = $popupReminder->related_event_module_id;

        if (!empty($parentType) && !empty($parentId)) {
            $parentBean = BeanFactory::getBean($parentType, $parentId);
            if (($parentBean instanceof SugarBean) && isset($parentBean->name)) {
                return $parentBean->name;
            }
        }

        return '';
    }

    /**
     * Get date range
     * @return array
     */
    protected function getDateRange(): array
    {
        global $timedate, $app_list_strings;

        $reminder_max_time = $app_list_strings['reminder_max_time'] ?? 0;

        $dateTimeMax = $timedate->getNow(true)->modify("+{$reminder_max_time} seconds")->asDb(false);

        $dateTimeNow = $timedate->getNow(true)->asDb(false);

        $dateTimeNowStamp = strtotime(Reminder::unQuoteTime($dateTimeNow));
        $dateTimeMaxStamp = strtotime(Reminder::unQuoteTime($dateTimeMax));

        return [$dateTimeMaxStamp, $dateTimeNowStamp];
    }

    /**
     * @param SugarBean $popupReminder
     * @param $relatedEventStart
     * @return void
     */
    protected function updateReminderExecutionTime(SugarBean $popupReminder, $relatedEventStart): void
    {
        //we have column to save data
        if (!$relatedEventStart) {
            $popupReminder->date_willexecute = -2;
        } else {
            $popupReminder->date_willexecute = $relatedEventStart;
        }
        $popupReminder->save();
    }

    /**
     * @param int $i_runs
     * @param SugarBean $popupReminder
     * @return bool
     */
    protected function shouldUpdateExecutionTime(int $i_runs, SugarBean $popupReminder): bool
    {
        return $i_runs < 1000 &&
            isset($popupReminder->fetched_row['date_willexecute']) &&
            $popupReminder->fetched_row['date_willexecute'] == -1;
    }

    /**
     * @param $relatedEventStart
     * @param $dateTimeNowStamp
     * @param $dateTimeMaxStamp
     * @return bool
     */
    protected function isWithinExecutionDateRange($relatedEventStart, $dateTimeNowStamp, $dateTimeMaxStamp): bool
    {
        if (empty($relatedEventStart)) {
            return false;
        }

        //increase time by 10 secs to make sure we calculate within the correct minute
        if (($dateTimeNowStamp + 10) < $relatedEventStart) {
            return false;
        }

        if ($relatedEventStart > $dateTimeMaxStamp) {
            return false;
        }

        return true;
    }

    /**
     * @param $relatedEvent
     * @return mixed|string
     */
    protected function getAlertTime($relatedEvent)
    {
        global $app_strings, $timedate, $db;

        $default = '';
        $dateStart = $relatedEvent->date_start ?? '';

        if (!empty($dateStart)) {
            $time_dbFromConvert = $db->fromConvert($dateStart, 'datetime');
            $time = $timedate->to_display_date_time($time_dbFromConvert);
            if (!$time) {
                $time = $dateStart;
            }
            if (!$time) {
                $time = $default;
            }
        } else {
            $time = $default;
        }

        return $time;
    }

    /**
     * @param SugarBean $popupReminder
     * @return string
     */
    protected function getURL(SugarBean $popupReminder): string
    {
        return Reminder::makeAlertURL(
            $popupReminder->related_event_module,
            $popupReminder->related_event_module_id
        );
    }

    /**
     * @param $MSG_JS_ALERT_MTG_REMINDER_MEETING_MSG
     * @return mixed
     */
    protected function getInstructions($MSG_JS_ALERT_MTG_REMINDER_MEETING_MSG)
    {
        return $MSG_JS_ALERT_MTG_REMINDER_MEETING_MSG;
    }

    /**
     * @param array|null $meetingIntegration
     * @param SugarBean $popupReminder
     * @param $url
     * @param $instructions
     * @return array
     */
    protected function addMeetingOverrides(
        ?array $meetingIntegration,
        SugarBean $popupReminder,
        $url,
        $instructions
    ): array {
        if (!empty($meetingIntegration) && $meetingIntegration->isIntegratedMeeting($popupReminder->related_event_module_id)) {
            $url = $meetingIntegration->miUrlGetJsAlert((array)$popupReminder);
            $instructions = $meetingIntegration->miGetJsAlertInstructions();
        }

        return [$url, $instructions];
    }
}
