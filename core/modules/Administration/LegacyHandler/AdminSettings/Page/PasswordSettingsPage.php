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
 * The Password Management (legacy Administration&action=PasswordManager): system
 * generated passwords, password rules, user password reset and captcha, email templates, LDAP
 * (v4 API); shown and saved as the legacy page: the password settings in the config (cleared
 * while LDAP is on, its sections then hidden), the others in the settings.
 *
 * The passwords (LDAP admin, LDAP encryption key, captcha private key) are never sent to the
 * page: kept when left empty, or removed.
 */
class PasswordSettingsPage extends AdminSettingsPage
{
    /**
     * The password settings reset by the legacy page while LDAP is on (clearPasswordSettings).
     */
    protected const CLEARED = [
        'SystemGeneratedPasswordON' => '',
        'generatepasswordtmpl' => '',
        'lostpasswordtmpl' => '',
        'factoremailtmpl' => '',
        'forgotpasswordON' => '0',
        'linkexpiration' => '1',
        'linkexpirationtime' => '30',
        'linkexpirationtype' => '1',
        'systexpiration' => '0',
        'systexpirationtime' => '',
        'systexpirationtype' => '0',
        'systexpirationlogin' => '',
    ];

    protected const RULES = ['oneupper', 'onelower', 'onenumber', 'onespecial'];

    protected const TEMPLATES = [
        'generatepasswordtmpl' => 'LBL_PASSWORD_GENERATE_TEMPLATE_MSG',
        'lostpasswordtmpl' => 'LBL_PASSWORD_LOST_TEMPLATE_MSG',
        'factoremailtmpl' => 'LBL_TWO_FACTOR_AUTH_EMAIL_TPL',
    ];

    /**
     * The LDAP texts of the settings, with their label.
     */
    protected const LDAP_TEXTS = [
        'ldap_hostname' => 'LBL_LDAP_SERVER_HOSTNAME',
        'ldap_port' => 'LBL_LDAP_SERVER_PORT',
        'ldap_base_dn' => 'LBL_LDAP_USER_DN',
        'ldap_login_filter' => 'LBL_LDAP_USER_FILTER',
        'ldap_bind_attr' => 'LBL_LDAP_BIND_ATTRIBUTE',
        'ldap_login_attr' => 'LBL_LDAP_LOGIN_ATTRIBUTE',
        'ldap_group_dn' => 'LBL_LDAP_GROUP_DN',
        'ldap_group_name' => 'LBL_LDAP_GROUP_NAME',
        'ldap_group_user_attr' => 'LBL_LDAP_GROUP_USER_ATTR',
        'ldap_group_attr' => 'LBL_LDAP_GROUP_ATTR',
        'ldap_admin_user' => 'LBL_LDAP_ADMIN_USER',
    ];

    protected const LDAP_FLAGS = ['ldap_group', 'ldap_group_attr_req_dn', 'ldap_authentication', 'ldap_auto_create_users'];

    /**
     * The passwords of the settings, never sent to the page.
     */
    protected const SECRETS = ['ldap_admin_password', 'ldap_enc_key', 'captcha_private_key'];

    public function getPageKey(): string
    {
        return 'password';
    }

