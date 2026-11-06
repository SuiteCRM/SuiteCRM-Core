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
 * The email settings (legacy EmailMan&action=config): the system outbound account (link
 * to its record view, or to create it), the use of the system account by the users, the
 * notifications and email defaults, the opt-in, the email import and the security of the email
 * HTML (tags removed), saved by the legacy EmailManController::action_Save() with the request of
 * the legacy form.
 */
class EmailSettingsPage extends AdminSettingsPage
{
    /** The HTML tags removed from the emails (email_xss), in the order of the legacy page. */
    protected const SECURITY_TAGS = [
        'applet', 'base', 'embed', 'form', 'frame', 'frameset', 'iframe', 'import', 'layer', 'link', 'object', 'style', 'xmp',
    ];

    public function getPageKey(): string
    {
        return 'email-settings';
    }

    protected function getPage(): array
    {
        global $app_list_strings, $sugar_config;

        $admin = \BeanFactory::newBean('Administration');
        $admin->retrieveSettings();
        $settings = $admin->settings;

        require_once 'modules/Configurator/Configurator.php';
        $config = (new \Configurator())->config;

        $xss = $sugar_config['email_xss'] ?? '';
        $tags = $xss !== '' ? unserialize(base64_decode($xss), ['allowed_classes' => false]) : null;
        if (!is_array($tags)) {
            $tags = unserialize(base64_decode(getDefaultXssTags()), ['allowed_classes' => false]) ?: [];
        }

        $templates = get_bean_select_array(true, 'EmailTemplate', 'name', '', 'name', true);
        $security = [];
        foreach (self::SECURITY_TAGS as $tag) {
            $security[] = $this->field($tag, 'LBL_SECURITY_' . strtoupper($tag), 'bool', [], 'EmailMan');
        }

        $values = [
            'notify_allow_default_outbound' => !empty($settings['notify_allow_default_outbound']),
            'mail_allowusersend' => !empty($sugar_config['email_allow_send_as_user']),
            'notify_on' => !empty($settings['notify_on']),
            'email_warning_notifications' => !empty($sugar_config['email_warning_notifications']),
            'email_default_delete_attachments' => !empty($sugar_config['email_default_delete_attachments']),
            'notify_send_from_assigning_user' => !empty($settings['notify_send_from_assigning_user']),
            'email_enable_confirm_opt_in' => (string)($config['email_enable_confirm_opt_in'] ?? ''),
            'email_enable_auto_send_opt_in' => !empty($config['email_enable_auto_send_opt_in']),
            'email_template_id_opt_in' => (string)($config['email_confirm_opt_in_email_template_id'] ?? ''),
            'system_drafts_popup' => !empty($settings['system_drafts_popup']),
            'legacy_email_behaviour' => !empty($sugar_config['legacy_email_behaviour']),
            'email_import_per_run_threshold' => is_numeric($sugar_config['email_import_per_run_threshold'] ?? null)
                ? (string)(int)$sugar_config['email_import_per_run_threshold'] : '25',
            'email_import_timeframe_start' => (string)($sugar_config['email_import_timeframe_start'] ?? '-30 days'),
            'email_import_fetch_unread_only' => isTrue($sugar_config['email_import_fetch_unread_only'] ?? false),
        ];
        foreach (self::SECURITY_TAGS as $tag) {
            $values[$tag] = array_key_exists($tag, $tags);
        }

        return [
            'title' => $this->text('LBL_MASS_EMAIL_CONFIG_TITLE'),
            'sections' => [
                $this->section('LBL_EMAIL_OUTBOUND_CONFIGURATION', [
                    ['name' => 'system_account', 'label' => $this->text('LBL_SYSTEM_OUTBOUND_EMAIL_ACCOUNT', 'EmailMan'), 'type' => 'html', 'help' => $this->systemAccountHtml()],
                    $this->field('notify_allow_default_outbound', 'LBL_ALLOW_DEFAULT_SELECTION', 'bool', ['help' => 'LBL_ALLOW_DEFAULT_SELECTION_HELP'], 'EmailMan'),
                    $this->field('mail_allowusersend', 'LBL_ALLOW_SEND_AS_USER', 'bool', ['help' => 'LBL_ALLOW_SEND_AS_USER_DESC'], 'EmailMan'),
                ], 'EmailMan', 'LBL_OUTGOING_SECTION_HELP'),
                $this->section('LBL_NOTIFY_TITLE', [
                    $this->field('notify_on', 'LBL_NOTIFY_ON', 'bool', ['help' => 'LBL_NOTIFICATION_ON_DESC'], 'EmailMan'),
                    $this->field('email_warning_notifications', 'LBL_EMAIL_WARNING_NOTIFICATIONS', 'bool', [], 'EmailMan'),
                    $this->field('email_default_delete_attachments', 'LBL_EMAIL_DEFAULT_DELETE_ATTACHMENTS', 'bool', [], 'EmailMan'),
                    $this->field('notify_send_from_assigning_user', 'LBL_NOTIFY_SEND_FROM_ASSIGNING_USER', 'bool', ['help' => 'LBL_FROM_ADDRESS_HELP'], 'EmailMan'),
                    $this->field('email_enable_confirm_opt_in', 'LBL_EMAIL_ENABLE_CONFIRM_OPT_IN', 'select', [
                        'options' => $this->options($app_list_strings['email_settings_opt_in_dom'] ?? []),
                    ], 'EmailMan'),
                    $this->field('email_enable_auto_send_opt_in', 'LBL_EMAIL_ENABLE_SEND_OPT_IN', 'bool', [], 'EmailMan'),
                    $this->field('email_template_id_opt_in', 'LBL_EMAIL_CONFIRM_OPT_IN_TEMPLATE_ID', 'select', [
                        'options' => $this->options(is_array($templates) ? $templates : []),
                    ], 'EmailMan'),
                    $this->field('system_drafts_popup', 'LBL_DRAFTS_POPUP', 'bool', [], 'EmailMan'),
                    $this->field('legacy_email_behaviour', 'LBL_LEGACY_EMAIL_COMPOSE_BEHAVIOR', 'bool', [], 'EmailMan'),
                ], 'EmailMan'),
                $this->section('LBL_EMAIL_IMPORT_CONFIGURATION', [
                    $this->field('email_import_per_run_threshold', 'LBL_EMAIL_IMPORT_PER_RUN_THRESHOLD', 'number', [
                        'help' => 'LBL_EMAIL_IMPORT_PER_RUN_THRESHOLD_DESC',
                    ], 'EmailMan'),
                    $this->field('email_import_timeframe_start', 'LBL_EMAIL_IMPORT_TIMEFRAME_START', 'select', [
                        'options' => $this->options($app_list_strings['email_import_timeframe_start_dom'] ?? []),
                        'help' => 'LBL_EMAIL_IMPORT_TIMEFRAME_START_DESC',
                    ], 'EmailMan'),
                    $this->field('email_import_fetch_unread_only', 'LBL_EMAIL_IMPORT_FETCH_UNREAD_ONLY', 'bool', [], 'EmailMan'),
                ], 'EmailMan'),
                // The Outlook defaults and the toggle of all the tags, as the checkboxes of the legacy page.
                $this->section('LBL_SECURITY_TITLE', $security, 'EmailMan', 'LBL_SECURITY_DESC', [
                    'outlook' => 'LBL_SECURITY_OUTLOOK_DEFAULTS',
                    'toggle' => 'LBL_SECURITY_TOGGLE_ALL',
                ]),
            ],
            'values' => $values,
        ];
    }

