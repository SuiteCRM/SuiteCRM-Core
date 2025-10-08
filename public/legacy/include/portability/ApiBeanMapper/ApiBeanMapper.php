<?php
/**
 * SuiteCRM is a customer relationship management program developed by SalesAgility Ltd.
 * Copyright (C) 2021 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SALESAGILITY, SALESAGILITY DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */

require_once __DIR__ . '/FieldMappers/AssignedUserMapper.php';
require_once __DIR__ . '/FieldMappers/RelateToFieldMapper.php';
require_once __DIR__ . '/LinkMappers/LinkMapperInterface.php';
require_once __DIR__ . '/LinkMappers/EmailAddressLinkMapper.php';
require_once __DIR__ . '/LinkMappers/DefaultLinkMapper.php';
require_once __DIR__ . '/TypeMappers/FullNameMapper.php';
require_once __DIR__ . '/TypeMappers/ParentMapper.php';
require_once __DIR__ . '/TypeMappers/DateMapper.php';
require_once __DIR__ . '/TypeMappers/DateTimeMapper.php';
require_once __DIR__ . '/TypeMappers/DateTimeComboMapper.php';
require_once __DIR__ . '/TypeMappers/MultiEnumMapper.php';
require_once __DIR__ . '/TypeMappers/MultiRelateMapper.php';
require_once __DIR__ . '/TypeMappers/BooleanMapper.php';
require_once __DIR__ . '/TypeMappers/HtmlMapper.php';
require_once __DIR__ . '/TypeMappers/TextMapper.php';
require_once __DIR__ . '/TypeMappers/CurrencyMapper.php';
require_once __DIR__ . '/ApiBeanModuleMappers.php';
require_once __DIR__ . '/ModuleMappers/SavedSearch/SavedSearchMappers.php';
require_once __DIR__ . '/ModuleMappers/AOP_Case_Updates/CaseUpdatesMappers.php';
require_once __DIR__ . '/ModuleMappers/Alerts/AlertsMappers.php';
require_once __DIR__ . '/ModuleMappers/EmailMan/EmailManMappers.php';
require_once __DIR__ . '/ModuleMappers/Tasks/TasksMappers.php';
require_once __DIR__ . '/../Bean/Field/Validation/FieldValidatorRegistry.php';

class ApiBeanMapper
{

    /**
     * @var FieldMapperInterface[]
     */
    protected $fieldMappers = [];

    /**
     * @var TypeMapperInterface[]
     */
    protected $typeMappers = [];

    /**
     * @var LinkMapperInterface[][]
     */
    protected $linkMappers = [];

    /**
     * @var ExtraAttributeMapperInterface[][]
     */
    protected $extraAttributeMappers = [];

    /**
     * @var ApiBeanModuleMappers[]
     */
    protected $moduleMappers = [];

