<?php
$viewdefs ['OutboundEmailAccounts'] = [
    'DetailView' => [
        'templateMeta' => [
            'form' => [
                'buttons' => [
                    'EDIT',
                    'DELETE',
                    [
                        'customCode' => '
                            {if $fields.type.value === "user" && $fields.created_by.value == $current_user_id}
                            <input title="{$MOD.LBL_SET_AS_DEFAULT_BUTTON}"
                                   type="button"
                                   class="button"
                                   id="set-as-default-outbound"
                                   onClick="document.location.href=\'index.php?module=OutboundEmailAccounts&action=SetDefault&record={$fields.id.value}&return_module=OutboundEmailAccounts&return_action=DetailView&return_id={$fields.id.value}\';window.parent.postMessage(\'cache-reload\');"
                                   name="button" value="{$MOD.LBL_SET_AS_DEFAULT_BUTTON}" />
                           {/if}
                        '
                    ]
                ],
            ],
            'maxColumns' => '2',
            'widths' => [
                [
                    'label' => '10',
                    'field' => '30',
                ],
                [
                    'label' => '10',
                    'field' => '30',
                ],
            ],
            'useTabs' => false,
            'tabDefs' => [
                'DEFAULT' => [
                    'newTab' => false,
                    'panelDefault' => 'expanded',
                ],
                'LBL_EDITVIEW_PANEL1' => [
                    'newTab' => false,
                    'panelDefault' => 'expanded',
                ],
            ],
            'preForm' => '
                <script type="text/javascript">
                    {literal}var userService = function() { return { isAdmin: function() { return {/literal}{if $is_admin}true{else}false{/if}{literal};}}}();{/literal}
                    {suite_combinescripts
                        files="modules/OutboundEmailAccounts/js/fields.js,
                               modules/OutboundEmailAccounts/js/owner_toggle.js,
                               modules/OutboundEmailAccounts/js/panel_toggle.js,
                               modules/OutboundEmailAccounts/js/auth_type_fields_toggle.js"}
                </script>
            ',
        ],
        'panels' => [
            'default' => [
                [
                    'name',
                    'is_default'
                ],
                [
                    'type',
                    ''
                ],
                [
                    'owner_name',
                ],
            ],
            'lbl_connection_configuration' => [
                [
                    'auth_type',
                    'mail_smtpuser',
                ],
                [
                    'mail_smtpserver',
                    'external_oauth_connection_name',
                ],
                [
                    'mail_smtpssl',
                    '',
                ],
                [
                    'mail_smtpport',
                    ''
                ],
            ],
            'lbl_outbound_configuration' => [
                [
                    'smtp_from_name',
                    'reply_to_name'
                ],
                [
                    'smtp_from_addr',
                    'reply_to_addr'
                ],
                [
                    'signature',
                    ''
                ]
            ],
        ],
    ],
];

$viewdefs['OutboundEmailAccounts']['DetailView']['templateMeta']['tabDefs']['LBL_CONNECTION_CONFIGURATION']['displayLogic'] = [
    'hide-by-type' => [
        'key' => 'displayType',
        'modes' => ['detail'],
        'params' => [
            'fieldDependencies' => ['type'],
            'activeOnFields' => ['type' => ['system-override']],
        ],
    ],
];

$viewdefs['OutboundEmailAccounts']['DetailView']['recordActions'] = [
    'actions' => [
        'outbound-email-edit' => [
            'key' => 'outbound-email-edit',
            'labelKey' => 'LBL_EDIT_BUTTON',
            'priority' => 100,
            'modes' => ['detail'],
            'acl' => ['edit'],
        ],
        'outbound-email-set-default' => [
            'key' => 'outbound-email-set-default',
            'labelKey' => 'LBL_SET_AS_DEFAULT_BUTTON',
            'priority' => 110,
            'modes' => ['detail'],
            'acl' => ['view'],
        ],
    ],
    // SuiteCRM 8 record view: edited in the account editor; no Duplicate in the legacy detail view.
    'exclude' => ['edit', 'duplicate'],
];