    protected function saveValues(array $values): ?string
    {
        global $current_user;

        if (!is_admin($current_user) && !is_admin_for_module($current_user, 'Emails') && !is_admin_for_module($current_user, 'Campaigns')) {
            return $this->text('LBL_SAVE_ERROR', 'Administration', false);
        }
        $threshold = trim((string)($values['email_import_per_run_threshold'] ?? ''));
        if ($threshold !== '' && !ctype_digit($threshold)) {
            return $this->text('LBL_SAVE_ERROR', 'Administration', false);
        }

        // The request of the legacy form: hidden fields with the value of an unchecked box, other
        // boxes posted only when checked.
        $post = [
            'module' => 'EmailMan',
            'action' => 'Save',
            'source_form' => 'config',
            'notify_allow_default_outbound' => !empty($values['notify_allow_default_outbound']) ? '2' : '0',
            'mail_allowusersend' => !empty($values['mail_allowusersend']) ? '1' : '0',
            'notify_on' => !empty($values['notify_on']) ? '1' : '0',
            'notify_send_from_assigning_user' => !empty($values['notify_send_from_assigning_user']) ? '2' : '0',
            'email_enable_confirm_opt_in' => (string)($values['email_enable_confirm_opt_in'] ?? ''),
            'email_template_id_opt_in' => (string)($values['email_template_id_opt_in'] ?? ''),
            'system_drafts_popup' => !empty($values['system_drafts_popup']) ? '1' : '0',
            'email_import_per_run_threshold' => $threshold,
            'email_import_timeframe_start' => (string)($values['email_import_timeframe_start'] ?? ''),
        ];
        foreach (['email_warning_notifications', 'email_default_delete_attachments', 'email_enable_auto_send_opt_in'] as $name) {
            if (!empty($values[$name])) {
                $post[$name] = '1';
            }
        }
        foreach (['legacy_email_behaviour', 'email_import_fetch_unread_only'] as $name) {
            if (!empty($values[$name])) {
                $post[$name] = 'true';
            }
        }
        foreach (self::SECURITY_TAGS as $tag) {
            if (!empty($values[$tag])) {
                $post[$tag] = '1';
            }
        }

        $this->saveLegacyForm($post);

        return null;
    }

