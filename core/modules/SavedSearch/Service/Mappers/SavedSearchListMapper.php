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

namespace App\Module\SavedSearch\Service\Mappers;

use App\Data\Entity\Record;
use App\Data\Service\Record\EntityRecordMappers\EntityRecordMapperInterface;
use App\FieldDefinitions\Entity\FieldDefinition;
use App\Module\Service\ModuleNameMapperInterface;

/**
 * The Module and User columns of the saved filters list. The bean empties the module of a listed
 * filter (fill_in_additional_list_fields reads it from the contents, not decoded yet) and the user
 * comes as an object, shown as "[object Object]": the module is read from the contents and its
 * name given in search_module_name, the user name is given as text.
 */
class SavedSearchListMapper implements EntityRecordMapperInterface
{
    public function __construct(protected ModuleNameMapperInterface $moduleNameMapper)
    {
    }

    public function getKey(): string
    {
        return 'saved-search-list';
    }

    public function getModule(): string
    {
        return 'saved-search';
    }

    public function getModes(): array
    {
        return ['list'];
    }

    public function getOrder(): int
    {
        return 0;
    }

    public function toInternal(Record $record, FieldDefinition $fieldDefinitions): void
    {
    }

    public function toExternal(Record $record, FieldDefinition $fieldDefinitions): void
    {
        $attributes = $record->getAttributes() ?? [];

        $module = (string)($attributes['search_module'] ?? '');
        if ($module === '') {
            $module = (string)($attributes['contents']['searchModule'] ?? '');
            $attributes['search_module'] = $module;
        }
        $legacyModule = $module !== '' ? $this->moduleNameMapper->toLegacy($module) : '';
        $attributes['search_module_name'] = $GLOBALS['app_list_strings']['moduleList'][$legacyModule] ?? $legacyModule;

        if (is_array($attributes['assigned_user_name'] ?? null)) {
            $attributes['assigned_user_name'] = (string)($attributes['assigned_user_name']['user_name'] ?? '');
        }

        $record->setAttributes($attributes);
    }
}
