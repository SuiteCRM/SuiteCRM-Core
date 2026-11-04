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

namespace App\Module\OAuth2Clients\Service\Mappers;

use App\Data\Entity\Record;
use App\Data\Service\Record\EntityRecordMappers\EntityRecordMapperInterface;
use App\FieldDefinitions\Entity\FieldDefinition;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * The OAuth2 clients are for the admins, as in the legacy pages (OAuth2ClientsController): the
 * bean has no ACL, so the records are refused to the other users. The hash of the secret is not
 * sent to the page (the legacy pages do not show it): it is changed with new_secret.
 */
class OAuth2ClientAccessMapper implements EntityRecordMapperInterface
{
    public function __construct(protected Security $security)
    {
    }

    public function getKey(): string
    {
        return 'oauth2-client-access';
    }

    public function getModule(): string
    {
        return 'oauth2-clients';
    }

    public function getModes(): array
    {
        return ['retrieve', 'list', 'save'];
    }

    public function getOrder(): int
    {
        return 0;
    }

    public function toInternal(Record $record, FieldDefinition $fieldDefinitions): void
    {
    }

    public function toExternal(Record $record, FieldDefinition $fieldDefinitions): void
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('The OAuth2 clients are for the admins');
        }

        $attributes = $record->getAttributes() ?? [];
        unset($attributes['secret']);
        $record->setAttributes($attributes);
    }
}