    /**
     * Saves the request of the legacy form (a GET form: Administration::saveConfig() reads $_GET)
     * by the legacy EmailManController::action_Save().
     *
     * @param array<string, string> $post
     */
    protected function saveLegacyForm(array $post): void
    {
        $get = $_GET;
        $_GET = $this->encode($post);
        try {
            $this->withPost($post, static function () {
                require_once 'modules/EmailMan/controller.php';
                (new \EmailManController())->action_Save();
            });
        } finally {
            $_GET = $get;
        }
    }

    /**
     * The Outlook defaults (all the tags but style) and the toggle of all the tags (all checked,
     * else none), as the legacy page.
     */
    protected function runAction(string $action, array $values): array
    {
        if ($action === 'outlook') {
            $tags = array_fill_keys(self::SECURITY_TAGS, true);
            $tags['style'] = false;

            return ['status' => 'success', 'values' => $tags];
        }
        if ($action === 'toggle') {
            $all = array_reduce(self::SECURITY_TAGS, static fn (bool $carry, string $tag) => $carry && !empty($values[$tag]), true);

            return ['status' => 'success', 'values' => array_fill_keys(self::SECURITY_TAGS, !$all)];
        }

        return parent::runAction($action, $values);
    }

    /**
     * The system outbound account: a link to its record view, or to create it (as the legacy page).
     */
    protected function systemAccountHtml(): string
    {
        global $app_strings;

        require_once 'include/OutboundEmail/OutboundEmail.php';
        $system = (new \OutboundEmail())->getSystemEmail();
        if ($system === null || empty($system->id)) {
            return '<a class="btn btn-sm btn-main" href="#/outbound-email-accounts/edit?type=system">'
                . htmlspecialchars($this->decode((string)($app_strings['LBL_CREATE_BUTTON_LABEL'] ?? 'Create'))) . '</a>';
        }

        return '<a href="#/outbound-email-accounts/record/' . rawurlencode((string)$system->id) . '">'
            . htmlspecialchars($this->decode((string)$system->name)) . '</a>';
    }
}