    public function __construct()
    {
        $this->fieldMappers[AssignedUserMapper::getField()] = new AssignedUserMapper();
        $this->extraAttributeMappers[RelateToFieldMapper::getField()] = new RelateToFieldMapper();
        $this->typeMappers[FullNameMapper::getType()] = new FullNameMapper();
        $this->typeMappers[ParentMapper::getType()] = new ParentMapper();
        $this->typeMappers[DateMapper::getType()] = new DateMapper();
        $this->typeMappers[DateTimeMapper::getType()] = new DateTimeMapper();
        $this->typeMappers[MultiEnumMapper::getType()] = new MultiEnumMapper();
        $this->typeMappers[MultiRelateMapper::getType()] = new MultiRelateMapper();
        $this->typeMappers[BooleanMapper::getType()] = new BooleanMapper();
        $this->typeMappers['boolean'] = $this->typeMappers[BooleanMapper::getType()];
        $this->typeMappers[HtmlMapper::getType()] = new HtmlMapper();
        $this->typeMappers[TextMapper::getType()] = new TextMapper();
        $this->typeMappers[CurrencyMapper::getType()] = new CurrencyMapper();
        $this->moduleMappers[SavedSearchMappers::getModule()] = new SavedSearchMappers();
        $this->typeMappers[DateTimeComboMapper::getType()] = new DateTimeMapper();
        $this->linkMappers[EmailAddressLinkMapper::getRelateModule()] = [];
        $this->linkMappers[EmailAddressLinkMapper::getRelateModule()]['all'] = new EmailAddressLinkMapper();
        $this->moduleMappers[CaseUpdatesMappers::getModule()] = new CaseUpdatesMappers();
        $this->moduleMappers[AlertsMappers::getModule()] = new AlertsMappers();
        $this->moduleMappers[EmailManMappers::getModule()] = new EmailManMappers();
        $this->moduleMappers[TasksMappers::getModule()] = new TasksMappers();
        $this->linkMappers[DefaultLinkMapper::getRelateModule()] = [];
        $this->linkMappers[DefaultLinkMapper::getRelateModule()]['all'] = new DefaultLinkMapper();
        global $api_bean_mappers;
        $api_bean_mappers = $api_bean_mappers ?? [];
        if (file_exists('custom/application/Ext/ApiBeanMappers/api_bean_mappers.ext.php')) {
            include('custom/application/Ext/ApiBeanMappers/api_bean_mappers.ext.php');
        }
        if(!empty($api_bean_mappers['typeMappers'])){
            $this->typeMappers = array_merge($this->typeMappers, $api_bean_mappers['typeMappers']);
        }
        if(!empty($api_bean_mappers['fieldMappers'])){
            $this->fieldMappers = array_merge($this->fieldMappers, $api_bean_mappers['fieldMappers']);
        }
        if(!empty($api_bean_mappers['linkMappers'])){
            $this->linkMappers = array_merge($this->linkMappers, $api_bean_mappers['linkMappers']);
        }
        if(!empty($api_bean_mappers['moduleMappers'])){
            $this->moduleMappers = array_merge($this->moduleMappers, $api_bean_mappers['moduleMappers']);
        }

    }

    /**
     * @param SugarBean $bean
     * @return array
     */
    public function toApi(SugarBean $bean): array
    {
        $bean->field_defs = $this->mapLinkedModule($bean);

        $arr = [];

        $arr['module_name'] = $bean->module_name ?? '';
        $arr['object_name'] = $bean->object_name ?? '';

        [$linkFields, $idFields] = $this->getLinkFields($bean);

        foreach ($bean->field_defs as $field => $definition) {
            if ($this->isSensitiveField($definition)) {
                continue;
            }

            if (!$this->checkFieldAccess($bean, $definition)) {
                continue;
            }

            if (!$this->isIdField($idFields, $field) && $this->isLinkField($definition)) {
                if (!$this->hasLinkMapper($bean->module_name, $definition)) {
                    continue;
                }

                $this->mapLinkFieldToApi($bean, $arr, $definition);

                continue;
            }

            if ($this->isIdField($idFields, $field) && $this->isLinkField($definition)) {
                $this->setValue($bean, $field, $arr, $definition);
                continue;
            }

            if ($this->isRelateField($definition)) {
                $this->addRelateFieldToArray($bean, $definition, $arr, $field);
                continue;
            }

            if ($this->isLinkField($definition)) {
                continue;
            }

            $this->setValue($bean, $field, $arr, $definition);
        }

        $attributeMappers = $this->getExtraAttributesMappers($bean->module_name);

        foreach ($attributeMappers as $attributeMapper) {
            if (null !== $attributeMapper) {
                $attributeMapper->toApi($bean, $arr, $attributeMapper->getField());
            }
        }

        return $arr;
    }

    /**
     * @param string $module
     * @param string $field
     * @return ExtraAttributeMapperInterface|array
     */
    protected function getExtraAttributesMapper(string $module, string $field)
    {
        $moduleMappers = $this->moduleMappers[$module] ?? null;

        if ($moduleMappers !== null && $moduleMappers->hasExtraAttributeMapper($field)) {
            return $moduleMappers->getExtraAttributeMappers()[$field];
        }

        return $this->extraAttributeMappers[$field] ?? null;
    }

    /**
     * @param string $module
     * @return array|ExtraAttributeMapperInterface[]|null
     */
    protected function getExtraAttributesMappers(string $module)
    {
        $moduleMappers = $this->moduleMappers[$module] ?? null;

        $attributeMappers = [];
        if ($moduleMappers !== null && !empty($moduleMappers->getExtraAttributeMappers())) {
            $attributeMappers = $moduleMappers->getExtraAttributeMappers();
        }

        if (!empty($this->extraAttributeMappers ?? [])) {
            $attributeMappers = array_merge($this->extraAttributeMappers ?? [], $attributeMappers);
        }

        return $attributeMappers;
    }


