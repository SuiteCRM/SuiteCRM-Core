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


namespace App\ViewDefinitions\LegacyHandler;

use App\FieldDefinitions\Service\FieldDefinitionsProviderInterface;
use App\ViewDefinitions\Service\FieldAliasMapper;
use App\ViewDefinitions\Service\MassUpdate\MassUpdateFieldDefinitionsInjectorTrait;
use App\ViewDefinitions\Service\MassUpdateDefinitionMappers;
use App\ViewDefinitions\Service\MassUpdateDefinitionProviderInterface;

/**
 * Class MassUpdateDefinitionHandler
 */
class MassUpdateDefinitionHandler implements MassUpdateDefinitionProviderInterface
{
    use MassUpdateFieldDefinitionsInjectorTrait;

    /**
     * @var FieldDefinitionsProviderInterface
     */
    private $fieldDefinitionProvider;

    /**
     * @var array
     */
    protected $bannedFields = [];

    /**
     * @var array
     */
    protected $massUpdateTypes = [];

    /**
     * @var array
     */
    protected $activeByDefaultRelateModules = [];

    /**
     * @var array
     */
    protected $activeByDefaultFields = [];

    /**
     * @var array
     */
    protected $activeByDefaultTypes = [];
    /**
     * @var MassUpdateDefinitionMappers
     */
    private $mappers;

    /**
     * @var FieldAliasMapper
     */
    private $fieldAliasMapper;

    /**
     * MassUpdateDefinitionHandler constructor.
     * @param FieldDefinitionsProviderInterface $fieldDefinitionProvider
     * @param MassUpdateDefinitionMappers $mappers
     * @param FieldAliasMapper $fieldAliasMapper
     * @param array $massupdate
     */
    public function __construct(
        FieldDefinitionsProviderInterface $fieldDefinitionProvider,
        MassUpdateDefinitionMappers $mappers,
        FieldAliasMapper $fieldAliasMapper,
        array $massupdate
    ) {
        $this->fieldDefinitionProvider = $fieldDefinitionProvider;

        $excludedFields = $massupdate['excludedFields'] ?? [];
        $includedTypes = $massupdate['includedTypes'] ?? [];
        $activeByDefaultFields = $massupdate['activeByDefaultFields'] ?? [];
        $activeByDefaultRelateModules = $massupdate['activeByDefaultRelateModules'] ?? [];
        $activeByDefaultTypes = $massupdate['activeByDefaultTypes'] ?? [];

        $this->mappers = $mappers;

        foreach ($excludedFields as $excludedField) {
            $this->bannedFields[$excludedField] = true;
        }

        foreach ($includedTypes as $includedType) {
            $this->massUpdateTypes[$includedType] = true;
        }

        foreach ($activeByDefaultFields as $filed) {
            $this->activeByDefaultFields[$filed] = true;
        }

        foreach ($activeByDefaultRelateModules as $activeByDefaultRelateModule) {
            $this->activeByDefaultRelateModules[$activeByDefaultRelateModule] = true;
        }

        foreach ($activeByDefaultTypes as $activeByDefaultType) {
            $this->activeByDefaultTypes[$activeByDefaultType] = true;
        }
        $this->fieldAliasMapper = $fieldAliasMapper;
    }

    /**
     * @inheritDoc
     */
    public function getDefinitions(string $moduleName): array
    {
        return $this->getMassUpdateDefinitions($moduleName);
    }

    /**
     * Get module subpanels
     * @param string $module
     * @return array
     */
    protected function getMassUpdateDefinitions(string $module): array
    {
        $fieldDefinitions = $this->fieldDefinitionProvider->getVardef($module);

        $vardefs = $fieldDefinitions->getVardef();

        $fields = [];
        foreach ($vardefs as $key => $vardef) {
            if (!$this->isMassUpdateEnabled($vardef)) {
                continue;
            }

            $fields[] = $this->buildField(
                [
                    'name' => $vardef['name'] ?? $key,
                    'label' => $vardef['vname'] ?? ''
                ],
                $key,
                $vardefs,
                $this->fieldAliasMapper
            );
        }

        $mappers = $this->mappers->get($module) ?? [];
        foreach ($mappers as $mapper) {
            $mapper->map($module, $fields, $vardefs);
        }

        return [
            'fields' => $fields
        ];
    }

    /**
     * Is mass update enabled
     * @param array $vardef
     * @return bool|mixed
     */
    protected function isMassUpdateEnabled(array $vardef): bool
    {
        $default = false;
        $isBanned = $this->bannedFields[$vardef['name']] ?? false;
        $isActiveByDefault = $this->activeByDefaultFields[$vardef['name']] ?? false;

        if ($isBanned) {
            return false;
        }

        $type = $vardef['custom_type'] ?? $vardef['type'] ?? '';

        if ($type === 'int' && !empty($vardef['auto_increment'])) {
            return false;
        }

        if ($isActiveByDefault) {
            return true;
        }

        $isMassUpdateType = $this->massUpdateTypes[$type] ?? false;

        if (!$isMassUpdateType) {
            return false;
        }

        $isActiveByDefaultType = $this->activeByDefaultTypes[$type] ?? false;

        if ($isActiveByDefaultType) {
            $default = true;
        }

        if ($type === 'relate' && isset($vardef['module'])) {
            $default = $this->activeByDefaultRelateModules[$vardef['module']] ?? false;
        }

        $isMassUpdatable = $vardef['massupdate'] ?? $default;

        return (bool)$isMassUpdatable;
    }
}
