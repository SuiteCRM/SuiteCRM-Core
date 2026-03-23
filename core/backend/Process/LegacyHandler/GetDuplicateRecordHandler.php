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

namespace App\Process\LegacyHandler;

use App\Data\Entity\Record;
use App\Data\Service\Record\Duplicate\DuplicateFieldMapperRegistry;
use App\Data\Service\RecordProviderInterface;
use App\FieldDefinitions\Service\FieldDefinitionsProviderInterface;
use App\Module\Service\ModuleNameMapperInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use App\SystemConfig\Service\SystemConfigProviderInterface;

class GetDuplicateRecordHandler implements ProcessHandlerInterface
{
    protected const PROCESS_TYPE = 'get-duplicate-record';

    public function __construct(
        protected RecordProviderInterface $recordProvider,
        protected FieldDefinitionsProviderInterface $fieldDefinitionsProvider,
        protected DuplicateFieldMapperRegistry $duplicateFieldMapperRegistry,
        protected SystemConfigProviderInterface $systemConfigProvider,
        protected ModuleNameMapperInterface $moduleNameMapper
    )
    {
    }

    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    public function requiredAuthRole(): string
    {
        return 'ROLE_USER';
    }

    public function getRequiredACLs(Process $process): array
    {
        $options = $process->getOptions();
        $module = $options['module'] ?? '';

        return [
            $module => [
                ['action' => 'view'],
            ],
        ];
    }

    public function configure(Process $process): void
    {
        $process->setId(self::PROCESS_TYPE);
        $process->setStatus('success');
        $process->setAsync(false);
    }

    public function validate(Process $process): void
    {
        $options = $process->getOptions();

        if (empty($options['module']) || empty($options['id'])) {
            $process->setStatus('error');
            $process->setMessages(['ERR_MISSING_REQUIRED_FIELDS']);
        }
    }

    /**
     * @throws \Exception
     */
    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $module = $options['module'];
        $id = $options['id'];

        $record = $this->recordProvider->getRecord($module, $id);

        $this->applyDuplicateMappers($record);

        $process->setStatus('success');
        $process->setData(['record' => $record->toArray()]);
    }

    protected function applyDuplicateMappers(Record $record): void
    {
        $module = $record->getModule();
        $fieldDefinition = $this->fieldDefinitionsProvider->getVardef($module);
        $vardefs = $fieldDefinition->getVardef();
        $attributes = $record->getAttributes();

        $duplicateIgnore = $this->getDuplicateIgnoreList($module);

        foreach ($vardefs as $fieldName => $fieldDef) {
            if (!isset($attributes[$fieldName])) {
                continue;
            }

            if (in_array($fieldName, $duplicateIgnore, true)) {
                continue;
            }

            $allowDuplicate = $fieldDef['metadata']['allow_duplicate'] ?? true;
            if (isFalse($allowDuplicate)) {
                continue;
            }

            $fieldType = $fieldDef['type'] ?? '';
            $mappers = $this->duplicateFieldMapperRegistry->getMappers($module, $fieldType, $fieldName);

            foreach ($mappers as $mapper) {
                $mapper->duplicate($record, $fieldName, $fieldDef);
            }
        }
    }

    protected function getDuplicateIgnoreList(string $module): array
    {
        $config = $this->systemConfigProvider->getSystemConfig('duplicate_ignore');

        if ($config === null) {
            return [];
        }

        $items = $config->getItems();
        $legacyModule = $this->moduleNameMapper->toLegacy($module);

        return array_merge(
            $items['default'] ?? [],
            (array)($items[$legacyModule] ?? []),
            (array)($items[$module] ?? [])
        );
    }
}
