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
 * The Case Module Settings (legacy Administration&action=AOPAdmin): AOP and its portal,
 * the assignment of the cases created from emails, the case status changes on a reply, the
 * support emails and their templates; shown and saved as the legacy page (config aop), the
 * fields hidden by its script hidden the same way.
 */
class AopSettingsPage extends AdminSettingsPage
{
    protected const SINGLE_USER = 'singleUser';

    protected const TEMPLATES = [
        'user_email_template_id' => 'LBL_AOP_USER_EMAIL_TEMPLATE',
        'contact_email_template_id' => 'LBL_AOP_CONTACT_EMAIL_TEMPLATE',
        'case_creation_email_template_id' => 'LBL_AOP_CASE_CREATION_EMAIL_TEMPLATE',
        'case_closure_email_template_id' => 'LBL_AOP_CASE_CLOSURE_EMAIL_TEMPLATE',
        'joomla_account_creation_email_template_id' => 'LBL_AOP_JOOMLA_ACCOUNT_CREATION_EMAIL_TEMPLATE',
    ];

    public function getPageKey(): string
    {
        return 'aop';
    }

    protected function getPage(): array
    {
        $config = $this->getConfig();
        $options = $this->getOptions();

        $aop = ['field' => 'enable_aop', 'values' => [true]];
        $otherMethods = [
            'field' => 'distribution_method',
            'values' => array_values(array_diff(array_map('strval', array_keys($options['distribution_method'])), [self::SINGLE_USER])),
        ];
        $assign = is_array($config['distribution_options'] ?? null) ? array_values($config['distribution_options']) : [];

        $templates = [];
        foreach (self::TEMPLATES as $name => $label) {
            $templates[] = $this->field($name, $label, 'select', [
                'options' => $this->options($options['templates']),
                'links' => [
                    ['label' => $this->text('LBL_CREATE_EMAIL_TEMPLATE'), 'href' => '#/email-templates/edit'],
                    ['label' => $this->text('LBL_EDIT_EMAIL_TEMPLATE'), 'href' => '#/email-templates/record/{value}', 'withValue' => true],
                ],
            ]);
        }

        $statuses = [];
        foreach (json_decode((string)($config['case_status_changes'] ?? '[]'), true) ?: [] as $if => $then) {
            $statuses[] = [(string)$if, (string)$then];
        }

        $values = [
            'enable_aop' => !empty($config['enable_aop']),
            'enable_portal' => !empty($config['enable_portal']),
            'joomla_url' => $this->decode((string)($config['joomla_url'] ?? '')),
            'distribution_method' => (string)($config['distribution_method'] ?? ''),
            'distribution_options_type' => (string)($assign[0] ?? ''),
            'distribution_options_group' => (string)($assign[1] ?? ''),
            'distribution_options_role' => (string)($assign[2] ?? ''),
            'distribution_user_id' => (string)($config['distribution_user_id'] ?? ''),
            'case_status_changes' => $statuses,
            'support_from_address' => $this->decode((string)($config['support_from_address'] ?? '')),
            'support_from_name' => $this->decode((string)($config['support_from_name'] ?? '')),
            'inbound_email_case_macro' => $this->getCaseMacro(),
        ];
        foreach (array_keys(self::TEMPLATES) as $name) {
            $values[$name] = (string)($config[$name] ?? '');
        }

        return [
            'title' => $this->text('LBL_AOP_ADMIN_MANAGE_AOP'),
            'sections' => [
                $this->section('LBL_AOP_JOOMLA_SETTINGS', [
                    $this->field('enable_aop', 'LBL_AOP_ENABLE_AOP', 'bool'),
                    $this->field('enable_portal', 'LBL_AOP_ENABLE_PORTAL', 'bool', ['showIf' => [$aop]]),
                    $this->field('joomla_url', 'LBL_AOP_JOOMLA_URL', 'text', [
                        'required' => true,
                        'showIf' => [$aop, ['field' => 'enable_portal', 'values' => [true]]],
                    ]),
                ]),
                $this->section('LBL_AOP_CASE_ASSIGNMENT', [
                    $this->field('distribution_method', 'LBL_AOP_DISTRIBUTION_METHOD', 'select', ['options' => $this->options($options['distribution_method'])]),
                    $this->field('distribution_options_type', 'LBL_AOP_ASSIGNMENT_OPTIONS', 'select', [
                        'options' => $this->options($options['assign']),
                        'showIf' => [$otherMethods],
                    ]),
                    $this->field('distribution_options_group', 'LBL_AOP_ASSIGNMENT_OPTIONS', 'select', [
                        'options' => $this->options($options['groups']),
                        'showIf' => [$otherMethods, ['field' => 'distribution_options_type', 'values' => ['security_group']]],
                    ]),
                    $this->field('distribution_options_role', 'LBL_AOP_ASSIGNMENT_OPTIONS', 'select', [
                        'options' => $this->options($options['roles']),
                        'showIf' => [$otherMethods, ['field' => 'distribution_options_type', 'values' => ['role', 'security_group']]],
                    ]),
                    $this->field('distribution_user_id', 'LBL_AOP_ASSIGNMENT_USER', 'select', [
                        'options' => $this->options($options['users']),
                        'required' => true,
                        'showIf' => [['field' => 'distribution_method', 'values' => [self::SINGLE_USER]]],
                    ]),
                ]) + ['showIf' => [$aop]],
                $this->section('LBL_AOP_CASE_STATUS_SETTINGS', [
                    $this->field('case_status_changes', '', 'pairs', [
                        'options' => $this->options($options['statuses']),
                        'leftLabel' => $this->text('LBL_AOP_IF_STATUS'),
                        'rightLabel' => $this->text('LBL_AOP_THEN_STATUS'),
                        'addLabel' => $this->text('LBL_AOP_ADD_STATUS'),
                        'removeLabel' => $this->text('LBL_AOP_REMOVE_STATUS'),
                    ]),
                ]),
                $this->section('LBL_AOP_EMAIL_SETTINGS', [
                    $this->field('support_from_address', 'LBL_SUPPORT_FROM_ADDRESS', 'text'),
                    $this->field('support_from_name', 'LBL_SUPPORT_FROM_NAME', 'text'),
                    $this->field('inbound_email_case_macro', 'LBL_CASE_MACRO', 'text', [
                        'help' => $this->text('LBL_CASE_MACRO_DESC', 'Administration', false)
                            . ' <i>' . htmlspecialchars($this->text('LBL_CASE_MACRO_DESC2', 'Administration', false)) . '</i>',
                    ]),
                    ...$templates,
                ]) + ['showIf' => [$aop]],
            ],
            'values' => $values,
        ];
    }

