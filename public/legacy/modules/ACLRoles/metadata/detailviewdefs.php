<?php
// The layout of the record view (the legacy pages use their own templates): the name and the
// description, then the access matrix (acl-matrix field, AclRoleFieldDefinitionMapper).
$viewdefs['ACLRoles']['DetailView'] = array(
    'templateMeta' => array(
        'form' => array(
            'buttons' => array('EDIT', 'DUPLICATE', 'DELETE'),
        ),
        'maxColumns' => '2',
        'widths' => array(
            array('label' => '10', 'field' => '30'),
            array('label' => '10', 'field' => '30'),
        ),
        'useTabs' => false,
        'tabDefs' => array(
            'DEFAULT' => array(
                'newTab' => false,
                'panelDefault' => 'expanded',
            ),
        ),
    ),
    'panels' => array(
        'default' => array(
            array('name', ''),
            array('description'),
            array('access_matrix'),
        ),
    ),
);
