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

namespace App\Module\Employees\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;

/**
 * What the current user may do with employees, as the legacy Employees pages (the Employee bean
 * has no ACL: its record view would allow everything):
 *  - edit: admins, admins of the Employees module, and the employee himself
 *    (EmployeesViewDetail, Save.php);
 *  - create: admins and admins of the Employees module (Save.php);
 *  - delete: admins of the Users module, not on their own record (EmployeesViewDetail);
 *  - the fields saved: those of the legacy edit form (Save.php populateFromRow()), the job
 *    title, department and employee status by admins only, the email addresses and the custom
 *    fields.
 */
class EmployeeAccessHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'employee-access';

    protected const FIELDS = [
        'first_name', 'last_name', 'reports_to_id', 'reports_to_name', 'description', 'phone_home', 'phone_mobile',
        'phone_work', 'phone_other', 'phone_fax', 'address_street', 'address_city', 'address_state', 'address_country',
        'address_postalcode', 'messenger_id', 'messenger_type',
    ];

    protected const ADMIN_FIELDS = ['title', 'department', 'employee_status'];

    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * @return array{edit: bool, delete: bool, create: bool, admin: bool}
     */
    public function getAccess(string $employeeId): array
    {
        return $this->inLegacy(static function () use ($employeeId) {
            global $current_user;

            $admin = is_admin($current_user);
            $employeesAdmin = $admin || $current_user->isAdminForModule('Employees');
            $self = $employeeId !== '' && $employeeId === $current_user->id;

            return [
                'edit' => $employeesAdmin || $self,
                'delete' => !$self && $employeeId !== '' && $current_user->isAdminForModule('Users'),
                'create' => $employeesAdmin,
                'admin' => $admin,
            ];
        });
    }

    /**
     * The fields of an employee the current user may save.
     *
     * @return string[]
     */
    public function getSavedFields(): array
    {
        return $this->inLegacy(static function () {
            global $current_user;

            $fields = self::FIELDS;
            if (is_admin($current_user)) {
                $fields = array_merge($fields, self::ADMIN_FIELDS);
            }

            foreach (\BeanFactory::newBean('Employees')->field_defs as $name => $definition) {
                if (($definition['source'] ?? '') === 'custom_fields' || str_starts_with((string)$name, 'email')) {
                    $fields[] = $name;
                }
            }

            return $fields;
        });
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
