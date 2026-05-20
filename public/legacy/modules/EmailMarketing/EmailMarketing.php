<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}
/**
 *
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2013 SugarCRM Inc.
 *
 * SuiteCRM is an extension to SugarCRM Community Edition developed by SalesAgility Ltd.
 * Copyright (C) 2011 - 2018 SalesAgility Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo and "Supercharged by SuiteCRM" logo. If the display of the logos is not
 * reasonably feasible for technical reasons, the Appropriate Legal Notices must
 * display the words "Powered by SugarCRM" and "Supercharged by SuiteCRM".
 */


#[\AllowDynamicProperties]
class EmailMarketing extends SugarBean
{
    public $field_name_map;

    public $id;
    public $deleted;
    public $date_entered;
    public $date_modified;
    public $modified_user_id;
    public $created_by;
    public $name;
    public $from_addr;
    public $from_name;
    public $reply_to_name;
    public $reply_to_addr;
    public $date_start;
    public $time_start;
    public $template_id;
    public $campaign_id;
    public $all_prospect_lists;
    public $status;
    public $queueing_status;
    public $inbound_email_id;
    public $outbound_email_id;
    public $prospectlists;
    public $duplicate;

    public $table_name = 'email_marketing';
    public $object_name = 'EmailMarketing';
    public $module_dir = 'EmailMarketing';

    public $new_schema = true;
    public $log_entries;
    public $queueitems;

    public function __construct()
    {
        parent::__construct();
    }


    public function save($check_notify = false)
    {
        global $current_user;

        $date_start = trim($this->date_start ?? '');
        $time_start = trim($this->time_start ?? '');
        if ($time_start && strpos($date_start, $time_start) === false) {
            $this->date_start = "$date_start $time_start";
            $this->time_start = '';
        }

        $userTimeZone = $current_user->getPreference('timezone');

        if (empty($userTimeZone)) {
            return parent::save($check_notify);
        }

        $timedate = TimeDate::getInstance();

        $timeZone = new DateTimeZone($userTimeZone);

        if ($dateTime = DateTime::createFromFormat($current_user->getPreference('datef') . ' ' . $current_user->getPreference('timef'), $this->date_start, $timeZone)) {
            $dateStart = $timedate->asDb($dateTime);
            $this->date_start = $dateStart;
        }


        return parent::save($check_notify);
    }


    public function get_summary_text()
    {
        return $this->name;
    }

    public function create_export_query($order_by, $where)
    {
        return $this->create_new_list_query($order_by, $where);
    }

    public function get_list_view_data()
    {
        $temp_array = $this->get_list_view_array();

        if (!isset($temp_array['ID'])) {
            LoggerManager::getLogger()->warn('EmailMarketing get list view data error: list view array has not ID.');
            $id = null;
        } else {
            $id = $temp_array['ID'];
        }


        if (!isset($temp_array['ID'])) {
            LoggerManager::getLogger()->warn('EmailMarketing get list view data error: list view array has not Template ID.');
            $template_id = null;
        } else {
            $template_id = $temp_array['TEMPLATE_ID'];
        }


        //mode is set by schedule.php from campaigns module.
        if (!isset($this->mode) || empty($this->mode) || $this->mode!='test') {
            $this->mode='rest';
        }

        if ($temp_array['ALL_PROSPECT_LISTS']==1) {
            $query="SELECT name from prospect_lists ";
            $query.=" INNER JOIN prospect_list_campaigns plc ON plc.prospect_list_id = prospect_lists.id";
            $query.=" WHERE plc.campaign_id='{$temp_array['CAMPAIGN_ID']}'";
            $query.=" AND prospect_lists.deleted=0";
            $query.=" AND plc.deleted=0";
            if ($this->mode=='test') {
                $query.=" AND prospect_lists.list_type='test'";
            } else {
                $query.=" AND prospect_lists.list_type!='test'";
            }
        } else {
            $query="SELECT name from prospect_lists ";
            $query.=" INNER JOIN email_marketing_prospect_lists empl ON empl.prospect_list_id = prospect_lists.id";
            $query.=" WHERE empl.email_marketing_id='{$id}'";
            $query.=" AND prospect_lists.deleted=0";
            $query.=" AND empl.deleted=0";
            if ($this->mode=='test') {
                $query.=" AND prospect_lists.list_type='test'";
            } else {
                $query.=" AND prospect_lists.list_type!='test'";
            }
        }
        $res = $this->db->query($query);
        while (($row = $this->db->fetchByAssoc($res)) != null) {
            if (!empty($temp_array['PROSPECT_LIST_NAME'])) {
                $temp_array['PROSPECT_LIST_NAME'].="<BR>";
            }
            $temp_array['PROSPECT_LIST_NAME'].=$row['name'];
        }
        if ($this->isCampaignDetailView()) {
            $temp_array = $this->makeCampaignWizardEditLink($temp_array);
        }
        return $temp_array;
    }

    private function isCampaignDetailView()
    {
        $module = isset($_REQUEST['module']) ? $_REQUEST['module'] : null;
        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
        $isCampaignDetailView = $module = 'Campaigns' && $action == 'DetailView';
        return $isCampaignDetailView;
    }

    private function makeCampaignWizardEditLink($tempArray)
    {
        $campaignId = $_REQUEST['record'];
        $link = 'index.php?action=WizardMarketing&module=Campaigns&return_module=Campaigns&return_action=WizardHome&return_id='.$campaignId.'&campaign_id='.$campaignId.'&marketing_id='.$this->id.'&func=editEmailMarketing';
        if (!empty($tempArray['NAME'])) {
            $tempArray['NAME'] = '<a href="' . $link . '">' . $tempArray['NAME'] . '</a>';
        }
        if (!empty($tempArray['TEMPLATE_NAME'])) {
            $tempArray['TEMPLATE_NAME'] = '<a href="' . $link . '">' . $tempArray['TEMPLATE_NAME'] . '</a>';
        }
        return $tempArray;
    }

