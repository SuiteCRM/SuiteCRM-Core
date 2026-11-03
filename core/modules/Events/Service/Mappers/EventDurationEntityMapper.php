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

namespace App\Module\Events\Service\Mappers;

use App\Data\Entity\Record;
use App\Data\Service\Record\EntityRecordMappers\EntityRecordFieldMapperInterface;
use App\FieldDefinitions\Entity\FieldDefinition;

/**
 * Duration of an event when it is retrieved: "2h 30m", from its duration in hours and
 * minutes, as the legacy detail view.
 */
class EventDurationEntityMapper implements EntityRecordFieldMapperInterface
{
    public const FIELD = 'duration';

    public function getField(): string
    {
        return self::FIELD;
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
        return ['retrieve'];
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
        $attributes = $record->getAttributes() ?? [];
        $hours = $attributes['duration_hours'] ?? '';
        $minutes = $attributes['duration_minutes'] ?? '';
        $attributes[self::FIELD] = $hours === '' && $minutes === '' ? '' : sprintf('%dh %dm', (int)$hours, (int)$minutes);
        $record->setAttributes($attributes);
    }
}
