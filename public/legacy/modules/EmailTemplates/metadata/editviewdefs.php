<?php

$viewdefs['EmailTemplates']['EditView'] = array(
    'templateMeta' => array('maxColumns' => '2',
                            'widths' => array(
                                            array('label' => '10', 'field' => '30'),
                                            array('label' => '10', 'field' => '30')
                                            ),
    ),
 'panels' =>array(
  'default' =>
  array(
    
    array(
      'name',
      'type',
    ),
    
    array(
      
      array(
        'name' => 'description',
        'displayParams' =>
        array(
          'rows' => '1',
          'cols' => '90',
        ),
      ),
      'assigned_user_name',
    ),
    
    array(
      
      array(
        'name' => 'tracker_url',
        'fields' =>
        array(
          'tracker_url',
          'url_text',
        ),
      ),
    ),
    
    array(
      'variable_tools',
    ),

    array(
      
      array(
        'name' => 'subject',
        'displayParams' =>
        array(
          'rows' => '1',
          'cols' => '90',
        ),
      ),
    ),
    
    array(
      'text_only',
    ),
    
    array(
      
      array(
        'name' => 'body_html',
        'displayParams' =>
        array(
          'rows' => '20',
          'cols' => '100',
        ),
      ),
    ),

    array(
      
      array(
        'name' => 'plain_text_toggle',
      ),
    ),

    array(
      
      array(
        'name' => 'body',
        'displayParams' =>
        array(
          'rows' => '20',
          'cols' => '100',
        ),
      ),
    ),
    
    array(
      
      'attachments',
    ),
  ),
)


);
