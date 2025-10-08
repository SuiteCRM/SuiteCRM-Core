<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUITECRM, SUITECRM DISCLAIMS THE
 * WARRANTY OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the display of the
 * "Supercharged by SuiteCRM" logo. If the display of the logos is not reasonably
 * feasible for technical reasons, the Appropriate Legal Notices must display
 * the words "Supercharged by SuiteCRM".
 */
namespace App\Process\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

class PortalUserActivator extends LegacyHandler
{
    public const PROCESS_TYPE = 'portal-user-activator';

    public function getHandlerKey(): string
    {
        return self::PROCESS_TYPE;
    }

    public function switchPortalUserStatus(string $contact_id, string $failLabel, string $successLabel, bool $activate): string
    {
        $this->init();

        global $sugar_config, $mod_strings;

        $msg = '';

        $action = $activate ? 'enable_user' : 'disable_user';
        require_once 'modules/AOP_Case_Updates/util.php';
        if (!isAOPEnabled()) {
            return $mod_strings['LBL_AOP_DISABLED'];
        }


        $contact = \BeanFactory::getBean('Contacts', $contact_id);

        if (
            array_key_exists("aop", $sugar_config) &&
            array_key_exists("joomla_url", $sugar_config['aop']) &&
            !empty($contact) &&
            property_exists($contact, 'joomla_account_id') &&
            $contact->joomla_account_id !== null
        ) {
            $portalURL = $sugar_config['aop']['joomla_url'];
            $apiEndpoint = $portalURL . '/index.php?option=com_advancedopenportal&task='
                . $action . '&sug=' . $contact->id
                . '&uid=' . $contact->joomla_account_id;

            $apiResponse = file_get_contents($apiEndpoint);
            $decodedResponse = json_decode($apiResponse);

            if (empty($decodedResponse) || !$decodedResponse->success) {
                $msg = $decodedResponse->error ?: $mod_strings[$failLabel];
            } else {
                $msg = $mod_strings[$successLabel];
                $contact->portal_account_disabled = !$activate;
                $contact->save();
            }
        }

        $this->close();

        return $msg;
    }

}
