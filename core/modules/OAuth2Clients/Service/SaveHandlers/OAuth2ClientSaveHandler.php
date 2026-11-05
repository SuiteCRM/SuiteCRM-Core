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

namespace App\Module\OAuth2Clients\Service\SaveHandlers;

use App\Data\Entity\Record;
use App\Data\Service\Record\RecordSaveHandlers\RecordSaveHandlerInterface;
use App\FieldDefinitions\Entity\FieldDefinition;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Save of an OAuth2 client from the record view, as the legacy edit views of the grant types:
 * admins only, the new secret given to the bean as the legacy form posts it (the bean hashes
 * $_REQUEST['new_secret'] on save), and the grant type of a client kept (the legacy form of a
 * client posts its type).
 */
class OAuth2ClientSaveHandler implements RecordSaveHandlerInterface
{
    public function __construct(protected Security $security)
    {
    }

    public function getKey(): string
    {
        return 'oauth2-client-save';
    }

    public function getModule(): string
    {
        return 'oauth2-clients';
    }

    public function getModes(): array
    {
        return ['before-save', 'after-save'];
    }

    public function getOrder(): int
    {
        return 0;
    }

    public function run(?Record $previousVersion, Record $inputRecord, ?Record $savedRecord, FieldDefinition $fieldDefinition): void
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('The OAuth2 clients are for the admins');
        }

        // After the save: the request of the legacy form is no longer needed.
        if ($savedRecord !== null) {
            unset($_REQUEST['new_secret']);

            return;
        }

        $attributes = $inputRecord->getAttributes() ?? [];
        $secret = (string)($attributes['new_secret'] ?? '');
        unset($attributes['new_secret'], $attributes['secret']);
        if ($secret !== '') {
            $_REQUEST['new_secret'] = $secret;
        } else {
            unset($_REQUEST['new_secret']);
        }

        $previous = $previousVersion !== null ? ($previousVersion->getAttributes() ?? []) : [];
        if (!empty($previous['id']) && isset($previous['allowed_grant_type'])) {
            $attributes['allowed_grant_type'] = $previous['allowed_grant_type'];
        }

        $inputRecord->setAttributes($attributes);
    }
}
