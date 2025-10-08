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

    $awsS3ClientsEnv = $env['AWS_S3_INSTANCES'] ?? '{}';
    $awsS3Clients = json_decode($awsS3ClientsEnv, true, 512, JSON_THROW_ON_ERROR);

    if (!empty($awsS3Clients)) {

        foreach ($awsS3Clients as $name => $config) {
            if (empty($config)) {
                continue;
            }

            $version = $config['version'] ?? '2006-03-01';
            $region = $config['region'] ?? '';
            $accessKey = $config['access_key'] ?? '';
            $accessSecret = $config['access_secret'] ?? '';

            $containerConfig->services()
                            ->set("aws.s3.client.$name", 'Aws\S3\S3Client')
                            ->factory(['App\MediaObjects\DependencyInjection\AwsS3ClientFactory', 'create'])
                            ->args(
                                [
                                    '$accessKey' => $accessKey,
                                    '$accessSecret' => $accessSecret,
                                    '$region' => $region,
                                    '$version' => $version,
                                ]
                            )
                            ->public();
        }
    }


    $azureBlobClientsEnv = $env['AZURE_BLOB_INSTANCES'] ?? '{}';
    $azureBlobClients = json_decode($azureBlobClientsEnv, true, 512, JSON_THROW_ON_ERROR);

    if (!empty($azureBlobClients)) {

        foreach ($azureBlobClients as $name => $config) {
            if (empty($config) || empty($config['connection_string'])) {
                continue;
            }

            $containerConfig->services()
                            ->set("azure.blob.client.$name", 'AzureOss\Storage\Blob\BlobServiceClient')
                            ->factory(['\App\MediaObjects\DependencyInjection\AzureBlobClientFactory', 'create'])
                            ->args(
                                [
                                    '$connectionString' => $config['connection_string'],
                                ]
                            )
                            ->public();
        }
    }


    $dbDriver = $env['MEDIA_UPLOADER_DB_DRIVER'] ?? 'orm';

    if (empty($dbDriver) || !in_array($dbDriver, ['orm', 'mongodb', 'phpcr'])) {
        $dbDriver = 'orm';
    }

    $storage = $env['MEDIA_UPLOADER_STORAGE'] ?? 'flysystem';

    if (empty($storage) || !in_array($storage, ['file_system', 'flysystem', 'gaufrette'])) {
        $storage = 'flysystem';
    }

    $metadata = $env['MEDIA_UPLOADER_METADATA'] ?? '';

    $defaultMetadata = [
        'auto_detection' => true,
        'cache' => 'file',
        'type' => 'attribute',
    ];

    $decodedMetadata = $metadata ? json_decode($metadata, true, 512, JSON_THROW_ON_ERROR) : [];
    if (!is_array($decodedMetadata)) {
        $decodedMetadata = [];
    }
    $metadata = array_merge($defaultMetadata, $decodedMetadata);


    $mappings = $env['MEDIA_UPLOADER_MAPPINGS'] ?? '';
    $defaultMappings = [
        'archived_documents_media_object' => [
            'uri_prefix' => '/media/archived',
            'upload_destination' => 'archived.documents.storage',
            'namer' => 'App\MediaObjects\Services\UuidMediaObjectFileNamer',
            'directory_namer' => [
                'service' => 'Vich\UploaderBundle\Naming\CurrentDateTimeDirectoryNamer',
                'options' => [
                    'date_time_format' => 'Y/m',
                    'date_time_property' => 'dateEntered'
                ]
            ]
        ],
        'private_documents_media_object' => [
            'uri_prefix' => '/media/documents',
            'upload_destination' => 'private.documents.storage',
            'namer' => 'App\MediaObjects\Services\UuidMediaObjectFileNamer',
            'directory_namer' => [
                'service' => 'Vich\UploaderBundle\Naming\CurrentDateTimeDirectoryNamer',
                'options' => [
                    'date_time_format' => 'Y/m',
                    'date_time_property' => 'dateEntered'
                ]
            ]
        ],
        'private_images_media_object' => [
            'uri_prefix' => '/media/images',
            'upload_destination' => 'private.images.storage',
            'namer' => 'App\MediaObjects\Services\UuidMediaObjectFileNamer',
            'directory_namer' => [
                'service' => 'Vich\UploaderBundle\Naming\CurrentDateTimeDirectoryNamer',
                'options' => [
                    'date_time_format' => 'Y/m',
                    'date_time_property' => 'dateEntered'
                ]
            ]
        ],
        'public_images_media_object' => [
            'uri_prefix' => '/media-upload/images',
            'upload_destination' => 'public.images.storage',
            'namer' => 'Vich\UploaderBundle\Naming\SmartUniqueNamer',
            'directory_namer' => [
                'service' => 'Vich\UploaderBundle\Naming\CurrentDateTimeDirectoryNamer',
                'options' => [
                    'date_time_format' => 'Y/m',
                    'date_time_property' => 'dateEntered'
                ]
            ]
        ],
        'public_documents_media_object' => [
            'uri_prefix' => '/media-upload/documents',
            'upload_destination' => 'public.documents.storage',
            'namer' => 'Vich\UploaderBundle\Naming\SmartUniqueNamer',
            'directory_namer' => [
                'service' => 'Vich\UploaderBundle\Naming\CurrentDateTimeDirectoryNamer',
                'options' => [
                    'date_time_format' => 'Y/m',
                    'date_time_property' => 'dateEntered'
                ]
            ]
        ],
    ];

    $decodedMappings = $mappings ? json_decode($mappings, true, 512, JSON_THROW_ON_ERROR) : [];
    if (!is_array($decodedMappings)) {
        $decodedMappings = [];
    }

    $mappings = array_merge($defaultMappings, $decodedMappings);


    $containerConfig->extension(
        'vich_uploader',
        [
            'db_driver' => $dbDriver,
            'storage' => $storage,
            'metadata' => $metadata,
            'mappings' => $mappings,
            'use_flysystem_to_resolve_uri' => true
        ]
    );

};
