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

namespace App\Schedulers\Service\Fields;

use App\Authentication\LegacyHandler\UserHandler;
use App\Data\Entity\Record;
use App\Data\Service\Record\ApiRecordMappers\ApiRecordFieldMapperInterface;
use App\Data\Service\Record\ApiRecordMappers\ApiRecordFieldTypeMapperInterface;
use App\DateTime\LegacyHandler\DateTimeHandler;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\Languages\LegacyHandler\ModStringsHandler;

#[Autoconfigure(lazy: true)]
class RangeListApiMapper implements ApiRecordFieldMapperInterface
{
    public const FIELD = 'date_time_start';

    public function __construct(
        protected UserHandler       $userHandler,
        protected ModStringsHandler $modStringsHandler,
        protected DateTimeHandler  $dateTimeHandler
    )
    {
    }

    public function getModule(): string
    {
        return 'schedulers';
    }

    public function getKey(): string
    {
        return 'default';
    }

    public function getModes(): array
    {
        return ['list'];
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
        $field = $this->getField();

        $appStrings = $this->modStringsHandler->getModStrings($this->userHandler->getCurrentLanguage())->getItems()['schedulers'];

        $value = $record->getAttributes()[$field] ?? '';

        $formattedValue = $this->dateTimeHandler->toUserDateTime($value);

        $dateEnd = $record->getAttributes()['date_time_end'] ?? '';

        if ($dateEnd === '') {
            $dateEnd = $appStrings['LBL_PERENNIAL'];
        } else {
            $dateEnd = $this->dateTimeHandler->toUserDateTime($dateEnd);
        }

        $attributes = $record->getAttributes();
        $attributes[$field] = "$formattedValue - $dateEnd";
        $record->setAttributes($attributes);
    }

    public function getField(): string
    {
        return self::FIELD;
    }

    public function replaceDefaultTypeMapper(): bool
    {
        return true;
    }
}
