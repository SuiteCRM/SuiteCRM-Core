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

namespace App\Module\OAuth2Clients\ViewDefinitions;

use App\ViewDefinitions\LegacyHandler\ViewConfigMapperInterface;

/**
 * Layout of the OAuth2 clients in the record, create and edit views: metadata/recordviewdefs.php,
 * one layout for the grant types. The legacy view of the module picks the layout of a grant type
 * (the password one for a new client), which the record view would take otherwise.
 */
class OAuth2ClientViewConfigMapper implements ViewConfigMapperInterface
{
    protected const LAYOUT_FILES = [
        'custom/modules/OAuth2Clients/metadata/recordviewdefs.php',
        'modules/OAuth2Clients/metadata/recordviewdefs.php',
    ];

    public function getKey(): string
    {
        return 'oauth2-client-record-view';
    }

    public function getView(): string
    {
        return 'record';
    }

    public function getModule(): string
    {
        return 'oauth2-clients';
    }

    public function map(array $viewDefs): array
    {
        // Run in the legacy app (its directory), as the legacy view loads its layout.
        foreach (self::LAYOUT_FILES as $file) {
            if (!is_file($file)) {
                continue;
            }

            $viewdefs = [];
            include $file;

            return $viewdefs['OAuth2Clients']['DetailView'] ?? $viewDefs;
        }

        return $viewDefs;
    }
}
