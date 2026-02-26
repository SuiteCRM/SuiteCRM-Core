<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

$searchdefs['Releases'] = [
    'templateMeta' => [
        'maxColumns' => '3',
        'maxColumnsBasic' => '4',
        'widths' => ['label' => '10', 'field' => '30'],
    ],
    'layout' => [
        'basic_search' => [
            'name',
        ],
        'advanced_search' => [
            'name',
        ],
    ],
];
