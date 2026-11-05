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

namespace App\ViewDefinitions\LegacyHandler\RecordView;

use App\Data\Service\Record\DuplicateCheck\DuplicateCheckProviderRegistry;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\Process\Service\RecordValidators\RecordDuplicateCheckHandler;
use App\ViewDefinitions\Entity\ViewDefinition;
use App\ViewDefinitions\LegacyHandler\ViewDefinitionMapperInterface;

/**
 * Adds the duplicate check save validator to the record view of the modules
 * enabled in module.recordview.duplicate_check
 */
class RecordViewDuplicateCheckMapper implements ViewDefinitionMapperInterface
{
    /**
     * @param DuplicateCheckProviderRegistry $duplicateCheckProviderRegistry
     */
    public function __construct(protected DuplicateCheckProviderRegistry $duplicateCheckProviderRegistry)
    {
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return 'record-view-duplicate-check-mapper';
    }

    /**
     * @inheritDoc
     */
    public function getModule(): string
    {
        return 'default';
    }

    /**
     * @inheritDoc
     */
    public function map(ViewDefinition $definition, FieldDefinition $fieldDefinition): void
    {
        $module = $definition->getId();
        $recordView = $definition->getRecordView() ?? [];

        if (empty($recordView) || $this->duplicateCheckProviderRegistry->get($module) === null) {
            return;
        }

        $recordView['asyncValidators'][RecordDuplicateCheckHandler::PROCESS_TYPE] = [
            'key' => RecordDuplicateCheckHandler::PROCESS_TYPE
        ];

        $definition->setRecordView($recordView);
    }
}
