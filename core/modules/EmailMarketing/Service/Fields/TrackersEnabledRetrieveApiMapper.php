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

namespace App\Module\EmailMarketing\Service\Fields;

use App\Data\Entity\Record;
use App\Data\Service\Record\ApiRecordMappers\ApiRecordFieldMapperInterface;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\Module\Campaigns\Service\Email\Trackers\EmailTrackerManagerInterface;

class TrackersEnabledRetrieveApiMapper implements ApiRecordFieldMapperInterface
{
    public const FIELD = 'trackers_enabled';

    public function __construct(
        protected EmailTrackerManagerInterface $emailTrackerManager
    )
    {
    }

    public function getField(): string
    {
       return self::FIELD;
    }

    public function replaceDefaultTypeMapper(): bool
    {
        return true;
    }

    public function toInternal(Record $record, FieldDefinition $fieldDefinitions): void
    {
    }

    public function toExternal(Record $record, FieldDefinition $fieldDefinitions): void
    {
        $isTrackingEnabled = $this->emailTrackerManager->isTrackingEnabled();

        if ($isTrackingEnabled) {
            return;
        }

        $field = $this->getField();
        $attributes = $record->getAttributes();
        $attributes[$field] = 0;
        $record->setAttributes($attributes);
    }

    public function getKey(): string
    {
        return 'default';
    }

    public function getModule(): string
    {
        return 'email-marketing';
    }

    public function getOrder(): int
    {
        return 0;
    }

    public function getModes(): array
    {
        return ['retrieve'];
    }
}
