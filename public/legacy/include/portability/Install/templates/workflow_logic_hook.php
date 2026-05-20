<?php

if (!isset($hook_array) || !is_array($hook_array)) {
    $hook_array = array();
}

if (!isset($hook_array['after_save']) || !is_array($hook_array['after_save'])) {
    $hook_array['after_save'] = array();
}

$hook_array['after_save'][] = array(99, 'AOW_Workflow', 'modules/AOW_WorkFlow/AOW_WorkFlow.php', 'AOW_WorkFlow', 'run_bean_flows');