    /**
     * Saves as the legacy page: the portal URL with its scheme, the portal off without AOP, the
     * single user only with that method, the status changes by status (the last one of a status
     * kept). The portal access key (not shown by the legacy page) is kept.
     */
    protected function saveValues(array $values): ?string
    {
        $options = $this->getOptions();
        $configurator = new \Configurator();
        $config = $configurator->config['aop'] ?? [];

        $enabled = !empty($values['enable_aop']);
        $portal = $enabled && !empty($values['enable_portal']);
        $url = strtolower(trim((string)($values['joomla_url'] ?? '')));
        if ($portal && $url === '') {
            return $this->text('LBL_VALIDATION_ERRORS', 'Administration', false);
        }

        $method = (string)($values['distribution_method'] ?? '');
        if (!$this->isOption($method, $options['distribution_method'])) {
            $method = (string)($config['distribution_method'] ?? '');
        }
        $user = $method === self::SINGLE_USER ? (string)($values['distribution_user_id'] ?? '') : '';
        if ($enabled && $method === self::SINGLE_USER && ($user === '' || !$this->isOption($user, $options['users']))) {
            return $this->text('LBL_VALIDATION_ERRORS', 'Administration', false);
        }

        $statuses = [];
        foreach (is_array($values['case_status_changes'] ?? null) ? $values['case_status_changes'] : [] as $pair) {
            if (is_array($pair) && $this->isOption($pair[0] ?? null, $options['statuses']) && $this->isOption($pair[1] ?? null, $options['statuses'])) {
                $statuses[(string)$pair[0]] = (string)$pair[1];
            }
        }

        // The texts as the legacy page posted them (encoded).
        $url = $this->encode($url);
        // In the order of the legacy page (the order of the new keys in the config): enable_aop
        // set by its display when missing.
        if (!array_key_exists('enable_aop', $config)) {
            $config['enable_aop'] = 1;
        }
        $config['joomla_url'] = $url === '' ? '' : (preg_match('@^https?://@', $url) ? $url : 'http://' . $url);
        $config['enable_aop'] = $enabled;
        $config['enable_portal'] = $portal;
        $config['joomla_access_key'] = $config['joomla_access_key'] ?? '';
        $config['distribution_method'] = $method;
        $config['distribution_user_id'] = $user;
        $config['distribution_options'] = [
            $this->isOption($values['distribution_options_type'] ?? null, $options['assign']) ? (string)$values['distribution_options_type'] : '',
            $this->isOption($values['distribution_options_group'] ?? null, $options['groups']) ? (string)$values['distribution_options_group'] : '',
            $this->isOption($values['distribution_options_role'] ?? null, $options['roles']) ? (string)$values['distribution_options_role'] : '',
        ];
        foreach (array_keys(self::TEMPLATES) as $name) {
            $config[$name] = $this->isOption($values[$name] ?? null, $options['templates']) ? (string)$values[$name] : '';
        }
        $config['support_from_address'] = $this->encode(trim((string)($values['support_from_address'] ?? '')));
        $config['support_from_name'] = $this->encode(trim((string)($values['support_from_name'] ?? '')));
        $config['case_status_changes'] = json_encode($statuses);

        $configurator->config['aop'] = $config;
        if (isset($values['inbound_email_case_macro'])) {
            $configurator->config['inbound_email_case_subject_macro'] = $this->encode((string)$values['inbound_email_case_macro']);
        }
        $this->withPost([], static fn () => $configurator->saveConfig());

        return null;
    }

