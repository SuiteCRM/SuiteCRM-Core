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

namespace App\Module\ScheduledReports\Service\SaveHandlers;

use App\Data\Entity\Record;
use App\Data\Service\Record\RecordSaveHandlers\RecordFieldSaveHandlerInterface;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\Module\ScheduledReports\LegacyHandler\ScheduledReportRecipientsHandler;
use App\Module\ScheduledReports\Service\Mappers\RecipientsEntityMapper;

/**
 * Saves the recipients of the record view of a scheduled report: the legacy bean takes them from the edit view post only (AOR_Scheduled_Reports::parseRecipients,
 * which leaves the stored value as it is otherwise: the update skips null fields). Before the
 * record save, the list is taken out of the record; after it, it is stored, empty included
 * (ScheduledReportRecipientsHandler). Saves without the field (inline edit...) leave them as they are.
 */
class RecipientsSaveHandler implements RecordFieldSaveHandlerInterface
{
    /**
     * Stored value of the record being saved, false when it has no recipients value.
     */
    protected string|null|false $pending = false;

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
        return ['before-save', 'after-save'];
    }

    public function getOrder(): int
    {
        return 0;
    }

    public function replaceDefaultTypeMapper(): bool
    {
        return false;
    }

    public function run(?Record $previousVersion, Record $inputRecord, ?Record $savedRecord, FieldDefinition $fieldDefinition): void
    {
        if ($savedRecord === null) {
            $this->pending = false;
            $attributes = $inputRecord->getAttributes() ?? [];
            if (!array_key_exists($this->getField(), $attributes)) {
                return;
            }

            $value = $attributes[$this->getField()];
            unset($attributes[$this->getField()]);
            $inputRecord->setAttributes($attributes);
            if (is_array($value)) {
                $this->pending = $this->recipients->toStored($value);
            }

            return;
        }

        if ($this->pending === false || empty($savedRecord->getId())) {
            return;
        }

        $this->recipients->store((string)$savedRecord->getId(), $this->pending);
        $this->pending = false;
    }
}
