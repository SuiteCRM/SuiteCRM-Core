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

$mediaObjectFields = [
    'file_path' => [
        'name' => 'file_path',
        'type' => 'varchar',
        'len' => 255,
        'required' => true,
    ],
    'size' => [
        'name' => 'size',
        'type' => 'int',
        'required' => true,
    ],
    'mime_type' => [
        'name' => 'mime_type',
        'type' => 'varchar',
        'len' => 255,
    ],
    'original_name' => [
        'name' => 'original_name',
        'type' => 'varchar',
        'len' => 255,
    ],
    'dimensions' => [
        'name' => 'dimensions',
        'type' => 'varchar',
        'len' => 50,
    ],
    'parent_type' => [
        'name' => 'parent_type',
        'type' => 'varchar',
        'len' => 100,
    ],
    'parent_id' => [
        'name' => 'parent_id',
        'type' => 'id',
    ],
    'parent_field' => [
        'name' => 'parent_field',
        'type' => 'varchar',
        'len' => 100,
    ],
    'temporary' => [
        'name' => 'temporary',
        'type' => 'bool',
        'vname' => 'LBL_TEMPORARY',
        'default' => '1',
        'reportable' => false,
    ],
];

$dictionary['ArchivedDocumentMediaObject'] = [
    'table' => 'archived_documents_media_objects',
    'audited' => false,
    'inline_edit' => false,
    'duplicate_merge' => false,
    'fields' => array_merge(
        $mediaObjectFields,
        []
    ),
    'relationships' => [
    ],
    'optimistic_locking' => true,
    'unified_search' => false,
];

$dictionary['PrivateDocumentMediaObject'] = [
    'table' => 'private_documents_media_objects',
    'audited' => false,
    'inline_edit' => false,
    'duplicate_merge' => false,
    'fields' => array_merge(
        $mediaObjectFields,
        []
    ),
    'relationships' => [
    ],
    'optimistic_locking' => true,
    'unified_search' => false,
];

$dictionary['PrivateImageMediaObject'] = [
    'table' => 'private_images_media_objects',
    'audited' => false,
    'inline_edit' => false,
    'duplicate_merge' => false,
    'fields' => array_merge(
        $mediaObjectFields,
        []
    ),
    'relationships' => [
    ],
    'optimistic_locking' => true,
    'unified_search' => false,
];

$dictionary['PublicDocumentMediaObject'] = [
    'table' => 'public_documents_media_objects',
    'audited' => false,
    'inline_edit' => false,
    'duplicate_merge' => false,
    'fields' => array_merge(
        $mediaObjectFields,
        []
    ),
    'relationships' => [
    ],
    'optimistic_locking' => true,
    'unified_search' => false,
];

$dictionary['PublicImageMediaObject'] = [
    'table' => 'public_images_media_objects',
    'audited' => false,
    'inline_edit' => false,
    'duplicate_merge' => false,
    'fields' => array_merge(
        $mediaObjectFields,
        []
    ),
    'relationships' => [
    ],
    'optimistic_locking' => true,
    'unified_search' => false,
];

if (!class_exists('VardefManager')) {
    require_once 'include/SugarObjects/VardefManager.php';
}

VardefManager::createVardef('MediaObjects', 'ArchivedDocumentMediaObject', ['basic', 'assignable', 'security_groups']);
VardefManager::createVardef('MediaObjects', 'PrivateDocumentMediaObject', ['basic', 'assignable', 'security_groups']);
VardefManager::createVardef('MediaObjects', 'PublicDocumentMediaObject', ['basic', 'assignable', 'security_groups']);
VardefManager::createVardef('MediaObjects', 'PrivateImageMediaObject', ['basic', 'assignable', 'security_groups']);
VardefManager::createVardef('MediaObjects', 'PublicImageMediaObject', ['basic', 'assignable', 'security_groups']);
