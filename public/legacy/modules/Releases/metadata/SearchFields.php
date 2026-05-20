<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

$searchFields['Releases'] = [
    'name' => [
        'query_type' => 'default',
    ],
    'status' => [
        'query_type' => 'default',
        'options' => 'release_status_dom',
    ],
];