    /**
     * @param SugarBean $bean
     * @param array $values
     * @return void
     */
    public function toBean(SugarBean $bean, array $values): void
    {
        require_once __DIR__ . '/../../../include/SugarFields/SugarFieldHandler.php';

        $bean->field_defs = $this->mapLinkedModule($bean);

        [$linkFields, $idFields] = $this->getLinkFields($bean);

        $idTypeFields = $this->getIdTypeFields($bean);

        $mappedAttributes = [];

        foreach ($bean->field_defs as $field => $properties) {

            if ($idTypeFields[$field] ?? null){
                continue;
            }

            $this->processMapping($bean, $values, $idFields, $mappedAttributes, $field, $properties);
        }

        foreach ($idTypeFields as $field => $properties) {
            $this->processMapping($bean, $values, $idFields, $mappedAttributes, $field, $properties, true);
        }

        foreach ($bean->relationship_fields as $field => $link) {
            $mappedAttributes[$field] = true;
            if (!empty($values[$field])) {
                $bean->$field = $values[$field];
            }
        }

        foreach ($values as $field => $value) {
            if (!empty($mappedAttributes[$field])) {
                continue;
            }

            $attributeMapper = $this->getExtraAttributesMapper($bean->module_name, $field);
            if (null !== $attributeMapper) {
                $attributeMapper->toBean($bean, $values, $field);
            }
        }
    }

    protected function processMapping($bean, &$values, $idFields, &$mappedAttributes, $field, $properties): void
    {
        $mappedAttributes[$field] = true;

        if (!isset($values[$field])) {
            return;
        }

        $this->toBeanMap($bean, $values, $properties, $field);

        if (!$this->isIdField($idFields, $field) && $this->isLinkField($properties)) {
            if (!$this->hasLinkMapper($bean->module_name, $properties)) {
                return;
            }

            $this->mapLinkFieldToBean($bean, $values, $properties);
            return;
        }

        $this->validate($bean->module_name, $field, $properties, $values[$field] ?? null, $idFields);

        $bean->$field = $values[$field];
    }


    /**
     * @param SugarBean $bean
     * @param array $values
     * @return void
     */
    public function toBeanAttributes(SugarBean $bean, array &$values): void
    {
        require_once __DIR__ . '/../../../include/SugarFields/SugarFieldHandler.php';

        foreach ($bean->field_defs as $field => $properties) {
            if (!isset($values[$field])) {
                continue;
            }

            $this->toBeanMap($bean, $values, $properties, $field);
        }
    }

    /**
     * @param $definition
     * @return bool
     */
    protected function isRelateField($definition): bool
    {
        return isset($definition['type']) && $definition['type'] === 'relate';
    }

    /**
     * @param $definition
     * @return bool
     */
    protected function isLinkField($definition): bool
    {
        return isset($definition['type']) && $definition['type'] === 'link';
    }


    /**
     * @param $fieldDefinition
     * @return bool
     */
    protected function isSensitiveField($fieldDefinition): bool
    {
        return $fieldDefinition['sensitive'] ?? false;
    }

    /**
     * @param $fieldDefinition
     * @return bool
     */
    protected function isAdminOnlyField($fieldDefinition): bool
    {
        return $fieldDefinition['admin-only'] ?? false;
    }

    /**
     * @param $fieldDefinition
     * @return bool
     */
    protected function isOwnerOnlyField($fieldDefinition): bool
    {
        return $fieldDefinition['owner-only'] ?? false;
    }

    /**
     * @param $fieldDefinition
     * @return bool
     */
    protected function checkAdminOnlyField($fieldDefinition): bool
    {
        global $current_user;

        $isAdminOnlyField = $this->isAdminOnlyField($fieldDefinition);

        if (!$isAdminOnlyField) {
            return true;
        }

        return $isAdminOnlyField && $current_user->isAdmin();
    }