    protected function getPage(): array
    {
        $password = $this->getPasswordConfig();
        $settings = $this->getSettings();
        $options = $this->getOptions();

        $ldap = ['field' => 'system_ldap_enabled', 'values' => [false]];
        $generated = ['field' => 'passwordsetting_SystemGeneratedPasswordON', 'values' => [true]];
        $forgot = ['field' => 'passwordsetting_forgotpasswordON', 'values' => [true]];
        $smtpWarning = $this->isSmtpServerSet() ? '' : $this->text('ERR_SMTP_SERVER_NOT_SET', 'Administration', false)
            . '<br>' . $this->text('LBL_EMAIL_ADDRESS_REQUIRED_FOR_FEATURE', 'Administration', false);

        $generatedFields = [$this->field('passwordsetting_SystemGeneratedPasswordON', 'LBL_PASSWORD_SYST_GENERATED_PWD_ON', 'bool', ['help' => 'LBL_PASSWORD_SYST_GENERATED_PWD_HELP'])];
        $forgotFields = [$this->field('passwordsetting_forgotpasswordON', 'LBL_PASSWORD_FORGOT_FEATURE', 'bool', ['help' => 'LBL_PASSWORD_FORGOT_FEATURE_HELP'])];
        if ($smtpWarning !== '') {
            $generatedFields[] = ['name' => 'smtp_generated', 'label' => '', 'type' => 'html', 'help' => '<i>' . $smtpWarning . '</i>', 'showIf' => [$generated]];
            $forgotFields[] = ['name' => 'smtp_forgot', 'label' => '', 'type' => 'html', 'help' => '<i>' . $smtpWarning . '</i>', 'showIf' => [$forgot]];
        }

        $templates = [];
        foreach (self::TEMPLATES as $name => $label) {
            $templates[] = $this->field('passwordsetting_' . $name, $label, 'select', [
                'options' => $this->options($options['templates']),
                'links' => [
                    ['label' => $this->text('LBL_PASSWORD_CREATE_TEMPLATE'), 'href' => '#/email-templates/edit'],
                    ['label' => $this->text('LBL_PASSWORD_EDIT_TEMPLATE'), 'href' => '#/email-templates/record/{value}', 'withValue' => true],
                ],
            ]);
        }

        $ldapOn = ['field' => 'system_ldap_enabled', 'values' => [true]];
        $ldapFields = [$this->field('system_ldap_enabled', 'LBL_LDAP_ENABLE', 'bool', ['help' => 'LBL_LDAP_HELP_TXT'])];
        foreach (['ldap_hostname', 'ldap_port', 'ldap_base_dn', 'ldap_login_filter', 'ldap_bind_attr', 'ldap_login_attr'] as $name) {
            $ldapFields[] = $this->field($name, self::LDAP_TEXTS[$name], 'text', ['help' => self::LDAP_TEXTS[$name] . '_DESC', 'showIf' => [$ldapOn]]);
        }
        $ldapFields[] = $this->field('ldap_group', 'LBL_LDAP_GROUP_MEMBERSHIP', 'bool', ['help' => 'LBL_LDAP_GROUP_MEMBERSHIP_DESC', 'showIf' => [$ldapOn], 'wide' => true]);
        $group = [$ldapOn, ['field' => 'ldap_group', 'values' => [true]]];
        foreach (['ldap_group_dn', 'ldap_group_name', 'ldap_group_user_attr', 'ldap_group_attr'] as $name) {
            $ldapFields[] = $this->field($name, self::LDAP_TEXTS[$name], 'text', ['help' => self::LDAP_TEXTS[$name] . '_DESC', 'showIf' => $group]);
        }
        $ldapFields[] = $this->field('ldap_group_attr_req_dn', 'LBL_LDAP_GROUP_ATTR_REQ_DN', 'bool', ['help' => 'LBL_LDAP_GROUP_ATTR_REQ_DN_DESC', 'showIf' => $group]);
        $ldapFields[] = $this->field('ldap_authentication', 'LBL_LDAP_AUTHENTICATION', 'bool', ['help' => 'LBL_LDAP_AUTHENTICATION_DESC', 'showIf' => [$ldapOn], 'wide' => true]);
        $authentication = [$ldapOn, ['field' => 'ldap_authentication', 'values' => [true]]];
        $ldapFields[] = $this->field('ldap_admin_user', 'LBL_LDAP_ADMIN_USER', 'text', ['help' => 'LBL_LDAP_ADMIN_USER_DESC', 'showIf' => $authentication]);
        $ldapFields[] = $this->field('ldap_admin_password', 'LBL_LDAP_ADMIN_PASSWORD', 'password', ['set' => ($settings['ldap_admin_password'] ?? '') !== '', 'showIf' => $authentication]);
        $ldapFields[] = $this->field('ldap_auto_create_users', 'LBL_LDAP_AUTO_CREATE_USERS', 'bool', ['help' => 'LBL_LDAP_AUTO_CREATE_USERS_DESC', 'showIf' => [$ldapOn]]);
        $ldapFields[] = $this->field('ldap_enc_key', 'LBL_LDAP_ENC_KEY', 'password', [
            'set' => ($settings['ldap_enc_key'] ?? '') !== '',
            'help' => function_exists('openssl_encrypt') ? 'LBL_LDAP_ENC_KEY_DESC' : 'LDAP_ENC_KEY_NO_FUNC_OPENSSL_DESC',
            'showIf' => [$ldapOn],
        ], 'Configurator');

        $values = [
            'passwordsetting_SystemGeneratedPasswordON' => (string)($password['SystemGeneratedPasswordON'] ?? '') === '1',
            'passwordsetting_systexpiration' => in_array((string)($password['systexpiration'] ?? ''), ['1', '2'], true) ? (string)$password['systexpiration'] : '0',
            'passwordsetting_systexpirationtime' => (string)($password['systexpirationtime'] ?? ''),
            'passwordsetting_systexpirationtype' => (string)($password['systexpirationtype'] ?? '1'),
            'passwordsetting_systexpirationlogin' => (string)($password['systexpirationlogin'] ?? ''),
            'passwordsetting_minpwdlength' => (string)($password['minpwdlength'] ?? ''),
            'passwordsetting_forgotpasswordON' => (string)($password['forgotpasswordON'] ?? '') === '1',
            'passwordsetting_linkexpiration' => (string)($password['linkexpiration'] ?? '') === '1' ? '1' : '0',
            'passwordsetting_linkexpirationtime' => (string)($password['linkexpirationtime'] ?? ''),
            'passwordsetting_linkexpirationtype' => (string)($password['linkexpirationtype'] ?? '1'),
            'captcha_on' => !empty($settings['captcha_on']),
            'captcha_public_key' => $this->decode((string)($settings['captcha_public_key'] ?? '')),
            'captcha_private_key' => '',
            'system_ldap_enabled' => !empty($settings['system_ldap_enabled']),
            'ldap_admin_password' => '',
            'ldap_enc_key' => '',
        ];
        foreach (self::RULES as $rule) {
            $values['passwordsetting_' . $rule] = !empty($password[$rule]);
        }
        foreach (array_keys(self::TEMPLATES) as $name) {
            $values['passwordsetting_' . $name] = (string)($password[$name] ?? '');
        }
        foreach (array_keys(self::LDAP_TEXTS) as $name) {
            $values[$name] = $this->decode((string)($settings[$name] ?? ''));
        }
        foreach (self::LDAP_FLAGS as $name) {
            $values[$name] = !empty($settings[$name]);
        }

        $expirationTime = ['field' => 'passwordsetting_systexpiration', 'values' => ['1']];
        $linkTime = ['field' => 'passwordsetting_linkexpiration', 'values' => ['1']];

        return [
            'title' => $this->text('LBL_MANAGE_PASSWORD_TITLE'),
            'sections' => [
                $this->section('LBL_PASSWORD_SYST_GENERATED_TITLE', [
                    ...$generatedFields,
                    $this->field('passwordsetting_systexpiration', 'LBL_PASSWORD_SYST_EXPIRATION', 'select', [
                        'options' => $this->options($options['systexpiration']),
                        'showIf' => [$generated],
                    ]),
                    $this->field('passwordsetting_systexpirationtime', 'LBL_PASSWORD_EXP_IN', 'number', ['max' => 999, 'required' => true, 'showIf' => [$generated, $expirationTime]]),
                    $this->field('passwordsetting_systexpirationtype', 'LBL_PASSWORD_EXP_IN', 'select', [
                        'options' => $this->options($options['systexpirationtype']),
                        'showIf' => [$generated, $expirationTime],
                    ]),
                    $this->field('passwordsetting_systexpirationlogin', 'LBL_PASSWORD_EXP_AFTER', 'number', [
                        'max' => 999,
                        'required' => true,
                        'suffix' => $this->text('LBL_PASSWORD_LOGINS'),
                        'showIf' => [$generated, ['field' => 'passwordsetting_systexpiration', 'values' => ['2']]],
                    ]),
                ]) + ['showIf' => [$ldap]],
                $this->section('LBL_PWDSEC_SETS', [
                    $this->field('passwordsetting_minpwdlength', 'LBL_PWDSEC_MIN_LENGTH', 'number', ['help' => 'LBL_PWDSEC_MIN_LENGTH_DESC']),
                    $this->field('passwordsetting_oneupper', 'LBL_PWDSEC_UPPERCASE', 'bool', ['help' => 'LBL_PWDSEC_UPPERCASE_DESC']),
                    $this->field('passwordsetting_onelower', 'LBL_PWDSEC_LOWERCASE', 'bool', ['help' => 'LBL_PWDSEC_LOWERCASE_DESC']),
                    $this->field('passwordsetting_onenumber', 'LBL_PWDSEC_NUMBERS', 'bool', ['help' => 'LBL_PWDSEC_NUMBERS_DESC']),
                    $this->field('passwordsetting_onespecial', 'LBL_PWDSEC_SPECCHAR', 'bool', ['help' => 'LBL_PWDSEC_SPECCHAR_DESC']),
                ]),
                $this->section('LBL_PASSWORD_USER_RESET', [
                    ...$forgotFields,
                    $this->field('passwordsetting_linkexpiration', 'LBL_PASSWORD_LINK_EXPIRATION', 'select', [
                        'options' => $this->options($options['linkexpiration']),
                        'help' => 'LBL_PASSWORD_LINK_EXPIRATION_HELP',
                        'showIf' => [$forgot],
                    ]),
                    $this->field('passwordsetting_linkexpirationtime', 'LBL_PASSWORD_LINK_EXP_IN', 'number', ['max' => 999, 'required' => true, 'showIf' => [$forgot, $linkTime]]),
                    $this->field('passwordsetting_linkexpirationtype', 'LBL_PASSWORD_LINK_EXP_IN', 'select', [
                        'options' => $this->options($options['linkexpirationtype']),
                        'showIf' => [$forgot, $linkTime],
                    ]),
                    $this->field('captcha_on', 'ENABLE_CAPTCHA', 'bool', ['help' => 'LBL_CAPTCHA_HELP_TEXT', 'wide' => true, 'showIf' => [$forgot]]),
                    $this->field('captcha_public_key', 'LBL_PUBLIC_KEY', 'text', ['showIf' => [$forgot, ['field' => 'captcha_on', 'values' => [true]]]]),
                    $this->field('captcha_private_key', 'LBL_PRIVATE_KEY', 'password', [
                        'set' => ($settings['captcha_private_key'] ?? '') !== '',
                        'showIf' => [$forgot, ['field' => 'captcha_on', 'values' => [true]]],
                    ]),
                ]) + ['showIf' => [$ldap]],
                $this->section('LBL_PASSWORD_TEMPLATE', $templates) + ['showIf' => [$ldap]],
                $this->section('LBL_LDAP_TITLE', $ldapFields, 'Administration', 'LBL_LDAP_LEGACY_WARNING_MORE'),
            ],
            'values' => $values,
        ];
    }

