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

namespace App\Module\ACLRoles\Service\SaveHandlers;

use App\Data\Entity\Record;
use App\Data\Service\Record\RecordSaveHandlers\RecordFieldSaveHandlerInterface;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\Module\ACLRoles\LegacyHandler\AclRoleMatrixHandler;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Saves the access matrix of the record view of a role (not a field of the
 * bean): taken out of the record before its save, its changed levels are saved after it
 * (AclRoleMatrixHandler), for a new role too (copy of a role). Admins only, as the legacy module. Saves
 * without the matrix leave the levels as they are.
 */
class AclRoleMatrixSaveHandler implements RecordFieldSaveHandlerInterface
{
    /**
     * Matrix of the role being saved, null when the save has none.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $pending = null;

    public function __construct(protected AclRoleMatrixHandler $matrix)
    {
    }

    public function getField(): string
    {
        return AclRoleMatrixHandler::FIELD;
    }

    public function getKey(): string
    {
        return AclRoleMatrixHandler::HANDLER_KEY;
    }

    public function getModule(): string
    {
        return 'acl-roles';
    }

    public function getModes(): array
    {
        return ['before-save', 'after-save'];
    }

    public function getOrder(): int
    {
        return 0;
    }

    public function replaceDefaultTypeMapper(): bool
    {
        return false;
    }

    public function run(?Record $previousVersion, Record $inputRecord, ?Record $savedRecord, FieldDefinition $fieldDefinition): void
    {
        if ($savedRecord === null) {
            $this->pending = null;
            $attributes = $inputRecord->getAttributes() ?? [];
            if (!array_key_exists($this->getField(), $attributes)) {
                return;
            }

            $value = $attributes[$this->getField()];
            unset($attributes[$this->getField()]);
            $inputRecord->setAttributes($attributes);
            if (!is_array($value) || $value === []) {
                return;
            }

            if (!$this->matrix->isAllowed()) {
                throw new AccessDeniedHttpException('Not allowed to change the access of this role');
            }
            $this->pending = $value;

            return;
        }

        if ($this->pending === null || empty($savedRecord->getId())) {
            return;
        }

        $this->matrix->save((string)$savedRecord->getId(), $this->pending);
        $this->pending = null;
    }
}
