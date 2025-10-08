<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
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

namespace App\Data\Service\Record\RecordSaveHandlers;

use App\Data\Entity\Record;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\Service\FieldDefinitionsProviderInterface;

class RecordSaveHandlerRunner implements RecordSaveHandlerRunnerInterface
{
    protected RecordFieldSaveHandlerRegistry $fieldSaveHandlerRegistry;
    protected RecordFieldTypeSaveHandlerRegistry $fieldTypeSaveHandlerRegistry;
    protected RecordSaveHandlerRegistry $recordSaveHandlerRegistry;
    protected FieldDefinitionsProviderInterface $fieldDefinitions;

    /**
     * RecordSaveHandlerRunner constructor.
     * @param RecordFieldSaveHandlerRegistry $fieldSaveHandlerRegistry
     * @param RecordFieldTypeSaveHandlerRegistry $fieldTypeSaveHandlerRegistry
     * @param RecordSaveHandlerRegistry $recordSaveHandlerRegistry
     * @param FieldDefinitionsProviderInterface $fieldDefinitions
     */
    public function __construct(
        RecordFieldSaveHandlerRegistry $fieldSaveHandlerRegistry,
        RecordFieldTypeSaveHandlerRegistry $fieldTypeSaveHandlerRegistry,
        RecordSaveHandlerRegistry $recordSaveHandlerRegistry,
        FieldDefinitionsProviderInterface $fieldDefinitions
    ) {
        $this->fieldSaveHandlerRegistry = $fieldSaveHandlerRegistry;
        $this->fieldTypeSaveHandlerRegistry = $fieldTypeSaveHandlerRegistry;
        $this->recordSaveHandlerRegistry = $recordSaveHandlerRegistry;
        $this->fieldDefinitions = $fieldDefinitions;
    }

    public function run(?Record $previousVersion, Record $inputRecord, ?Record $savedRecord, string $mode): void
    {
        $this->runHandlers($previousVersion, $inputRecord, $savedRecord, $mode);
    }

    protected function runHandlers(?Record $previousVersion, Record $inputRecord, ?Record $savedRecord, string $mode): void
    {
        $attributes = $inputRecord->getAttributes() ?? [];
        $module = $inputRecord->getModule();

        if (empty($attributes) || empty($module)) {
            return;
        }

        $fieldDefinitions = $this->fieldDefinitions->getVardef($module);
        $vardefs = $fieldDefinitions->getVardef();

        $recordSaveHandlers = $this->recordSaveHandlerRegistry->getHandlers($module, $mode);
        foreach ($recordSaveHandlers as $recordSaveHandler) {
            $recordSaveHandler->run($previousVersion, $inputRecord, $savedRecord, $fieldDefinitions);
        }

        $handledFields = [];

        foreach ($vardefs as $field => $vardef) {
            $handledFields[$field] = true;

            $this->runHandlersForField($vardef, $field, $previousVersion, $inputRecord, $savedRecord, $fieldDefinitions, $mode);
        }

        foreach ($attributes as $field => $attribute) {
            if (!empty($handledFields[$field])) {
                continue;
            }
            $this->runHandlersForField($vardefs[$field] ?? null, $field, $previousVersion, $inputRecord, $savedRecord, $fieldDefinitions, $mode);
        }
    }

    /**
     * @param array|null $vardefs
     * @param string $field
     * @param Record|null $previousVersion
     * @param Record $inputRecord
     * @param Record|null $savedRecord
     * @param FieldDefinition $fieldDefinitions
     * @param string|null $mode
     * @return void
     */
    protected function runHandlersForField(
        ?array $vardefs,
        string $field,
        ?Record $previousVersion,
        Record $inputRecord,
        ?Record $savedRecord,
        FieldDefinition $fieldDefinitions,
        ?string $mode = ''
    ): void {
        $fieldVardefs = $vardefs ?? [];
        $type = $fieldVardefs['type'] ?? '';
        $fieldSaveHandlers = $this->fieldSaveHandlerRegistry->getSaveHandlers($inputRecord->getModule(), $field, $mode);

        if ($type !== '') {
            $this->runDefaultHandler($inputRecord, $savedRecord, $type, $mode, $field, $previousVersion, $fieldDefinitions);

            $fieldTypeSaveHandlers = $this->fieldTypeSaveHandlerRegistry->getHandlers($inputRecord->getModule(), $type, $mode);
            foreach ($fieldTypeSaveHandlers as $fieldTypeSaveHandler) {
                $fieldTypeSaveHandler->run($previousVersion, $inputRecord, $savedRecord, $fieldDefinitions, $field);
            }
        }

        foreach ($fieldSaveHandlers as $fieldSaveHandler) {
            $fieldSaveHandler->run($previousVersion, $inputRecord, $savedRecord, $fieldDefinitions);
        }
    }

    /**
     * @param Record $inputRecord
     * @param Record|null $savedRecord
     * @param string $type
     * @param string|null $mode
     * @param string $field
     * @param Record|null $previousVersion
     * @param FieldDefinition $fieldDefinitions
     * @return void
     */
    protected function runDefaultHandler(
        Record $inputRecord,
        ?Record $savedRecord,
        string $type,
        ?string $mode,
        string $field,
        ?Record $previousVersion,
        FieldDefinition $fieldDefinitions
    ): void {
        $default = $this->fieldTypeSaveHandlerRegistry->getDefaultHandler($inputRecord->getModule(), $type, $mode);
        $defaultOverride = $this->fieldSaveHandlerRegistry->getTypeDefaultOverride($inputRecord->getModule(), $field, $mode);

        if ($defaultOverride !== null) {
            $defaultOverride->run($previousVersion, $inputRecord, $savedRecord, $fieldDefinitions);
        } elseif ($default !== null) {
            $default->run($previousVersion, $inputRecord, $savedRecord, $fieldDefinitions, $field);
        }
    }

}