    /**
     * Saves as the legacy page: the password settings and rules in the config (reset while LDAP
     * is on), the authentication class emptied (no SAML option on the page). The settings (LDAP,
     * captcha) are saved too: Administration::saveConfig() reads them from $_GET only since
     * SuiteCRM 8, so the legacy page no longer saves them.
     */
    protected function saveValues(array $values): ?string
    {
        $options = $this->getOptions();
        $ldap = !empty($values['system_ldap_enabled']);

        $fields = [];
        foreach (['systexpirationtime', 'systexpirationlogin', 'linkexpirationtime', 'minpwdlength'] as $name) {
            $value = trim((string)($values['passwordsetting_' . $name] ?? ''));
            if ($value !== '' && !ctype_digit($value)) {
                return $this->text('LBL_VALIDATION_ERRORS', 'Administration', false);
            }
            $fields['passwordsetting_' . $name] = $value;
        }
        $fields['passwordsetting_SystemGeneratedPasswordON'] = !empty($values['passwordsetting_SystemGeneratedPasswordON']) ? '1' : '0';
        $fields['passwordsetting_forgotpasswordON'] = !empty($values['passwordsetting_forgotpasswordON']) ? '1' : '0';
        foreach (['systexpiration', 'systexpirationtype', 'linkexpiration', 'linkexpirationtype'] as $name) {
            $value = (string)($values['passwordsetting_' . $name] ?? '');
            $fields['passwordsetting_' . $name] = $this->isOption($value, $options[$name]) ? $value : (string)array_key_first($options[$name]);
        }
        // As the radios of the legacy page: the values of the other choices emptied, the value of
        // the choice required.
        if ($fields['passwordsetting_systexpiration'] !== '1') {
            $fields['passwordsetting_systexpirationtime'] = '';
        }
        if ($fields['passwordsetting_systexpiration'] !== '2') {
            $fields['passwordsetting_systexpirationlogin'] = '';
        }
        if ($fields['passwordsetting_linkexpiration'] !== '1') {
            $fields['passwordsetting_linkexpirationtime'] = '';
        }
        $generated = $fields['passwordsetting_SystemGeneratedPasswordON'] === '1';
        $forgot = $fields['passwordsetting_forgotpasswordON'] === '1';
        if (!$ldap && (($generated && $fields['passwordsetting_systexpiration'] === '1' && $fields['passwordsetting_systexpirationtime'] === '')
            || ($generated && $fields['passwordsetting_systexpiration'] === '2' && $fields['passwordsetting_systexpirationlogin'] === '')
            || ($forgot && $fields['passwordsetting_linkexpiration'] === '1' && $fields['passwordsetting_linkexpirationtime'] === ''))) {
            return $this->text('LBL_VALIDATION_ERRORS', 'Administration', false);
        }
        // The disabled fields are not posted by the legacy page: their values are kept.
        if (!$generated) {
            unset($fields['passwordsetting_systexpiration'], $fields['passwordsetting_systexpirationtime'],
                $fields['passwordsetting_systexpirationtype'], $fields['passwordsetting_systexpirationlogin']);
        }
        if (!$forgot) {
            unset($fields['passwordsetting_linkexpiration'], $fields['passwordsetting_linkexpirationtime'],
                $fields['passwordsetting_linkexpirationtype']);
        }
        foreach (array_keys(self::TEMPLATES) as $name) {
            $value = (string)($values['passwordsetting_' . $name] ?? '');
            $fields['passwordsetting_' . $name] = $this->isOption($value, $options['templates']) ? $value : '';
        }
        foreach (self::RULES as $rule) {
            $fields['passwordsetting_' . $rule] = !empty($values['passwordsetting_' . $rule]) ? '1' : '0';
        }
        if ($ldap) {
            foreach (self::CLEARED as $name => $value) {
                $fields['passwordsetting_' . $name] = $value;
            }
        }
        $fields['authenticationClass'] = '';

        $configurator = new \Configurator();
        $configurator->useAuthenticationClass = false;
        foreach (array_merge(self::RULES, ['minpwdlength']) as $name) {
            $configurator->config['passwordsetting'][$name] = $fields['passwordsetting_' . $name];
        }
        $this->withPost($fields, static fn () => $configurator->saveConfig());

        // Without the forgotten password feature, the legacy page turns the captcha off.
        $this->saveSettings($values, $ldap, $ldap || $forgot);
        $this->markCache('app-metadata-system-configs');

        return null;
    }

