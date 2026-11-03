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

namespace App\Module\ACLRoles\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * The access matrix of a role (legacy ACLRoles DetailView / EditAllBody.tpl) for the record view: the access level of each action of each module, read with the role
 * (ACLRole::getRoleActions) and saved as the legacy Save.php (ACLRole::setAction).
 *
 * The matrix value is {module: {action: access level}}; the modules, the actions and the
 * levels to choose from are the structure (ACLAction::setupCategoriesMatrix).
 */
class AclRoleMatrixHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'acl-role-matrix';

    public const FIELD = 'access_matrix';

    /**
     * Modules the legacy matrix does not show (EditAllBody.tpl): their levels are kept as they
     * are, and copied with the role.
     */
    protected const HIDDEN = ['Users'];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * The modules (rows, in the order of the legacy matrix), the actions (columns) with their
     * levels, the labels of all the levels and the level of a disabled module.
     */
    public function getStructure(): array
    {
        return $this->inLegacy(function () {
            global $app_list_strings, $ACLActionAccessLevels;

            $categories = $this->getRoleActions('');
            $names = \ACLAction::setupCategoriesMatrix($categories);

            $actions = [];
            foreach ($names as $name => $label) {
                $actions[$name] = ['name' => (string)$name, 'label' => $this->decode((string)$label), 'options' => []];
            }

            $modules = [];
            foreach ($categories as $category => $types) {
                if (in_array($category, self::HIDDEN, true)) {
                    continue;
                }

                $moduleActions = [];
                foreach ($types as $type) {
                    foreach ($type as $name => $action) {
                        $moduleActions[] = (string)$name;
                        if (empty($actions[$name]['options'])) {
                            $actions[$name]['options'] = $this->toOptions($action['accessOptions'] ?? []);
                        }
                    }
                }

                $modules[] = [
                    'name' => (string)$category,
                    'label' => $this->decode((string)($app_list_strings['moduleList'][$category] ?? $category)),
                    'actions' => $moduleActions,
                ];
            }

            $levels = [];
            foreach (array_keys($ACLActionAccessLevels ?? []) as $access) {
                $levels[] = ['value' => (int)$access, 'label' => $this->decode((string)\ACLAction::AccessName($access))];
            }

            return [
                'modules' => $modules,
                'actions' => array_values($actions),
                'levels' => $levels,
                'disabled' => ACL_ALLOW_DISABLED,
            ];
        });
    }

    /**
     * The levels of a role, hidden modules included; the default levels for a new role.
     *
     * @return array<string, array<string, int>>
     */
    public function load(string $roleId): array
    {
        return $this->inLegacy(function () use ($roleId) {
            $matrix = [];
            foreach ($this->getRoleActions($roleId) as $category => $types) {
                foreach ($types as $type) {
                    foreach ($type as $name => $action) {
                        $matrix[(string)$category][(string)$name] = (int)($action['aclaccess'] ?? ACL_ALLOW_DEFAULT);
                    }
                }
            }

            return $matrix;
        });
    }

    /**
     * Saves the levels of a role that changed, as the legacy Save.php: the actions of the matrix
     * (ACLRole::setAction), the caches of the front end rebuilt (rebuild_all). Unknown modules, actions
     * and levels are left out.
     *
     * @param array<string, mixed> $matrix
     * @return bool true when a level changed
     */
    public function save(string $roleId, array $matrix): bool
    {
        return $this->inLegacy(function () use ($roleId, $matrix) {
            $role = \BeanFactory::getBean('ACLRoles', $roleId);
            if (!$role || empty($role->id)) {
                return false;
            }

            $current = $this->getRoleActions($role->id);
            \ACLAction::setupCategoriesMatrix($current);

            $changed = false;
            foreach ($current as $category => $types) {
                $levels = $matrix[$category] ?? null;
                if (!is_array($levels)) {
                    continue;
                }

                foreach ($types as $type) {
                    foreach ($type as $name => $action) {
                        $access = $levels[$name] ?? null;
                        if (!is_numeric($access) || empty($action['id'])) {
                            continue;
                        }

                        $access = (int)$access;
                        if ($access === (int)($action['aclaccess'] ?? ACL_ALLOW_DEFAULT)
                            || !array_key_exists($access, $action['accessOptions'] ?? [])) {
                            continue;
                        }

                        $role->setAction($role->id, $action['id'], $access);
                        $changed = true;
                    }
                }
            }

            if ($changed) {
                require_once 'include/portability/Services/Cache/CacheManager.php';
                (new \CacheManager())->markAsNeedsUpdate('rebuild_all');
            }

            return $changed;
        });
    }

    /**
     * Whether the current user may manage the roles: admins, as the legacy module ($adminOnlyList).
     */
    public function isAllowed(): bool
    {
        return $this->inLegacy(static function () {
            global $current_user;

            return !empty($current_user) && is_admin($current_user);
        });
    }

    /**
     * @return array<string, array<string, array<string, array>>>
     */
    protected function getRoleActions(string $roleId): array
    {
        // The access levels of the actions, with the security group level (actiondefs.override.php).
        require_once 'modules/ACLActions/ACLAction.php';
        require_once 'modules/ACLRoles/ACLRole.php';

        return (new \ACLRole())->getRoleActions($roleId);
    }

    /**
     * The levels of an action in their legacy order, as a list (a map keyed by level would be
     * reordered by the front end).
     *
     * @param array<int|string, string> $options
     * @return array<int, array{value: int, label: string}>
     */
    protected function toOptions(array $options): array
    {
        $list = [];
        foreach ($options as $value => $label) {
            $list[] = ['value' => (int)$value, 'label' => $this->decode((string)$label)];
        }

        return $list;
    }

    protected function decode(string $value): string
    {
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    protected function inLegacy(callable $callback)
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
