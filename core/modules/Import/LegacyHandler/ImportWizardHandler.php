<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2026 SuiteCRM Ltd.
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

namespace App\Module\Import\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * The import wizard of the modules (legacy module Import) for the SuiteCRM 8 import view. The
 * view posts the forms of the legacy steps to their actions, as the legacy wizard (the
 * file uploaded, its properties, the field mapping, the duplicate check, the import, the undo);
 * this handler gives it what the legacy pages show otherwise:
 *  - the strings of the legacy wizard and the name of the module imported into;
 *  - the records of the last import of the user (legacy view.last, its list of the records);
 *  - the target lists to add the imported targets to, and the targets added (legacy view.last,
 *    Add To Target List);
 *  - a vCard imported as a record (legacy view.importvcardsave).
 */
class ImportWizardHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'import-wizard';

    /**
     * The records of a list of the results, as the legacy list.
     */
    protected const PAGE_SIZE = 20;

    /**
     * The modules a vCard is imported into (their legacy menu: Create From vCard).
     */
    protected const VCARD_MODULES = ['Contacts', 'Leads'];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The strings of the legacy wizard and the name of the module imported into (none for the
     * import wizard of the administration).
     */
    public function getLabels(string $module): array
    {
        return $this->inLegacy(function () use ($module): array {
            global $current_language, $app_list_strings, $current_user;

            $labels = [];
            foreach (return_module_language($current_language, 'Import') as $key => $value) {
                if (is_string($value)) {
                    $labels[$key] = $this->decode($value);
                }
            }

            return [
                'labels' => $labels,
                'moduleName' => $this->decode((string)($app_list_strings['moduleList'][$module] ?? '')),
                'isAdmin' => is_admin($current_user),
            ];
        });
    }

    /**
     * The records of the last import of the user into a module, by type of record (a module also
     * creates the records of its related fields), a page of each, as the legacy results list
     * (UsersLastImport::getBeansByImport() and its list of each).
     */
    public function getImported(string $module, int $offset): array
    {
        return $this->inLegacy(function () use ($module, $offset): array {
            global $current_user, $beanList;

            $this->getImportBean($module);
            require_once 'modules/Import/UsersLastImport.php';
            $db = \DBManagerFactory::getInstance();
            $groups = [];
            foreach (\UsersLastImport::getBeansByImport($module) as $beanName) {
                $beanModule = array_search($beanName, $beanList, true);
                $seed = $beanModule ? \BeanFactory::newBean($beanModule) : null;
                if (!$seed || !$seed->ACLAccess('list')) {
                    continue;
                }

                $table = $seed->getTableName();
                $from = "FROM users_last_import u INNER JOIN $table t ON t.id = u.bean_id AND t.deleted = 0"
                    . ' WHERE u.assigned_user_id = ' . $db->quoted($current_user->id)
                    . ' AND u.import_module = ' . $db->quoted($module)
                    . ' AND u.bean_type = ' . $db->quoted($beanName)
                    . ' AND u.deleted = 0';
                $total = (int)$db->getOne("SELECT COUNT(*) $from");
                $order = isset($seed->field_defs['date_entered']) ? 't.date_entered DESC, t.id' : 't.id';
                $result = $db->limitQuery("SELECT t.id $from ORDER BY $order", max(0, $offset), self::PAGE_SIZE);

                $records = [];
                while ($row = $db->fetchByAssoc($result)) {
                    $bean = \BeanFactory::getBean($beanModule, $row['id']);
                    if ($bean && $bean->ACLAccess('view')) {
                        $records[] = ['id' => (string)$bean->id, 'name' => $this->decode((string)$bean->get_summary_text())];
                    }
                }

                $groups[] = [
                    'module' => (string)$beanModule,
                    'moduleName' => $this->decode((string)($GLOBALS['app_list_strings']['moduleList'][$beanModule] ?? $beanModule)),
                    'total' => $total,
                    'offset' => max(0, $offset),
                    'pageSize' => self::PAGE_SIZE,
                    'records' => $records,
                ];
            }

            return ['groups' => $groups];
        });
    }

    /**
     * The target lists of the user (by the start of their name) to add the imported targets to.
     */
    public function getProspectLists(string $term): array
    {
        return $this->inLegacy(function () use ($term): array {
            $seed = \BeanFactory::newBean('ProspectLists');
            if (!$seed || !$seed->ACLAccess('list')) {
                return ['lists' => []];
            }

            $db = \DBManagerFactory::getInstance();
            $where = $term !== '' ? 'prospect_lists.name LIKE ' . $db->quoted($term . '%') : '';
            $list = $seed->get_list('prospect_lists.name', $where, 0, self::PAGE_SIZE, self::PAGE_SIZE);
            $lists = [];
            foreach ($list['list'] ?? [] as $bean) {
                $lists[] = ['id' => (string)$bean->id, 'name' => $this->decode((string)$bean->name)];
            }

            return ['lists' => $lists];
        });
    }

    /**
     * Adds the targets of the last import of the user to a target list, as the legacy Add To
     * Target List (the targets of its query, view.last::_addToProspectListButton()).
     */
    public function addToProspectList(string $listId): array
    {
        return $this->inLegacy(function () use ($listId): array {
            global $current_user;

            $this->getImportBean('Prospects');
            $list = \BeanFactory::getBean('ProspectLists', $listId);
            if (!$list || empty($list->id) || !$list->ACLAccess('edit')) {
                throw new \InvalidArgumentException('Not a target list: ' . $listId);
            }

            $db = \DBManagerFactory::getInstance();
            $result = $db->query(
                'SELECT DISTINCT prospects.id FROM users_last_import, prospects'
                . ' WHERE users_last_import.assigned_user_id = ' . $db->quoted($current_user->id)
                . " AND users_last_import.bean_type = 'Prospect' AND users_last_import.bean_id = prospects.id"
                . ' AND users_last_import.deleted = 0 AND prospects.deleted = 0'
            );
            $ids = [];
            while ($row = $db->fetchByAssoc($result)) {
                $ids[] = $row['id'];
            }
            if ($ids && $list->load_relationship('prospects')) {
                $list->prospects->add($ids);
            }

            return ['count' => count($ids), 'name' => $this->decode((string)$list->name)];
        });
    }

    /**
     * A vCard imported as a record of a module, as the legacy Create From vCard
     * (ViewImportvcardsave): the record, none when the vCard has not the required values.
     */
    public function importVCard(string $module, string $content): array
    {
        return $this->inLegacy(function () use ($module, $content): array {
            if (!in_array($module, self::VCARD_MODULES, true)) {
                throw new \InvalidArgumentException('No vCard import for ' . $module);
            }
            $seed = \BeanFactory::newBean($module);
            if (!$seed || !$seed->ACLAccess('edit')) {
                throw new \InvalidArgumentException('No access to ' . $module);
            }
            $data = base64_decode($content, true);
            if ($data === false || $data === '') {
                throw new \InvalidArgumentException('No vCard');
            }

            require_once 'include/vCard.php';
            $file = tempnam(sys_get_temp_dir(), 'vcard-');
            try {
                file_put_contents($file, $data);
                $id = (new \vCard())->importVCard($file, $module);
            } finally {
                @unlink($file);
            }

            return ['id' => (string)($id ?? '')];
        });
    }

    /**
     * The module imported into, checked as the legacy wizard checks it (ImportController::loadBean()):
     * importable, Users for the admins only, the import access of the user.
     */
    protected function getImportBean(string $module): \SugarBean
    {
        global $current_user;

        $bean = \BeanFactory::newBean($module);
        if (!$bean || empty($bean->importable) || ($module === 'Users' && !is_admin($current_user))
            || ($bean->bean_implements('ACL') && !\ACLController::checkAccess($bean->module_dir, 'import', true))) {
            throw new \InvalidArgumentException('No import into ' . $module);
        }

        return $bean;
    }

    protected function decode(string $value): string
    {
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    protected function inLegacy(callable $callback): mixed
    {
        $this->init();
        $this->startLegacyApp();

        try {
            return $callback();
        } finally {
            $this->close();
        }
    }
}
