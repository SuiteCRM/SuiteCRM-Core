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

namespace App\FieldDefinitions\LegacyHandler\DefaultMapper;

use App\Authentication\LegacyHandler\UserHandler;
use App\FieldDefinitions\Service\VardefConfigMapperInterface;
use App\Languages\LegacyHandler\AppListStringsHandler;

class ParentTypeDefaultMapper implements VardefConfigMapperInterface
{

    public function __construct(
        protected AppListStringsHandler $appListStringsHandler,
        protected UserHandler $userHandler,
    )
    {
    }

    public function getHandlerKey(): string
    {
        return 'parent-type-vardef-mapper';
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return 'parent-type-vardef-mapper';
    }

    /**
     * @inheritDoc
     */
    public function getModule(): string
    {
        return 'default';
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function map(array $vardefs): array
    {

        foreach ($vardefs as $fieldName => $fieldDefinition) {

            $type = $fieldDefinition['type'] ?? '';

            if ($type !== 'parent_type') {
                continue;
            }

            if (isset($fieldDefinition['default'])){
                continue;
            }

            $options = $fieldDefinition['options'] ?? '';

            if ($options === '') {
                continue;
            }

            $language = $this->userHandler->getCurrentLanguage();
            $strings = $this->appListStringsHandler->getAppListStrings($language)?->getItems();

            $list = $strings[$options] ?? [];

            if (empty($list)){
                continue;
            }

            $fieldDefinition['defaultValue'] = array_key_first($list) ?? '';
            $fieldDefinition['module'] = array_key_first($list) ?? '';

            $vardefs[$fieldName] = $fieldDefinition;
        }

        return $vardefs;
    }
}