    public function bean_implements($interface)
    {
        switch ($interface) {
            case 'ACL':return true;
        }
        return false;
    }

    public function get_all_prospect_lists()
    {
        $query="select prospect_lists.* from prospect_lists ";
        $query.=" left join prospect_list_campaigns on prospect_list_campaigns.prospect_list_id=prospect_lists.id";
        $query.=" where prospect_list_campaigns.deleted=0";
        $query.=" and prospect_list_campaigns.campaign_id='$this->campaign_id'";
        $query.=" and prospect_lists.deleted=0";
        $query.=" and prospect_lists.list_type not like 'exempt%'";

        return $query;
    }

    public function validate()
    {
        global $mod_strings;
        $errors = array();
        if (!$this->name) {
            $errors['name'] = isset($mod_strings['LBL_NO_MARKETING_NAME']) ? $mod_strings['LBL_NO_MARKETING_NAME'] : 'LBL_NO_MARKETING_NAME';
        }
        if (!$this->inbound_email_id) {
            $errors['inbound_email_id'] = isset($mod_strings['LBL_NO_INBOUND_EMAIL_SELECTED']) ? $mod_strings['LBL_NO_INBOUND_EMAIL_SELECTED'] : 'LBL_NO_INBOUND_EMAIL_SELECTED';
        }
        if (!$this->date_start) {
            $errors['date_start'] = isset($mod_strings['LBL_NO_DATE_START']) ? $mod_strings['LBL_NO_DATE_START'] : 'LBL_NO_DATE_START';
        }
        if (!$this->from_name) {
            $errors['from_name'] = isset($mod_strings['LBL_NO_FROM_NAME']) ? $mod_strings['LBL_NO_FROM_NAME'] : 'LBL_NO_FROM_NAME';
        }
        if (!$this->from_addr) { // TODO test for valid email address
            $errors['from_addr'] = isset($mod_strings['LBL_NO_FROM_ADDR_OR_INVALID']) ? $mod_strings['LBL_NO_FROM_ADDR_OR_INVALID'] : 'LBL_NO_FROM_ADDR_OR_INVALID';
        }
        return $errors;
    }

    public function fill_in_additional_list_fields(): void {
        $this->created_by_name = get_assigned_user_name($this->created_by);
    }

    public function trackLogLeads()
    {
        $this->load_relationship('log_entries');
        $query_array = $this->log_entries->getQuery(true);

        $query_array['select'] = 'SELECT campaign_log.* ';
        $query_array['where'] .= " AND activity_type = 'lead' AND archived = 0 AND target_id IS NOT NULL";

        return implode(' ', $query_array);
    }

    public function trackLogEntries($type = array())
    {
        global $db;
        $args = func_get_args();

        $this->load_relationship('log_entries');
        $query_array = $this->log_entries->getQuery(true);


        foreach ($args as $arg) {
            if (isset($arg['group_by'])) {
                $query_array['group_by'] = $arg['group_by'];
            }
        }

        $params = $type['params'] ?? [];

        if (empty($type)) {
            $type[0] = 'targeted';
        }

        if (is_array($type[0])) {
            $type = $type[0];
        }

        $mkt_id = $this->db->quote($this->id);
        $query_array['select'] = "SELECT campaign_log.*, campaign_log.more_information as recipient_email";

        if (!empty($params['selectFields'])){
            foreach ($params['selectFields'] as $field) {
                $query_array['select'] .= ', ' . $field;
            }
        }

        if (!empty($params['join'])) {
            $query_array['from'] .= ' ' . $params['join'];
        }

        $query_array['where'] .= " AND campaign_log.archived=0 ";

        $typeClauses = [];
        foreach ($type as $item) {
            if (is_array($item)){
                continue;
            }
            $typeClauses[] = " activity_type like '" . $db->quote($item) . "%'";
        }

        if (!empty($typeClauses)) {
            $query_array['where'] .= " AND (" . implode(" OR ", $typeClauses) . ") ";
        }

        if (isset($query_array['group_by'])) {
            $group_by = str_replace("campaign_log", "cl", (string)$query_array['group_by']);
            $join_where = str_replace("campaign_log", "cl", $query_array['where']);
            $query_array['from'] .= " INNER JOIN (select min(id) as id from campaign_log cl $join_where GROUP BY $group_by  ) secondary
					on campaign_log.id = secondary.id	";
            unset($query_array['group_by']);
        }

        return (implode(" ", $query_array));
    }

    public function getQueueItems(...$args)
    {
        $mkt_id = $this->db->quote($this->id);

        $this->load_relationship('queueitems');
        $query_array = $this->queueitems->getQuery(true);

        foreach ($args as $arg) {

            if (isset($arg['group_by'])) {
                $query_array['group_by'] = $arg['group_by'];
            }
        }

        $query_array['where'] .= " AND marketing_id ='$mkt_id' ";

        $man = BeanFactory::newBean('EmailMan');

        $listQuery = $man->create_queue_items_query('', str_replace(array("WHERE", "where"), "", (string)$query_array['where']), null, $query_array);

        return $listQuery;
    }

    public function mark_deleted($id): void
    {
        $id = $this->db->quote($id);
        $query = "UPDATE campaign_log SET deleted = '1' WHERE marketing_id = '" . $id . "'";
        $this->db->query($query);
        parent::mark_deleted($id);
    }
}
