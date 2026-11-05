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

namespace App\Module\Emails\Service\Mappers;

use App\Data\Entity\Record;
use App\Data\Service\Record\EntityRecordMappers\EntityRecordMapperInterface;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\Module\Emails\LegacyHandler\EmailViewHandler;

/**
 * Sender, recipients and body of the emails of the list and record views (email_from,
 * email_to, email_cc, email_bcc, email_body; see EmailViewHandler).
 */
class EmailViewEntityMapper implements EntityRecordMapperInterface
{
    public function __construct(protected EmailViewHandler $handler)
    {
    }

    public function getKey(): string
    {
        return 'email-view';
    }

    public function getModule(): string
    {
        return 'emails';
    }

    public function getModes(): array
    {
        return ['list', 'retrieve'];
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
        $attributes = $record->getAttributes() ?? [];
        $emailId = (string)($record->getId() ?? $attributes['id'] ?? '');
        if ($emailId === '') {
            return;
        }

        $record->setAttributes(array_merge($attributes, $this->handler->getTexts($emailId)));
    }
}
