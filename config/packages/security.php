<?php
/**
 * SuiteCRM is a customer relationship management program developed by SalesAgility Ltd.
 * Copyright (C) 2022 SalesAgility Ltd.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Module\Users\Entity\User;
use App\Security\Ldap\AppLdapUserProvider;
use App\Security\Saml\AppSamlAuthenticator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Security\Core\User\InMemoryUserChecker;
use Symfony\Component\Security\Http\RateLimiter\DefaultLoginRateLimiter;

return static function (ContainerConfigurator $containerConfig) {

    $env = $_ENV ?? [];
    $authType = $env['AUTH_TYPE'] ?? 'native';

    $maxAttempts = (int)($env['LOGIN_THROTTLING_MAX_ATTEMPTS'] ?? 5);
    $ipLoginMaxAttempts = (int)($env['LOGIN_THROTTLING_IP_LOGIN_MAX_ATTEMPTS'] ?? 50);
    $loginThrottlingInterval = (string)($env['LOGIN_THROTTLING_INTERVAL'] ?? '30 minutes');

    $containerConfig->extension('framework', [
        'rate_limiter' => [
            // define 2 rate limiters (one for username+IP, the other for IP)
            'username_ip_login' => [
                'policy' => 'token_bucket',
                'limit' => $maxAttempts,
                'rate' => [ 'interval' => $loginThrottlingInterval ],
            ],
            'ip_login' => [
                'policy' => 'sliding_window',
                'limit' => $ipLoginMaxAttempts,
                'interval' => $loginThrottlingInterval,
            ],
        ],
    ]);

    $containerConfig->services()->set('app.login_rate_limiter')
        ->class(DefaultLoginRateLimiter::class)
        ->args(
            [
                // 1st argument is the limiter for IP
                new Reference('limiter.ip_login'),
                // 2nd argument is the limiter for username+IP
                new Reference('limiter.username_ip_login'),
                // 3rd argument is the app secret
                param('kernel.secret'),
            ]
        );

    $baseFirewall = [
        'dev' => [
            'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
            'user_checker' => InMemoryUserChecker::class,
            'security' => false
        ],
        'main' => [
            'lazy' => false,
        ]
    ];

    //Note: Only the *first* access control that matches will be used
    $baseAccessControl = [
        ['path' => '^/logged-out', 'roles' => 'PUBLIC_ACCESS'],
        ['path' => '^/logout$', 'roles' => 'PUBLIC_ACCESS'],
        ['path' => '^/login$', 'roles' => 'PUBLIC_ACCESS'],
        ['path' => '^/session-status$', 'roles' => 'PUBLIC_ACCESS'],
        ['path' => '^/2fa/enable-finalize', 'roles' => 'ROLE_USER'],
        ['path' => '^/2fa/check', 'roles' => 'IS_AUTHENTICATED_2FA_IN_PROGRESS'],
        ['path' => '^/2fa/enable', 'roles' => 'ROLE_USER'],
        ['path' => '^/2fa/disable', 'roles' => 'ROLE_USER'],
        ['path' => '^/$', 'roles' => 'PUBLIC_ACCESS'],
        ['path' => '^/api', 'roles' => 'PUBLIC_ACCESS'],
        ['path' => '^/api/graphql', 'roles' => 'PUBLIC_ACCESS'],
        ['path' => '^/api/graphql/graphiql*', 'roles' => 'PUBLIC_ACCESS'],
        ['path' => '^/ep', 'roles' => 'PUBLIC_ACCESS'],
        ['path' => '^/', 'roles' => 'PUBLIC_ACCESS']
    ];


    $appEnv = $env['APP_ENV'] ?? 'prod';
    $showDocs = $env['GRAPHQL_SHOW_DOCS'] ?? ($appEnv === 'dev');
    if ($showDocs === 'false' || $showDocs === false) {
        $baseAccessControl = array_merge([['path' => '^/docs', 'roles' => 'NO_ACCESS']], $baseAccessControl);
    }

    $containerConfig->parameters()->set('auth.logout.redirect', false);
    $containerConfig->parameters()->set('auth.logout.path', 'logout');
    $containerConfig->parameters()->set('auth.logout.after_logout_path', './');

    $containerConfig->parameters()->set('auth.session-expired.redirect', false);
    $containerConfig->parameters()->set('auth.session-expired.path', 'Login');

    if ($authType === 'native') {
        $containerConfig->extension('security', [
            'firewalls' => array_merge_recursive($baseFirewall, [
                'main' => [
                    'stateless' => false,
                    'json_login' => [
                        'check_path' => 'app_login',
                        'success_handler' => 'api_success_handler'
                    ],
                    'login_throttling' => [
                        'limiter' => 'app.login_rate_limiter'
                    ],
                    'logout' => [
                        'path' => 'app_logout',
                    ],
                    'two_factor' => [
                        'check_path' => 'app_2fa_check',
                        'prepare_on_login' => true,
                        'prepare_on_access_denied' => true,
                        'auth_code_parameter_name' => '_auth_code',
                        'default_target_path' => '/',
                        'provider' => 'app_user_provider',
                        'authentication_required_handler' => '2fa_required',
                        'success_handler' => '2fa_success',
                        'failure_handler' => '2fa_failed'
                    ]
                ],
            ]),
            'access_control' => $baseAccessControl
        ]);

        return;
    }

    if ($authType === 'ldap') {

        $baseLdapConfig = [
            'check_path' => 'app_login',
            'service' => Ldap::class,
            'provider' => 'app_user_provider'
        ];

        $baseLdapConfigEntries = [
            'dn_string' => 'LDAP_DN_STRING',
            'query_string' => 'LDAP_QUERY_STRING',
            'search_dn' => 'LDAP_SEARCH_DN',
            'search_password' => 'LDAP_SEARCH_PASSWORD',
        ];

        foreach ($baseLdapConfigEntries as $configKey => $envKey) {
            if (!empty($env[$envKey])) {
                $baseLdapConfig[$configKey] = "%env($envKey)%";
            }
        }

        $ldapAutoCreate = $env['LDAP_AUTO_CREATE'] ?? 'disabled';

        if ($ldapAutoCreate === 'enabled') {

            $baseLdapConfig['provider'] = 'ldap_auto_create_provider';

            $ldapUsersConfig = [
                'service' => Ldap::class,
                'default_roles' => '%env(LDAP_PROVIDER_DEFAULT_ROLES)%',
                'extra_fields' => '%ldap.extra_fields%',
            ];

            $autoCreateEnvEntries = [
                'base_dn' => 'LDAP_PROVIDER_BASE_DN',
                'search_dn' => 'LDAP_PROVIDER_SEARCH_DN',
                'search_password' => 'LDAP_PROVIDER_SEARCH_PASSWORD',
                'uid_key' => 'LDAP_PROVIDER_UID_KEY',
                'filter' => 'LDAP_PROVIDER_FILTER'
            ];

            foreach ($autoCreateEnvEntries as $configKey => $envKey) {
                if (!empty($env[$envKey])) {
                    $ldapUsersConfig[$configKey] = "%env($envKey)%";
                }
            }

            $autoCreateConfig = [
                'providers' => [
                    'app_user_provider' => [
                        'entity' => [
                            'class' => User::class
                        ]
                    ],
                    'ldap_auto_create_provider' => [
                        'id' => AppLdapUserProvider::class
                    ],

                    'ldap_users' => [
                        'ldap' => $ldapUsersConfig
                    ],
                ],
            ];

            $containerConfig->extension('security', $autoCreateConfig);
        }

        $containerConfig->extension('security', [
            'firewalls' => array_merge_recursive($baseFirewall, [
                'main' => [
                    'json_login_ldap' => $baseLdapConfig,
                    'provider' => $baseLdapConfig['provider'],
                    'login_throttling' => [
                        'limiter' => 'app.login_rate_limiter',
                    ],
                    'logout' => [
                        'path' => 'app_logout'
                    ],
                    'two_factor' => [
                        'check_path' => 'app_2fa_check',
                        'prepare_on_login' => true,
                        'prepare_on_access_denied' => true,
                        'auth_code_parameter_name' => '_auth_code',
                        'default_target_path' => '/',
                        'provider' => 'app_user_provider',
                        'authentication_required_handler' => '2fa_required',
                        'success_handler' => '2fa_success',
                        'failure_handler' => '2fa_failed'
                    ]
                ],
            ]),
            'access_control' => $baseAccessControl
        ]);
    }

    if ($authType === 'saml') {

        $samlAutoCreate = $env['SAML_AUTO_CREATE'] ?? 'disabled';

        $samlMainFirewallConfig = [
            'context' => 'app_context',
            'pattern' => '^/(?!auth|logged-out)',
            'custom_authenticators' => [
                'app.saml.authenticator',
            ],
            'entry_point' => 'app.saml.authenticator',
            'saml' => [
                'provider' => 'app_user_provider',
                // Match SAML attribute 'uid' with username.
                // Uses getNameId() method by default.
                'identifier_attribute' => '%env(SAML_USERNAME_ATTRIBUTE)%',
                'use_attribute_friendly_name' => '%env(bool:SAML_USE_ATTRIBUTE_FRIENDLY_NAME)%',
                // Use the attribute's friendlyName instead of the name
                'check_path' => 'saml_acs',
                'login_path' => 'saml_login',
                'failure_path' => 'logged-out',
                'always_use_default_target_path' => true
            ],
            'logout' => [
                'path' => 'saml_logout'
            ]
        ];

        if ($samlAutoCreate === 'enabled') {
            $samlMainFirewallConfig['saml']['user_factory'] = 'saml_user_factory';
        }

        $samlAccessControl = [
            ['path' => '^/login$', 'roles' => 'PUBLIC_ACCESS'],
            ['path' => '^/session-status$', 'roles' => 'PUBLIC_ACCESS'],
            ['path' => '^/logout$', 'roles' => 'PUBLIC_ACCESS'],
            ['path' => '^/auth/2fa_check', 'roles' => 'IS_AUTHENTICATED_2FA_IN_PROGRESS'],
            ['path' => '^/saml/login', 'roles' => 'PUBLIC_ACCESS'],
            ['path' => '^/saml/metadata', 'roles' => 'PUBLIC_ACCESS'],
            ['path' => '^/saml/acs', 'roles' => 'PUBLIC_ACCESS'],
            ['path' => '^/saml/logout', 'roles' => 'PUBLIC_ACCESS'],
            ['path' => '^/logged-out', 'roles' => 'PUBLIC_ACCESS'],
            ['path' => '^/auth', 'roles' => 'PUBLIC_ACCESS'],
            ['path' => '^/auth/login', 'roles' => 'PUBLIC_ACCESS'],
            ['path' => '^/auth/session-status', 'roles' => 'PUBLIC_ACCESS'],
            ['path' => '^/auth/logout', 'roles' => 'PUBLIC_ACCESS'],
            ['path' => '^/$', 'roles' => 'ROLE_USER'],
            ['path' => '^/media', 'roles' => 'ROLE_USER'],
            ['path' => '^/api', 'roles' => 'PUBLIC_ACCESS'],
            ['path' => '^/api/graphql', 'roles' => 'PUBLIC_ACCESS'],
            ['path' => '^/api/graphql/graphiql*', 'roles' => 'PUBLIC_ACCESS'],
            ['path' => '^/', 'roles' => 'PUBLIC_ACCESS']
        ];

        if (!$showDocs) {
            $samlAccessControl = array_merge([['path' => '^/docs', 'roles' => 'NO_ACCESS']], $samlAccessControl);
        }

        $containerConfig->extension('security', [
            'providers' => [
                'app_user_provider' => [
                    'entity' => [
                        'class' => User::class
                    ]
                ],
                'saml_provider' => [
                    'saml' => [
                        'user_class' => User::class,
                        'default_roles' => ['ROLE_USER']
                    ]
                ],
            ],
            'firewalls' => array_merge_recursive($baseFirewall, [
                'main' => $samlMainFirewallConfig,
                'auth' => [
                    'context' => 'app_context',
                    'pattern' => '^/auth',
                    'lazy' => false,
                    'provider' => 'app_user_provider',
                    'json_login' => [
                        'provider' => 'app_user_provider',
                        'check_path' => 'native_auth_login',
                    ],
                    'login_throttling' => [
                        'limiter' => 'app.login_rate_limiter',
                    ],
                    'logout' => [
                        'path' => 'logged-out'
                    ],
                    'two_factor' => [
                        'check_path' => 'native_auth_2fa_check',
                        'prepare_on_login' => true,
                        'prepare_on_access_denied' => true,
                        'auth_code_parameter_name' => '_auth_code',
                        'default_target_path' => '/',
                        'provider' => 'app_user_provider',
                        'authentication_required_handler' => '2fa_required',
                        'success_handler' => '2fa_success',
                        'failure_handler' => '2fa_failed'
                    ]
                ],
                'logged-out' => [
                    'context' => 'app_context',
                    'pattern' => '^/logged-out',
                    'lazy' => false,
                    'provider' => 'app_user_provider',
                    'json_login' => [
                        'provider' => 'app_user_provider',
                        'check_path' => 'native_auth_login',
                    ],
                    'login_throttling' => [
                        'limiter' => 'app.login_rate_limiter',
                    ],
                    'logout' => [
                        'path' => 'native_auth_logout'
                    ]
                ],
            ]),
            'access_control' => $samlAccessControl
        ]);


        $containerConfig->parameters()->set('auth.logout.redirect', true);
        $containerConfig->parameters()->set('auth.logout.path', 'saml/logout');
        $containerConfig->parameters()->set('auth.logout.after_logout_path', './auth#logged-out');

        $containerConfig->parameters()->set('auth.session-expired.redirect', true);
        $containerConfig->parameters()->set('auth.session-expired.path', 'logged-out');

        $services = $containerConfig->services();

        $services->set('app.saml.authenticator')
            ->class(AppSamlAuthenticator::class)
            ->parent('security.authenticator.saml.main');
    }

};

