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

/**
 * The campaign email settings (legacy EmailMan&action=campaignconfig): marketing records
 * and emails sent per run, retries and threshold, tracker links, location of the tracking entry
 * points, copy of each email, saved by the legacy EmailManController::action_Save() (campaign
 * form) with the request of the legacy form.
 */
class CampaignEmailSettingsPage extends EmailSettingsPage
{
    /** The numbers of the page, with their default (the sugar_config default of the legacy view). */
    protected const NUMBERS = [
        'massemailer_campaign_marketing_items_per_run' => ['LBL_CAMPAIGN_MARKETING_RECORDS_PER_RUN', 'campaign_marketing_items_per_run_default', 3],
        'massemailer_campaign_emails_per_run' => ['LBL_EMAILS_PER_RUN', 'campaign_emails_per_run_default', 50],
        'massemailer_campaign_emails_max_retries' => ['LBL_CAMPAIGN_MAX_RETRIES', 'campaign_emails_max_retries_default', 3],
        'massemailer_campaign_emails_threshold' => ['LBL_CAMPAIGN_THRESHOLD', 'campaign_emails_threshold_default', 10],
    ];

    public function getPageKey(): string
    {
        return 'campaign-email-settings';
    }

    protected function getPage(): array
    {
        global $app_strings, $sugar_config;

        $admin = \BeanFactory::newBean('Administration');
        $admin->retrieveSettings();
        $settings = $admin->settings;

        $fields = [];
        $values = [];
        foreach (self::NUMBERS as $name => [$label, $default, $fallback]) {
            $fields[] = $this->field($name, $label, 'number', ['required' => true], 'EmailMan');
            $value = $settings[$name] ?? null;
            $values[$name] = (string)(is_numeric($value) && ($name !== 'massemailer_campaign_emails_per_run' || !empty($value))
                ? $value : ($sugar_config[$default] ?? $fallback));
        }

        $yesNo = fn (string $yes, string $no) => $this->options([$yes => $app_strings['LBL_YES'] ?? 'Yes', $no => $app_strings['LBL_NO'] ?? 'No']);
        $trackers = isset($settings['massemailer_trackers_enabled'])
            ? isTrue($settings['massemailer_trackers_enabled']) : isTrue($sugar_config['trackers_enabled'] ?? true);
        $customLocation = !empty($settings['massemailer_tracking_entities_location_type'])
            && $settings['massemailer_tracking_entities_location_type'] !== '1';

        $fields[] = $this->field('massemailer_trackers_enabled', 'LBL_TRACKER_LINKS_ENABLED', 'select', [
            'options' => $yesNo('true', 'false'),
        ], 'EmailMan');
        $fields[] = $this->field('massemailer_tracking_entities_location_type', 'LBL_LOCATION_TRACK', 'select', [
            'options' => $this->options([
                '1' => $this->text('LBL_DEFAULT_LOCATION', 'EmailMan', false),
                '2' => $this->text('LBL_CUSTOM_LOCATION', 'EmailMan', false),
            ]),
        ], 'EmailMan');
        // The custom location of the tracking entry points (the site URL by default, as the legacy page).
        $fields[] = $this->field('massemailer_tracking_entities_location', '', 'text', [
            'wide' => true,
            'showIf' => [['field' => 'massemailer_tracking_entities_location_type', 'values' => ['2']]],
        ]);
        $fields[] = $this->field('massemailer_email_copy', 'LBL_CAMP_MESSAGE_COPY', 'select', [
            'options' => $yesNo('1', '2'),
            'help' => 'LBL_CAMP_MESSAGE_COPY_DESC',
        ], 'EmailMan');

        $values += [
            'massemailer_trackers_enabled' => $trackers ? 'true' : 'false',
            'massemailer_tracking_entities_location_type' => $customLocation ? '2' : '1',
            'massemailer_tracking_entities_location' => $customLocation
                ? $this->decode((string)($settings['massemailer_tracking_entities_location'] ?? ''))
                : (string)($sugar_config['site_url'] ?? ''),
            'massemailer_email_copy' => ($settings['massemailer_email_copy'] ?? '') === '1' ? '1' : '2',
        ];

        return [
            'title' => $this->text('LBL_CAMPAIGN_CONFIG_TITLE'),
            'sections' => [
                $this->section('LBL_OUTBOUND_EMAIL_TITLE', $fields, 'EmailMan'),
            ],
            'values' => $values,
        ];
    }

    protected function saveValues(array $values): ?string
    {
        global $current_user;

        if (!is_admin($current_user) && !is_admin_for_module($current_user, 'Campaigns')) {
            return $this->text('LBL_SAVE_ERROR', 'Administration', false);
        }

        $post = ['module' => 'EmailMan', 'action' => 'Save', 'source_form' => 'config', 'campaignConfig' => '1'];
        foreach (array_keys(self::NUMBERS) as $name) {
            $value = trim((string)($values[$name] ?? ''));
            if (!ctype_digit($value)) {
                return $this->text('LBL_SAVE_ERROR', 'Administration', false);
            }
            $post[$name] = $value;
        }
        $post['massemailer_trackers_enabled'] = ($values['massemailer_trackers_enabled'] ?? '') === 'false' ? 'false' : 'true';
        $post['massemailer_tracking_entities_location_type'] = ($values['massemailer_tracking_entities_location_type'] ?? '') === '2' ? '2' : '1';
        // The location is posted only when custom (disabled field of the legacy form otherwise).
        if ($post['massemailer_tracking_entities_location_type'] === '2') {
            $post['massemailer_tracking_entities_location'] = trim((string)($values['massemailer_tracking_entities_location'] ?? ''));
        }
        $post['massemailer_email_copy'] = ($values['massemailer_email_copy'] ?? '') === '1' ? '1' : '2';

        $this->saveLegacyForm($post);

        return null;
    }
}
