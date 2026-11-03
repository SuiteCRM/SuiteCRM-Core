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

namespace App\Module\ScheduledReports\Service\Mappers;

use App\Data\Entity\Record;
use App\Data\Service\Record\ApiRecordMappers\ApiRecordFieldMapperInterface;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\Module\ScheduledReports\LegacyHandler\ScheduledReportRecipientsHandler;

/**
 * Recipients of a scheduled report sent back after a save: the list of the stored
 * recipients, once RecipientsSaveHandler stored them.
 */
class RecipientsApiMapper implements ApiRecordFieldMapperInterface
{
    public function __construct(protected ScheduledReportRecipientsHandler $recipients)
    {
    }

    public function getField(): string
    {
        return RecipientsEntityMapper::FIELD;
    }

    public function getKey(): string
    {
        return ScheduledReportRecipientsHandler::HANDLER_KEY;
    }

    public function getModule(): string
    {
        return 'scheduled-reports';
    }

    public function getModes(): array
    {
        return ['save'];
    }

    public function getOrder(): int
    {
        return 0;
    }

    public function replaceDefaultTypeMapper(): bool
    {
        return false;
    }

    public function toInternal(Record $record, FieldDefinition $fieldDefinitions): void
    {
    }

    public function toExternal(Record $record, FieldDefinition $fieldDefinitions): void
    {
        $id = (string)($record->getId() ?? $record->getAttributes()['id'] ?? '');
        if ($id === '') {
            return;
        }

        $attributes = $record->getAttributes() ?? [];
        $attributes[RecipientsEntityMapper::FIELD] = $this->recipients->toList($this->recipients->load($id));
        $record->setAttributes($attributes);
    }
}