    /**
     * Saves the settings of the page (captcha, LDAP), as the legacy page posted them (encoded),
     * the passwords only when given or removed (false).
     *
     * @param array<string, mixed> $values
     */
    protected function saveSettings(array $values, bool $ldap, bool $captcha): void
    {
        $settings = [
            'system_ldap_enabled' => $ldap ? '1' : '0',
            'captcha_on' => $captcha && !empty($values['captcha_on']) ? '1' : '0',
            'captcha_public_key' => trim((string)($values['captcha_public_key'] ?? '')),
        ];
        foreach (array_keys(self::LDAP_TEXTS) as $name) {
            $settings[$name] = trim((string)($values[$name] ?? ''));
        }
        foreach (self::LDAP_FLAGS as $name) {
            $settings[$name] = !empty($values[$name]) ? '1' : '0';
        }
        foreach (self::SECRETS as $name) {
            $secret = $values[$name] ?? '';
            if ($secret === false || (string)$secret !== '') {
                $settings[$name] = $secret === false ? '' : (string)$secret;
            }
        }

        $admin = \BeanFactory::newBean('Administration');
        foreach ($this->encode($settings) as $name => $value) {
            [$category, $key] = explode('_', $name, 2);
            $admin->saveSetting($category, $key, $value);
        }
        $admin->retrieveSettings(false, true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getPasswordConfig(): array
    {
        global $sugar_config;

        return is_array($sugar_config['passwordsetting'] ?? null) ? $sugar_config['passwordsetting'] : [];
    }

    /**
     * The settings of the page (the passwords decrypted by retrieveSettings).
     *
     * @return array<string, mixed>
     */
    protected function getSettings(): array
    {
        $admin = \BeanFactory::newBean('Administration');
        $admin->retrieveSettings(false, true);

        return is_array($admin->settings) ? $admin->settings : [];
    }

    /**
     * Whether the system mailer has a server, else the legacy page warns that the emails of the
     * passwords cannot be sent.
     */
    protected function isSmtpServerSet(): bool
    {
        require_once 'include/SugarPHPMailer.php';
        $mail = new \SugarPHPMailer();
        $mail->setMailerForSystem();

        return !($mail->Mailer === 'smtp' && $mail->Host === '');
    }

    /**
     * The options of the selects, value => label.
     *
     * @return array<string, array<string|int, mixed>>
     */
    protected function getOptions(): array
    {
        return [
            'systexpiration' => [
                '0' => $this->text('LBL_UW_NONE'),
                '1' => $this->text('LBL_PASSWORD_EXP_IN'),
                '2' => $this->text('LBL_PASSWORD_EXP_AFTER'),
            ],
            'systexpirationtype' => ['1' => $this->text('LBL_DAYS'), '7' => $this->text('LBL_WEEKS'), '30' => $this->text('LBL_MONTHS')],
            'linkexpiration' => ['0' => $this->text('LBL_UW_NONE'), '1' => $this->text('LBL_PASSWORD_LINK_EXP_IN')],
            'linkexpirationtype' => ['1' => $this->text('LBL_MINUTES'), '60' => $this->text('LBL_HOURS'), '1440' => $this->text('LBL_DAYS')],
            'templates' => get_bean_select_array(true, 'EmailTemplate', 'name', '', 'name', true),
        ];
    }
}
