<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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
    $storages = $env['MEDIA_FLY_SYSTEM_STORAGES'] ?? '';

    $defaultStorages = [
        'private.documents.storage' => [
            'adapter' => 'local',
            'options' => [
                'directory' => '%kernel.project_dir%/uploads/documents',
            ],
        ],
        'archived.documents.storage' => [
            'adapter' => 'local',
            'options' => [
                'directory' => '%kernel.project_dir%/uploads/archived',
            ],
        ],
        'private.images.storage' => [
            'adapter' => 'local',
            'options' => [
                'directory' => '%kernel.project_dir%/uploads/images',
            ],
        ],
        'public.images.storage' => [
            'adapter' => 'local',
            'options' => [
                'directory' => '%kernel.project_dir%/public/media-upload/images',
            ],
        ],
        'public.documents.storage' => [
            'adapter' => 'local',
            'options' => [
                'directory' => '%kernel.project_dir%/public/media-upload/documents',
            ],
        ],
    ];

    $decodedStorages = $storages ? json_decode($storages, true, 512, JSON_THROW_ON_ERROR) : [];
    if (!is_array($decodedStorages)) {
        $decodedStorages = [];
    }

    $storages = array_merge($defaultStorages, $decodedStorages);

    $containerConfig->extension(
        'flysystem',
        [
            'storages' => $storages
        ]
    );

};
