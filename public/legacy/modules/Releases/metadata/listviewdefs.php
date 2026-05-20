<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

$listViewDefs['Releases'] = [
    'NAME' => [
        'width' => '40%',
        'label' => 'LBL_NAME',
        'link' => true,
        'default' => true,
    ],
    'STATUS' => [
        'width' => '20%',
        'label' => 'LBL_STATUS',
        'default' => true,
    ],
    'LIST_ORDER' => [
        'width' => '10%',
        'label' => 'LBL_LIST_ORDER',
        'default' => true,
    ],
];