    /**
     * @param $fieldDefinition
     * @param SugarBean $bean
     * @return bool
     */
    protected function checkOwnerOnlyField($fieldDefinition, SugarBean $bean): bool
    {
        global $current_user;

        $assignedUserId = $bean->assigned_user_id ?? '';
        $isOwnerOnlyField = $this->isOwnerOnlyField($fieldDefinition);

        if (!$isOwnerOnlyField) {
            return true;
        }

        return $isOwnerOnlyField && $current_user->id === $assignedUserId;
    }

    /**
     * @param SugarBean $bean
     * @param $definition
     * @return bool
     */
    protected function checkFieldAccess(SugarBean $bean, $definition): bool
    {
        if (!$this->isAdminOnlyField($definition) && !$this->isOwnerOnlyField($definition)) {
            return true;
        }

        return !$this->checkAdminOnlyField($definition) && !$this->checkOwnerOnlyField($definition, $bean);
    }

    /**
     * @param SugarBean $bean
     * @param $definition
     * @param array $arr
     * @param $field
     */
    protected function addRelateFieldToArray(SugarBean $bean, $definition, array &$arr, $field): void
    {
        $fieldRName = $definition['rname'] ?? 'name';
        $idName = $definition['id_name'] ?? '';
        $source = $definition['source'] ?? '';
        $idDefinition = $definition[$idName] ?? [];
        $groupingField = $field;

        if ($source !== 'non-db') {
            $this->setValue($bean, $field, $arr, $definition);

            return;
        }

        if ($idName === $field) {
            $this->setValue($bean, $field, $arr, $definition);

            return;
        }

        $arr[$groupingField] = $arr[$groupingField] ?? [];
        $this->setValue($bean, $field, $arr[$groupingField], $definition, $fieldRName);

        if (isset($bean->$idName)) {
            $idFieldRName = $idDefinition['rname'] ?? 'id';
            $this->setValue($bean, $idName, $arr[$groupingField], $definition, $idFieldRName);
        }
    }

    /**
     * @param SugarBean $bean
     * @param $field
     * @param array $arr
     * @param array $definition
     * @param string $alternativeName
     */
    protected function setValue(
        SugarBean $bean,
        $field,
        array &$arr,
        array $definition,
        string $alternativeName = ''
    ): void {
        $name = $field;

        if (!empty($alternativeName)) {
            $name = $alternativeName;
        }

        $fieldMapper = $this->getFieldMapper($bean->module_name, $field);
        if (null !== $fieldMapper) {
            $fieldMapper->toApi($bean, $arr, $name);

            return;
        }

        $type = $definition['type'] ?? '';
        $typeMapper = $this->getTypeMappers($bean->module_name, $type);
        if (null !== $typeMapper) {
            $typeMapper->toApi($bean, $arr, $field, $name);

            return;
        }

        $arr[$name] = html_entity_decode($bean->$field ?? '', ENT_QUOTES);
    }

    /**
     * @param SugarBean $bean
     * @param array $container
     * @param array $definition
     */
    protected function mapLinkFieldToApi(SugarBean $bean, array &$container, array $definition): void
    {
        $module = $bean->module_name ?? '';
        $relateModule = $definition['module'] ?? '';
        $name = $definition['name'] ?? '';

        $linkMapper = $this->getLinkMapper($module, $relateModule, $name);
        if ($linkMapper === null) {
            return;
        }

        $linkMapper->toApi($bean, $container, $name);
    }

    /**
     * @param SugarBean $bean
     * @param array $container
     * @param array $definition
     */
    protected function mapLinkFieldToBean(SugarBean $bean, array &$container, array $definition): void
    {
        $module = $bean->module_name ?? '';
        $relateModule = $definition['module'] ?? '';
        $name = $definition['name'] ?? '';

        $linkMapper = $this->getLinkMapper($module, $relateModule, $name);

        if ($linkMapper === null) {
            return;
        }

        $linkMapper->toBean($bean, $container, $name);
    }

    /**
     * @param string $module
     * @param string $field
     * @return FieldMapperInterface
     */
    protected function getFieldMapper(string $module, string $field): ?FieldMapperInterface
    {
        $moduleMappers = $this->moduleMappers[$module] ?? null;

        if ($moduleMappers !== null && $moduleMappers->hasFieldMapper($field)) {
            return $moduleMappers->getFieldMappers()[$field];
        }

        return $this->fieldMappers[$field] ?? null;
    }

