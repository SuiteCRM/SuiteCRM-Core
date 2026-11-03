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

namespace App\Module\Employees\Service\SaveHandlers;

use App\Data\Entity\Record;
use App\Data\Service\Record\RecordSaveHandlers\RecordSaveHandlerInterface;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\Module\Employees\LegacyHandler\EmployeeAccessHandler;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Save of an employee, as the legacy Employees Save.php: only the users allowed by the legacy
 * page (EmployeeAccessHandler) save, and only the fields of the legacy form. The other fields of
 * the user record keep their value (status, user name, administrator...), and the job title,
 * department and employee status too for non admins.
 */
class EmployeeSaveHandler implements RecordSaveHandlerInterface
{
    public function __construct(protected EmployeeAccessHandler $access)
    {
    }

    public function getKey(): string
    {
        return EmployeeAccessHandler::HANDLER_KEY;
    }

    public function getModule(): string
    {
        return 'employees';
    }

    public function getModes(): array
    {
        return ['before-save'];
    }

    public function getOrder(): int
    {
        return 0;
    }

    public function run(?Record $previousVersion, Record $inputRecord, ?Record $savedRecord, FieldDefinition $fieldDefinition): void
    {
        $id = $previousVersion !== null ? (string)($previousVersion->getId() ?? '') : '';
        $access = $this->access->getAccess($id);
        if (($id === '' && !$access['create']) || ($id !== '' && !$access['edit'])) {
            throw new AccessDeniedHttpException('Not allowed to save this employee');
        }

        $saved = array_flip($this->access->getSavedFields());
        $previous = $previousVersion !== null ? ($previousVersion->getAttributes() ?? []) : [];
        $attributes = $inputRecord->getAttributes() ?? [];
        foreach (array_keys($attributes) as $field) {
            if (isset($saved[$field]) || in_array($field, ['id', 'module_name', 'object_name'], true)) {
                continue;
            }

            if (array_key_exists($field, $previous)) {
                $attributes[$field] = $previous[$field];
            } else {
                unset($attributes[$field]);
            }
        }

        $inputRecord->setAttributes($attributes);
    }
}
