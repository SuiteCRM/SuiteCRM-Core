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

namespace App\Data\Service\Record\RecordDeleteHandlers;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\Service\FieldDefinitionsProviderInterface;

class RecordDeleteHandlerRunner implements RecordDeleteHandlerRunnerInterface
{
    /**
     * RecordDeleteHandlerRunner constructor.
     * @param RecordFieldDeleteHandlerRegistry $fieldDeleteHandlerRegistry
     * @param RecordFieldTypeDeleteHandlerRegistry $fieldTypeDeleteHandlerRegistry
     * @param RecordDeleteHandlerRegistry $recordDeleteHandlerRegistry
     * @param FieldDefinitionsProviderInterface $fieldDefinitions
     */
    public function __construct(
        protected RecordFieldDeleteHandlerRegistry $fieldDeleteHandlerRegistry,
        protected RecordFieldTypeDeleteHandlerRegistry $fieldTypeDeleteHandlerRegistry,
        protected RecordDeleteHandlerRegistry $recordDeleteHandlerRegistry,
        protected FieldDefinitionsProviderInterface $fieldDefinitions
    ) {
    }

    /**
     * Run all the handlers for the module and mode
     * @param string $module
     * @param string $id
     * @param string $mode
     * @return void
     */
    public function run(string $module, string $id, string $mode): void
    {
        $this->runHandlers($module, $id, $mode);
    }

    /**
     * Run all the handlers for the module and mode
     * @param string $module
     * @param string $id
     * @param string $mode
     * @return void
     */
    protected function runHandlers(string $module, string $id, string $mode): void
    {
        $fieldDefinitions = $this->fieldDefinitions->getVardef($module);
        $vardefs = $fieldDefinitions->getVardef();

        $recordDeleteHandlers = $this->recordDeleteHandlerRegistry->getHandlers($module, $mode);
        foreach ($recordDeleteHandlers as $recordDeleteHandler) {
            $recordDeleteHandler->run($module, $id, $mode, $fieldDefinitions);
        }

        foreach ($vardefs as $field => $vardef) {
            $this->runHandlersForField($vardef, $field, $module, $id, $fieldDefinitions, $mode);
        }
    }

    /**
     * Run the handlers for a specific field
     * @param array|null $vardefs
     * @param string $field
     * @param string $module
     * @param string $id
     * @param FieldDefinition $fieldDefinitions
     * @param string|null $mode
     * @return void
     */
    protected function runHandlersForField(
        ?array $vardefs,
        string $field,
        string $module,
        string $id,
        FieldDefinition $fieldDefinitions,
        ?string $mode = ''
    ): void {
        $fieldVardefs = $vardefs ?? [];
        $type = $fieldVardefs['type'] ?? '';
        $fieldDeleteHandlers = $this->fieldDeleteHandlerRegistry->getDeleteHandlers($module, $field, $mode);

        if ($type !== '') {
            $this->runDefaultHandler($module, $id, $type, $mode, $field, $fieldDefinitions);

            $fieldTypeDeleteHandlers = $this->fieldTypeDeleteHandlerRegistry->getHandlers($module, $type, $mode);
            foreach ($fieldTypeDeleteHandlers as $fieldTypeDeleteHandler) {
                $fieldTypeDeleteHandler->run($module, $id, $fieldDefinitions, $field);
            }
        }

        foreach ($fieldDeleteHandlers as $fieldDeleteHandler) {
            $fieldDeleteHandler->run($module, $id, $fieldDefinitions);
        }
    }

    /**
     * Run the default handler for the field type if it exists
     * @param string $module
     * @param string $id
     * @param string $type
     * @param string|null $mode
     * @param string $field
     * @param FieldDefinition $fieldDefinitions
     * @return void
     */
    protected function runDefaultHandler(
        string $module,
        string $id,
        string $type,
        ?string $mode,
        string $field,
        FieldDefinition $fieldDefinitions
    ): void {
        $default = $this->fieldTypeDeleteHandlerRegistry->getDefaultHandler($module, $type, $mode);
        $defaultOverride = $this->fieldDeleteHandlerRegistry->getTypeDefaultOverride($module, $field, $mode);

        if ($defaultOverride !== null) {
            $defaultOverride->run($module, $id, $fieldDefinitions);
        } elseif ($default !== null) {
            $default->run($module, $id, $fieldDefinitions, $field);
        }
    }

}
