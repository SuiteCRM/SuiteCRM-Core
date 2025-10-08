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


namespace App\Emails\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use BeanFactory;

class FilterEmailListHandler extends LegacyHandler
{
    protected const HANDLER_KEY = 'record-filter-email-list';

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * @param array $fields
     * @param $max
     * @param bool $isTest
     * @return array|null
     */
    public function getBeans(array $fields): ?array
    {
        $beans = [];

        foreach ($fields as $field) {
            $module = $field['module'];
            $value = $field['value'] ?? [];

            if ($value === null) {
                continue;
            }

            if ($module === 'ProspectLists') {
                $this->getTargets($beans, $value, $module);
                continue;
            }

            if ($module === 'Users') {
                $this->getUsers($beans, $value, $module);
                continue;
            }

            foreach ($value as $key => $item) {
                $beans['emails'][$item] = $item;
            }
        }

        return $beans;
    }

    protected function getTargets(&$beans, $value, $module): void
    {
        $values = explode(',', $value);
        foreach ($values as $key => $item) {
            $bean = BeanFactory::getBean($module, $item);

            $linkedFields = $bean->get_linked_fields();

            foreach ($linkedFields as $linkedField) {
                $name = $linkedField['name'];

                if (!isset($linkedField['metadata']['member'])){
                    continue;
                }

                $bean->load_relationship($name);
                $this->getLoadedBeans($beans, $bean, $name);
            }
        }
    }

    protected function getUsers(array &$beans, mixed $value, string $module): void
    {
        $values = explode(',', $value);
        foreach ($values as $key => $item) {
            $bean = BeanFactory::getBean($module, $item);
            $beans[$module][] = $bean;
        }
    }

    protected function getLoadedBeans(&$beans, $bean, $name): void
    {
        $loadedBeans = $bean->$name->getBeans();
        if (empty($loadedBeans)){
            return;
        }

        foreach ($loadedBeans as $loadedBean) {
            $beans[$name][] = $loadedBean;
        }
    }
}
