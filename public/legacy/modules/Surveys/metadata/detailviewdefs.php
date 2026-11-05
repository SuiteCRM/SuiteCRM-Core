<?php
$module_name = 'Surveys';
$viewdefs [$module_name] = array(
    'DetailView' => array(
        'templateMeta' => array(
            'form'                => array(
                'buttons' => array(
                    0 => 'EDIT',
                    1 => 'DUPLICATE',
                    2 => 'DELETE',
                    3 => 'FIND_DUPLICATES',
                    4 => array(
                        'customCode' => '<input type="submit" class="button" onClick="var _form = document.getElementById(\'formDetailView\');_form.action.value=\'reports\';SUGAR.ajaxUI.submitForm(_form);" value="{$MOD.LBL_VIEW_SURVEY_REPORTS}">',
                    ),
                ),
            ),
            'maxColumns'          => '2',
            'widths'              => array(
                0 => array(
                    'label' => '10',
                    'field' => '30',
                ),
                1 => array(
                    'label' => '10',
                    'field' => '30',
                ),
            ),
            'useTabs'             => false,
            'tabDefs'             => array(
                'DEFAULT' => array(
                    'newTab'       => false,
                    'panelDefault' => 'expanded',
                ),
            ),
            'syncDetailEditViews' => true,
        ),
        'panels'       => array(
            'default' => array(
                0 => array(
                    0 => 'name',
                    1 => 'assigned_user_name',
                ),
                1 => array(
                    0 => 'survey_url_display',
                ),
                2 => array(
                    0 => array(
                        'name'   => 'status',
                        'studio' => 'visible',
                        'label'  => 'LBL_STATUS',
                    ),
                    1 => '',
                ),
                3 => array(
                    0 => 'description',
                ),
                4 => array(
                    0 => 'survey_questions_display',
                ),
                5 => array(
                    0 => 'submit_text',
                    1 => 'satisfied_text',
                ),
                6 => array(
                    0 => 'neither_text',
                    1 => 'dissatisfied_text',
                ),
            ),
        ),
        // SuiteCRM 8 record view: the URL and the questions of the survey under the details (the
        // record view drops the HTML fields of the legacy page: SurveyViewDefinitionMapper), View
        // Survey Reports, and Duplicate with the questions.
        'bottomWidgets' => array(
            0 => array(
                'type'    => 'survey-questions',
                'modes'   => array('detail'),
                'options' => array(),
            ),
        ),
        'recordActions' => array(
            'exclude' => array('duplicate'),
            'actions' => array(
                'survey-reports' => array(
                    'key'      => 'survey-reports',
                    'labelKey' => 'LBL_VIEW_SURVEY_REPORTS',
                    'modes'    => array('detail'),
                    'acl'      => array('view'),
                ),
                'survey-duplicate' => array(
                    'key'          => 'survey-duplicate',
                    'labelKey'     => 'LBL_DUPLICATE_BUTTON',
                    'asyncProcess' => true,
                    'modes'        => array('detail'),
                    'acl'          => array('edit'),
                ),
            ),
        ),
    ),
);
