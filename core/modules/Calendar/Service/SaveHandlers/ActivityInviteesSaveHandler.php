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

namespace App\Module\Calendar\Service\SaveHandlers;

use App\Data\Entity\Record;
use App\Data\Service\Record\RecordSaveHandlers\RecordSaveHandlerInterface;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\Module\Calendar\LegacyHandler\CalendarHandler;

/**
 * A meeting or a call created in SuiteCRM 8 (record view, calendar create modal) gets the
 * invitees the legacy forms give a new activity: its assigned user and the user creating it
 * (CalendarHandler::addCreatedActivityInvitees()). Without them it is on no
 * calendar.
 */
abstract class ActivityInviteesSaveHandler implements RecordSaveHandlerInterface
{
    public function __construct(protected CalendarHandler $builder)
    {
    }

    abstract protected function getLegacyModule(): string;

    public function getKey(): string
    {
        return 'activity-invitees';
    }

    public function getModes(): array
    {
        return ['after-save'];
    }

    public function getOrder(): int
    {
        return 0;
    }

    public function run(?Record $previousVersion, Record $inputRecord, ?Record $savedRecord, FieldDefinition $fieldDefinition): void
    {
        if ($previousVersion !== null || $savedRecord === null || empty($savedRecord->getId())) {
            return;
        }

        $this->builder->addCreatedActivityInvitees($this->getLegacyModule(), (string)$savedRecord->getId());
    }
}
