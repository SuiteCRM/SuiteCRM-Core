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

namespace App\Module\Schedulers\Service\SaveHandlers;

use App\Data\Entity\Record;
use App\Data\Service\Record\RecordSaveHandlers\RecordFieldSaveHandlerInterface;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\Module\Schedulers\LegacyHandler\SchedulerFieldsHandler;
use App\Module\Schedulers\Service\Mappers\SchedulerJobEntityMapper;

/**
 * Job of a scheduler saved from the record view: the job of its job function and job URL (not
 * stored), as the legacy Save.php (SchedulerFieldsHandler::joinJob()). Saves without the job
 * function leave the job as it is.
 */
class SchedulerJobSaveHandler implements RecordFieldSaveHandlerInterface
{
    public function __construct(protected SchedulerFieldsHandler $fields)
    {
    }

    public function getField(): string
    {
        return 'job_function';
    }

    public function getKey(): string
    {
        return SchedulerFieldsHandler::HANDLER_KEY;
    }

    public function getModule(): string
    {
        return 'schedulers';
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
        if (!array_key_exists('job_function', $attributes)) {
            return;
        }

        $job = $this->fields->joinJob($attributes['job_function'] ?? '', $attributes['job_url'] ?? '');
        if ($job !== null) {
            $attributes['job'] = $job;
        }
        unset($attributes['job_function'], $attributes['job_url'], $attributes[SchedulerJobEntityMapper::READABLE]);
        $inputRecord->setAttributes($attributes);
    }
}
