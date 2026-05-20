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
use App\Module\Service\ModuleNameMapperInterface;
use BeanFactory;
use Symfony\Component\HttpFoundation\RequestStack;

class LinkFieldDefinitionMapper extends LegacyHandler implements FieldDefinitionMapperInterface
{
    /**
     * @var ModuleNameMapperInterface
     */
    protected $moduleNameMapper;

    /**
     * LinkFieldDefinitionMapper constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param RequestStack $session
     * @param ModuleNameMapperInterface $moduleNameMapper
     */
    public function __construct(
        string                    $projectDir,
        string                    $legacyDir,
        string                    $legacySessionName,
        string                    $defaultSessionName,
        LegacyScopeState          $legacyScopeState,
        RequestStack          $session,
        ModuleNameMapperInterface $moduleNameMapper)
    {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $session);
        $this->moduleNameMapper = $moduleNameMapper;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return 'link-field-definition-mapper';
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return 'link-field-definition-mapper';
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
     * @param FieldDefinition $definition
     */
    public function map(FieldDefinition $definition): void
    {

        $vardefs = $definition->getVardef();
        if (empty($vardefs)) {
            return;
        }

        $beanModule = $this->moduleNameMapper->toLegacy($definition->getId());
        if (empty($beanModule)) {
            return;
        }

        $beanObject = BeanFactory::newBean($beanModule);
        if ($beanObject === null) {
            return;
        }

        $beanObject->load_relationships();
        if (empty($beanObject)) {
            return;
        }

        foreach ($vardefs as $fieldName => $fieldDefinition) {

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

            $this->injectRelationshipMetadata($fieldDefinition, $relationshipMetadata, $beanModule);

            $vardefs[$fieldName] = $fieldDefinition;
        }

        $definition->setVardef($vardefs);
    }

    /**
     * @param array $fieldDefinition
     * @param array $relationshipMetadata
     * @param string $beanModule
     * @return void
     * @desc this function retrieves the relationship metadata for the link type field.
     * this information is required to link the relationship between the two modules
     */
    public function injectRelationshipMetadata(array &$fieldDefinition, array $relationshipMetadata, string $beanModule): void
    {
        if (empty($relationshipMetadata)) {
            return;
        }

        $lhsModule = $relationshipMetadata['lhs_module'] ?? '';
        $rhsModule = $relationshipMetadata['rhs_module'] ?? '';
        $joinKeyLhs = $relationshipMetadata['join_key_lhs'] ?? '';
        $joinKeyRhs = $relationshipMetadata['join_key_rhs'] ?? '';
        $relationshipType = $relationshipMetadata['relationship_type'] ?? '';

        if ($lhsModule === $beanModule) {
            $fieldDefinition['module'] = $rhsModule;
            $fieldDefinition['relationshipMetadata'] =
                [
                    'side' => 'rhs',
                    'related_id' => $joinKeyRhs,
                    'type' => $relationshipType,
                    'parent_id' => $joinKeyLhs
                ];
        }

        if ($rhsModule === $beanModule) {
            $fieldDefinition['module'] = $lhsModule;
            $fieldDefinition['relationshipMetadata'] =
                [
                    'side' => 'lhs',
                    'related_id' => $joinKeyLhs,
                    'type' => $relationshipType,
                    'parent_id' => $joinKeyRhs
                ];
        }
    }

}