    /**
     * The config of the page, with the defaults of the legacy page.
     *
     * @return array<string, mixed>
     */
    protected function getConfig(): array
    {
        global $sugar_config;

        $config = is_array($sugar_config['aop'] ?? null) ? $sugar_config['aop'] : [];
        if (!array_key_exists('enable_aop', $config)) {
            $config['enable_aop'] = 1;
        }

        return $config;
    }

    protected function getCaseMacro(): string
    {
        global $sugar_config;

        return $this->decode((string)($sugar_config['inbound_email_case_subject_macro'] ?? \BeanFactory::newBean('Cases')->getEmailSubjectMacro()));
    }

    /**
     * The options of the selects, value => label.
     *
     * @return array<string, array<string|int, mixed>>
     */
    /**
     * The active users, with the user cases are assigned to (kept when no longer active, as the
     * legacy relate field).
     *
     * @return array<string, string>
     */
    protected function getUsers(): array
    {
        $users = get_user_array(true, 'Active');
        $current = (string)($this->getConfig()['distribution_user_id'] ?? '');
        if ($current !== '' && !array_key_exists($current, $users)) {
            $user = \BeanFactory::getBean('Users', $current);
            if ($user && !empty($user->id)) {
                $users[$current] = (string)$user->user_name;
            }
        }

        return $users;
    }

    protected function getOptions(): array
    {
        global $app_list_strings;

        $methods = $app_list_strings['dom_email_distribution_for_auto_create'] ?? [];
        unset($methods['AOPDefault']);
        $case = \BeanFactory::newBean('Cases');
        $statusList = $case->field_defs['status']['options'] ?? '';

        return [
            'distribution_method' => $methods,
            'assign' => $app_list_strings['aow_assign_options'] ?? [],
            'groups' => get_bean_select_array(true, 'SecurityGroup', 'name', '', 'name', true),
            'roles' => get_bean_select_array(true, 'ACLRole', 'name', '', 'name', true),
            'users' => $this->getUsers(),
            'templates' => get_bean_select_array(true, 'EmailTemplate', 'name', '', 'name'),
            'statuses' => $app_list_strings[$statusList] ?? [],
        ];
    }
}
