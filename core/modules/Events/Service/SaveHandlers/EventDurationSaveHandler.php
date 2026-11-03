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

namespace App\Module\Events\Service\SaveHandlers;

use App\Data\Entity\Record;
use App\Data\Service\Record\RecordSaveHandlers\RecordFieldSaveHandlerInterface;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\Module\Events\Service\Mappers\EventDurationEntityMapper;

/**
 * Duration in hours and minutes of an event saved from the record view:
 * from its start and end, as the legacy edit view calculated it (duration_dependency.js). The
 * duration shown (non-db) is not saved.
 */
class EventDurationSaveHandler implements RecordFieldSaveHandlerInterface
{
    public function getField(): string
    {
        return EventDurationEntityMapper::FIELD;
    }

    public function getKey(): string
    {
        return 'event-duration';
    }

    public function getModule(): string
    {
        return 'events';
    }

    public function getModes(): array
    {
        return ['before-save'];
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
        $attributes = $inputRecord->getAttributes() ?? [];
        unset($attributes[$this->getField()]);

        $start = strtotime((string)($attributes['date_start'] ?? ''));
        $end = strtotime((string)($attributes['date_end'] ?? ''));
        if ($start !== false && $end !== false && $end >= $start) {
            $minutes = intdiv($end - $start, 60);
            $attributes['duration_hours'] = intdiv($minutes, 60);
            $attributes['duration_minutes'] = $minutes % 60;
        }

        $inputRecord->setAttributes($attributes);
    }
}
