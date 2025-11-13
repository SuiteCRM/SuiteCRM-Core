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

namespace App\Module\EmailTemplates\LegacyHandler;

use App\Authentication\LegacyHandler\UserHandler;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Languages\LegacyHandler\AppListStringsHandler;
use Symfony\Component\HttpFoundation\RequestStack;

class CalculateTemplateInjectorVariables extends LegacyHandler
{

    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        protected AppListStringsHandler $appListStringsHandler,
        protected UserHandler $userHandler
    )
    {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $requestStack
        );
    }

    public function getHandlerKey(): string
    {
        return 'calculate-template-injector-variables';
    }

    public function getModules(array $modules): array
    {

        $mappedModules = [];

        foreach ($modules as $key => $name) {
            $associatedModules = $this->getAssociatedModules();
            if (!isset($associatedModules[$name])) {
                $mappedModules[$name] = $name;
                continue;
            }

            foreach ($associatedModules[$name] as $associatedModule) {
                $mappedModules[$associatedModule] = $associatedModule;
            }
        }

        return $this->filterModules($mappedModules);
    }

    public function getBaseModules(): array
    {
        $moduleList = $this->getModuleList();
        return $this->filterModules($moduleList);
    }

    public function getFieldDefs(array $moduleList = []): array
    {

        $beanList = $this->getBeanList();
        $beanFiles = $this->getBeanFiles();
        $badFields = $this->getBadFields();

        $modules = [];
        $prefixes = [];

        if (empty($moduleList)) {
            $moduleList = $this->getModuleList();
        }

        foreach ($moduleList as $key => $name) {
            if (!isset($beanList[$key]) || !isset($beanFiles[$beanList[$key]])) {
                continue;
            }

            if (str_begin($key, 'AOW_')) {
                continue;
            }

            $this->init();
            require_once($beanFiles[$beanList[$key]]);

            $focus = new $beanList[$key];
            $this->close();
            $modules[$name][$key] = $focus;
            $prefixes[$key] = strtolower($focus->object_name) . '_';
            if ($focus->object_name == 'Case') {
                $prefixes[$key] = 'a' . strtolower($focus->object_name) . '_';
            }
        }

        if (array_key_exists('Contacts', $moduleList)) {
            $prefixes['Users'] = 'contact_user_';
        }

        $collection = [];
        foreach ($modules as $moduleName => $moduleBeans) {

            $collection[$moduleName] = [];
            foreach ($moduleBeans as $beanKey => $bean) {
                foreach ($bean->field_defs as $fieldName => $fieldDefinition) {
                    if ($fieldDefinition['type'] === 'assigned_user_name' || $fieldDefinition['type'] === 'link') {
                        continue;
                    }

                    if ($fieldDefinition['type'] === 'bool' || in_array($fieldDefinition['name'], $badFields)) {
                        continue;
                    }

                    $optionKey = strtolower("{$prefixes[$beanKey]}{$fieldName}");

                    if (isset($fieldDefinition['vname'])) {
                        $optionLabel = preg_replace('/:$/', "", $fieldDefinition['vname']);
                    } else {
                        $optionLabel = preg_replace('/:$/', "", $fieldDefinition['name']);
                    }

                    $dup = 1;
                    foreach ($collection[$moduleName] as $value) {
                        if ($value['name'] === $optionKey) {
                            $dup = 0;
                            break;
                        }
                    }
                    if (!$dup) {
                        continue;
                    }

                    $collection[$moduleName][] = ['name' => $optionKey, 'value' => $optionLabel];
                }
            }
        }

        return $collection;
    }

    protected function getModuleList(): array
    {
        $appListStrings = $this->getAppListStrings();

        return $appListStrings['moduleList'];
    }

    protected function getBadFields(): array
    {
        return [
            'account_description',
            'contact_id',
            'lead_id',
            'opportunity_amount',
            'opportunity_id',
            'opportunity_name',
            'opportunity_role_id',
            'opportunity_role_fields',
            'opportunity_role',
            'campaign_id',
            // User objects
            'id',
            'user_preferences',
            'accept_status',
            'user_hash',
            'authenticate_id',
            'sugar_login',
            'reports_to_id',
            'reports_to_name',
            'employee_totp_secret',
            'employee_backup_codes',
            'totp_secret',
            'backup_codes',
            'is_admin',
            'receive_notifications',
            'modified_user_id',
            'modified_by_name',
            'created_by',
            'created_by_name',
            'accept_status_id',
            'accept_status_name',
        ];
    }

    protected function getAssociatedModules(): array
    {
        return [
            'EmailMarketing' => [
                'Prospects',
                'Contacts',
                'Leads',
                'Users',
                'Accounts',
            ],
            'Contacts' => [
                'Contacts',
                'Leads',
                'Prospects',
            ],
        ];
    }

    protected function getBeanList(): array
    {
        $this->init();
        global $beanList;
        $this->close();

        return $beanList;
    }

    protected function getBeanFiles(): array
    {
        $this->init();
        global $beanFiles;
        $this->close();

        return $beanFiles;
    }

    /**
     * @param array $moduleList
     * @return array
     */
    public function filterModules(array $moduleList): array
    {
        $beanFiles = $this->getBeanFiles();
        $beanList = $this->getBeanList();

        foreach ($moduleList as $key => $name) {
            if (!isset($beanList[$key]) || !isset($beanFiles[$beanList[$key]])) {
                unset($moduleList[$key]);
                continue;
            }

            if (str_begin($key, 'AOW_')) {
                unset($moduleList[$key]);
                continue;
            }

            if (str_begin($key, 'zr2_')) {
                unset($moduleList[$key]);
            }
        }

        asort($moduleList);

        return $moduleList;
    }

    /**
     * @return array|null
     */
    public function getAppListStrings(): ?array
    {
        return $this->appListStringsHandler->getAppListStrings($this->userHandler->getCurrentLanguage())->getItems();
    }


}