    /**
     * @param string $module
     * @param string $relateModule
     * @param string $field
     * @return LinkMapperInterface
     */
    protected function getLinkMapper(string $module, string $relateModule, string $field): ?LinkMapperInterface
    {
        if ($module === '' || $relateModule === '' || $field === '') {
            return null;
        }

        $moduleMappers = $this->moduleMappers[$module] ?? null;

        if ($moduleMappers !== null && $moduleMappers->hasLinkMapper($relateModule, $field)) {
            return $moduleMappers->getLinkMapper($relateModule, $field);
        }

        $moduleLinkMappers = $this->linkMappers[$relateModule] ?? $this->linkMappers['default'] ?? [];

        return $moduleLinkMappers[$field] ?? $moduleLinkMappers['all'] ?? null;
    }

    /**
     * @param $definition
     * @return bool
     */
    protected function hasLinkMapper($module, $definition): bool
    {
        $relateModule = $definition['module'] ?? '';
        $name = $definition['name'] ?? '';

        if ($relateModule === '' || $name === '') {
            return false;
        }

        return $this->getLinkMapper($module, $relateModule, $name) !== null;
    }

    /**
     * @param string $module
     * @param string $type
     * @return TypeMapperInterface
     */
    protected function getTypeMappers(string $module, string $type): ?TypeMapperInterface
    {
        $moduleMappers = $this->moduleMappers[$module] ?? null;

        if ($moduleMappers !== null && $moduleMappers->hasTypeMapper($type)) {
            return $moduleMappers->getTypeMappers()[$type];
        }

        return $this->typeMappers[$type] ?? null;
    }

    /**
     * @param SugarBean $bean
     * @param array $values
     * @param $properties
     * @param $field
     */
    protected function toBeanMap(SugarBean $bean, array &$values, $properties, $field): void
    {
        $type = $properties['type'] ?? '';

        if ($type === 'relate' && isset($bean->field_defs[$field])) {
            $fieldDef = $bean->field_defs[$field];
            $idName = $fieldDef['id_name'] ?? '';

            if ($field !== $idName) {
                if (!empty($idName)) {
                    $values[$idName] = $values[$idName] ?? $values[$field]['id'] ?? '';
                }

                $rName = $fieldDef['rname'] ?? 'name';
                $values[$field] = $values[$field][$rName] ?? $values[$field] ?? '';
            }
        }

        if ((!empty($properties['isMultiSelect']) || $type === 'multienum')) {
            $multiSelectValue = $values[$field];
            if($type === 'multirelate') {
                return;
            }
            if (!is_array($values[$field])) {
                $multiSelectValue = [];
            }
            $values[$field] = encodeMultienumValue($multiSelectValue);
        }

        $fieldMapper = $this->getFieldMapper($bean->module_name, $field);
        if (null !== $fieldMapper) {
            $fieldMapper->toBean($bean, $values, $field);
        }

        $typeMapper = $this->getTypeMappers($bean->module_name, $type);
        if (null !== $typeMapper) {
            $typeMapper->toBean($bean, $values, $field, $field);
        }
    }

    /**
     * @param SugarBean $bean
     * @return array
     */
    public function mapLinkedModule(SugarBean $bean): array
    {
        $beanModule = $bean->module_name;
        if (empty($beanModule)) {
            return [];
        }

        $field_defs = $bean->field_defs;
        if (empty($field_defs)) {
            return [];
        }

        $beanObject = BeanFactory::newBean($beanModule);
        if (empty($beanObject)) {
            return [];
        }

        $beanObject->load_relationships();

        foreach ($field_defs as $fieldName => $fieldDefinition) {

            //skip, if module property already exists in fieldDefinition
            $module = $fieldDefinition['module'] ?? '';
            if (!empty($module)) {
                continue;
            }

            $type = $fieldDefinition['type'] ?? '';
            if ($type !== 'link') {
                continue;
            }

            $relationship = $fieldDefinition['relationship'] ?? '';
            if (empty($relationship)) {
                continue;
            }

            $name = $fieldDefinition['name'] ?? '';
            if (empty($name)) {
                continue;
            }

            if (!property_exists($beanObject, $name)) {
                continue;
            }

            if (!property_exists($beanObject->$name, 'relationship')) {
                continue;
            }

            if (!property_exists($beanObject->$name->relationship, 'def')) {
                continue;
            }

            $relationshipMetadata = $beanObject->$name->relationship->def;
            if (empty($relationshipMetadata)) {
                continue;
            }

            $this->injectRelatedModule($fieldDefinition, $relationshipMetadata, $beanModule);

            $field_defs[$fieldName] = $fieldDefinition;
        }

        return $field_defs;
    }

