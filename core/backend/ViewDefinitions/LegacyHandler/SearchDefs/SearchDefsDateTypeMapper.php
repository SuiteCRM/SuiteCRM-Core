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

namespace App\ViewDefinitions\LegacyHandler\SearchDefs;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\ViewDefinitions\Entity\ViewDefinition;
use App\ViewDefinitions\LegacyHandler\ViewDefinitionMapperInterface;

class SearchDefsDateTypeMapper implements ViewDefinitionMapperInterface
{

    /**
     * SearchDefsDateTypeMapperMapper constructor.
     */
    public function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return 'search-defs-date-mapper';
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
        $this->mapListViewSearchDefs($definition);
        $this->mapSubPanelSearchDefs($definition);
    }

    /**
     * @param string $type
     * @param array $field
     * @return array
     */
    public function mapField(string $type, array $field): array
    {

        $field['type'] = 'composite';
        $field['fieldDefinition']['layout'] = ['operator', 'target', 'start', 'end'];
        $field['fieldDefinition']['display'] = 'inline';

        $dateActiveOnAttributes = [];
        $dateActiveOnAttributes[$field['name']] = [
            'operator' => [
                '=',
                'not_equal',
                'greater_than',
                'less_than'
            ]
        ];

        $startActiveOnAttributes = [];
        $startActiveOnAttributes[$field['name']] = [
            'operator' => [
                'between',
            ]
        ];

        $endActiveOnAttributes = [];
        $endActiveOnAttributes[$field['name']] = [
            'operator' => [
                'between',
            ]
        ];

        $field['fieldDefinition']['attributeFields'] = [
            'operator' => [
                'name' => 'operator',
                'type' => 'enum',
                'vname' => 'LBL_OPERATOR',
                'options' => $field['options'] ?? 'date_range_search_dom',
                'default' => '=',
                'defaultValueModes' => ['filter', 'edit']
            ],
            'target' => [
                'name' => 'target',
                'type' => $type,
                'display' => 'none',
                'logic' => [
                    'display' => [
                        'key' => 'displayType',
                        'modes' => ['edit', 'detail', 'create', 'filter'],
                        'params' => [
                            'attributeDependencies' => [
                                [
                                    'field' => $field['name'],
                                    'attribute' => 'operator'
                                ],
                            ],
                            'targetDisplayType' => 'default',
                            'activeOnAttributes' => $dateActiveOnAttributes
                        ]
                    ],
                ]
            ],
            'start' => [
                'name' => 'start',
                'type' => $type,
                'vname' => 'LBL_START',
                'showLabel' => ['*'],
                'display' => 'none',
                'logic' => [
                    'display' => [
                        'key' => 'displayType',
                        'modes' => ['edit', 'detail', 'create', 'filter'],
                        'params' => [
                            'attributeDependencies' => [
                                [
                                    'field' => $field['name'],
                                    'attribute' => 'operator'
                                ],
                            ],
                            'targetDisplayType' => 'default',
                            'activeOnAttributes' => $startActiveOnAttributes
                        ]
                    ]
                ]
            ],
            'end' => [
                'name' => 'end',
                'type' => $type,
                'vname' => 'LBL_END',
                'labelKey' => 'LBL_END',
                'showLabel' => ['*'],
                'display' => 'none',
                'logic' => [
                    'display' => [
                        'key' => 'displayType',
                        'modes' => ['edit', 'detail', 'create', 'filter'],
                        'params' => [
                            'attributeDependencies' => [
                                [
                                    'field' => $field['name'],
                                    'attribute' => 'operator'
                                ],
                            ],
                            'targetDisplayType' => 'default',
                            'activeOnAttributes' => $endActiveOnAttributes
                        ]
                    ]
                ]
            ],
        ];

        return $field;
    }

    /**
     * @param ViewDefinition $definition
     * @return void
     */
    public function mapListViewSearchDefs(ViewDefinition $definition): void
    {
        $search = $definition->getSearch() ?? [];
        $layout = $search['layout'] ?? [];
        $advanced = $layout['advanced'] ?? [];

        if (empty($advanced)) {
            return;
        }

        foreach ($advanced as $fieldKey => $field) {
            $type = $field['type'] ?? '';

            if ($type !== 'date' && $type !== 'datetime') {
                continue;
            }

            $enableRangeSearch = $field['enable_range_search'] ?? $field['fieldDefinition']['enable_range_search'] ?? false;

            if ($enableRangeSearch === 0 || $enableRangeSearch === false || $enableRangeSearch === '0' || $enableRangeSearch === 'false') {
                continue;
            }

            $field = $this->mapField($type, $field);

            $search['layout']['advanced'][$fieldKey] = $field;
        }

        $definition->setSearch($search);
    }

    /**
     * @param ViewDefinition $definition
     * @return void
     */
    public function mapSubPanelSearchDefs(ViewDefinition $definition): void
    {
        $subpanel = $definition->getSubPanel() ?? [];
        if (empty($subpanel)){
            return;
        }
        foreach ($subpanel as $subpanelKey => $def) {
            if (empty($def['searchdefs'])){
                continue;
            }

            foreach ($def['searchdefs'] as $fieldKey => $field){
                $type = $field['type'] ?? '';

                if ($type !== 'date' && $type !== 'datetime') {
                    continue;
                }

               $field = $this->mapField($type, $field);
               $subpanel[$subpanelKey]['searchdefs'][$fieldKey] = $field;
            }

        }

        $definition->setSubPanel($subpanel);
    }
}
