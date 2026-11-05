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

namespace App\Data\Service\Record\DuplicateCheck;

/**
 * Same matching as the legacy AccountFormBase::checkForDuplicates:
 * name starting with the entered one and, when a billing or shipping city is
 * entered, billing or shipping city starting with it
 */
class AccountsDuplicateCheckProvider extends BaseDuplicateCheckProvider
{
    protected const CITY_FIELDS = ['billing_address_city', 'shipping_address_city'];

    /**
     * @inheritDoc
     */
    public function getModule(): string
    {
        return 'accounts';
    }

    /**
     * @inheritDoc
     */
    public function getDuplicateIds(array $attributes, array $originalAttributes, string $recordId): array
    {
        $fields = array_merge(['name'], self::CITY_FIELDS);
        if (!$this->hasChanged($fields, $attributes, $originalAttributes, $recordId)) {
            return [];
        }

        $name = $this->getValue($attributes, 'name');
        if ($name === '') {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder();
        $this->andWhereStartsWith($queryBuilder, 't.name', $name);

        $cityConditions = [];
        foreach (self::CITY_FIELDS as $field) {
            $city = $this->getValue($attributes, $field);
            if ($city !== '') {
                $cityConditions[] = $this->startsWith($queryBuilder, 't.' . $field, $city);
            }
        }

        if (!empty($cityConditions)) {
            $queryBuilder->andWhere($queryBuilder->expr()->or(...$cityConditions));
        }

        return $this->fetchIds($queryBuilder, $recordId);
    }

    /**
     * @inheritDoc
     */
    protected function getTable(): string
    {
        return 'accounts';
    }

    /**
     * @inheritDoc
     */
    protected function getLegacyModule(): string
    {
        return 'Accounts';
    }
}
