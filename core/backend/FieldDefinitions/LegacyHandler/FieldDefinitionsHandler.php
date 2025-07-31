<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2021 SuiteCRM Ltd.
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

namespace App\FieldDefinitions\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\FieldDefinitions\Service\FieldDefinitionsProviderInterface;
use App\FieldDefinitions\Service\VardefConfigMapperRegistry;
use App\Module\Service\ModuleNameMapperInterface;
use Exception;
use Psr\Log\LoggerInterface;
use SugarView;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class FieldDefinitionsHandler
 * @package App\Legacy
 */
class FieldDefinitionsHandler extends LegacyHandler implements FieldDefinitionsProviderInterface
{
    public const HANDLER_KEY = 'field-definitions';

    /**
     * FieldDefinitionsHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param FieldDefinitionMappers $mappers
     * @param RequestStack $session
     * @param LoggerInterface $logger
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        protected ModuleNameMapperInterface $moduleNameMapper,
        protected FieldDefinitionMappers $mappers,
        RequestStack $session,
        protected LoggerInterface $logger,
        protected VardefConfigMapperRegistry $vardefConfigMapperRegistry,
    ) {
        parent::__construct($projectDir, $legacyDir, $legacySessionName, $defaultSessionName, $legacyScopeState,
            $session);
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * @param string $moduleName
     * @return FieldDefinition
     * @throws Exception
     */
    public function getVardef(string $moduleName): FieldDefinition
    {
        $this->init();

        $legacyModuleName = $this->moduleNameMapper->toLegacy($moduleName);

        $vardefs = new FieldDefinition();
        $vardefs->setId($moduleName);
        $vardefs->setVardef($this->getDefinitions($legacyModuleName, $moduleName));

        $mappers = $this->mappers->get($moduleName);

        foreach ($mappers as $mapper) {
            $mapper->map($vardefs);
        }

        $this->close();

        return $vardefs;
    }

    /**
     * @inheritDoc
     */
    public function getOptionsKey(string $module, string $fieldName): ?string
    {
        $fieldDefinition = $this->getVardef($module);
        $fieldDef = $fieldDefinition->getVardef()[$fieldName] ?? null;

        if (!$fieldDef) {
            return null;
        }

        $optionsKey = $fieldDef['options'] ?? null;
        if (!$optionsKey) {
            return null;
        }

        return $optionsKey;
    }

    /**
     * @inheritDoc
     */
    public function getFieldDefinition(string $moduleName, string $field): ?array
    {
        $fieldDefinitions = $this->getVardef($moduleName);
        if (empty($fieldDefinitions)) {
            return null;
        }

        $vardefs = $fieldDefinitions->getVardef();
        if (empty($vardefs)) {
            return null;
        }

        $fieldDefinition = $vardefs[$field] ?? null;
        if (empty($fieldDefinition)) {
            return null;
        }

        return $fieldDefinition;
    }


    /**
     * Get legacy definitions
     * @param string $legacyModuleName
     * @return array
     */
    protected function getDefinitions(string $legacyModuleName, string $moduleName): array
    {
        try {
            $sugarView = new SugarView();
            $data = $sugarView->getVardefsData($legacyModuleName);
        } catch (Exception $e) {
            $this->logger->error(
                "Failed to get legacy definitions for module {module}: {error}",
                [
                    'module' => $legacyModuleName,
                    'error' => $e->getMessage(),
                    'exception' => $e
                ]
            );
            return [];
        }

        $vardefs = $data[0][$legacyModuleName] ?? [];

        $mappers = $this->vardefConfigMapperRegistry->get($moduleName);

        foreach ($mappers as $mapper) {
            $vardefs = $mapper->map($vardefs);
        }

        return $vardefs;

    }
}
