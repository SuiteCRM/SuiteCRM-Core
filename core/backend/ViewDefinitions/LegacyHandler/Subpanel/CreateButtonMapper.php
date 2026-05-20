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


namespace App\ViewDefinitions\LegacyHandler\Subpanel;


use App\FieldDefinitions\Service\FieldDefinitionsProviderInterface;
use App\ViewDefinitions\LegacyHandler\SubpanelButtonMapperInterface;
use BeanFactory;

class CreateButtonMapper implements SubpanelButtonMapperInterface
{
    /**
     * @var FieldDefinitionsProviderInterface
     */
    protected $fieldDefinitionsProvider;

    /**
     * SubpanelTopCreateButtonMapper constructor.
     * @param FieldDefinitionsProviderInterface $fieldDefinitionsProvider
     */
    public function __construct(FieldDefinitionsProviderInterface $fieldDefinitionsProvider)
    {
        $this->fieldDefinitionsProvider = $fieldDefinitionsProvider;
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return 'SubPanelTopButtonQuickCreate';
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
    public function map(
        string $parentModule,
        array  $subpanel,
        array  $button,
        array  $parentVardefs
    ): array
    {

        $params = [];
        $additionalFields = [];

        $relationshipName = $this->getRelationshipName($subpanel, $parentVardefs);

        $subpanelModuleFieldDefs = $this->fieldDefinitionsProvider->getVardef($subpanel['module']);
        $subpanelModuleVardefs = $subpanelModuleFieldDefs->getVardef();

        $this->addRelateFields($button, $subpanelModuleVardefs, $relationshipName, $additionalFields);

        $params['target_module'] = $subpanel['module'];

        $bean = BeanFactory::newBean($parentModule);

        if ($bean && $bean->object_name) {
            $parentBeanName = strtolower($bean->object_name);
            $parentNameField = $parentBeanName . '_name';
            $parentIdField = $parentBeanName . '_id';
            $additionalFields[$parentIdField] = 'id';

            // include duplicate value for the parent id having a different param key derived from the meta relationship
            // find more description with function definition
            $subpanelModule = $subpanelModuleFieldDefs->getId();
            $this->includeParentIdFieldName($additionalFields, $subpanelModule, $parentVardefs);

            if (isset($parentVardefs['name'])) {
                $additionalFields[$parentNameField] = 'name';
                $additionalFields['return_name'] = 'name';
            }
        }

        $additionalFields[$relationshipName . '_name'] = 'name';
        $relationshipIdName = $subpanelModuleVardefs[$relationshipName . '_name']['id_name'] ?? '';
        if (!empty($relationshipIdName)) {
            $additionalFields[$relationshipIdName] = 'id';
        }

        $params['return_module'] = $parentModule;

        $additionalFields['return_id'] = 'id';
        if (isset($subpanel['action'])) {
            $params['return_action'] = $subpanel['action'];
        }

        if (!empty($relationshipName)) {
            $params['return_relationship'] = $relationshipName;
        }

        if ($relationshipName === 'activities' && $parentModule === 'Contacts') {
            $params['return_relationship'] = $parentModule;
        }

        if ($parentModule === 'Contacts' && isset($bean->account_id) && isset($bean->account_name)) {
            $params['parent_type'] = 'Accounts';
            $additionalFields['parent_name'] = 'account_name.name';
            $additionalFields['account_name'] = 'account_name.name';
            $additionalFields['parent_id'] = 'account_id';
            $additionalFields['account_id'] = 'account_id';
        } else {
            $params['parent_type'] = $parentModule; //to legacy;
            $additionalFields['parent_name'] = 'name';
            $additionalFields['parent_id'] = 'id';
        }

        if ($subpanel['module'] === 'contracts') {
            //set variables to account name, or parent account name
            if ($parentModule === 'Account') {
                $additionalFields['contract_account_id'] = 'id';
                $additionalFields['contract_account'] = 'name';
            } elseif ($parentModule === 'AOS_Quotes') {
                $additionalFields['contract_account_id'] = 'billing_account_id';
                $additionalFields['contract_account'] = 'billing_account_name.name';
            } elseif ($parentModule === 'Contacts') {
                $additionalFields['contract_account_id'] = 'account_id';
                $additionalFields['contract_account'] = 'account_name.name';
            } else {
                $additionalFields['contract_account_id'] = 'account_id';
                $additionalFields['contract_account'] = 'account_name.name';
            }
        }

        //Rename keys per vardefs
        if ($parentModule === 'AOS_Product_Categories') {

            if (array_key_exists('aos_product_categories_id', $additionalFields)) {
                $additionalFields['parent_category_id'] = $additionalFields['aos_product_categories_id'];
                unset($additionalFields['aos_product_categories_id']);
            }

            if (array_key_exists('aos_product_categories_name', $additionalFields)) {
                $additionalFields['parent_category_name'] = $additionalFields['aos_product_categories_name'];
                unset($additionalFields['aos_product_categories_name']);
            }
        }

        $button['additionalFields'] = $additionalFields;
        $button['extraParams'] = $params;

        return $button;
    }

    /**
     * @param $subpanel
     * @param $vardefs
     * @return string
     */
    public function getRelationshipName($subpanel, $vardefs): string
    {
        $relationship_name = $subpanel['module'] ?? '';
        $dataSource = $subpanel['get_subpanel_data'] ?? '';
        if (!empty($dataSource)) {
            $relationship_name = $dataSource;
            //Try to set the relationship name to the real relationship, not the link.
            if (!empty($vardefs[$dataSource]) && !empty($vardefs[$dataSource]['relationship'])) {
                $relationship_name = $vardefs[$dataSource]['relationship'];
            }
        }

        return $relationship_name;
    }

    /**
     * @param array $button
     * @param array|null $subpanelModuleVardefs
     * @param string $relationshipName
     * @param array $additionalFields
     * @return array
     */
    protected function addRelateFields(
        array  &$button,
        ?array $subpanelModuleVardefs,
        string $relationshipName,
        array  &$additionalFields
    ): void
    {
        $relateFields = $this->getRelateFields($subpanelModuleVardefs, $relationshipName);
        $button['relateFields'] = $relateFields;

        foreach ($relateFields as $key => $field) {
            $additionalFields[$key] = $field;
        }
    }

    /**
     * Get relate fields
     * @param array|null $subpanelModuleVardefs
     * @param string $relationship
     * @return array
     */
    protected function getRelateFields(?array $subpanelModuleVardefs, string $relationship): array
    {
        $link = $this->getRelationshipLink($subpanelModuleVardefs, $relationship);

        $relateFields = [];
        foreach ($subpanelModuleVardefs as $key => $fieldDef) {
            $relLink = $fieldDef['link'] ?? '';

            if ($relLink !== $link) {
                continue;
            }

            $name = $fieldDef['name'] ?? '';
            $rname = $fieldDef['rname'] ?? '';
            $idName = $fieldDef['id_name'] ?? '';

            if ($name === '' || $rname === '') {
                continue;
            }

            $relateFields[$name] = $rname;

            if ($idName !== '') {
                $relateFields[$idName] = 'id';
            }

        }

        return $relateFields;
    }

    /**
     * Get Relationship name
     * @param array|null $subpanelModuleVardefs
     * @param string $relationship
     * @return string
     */
    protected function getRelationshipLink(?array $subpanelModuleVardefs, string $relationship): string
    {
        $link = '';
        foreach ($subpanelModuleVardefs as $key => $fieldDef) {
            $rel = $fieldDef['relationship'] ?? '';
            $type = $fieldDef['type'] ?? '';
            $name = $fieldDef['name'] ?? '';

            if ($type === 'link' && $rel === $relationship && $name !== '') {
                $link = $name;
                break;
            }
        }

        return $link;
    }

    /**
     * Get Relationship name
     * @param array $additionalFields
     * @param string $subpanelModule
     * @param array $parentVardefs
     * @return void
     * @desc
     * inject parent id field from metadata relationship
     * the existing derivation of parent id gives wrong value sometimes e.g. for target list as a parent module,
     * it derives targetlist_id
     * but the right one is target_list-id(available in relationship metadata)
     * the existing code is kept as it is for now, to avoid any regression issue
     *
     * Sample Relationship Metadata for accounts subpanel
     * please note that the below relationship definition is injected on vardefs by a mapper
     * called LinkFieldDefinitionMapper and not available by default on vardefs
     *
     * Array
     * (
     * [side] => rhs
     * [related_id] => related_id
     * [type] => many-to-many
     * [parent_id] => prospect_list_id
     * )
     */
    private function includeParentIdFieldName(array &$additionalFields, string $subpanelModule, array $parentVardefs): void
    {

        if (empty($parentVardefs[$subpanelModule])) {
            return;
        }

        if (empty($parentVardefs[$subpanelModule]['relationshipMetadata'])) {
            return;
        }

        if (empty($parentVardefs[$subpanelModule]['relationshipMetadata']['parent_id'])) {
            return;
        }

        $parentIdField = $parentVardefs[$subpanelModule]['relationshipMetadata']['parent_id'];
        $additionalFields[$parentIdField] = 'id';
    }

}
