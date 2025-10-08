<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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

namespace App\Module\ProspectLists\Service\MultiRelate;

use App\Engine\LegacyHandler\LegacyHandler;
use BeanFactory;

class ProspectListsEmailMapper extends LegacyHandler {

    public function getHandlerKey(): string
    {
        return 'prospectlists-email-mapper';
    }

    /**
     * @param $emails
     * @param $count
     * @param $module
     * @param $value
     * @param $isTest
     * @return array|null
     */
    public function getEmailFromMultiRelate(&$emails, &$count, $module, $value, $isTest = false): ?array
    {
        $this->init();

        $this->getEmails($emails, $count, $module, $value, $isTest);

        return $emails;
    }


    /**
     * @param $emails
     * @param $count
     * @param $module
     * @param $value
     * @param $isTest
     * @return array
     */
    protected function getEmails(&$emails, &$count, $module, $value, $isTest): array {

        foreach ($value as $key => $item) {
            $id = $item['id'];
            $bean = BeanFactory::getBean($module, $id);

            $linkedFields = $bean->get_linked_fields();
            $beans = [];

            foreach ($linkedFields as $linkedField) {
                $name = $linkedField['name'];

                if (!isset($linkedField['metadata']['member'])){
                    continue;
                }

                $bean->load_relationship($name);
                $beans[$name] = $bean->$name->getBeans();
            }

            if (!empty($beans)){
                $this->getBeanEmails($beans, $emails, $count, $isTest);
            }
        }

        return $emails;
    }

    /**
     * @param array $beans
     * @param $emails
     * @param $count
     * @param $isTest
     * @return void
     */
    protected function getBeanEmails(array $beans, &$emails, &$count, $isTest): void
    {
        foreach($beans as $bean) {
            foreach ($bean as $key => $value) {
                $emails[$value->email1] = $value->email1;

                $count++;
            }
        }
    }

}
