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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $containerConfig) {
    $containerConfig->parameters()->set('drafts', [
            'modalConfig' => [
                'detached' => true,
                'module' => 'emails',
                'keepMinimizableFalse' => true,
                'container' => '#drafts-container',
                'recordModalOptions' => [
                    'labelKey' => 'LBL_DRAFTS',
                    'dynamicTitleKey' => '',
                    'closeOnOutsideClick' => true,
                    'titleClass' => 'drafts-modal-title',
                    'descriptionLabelKey' => '',
                    'dynamicDescriptionKey' => '',
                    'dynamicDescriptionContext' => '',
                    'headerClass' => 'drafts-header',
                    'modalHeaderActionKlass' => 'drafts-modal-actions float-left',
                    'bodyClass' => '',
                    'footerClass' => '',
                    'headerActions' => [
                        'actions' => [
                            [
                                'key' => 'dismiss-all-drafts',
                                'labelKey' => 'LBL_DISMISS_ALL',
                                'klass' => ['btn btn-sm'],
                                'asyncProcess' => true,
                                'params' => [
                                    'expanded' => true,
                                    'disableOnRun' => true,
                                    'inlineConfirmation' => true,
                                    'confirmationLabel' => 'LBL_DISMISS_INLINE_CONFIRM'
                                ],
                                'modes' => ['detail', 'edit', 'create', 'list'],
                                'acl' => ['delete']
                            ],
                        ]
                    ],
                    'wrapperClass' => 'drafts-modal-wrapper',
                ],
            ],
            'recordThreadConfig' => [
                'closeOnLoad' => true,
                'recordThreadOptions' => [
                    'autoRefresh' => false,
                    'module' => 'emails',
                    'class' => 'drafts-modal',
                    'maxListHeight' => 396,
                    'direction' => 'desc',
                    'loadMorePosition' => 'bottom',
                    'filters' => [
                        'orderBy' => 'date_modified',
                        'sortOrder' => 'desc',
                        'preset' => [
                            'type' => 'drafts',
                        ],
                    ],
                    'create' => null,
                    'item' => [
                        'collapsible' => false,
                        'collapseLimit' => 200,
                        'itemClass' => 'draft-item',
                        'containerClass' => 'containerClass',
                        'fields' => [
                            'type' => [
                                'name' => 'type',
                                'type' => 'enum',
                            ],
                            'name' => [
                                'name' => 'name',
                                'type' => 'varchar',
                            ],
                            'date_modified' => [
                                'name' => 'date_modified',
                                'type' => 'datetime',
                            ],
                            'to_addrs_names' => [
                                'name' => 'to_addrs_names',
                                'type' => 'relate',
                            ],
                        ],
                        'layout' => [
                            'body' => [
                                'class' => 'itemContentClass',
                                'rows' => [
                                    [
                                        'class' => 'draft-column-header',
                                        'align' => 'start',
                                        'cols' => [
                                            [
                                                'field' => [
                                                    'name' => 'module_name',
                                                    'type' => 'icon',
                                                ],
                                                'labelDisplay' => 'none',
                                                'hideIfEmpty' => false,
                                                'class' => 'font-weight-bold draft-column draft-module-icon',
                                            ],
                                            [
                                                'field' => [
                                                    'name' => 'to_icon_type',
                                                    'type' => 'icon',
                                                ],
                                                'labelDisplay' => 'none',
                                                'hideIfEmpty' => true,
                                                'class' => 'small pl-2 draft-column draft-to-icon',
                                            ],
                                            [
                                                'field' => [
                                                    'name' => 'to_addrs_names',
                                                    'dynamicLabelKey' => 'LBL_DRAFT_ITEM_TO',
                                                    'emptyDynamicLabelKey' => 'LBL_DRAFT_ITEM_TO_EMPTY',
                                                    'type' => 'relate',
                                                ],
                                                'labelClass' => 'm-0',
                                                'labelDisplay' => 'inline',
                                                'display' => 'none',
                                                'hideIfEmpty' => false,
                                                'class' => 'draft-to pl-1 draft-column',
                                            ],
                                            [
                                                'actionSlot' => 'true',
                                                'class' => 'draft-item-buttons',
                                            ],
                                        ],
                                    ],
                                    [
                                        'cols' => [
                                            [
                                                'field' => [
                                                    'name' => 'name',
                                                ],
                                                'labelDisplay' => 'none',
                                                'labelClass' => 'm-0',
                                                'display' => 'readonly',
                                                'hideIfEmpty' => false,
                                                'class' => 'drafts-name',
                                            ],
                                        ],
                                    ],
                                    [
                                        'cols' => [
                                            [
                                                'field' => [
                                                    'name' => 'date_modified',
                                                    'dynamicLabelKey' => 'LBL_DRAFT_ITEM_LAST_MODIFIED',
                                                    'type' => 'datetime',
                                                ],
                                                'labelClass' => 'm-0',
                                                'labelDisplay' => 'inline',
                                                'display' => 'none',
                                                'hideIfEmpty' => true,
                                                'class' => 'small drafts-date-modified',
                                            ],
                                        ],
                                    ],
                                ],
                            ], 'actions' => [
                                [
                                    'key' => 'open-draft',
                                    'icon' => 'no-fill-pencil',
                                    'titleKey' => 'LBL_OPEN_DRAFT',
                                    'asyncProcess' => true,
                                    'klass' => ['btn fill-complementary pr-1 fill-hover-complementary-light border-0 btn-sm p-0 m-0'],
                                    'modes' => ['detail', 'edit', 'list'],
                                    'acl' => [
                                        'edit'
                                    ],
                                ],
                                [
                                    'key' => 'dismiss-record',
                                    'icon' => 'trash',
                                    'titleKey' => 'LBL_DELETE',
                                    'asyncProcess' => true,
                                    'params' => [
                                        'errorLabel' => 'LBL_UNABLE_TO_DELETE_DRAFT',
                                        'successLabel' => 'LBL_DRAFT_DELETED_SUCCESSFULLY',
                                    ],
                                    'klass' => [
                                        'btn fill-complementary fill-hover-complementary-light border-0 btn-sm p-0 m-0'
                                    ],
                                    'modes' => [
                                        'detail', 'edit', 'list'
                                    ],
                                    'acl' => ['delete'],
                                ],
                            ],
                        ],
                    ],
                    'pageSize' => 10,
                ],
            ]
        ]
    );
};
