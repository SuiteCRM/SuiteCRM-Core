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

namespace App\ViewDefinitions\LegacyHandler\RecordView;

use App\FieldDefinitions\Entity\FieldDefinition;
use App\ViewDefinitions\Entity\ViewDefinition;
use App\ViewDefinitions\LegacyHandler\ViewDefinitionMapperInterface;

class RecordViewEmailMapper implements ViewDefinitionMapperInterface
{

    /**
     * RecordViewEmailMapper constructor.
     */
    public function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return 'record-view-email-mapper';
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
        $recordView = $definition->getRecordView() ?? [];
        $panels = $recordView['panels'] ?? [];

        if (empty($recordView) || empty($panels)) {
            return;
        }
        foreach ($panels as $panelKey => $panel) {
            foreach ($panel['rows'] as $rowKey => $row) {
                $cols = $this->mapCols($row);
                $panels[$panelKey]['rows'][$rowKey]['cols'] = $cols;
            }
        }
        $recordView['panels'] = $panels;
        $definition->setRecordView($recordView);
    }

    /**
     * @param $row
     * @return array cols
     */
    public function mapCols(array $row): array
    {
        $cols = [];
        foreach ($row['cols'] as $cellKey => $cell) {

            $type = strtolower($cell['type'] ?? '');

            if ($type !== 'email') {
                $cols[$cellKey] = $cell;
                continue;
            }

            $cell['type'] = 'line-items';
            $cell['name'] = 'email_addresses';
            $cell['fieldDefinition']['name'] = 'email_addresses';
            $cell['fieldDefinition']['module'] = 'EmailAddress';

            $isRequired = $cell['required'] ?? $cell['fieldDefinition']['required'] ?? false;

            $config = [
                'labelOnFirstLine' => true,
                'definition' => [
                    'name' => 'email-fields',
                    'vname' => 'LBL_EMAIL',
                    'type' => 'composite',
                    'layout' => ['email_address', 'primary_address', 'opt_out', 'invalid_email'],
                    'display' => 'inline',
                    'attributeFields' => [
                        'email_address' => [
                            'name' => 'email_address',
                            'type' => 'email',
                            'vname' => 'LBL_EMAIL_ADDRESS',
                            'labelKey' => 'LBL_EMAIL_ADDRESS',
                            'required' => $isRequired,
                            'valueParent' => 'record',
                            'showLabel' => ['*'],
                            'metadata' => [
                                'attributeMapping' => [
                                    'field' => 'email1',
                                    'activeOnAttributes' => [
                                        'email-fields' => [
                                            'primary_address' => [
                                                [
                                                    'operator' => 'is-equal',
                                                    'values' => ['true']
                                                ]
                                            ]
                                        ]
                                    ],
                                ]
                            ],
                        ],
                        'primary_address' => [
                            'name' => 'primary_address',
                            'type' => 'bool',
                            'vname' => 'LBL_PRIMARY',
                            'labelKey' => 'LBL_PRIMARY',
                            'valueParent' => 'record',
                            'showLabel' => ['*'],
                        ],
                        'invalid_email' => [
                            'name' => 'invalid_email',
                            'type' => 'bool',
                            'vname' => 'LBL_INVALID_EMAIL',
                            'labelKey' => 'LBL_INVALID_EMAIL',
                            'valueParent' => 'record',
                            'showLabel' => ['*'],
                        ],
                        'opt_out' => [
                            'name' => 'opt_out',
                            'type' => 'bool',
                            'vname' => 'LBL_OPT_OUT',
                            'valueParent' => 'record',
                            'showLabel' => ['*'],
                        ],
                    ],
                ]
            ];
            $cell['fieldDefinition']['lineItems'] = $config;
            $cell['fieldDefinition']['useFullColumn'] = ['xs', 'sm', 'md', 'lg', 'xl'];

            $cell['fieldDefinition']['logic'] = [
                'emailPrimarySelectLogic' => [
                    'key' => 'emailPrimarySelect',
                    'modes' => ['edit', 'create', 'massupdate'],
                    'params' => [
                        'triggerOnEvents' => [
                            'onLineItemAdd' => true,
                            'onLineItemRemove' => true
                        ]
                    ]
                ]
            ];
            $cols[$cellKey] = $cell;
        }

        return $cols;
    }

}