    /**
     * @param array $fieldDefinition
     * @param array $relationshipMetadata
     * @param string $beanModule
     * @return void
     * @desc this function retrieves the related module for the link type field.
     * this information is required to link the relationship between the two modules
     */
    public function injectRelatedModule(array &$fieldDefinition, array $relationshipMetadata, string $beanModule): void
    {
        if (empty($relationshipMetadata)) {
            return;
        }

        $lhsModule = $relationshipMetadata['lhs_module'] ?? '';
        $rhsModule = $relationshipMetadata['rhs_module'] ?? '';

        if ($lhsModule === $beanModule) {
            $fieldDefinition['module'] = $rhsModule;
        }

        if ($rhsModule === $beanModule) {
            $fieldDefinition['module'] = $lhsModule;
        }
    }

    /**
     * Get link field information
     * @param SugarBean $bean
     * @return array
     */
    protected function getLinkFields(SugarBean $bean): array
    {
        $linkFields = [];
        $idFields = [];

        foreach ($bean->field_defs as $field => $definition) {

            $link = $definition['link'] ?? '';
            $idField = $definition['id_name'] ?? '';

            if (!empty($link)) {
                $linkInfo = $linkFields[$link] ?? [];
                $linkInfo['link'] = $link;

                if (!empty($idField)) {
                    $idFieldInfo = $idFields[$idField] ?? [];
                    $idFieldInfo['name'] = $idField;
                    $idFieldInfo['link'] = $link;
                    $idFields[$idField] = $idFieldInfo;

                    $linkInfo['id'] = $idField;
                }

                $linkFields[$link] = $linkInfo;
            }
        }

        $idFields = $this->addIdFieldsFromLinks($bean, $linkFields, $idFields);

        return [$linkFields, $idFields];
    }

    /**
     * Add ids from link fields
     * @param SugarBean $bean
     * @param array $linkFields
     * @return array
     */
    protected function addIdFieldsFromLinks(SugarBean $bean, array $linkFields, array $idFields): array
    {
        foreach ($bean->field_defs as $field => $definition) {

            $relationship = $definition['relationship'] ?? '';

            if (empty($relationship)) {
                continue;
            }

            if (!empty($linkFields[$field]) && $this->isLinkField($definition)) {
                $idName = $linkFields[$field]['id'] ?? '';

                $idField = $idFields[$idName] ?? [];
                $idField['relationship'] = $relationship;
                $idFields[$idName] = $idField;
            }
        }

        return $idFields;
    }

    /**
     * @param $idFields
     * @param $field
     * @return bool
     */
    protected function isIdField($idFields, $field): bool
    {
        return isset($idFields[$field]);
    }

    /**
     * Validate field
     * @param string $module
     * @param string $field
     * @param array $definition
     * @param mixed $value
     * @param array $idFields
     * @return void
     */
    private function validate(string $module, string $field, array $definition, $value, array $idFields): void
    {
        $type = $definition['type'];

        if ($this->isIdField($idFields, $field)) {
            $type = 'id';
        }

        if ($field === 'id') {
            $type = 'id';
        }

        $registry = FieldValidatorRegistry::getInstance();

        $errors = $registry->validate($field, $type, $definition, $value, $module);

        if (!empty($errors)) {
            $registry->logErrors($errors, 'ApiBeanMapper field validation');
            throw new InvalidArgumentException("Invalid $field field value ");
        }
    }

    /**
     * @param SugarBean $bean
     * @return array
     */
    protected function getIdTypeFields(SugarBean $bean): array
    {
        $idTypeFields = [];
        foreach ($bean->field_defs as $field => $properties) {
            if (isset($properties['type']) && $properties['type'] !== 'id') {
                continue;
            }

            $idTypeFields[$field] = $properties;
        }
        return $idTypeFields;
    }
}
