<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

$popupMeta = array(
    'moduleMain' => 'EmailMarketing',
    'varName' => 'EMAILMARKETING',
    'orderBy' => 'email_marketing.name',
    'whereClauses' => array(
        'name' => 'email_marketing.name',
        'status' => 'email_marketing.status',
        'type' => 'email_marketing.type',
    ),
    'searchInputs' => array(
        1 => 'name',
        2 => 'status',
        3 => 'type',
    ),
    'searchdefs' => array(
        'name' => array(
            'name' => 'name',
            'label' => 'LBL_NAME',
            'width' => '20%',
        ),
        'status' => array(
            'name' => 'status',
            'type' => 'enum',
            'label' => 'LBL_STATUS',
            'options' => 'email_marketing_status_dom',
            'width' => '10%',
        ),
        'type' => array(
            'name' => 'type',
            'type' => 'enum',
            'label' => 'LBL_MARKETING_TYPE',
            'options' => 'email_marketing_type_dom',
            'width' => '10%',
        ),
    ),
    'listviewdefs' => array(
        'NAME' => array(
            'width' => '35%',
            'label' => 'LBL_LIST_NAME',
            'link' => true,
            'default' => true,
            'name' => 'name',
        ),
        'DATE_START' => array(
            'width' => '20%',
            'label' => 'LBL_DATE_START',
            'default' => true,
            'name' => 'date_start',
        ),
        'STATUS' => array(
            'width' => '15%',
            'label' => 'LBL_LIST_STATUS',
            'default' => true,
            'name' => 'status',
        ),
        'TYPE' => array(
            'width' => '15%',
            'label' => 'LBL_MARKETING_TYPE',
            'default' => true,
            'name' => 'type',
        ),
    ),
);
