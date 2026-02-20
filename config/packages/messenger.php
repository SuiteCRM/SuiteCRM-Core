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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $containerConfig) {
    $env = $_ENV ?? [];

    $transportsEnv = $env['MESSENGER_TRANSPORTS'] ?? '{}';
    $transports = json_decode($transportsEnv, true, 512, JSON_THROW_ON_ERROR);

    $defaultTransports = [
        'internal-async' => [
            'dsn' => '%env(MESSENGER_INTERNAL_ASYNC_TRANSPORT_DSN)%',
            'options' => []
        ],
        'failed' => [
            'dsn' => '%env(MESSENGER_INTERNAL_FAILURE_TRANSPORT_DSN)%',
            'options' => []
        ]
    ];

    $transportsConfig = [];
    if (!empty($transports)) {
        foreach ($transports as $name => $config) {
            if (empty($config)) {
                continue;
            }

            $dsn = '';
            $options = [];
            if (is_string($config)) {
                $dsn = $config;
            } else if (is_array($config)) {
                $dsn = $config['dsn'] ?? '';
                $options = $config['options'] ?? [];
                $serializer = $config['serializer'] ?? [];
            }

            if (empty($dsn)) {
                continue;
            }

            $transportsConfig[$name] = [
                'dsn' => $dsn,
                'options' => $options,
                'serializer' => $serializer
            ];
        }
    }

    $transportsConfig = array_merge($defaultTransports, $transportsConfig);


    $routingEnv = $env['MESSENGER_ROUTING'] ?? '{}';
    $routing = json_decode($routingEnv, true, 512, JSON_THROW_ON_ERROR);

    $defaultRouting = [
        'App\AsyncTask\Message\AsyncTaskRun' => 'internal-async',
        'App\AsyncTask\Message\AsyncTaskCompleted' => 'internal-async',
        'App\AsyncTask\Message\AsyncTaskProgressed' => 'internal-async',
        'App\AsyncTask\Message\AsyncTaskFailure' => 'internal-async'
    ];

    $routingConfig = [];

    if (!empty($routing)) {
        foreach ($routing as $messageClass => $routeConfig) {
            if (is_string($routeConfig)) {
                $routingConfig[$messageClass] = $routeConfig;
            }
        }
    }

    $routingConfig = array_merge($defaultRouting, $routingConfig);

    $serializerEnv = $env['MESSENGER_SERIALIZER'] ?? '{}';
    $serializerOverrides = json_decode($serializerEnv, true, 512, JSON_THROW_ON_ERROR);

    $defaultSerializer = [
        'default_serializer' => 'messenger.transport.symfony_serializer',
        'symfony_serializer' => [
            'format' => 'json',
            'context' => [],
        ],
    ];

    $serializerConfig = array_merge($defaultSerializer, $serializerOverrides);

    $containerConfig->extension(
        'framework',
        [
            'messenger' => [
                'failure_transport' => 'failed',
                'serializer' => $serializerConfig,
                'transports' => $transportsConfig,
                'routing' => $routingConfig
            ]
        ],
    );

};
