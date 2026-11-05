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
 * Same matching as the legacy ContactFormBase::checkForDuplicates:
 * first name starting with the entered one and same last name (last name only
 * when no first name is entered), or an email address used by another contact
 */
class ContactsDuplicateCheckProvider extends BaseDuplicateCheckProvider
{
    /**
     * @inheritDoc
     */
    public function getModule(): string
    {
        return 'contacts';
    }

    /**
     * @inheritDoc
     */
    public function getDuplicateIds(array $attributes, array $originalAttributes, string $recordId): array
    {
        return array_merge(
            $this->findByName($attributes, $originalAttributes, $recordId),
            $this->findByEmails($attributes, $recordId)
        );
    }

    /**
     * @param array $attributes
     * @param array $originalAttributes
     * @param string $recordId
     * @return string[]
     */
    protected function findByName(array $attributes, array $originalAttributes, string $recordId): array
    {
        if (!$this->hasChanged(['first_name', 'last_name'], $attributes, $originalAttributes, $recordId)) {
            return [];
        }

        $firstName = $this->getValue($attributes, 'first_name');
        $lastName = $this->getValue($attributes, 'last_name');

        if ($lastName === '') {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->andWhere('t.last_name = ' . $queryBuilder->createNamedParameter($lastName));

        if ($firstName !== '') {
            $this->andWhereStartsWith($queryBuilder, 't.first_name', $firstName);
        }

        return $this->fetchIds($queryBuilder, $recordId);
    }

    /**
     * @inheritDoc
     */
    protected function getTable(): string
    {
        return 'contacts';
    }

    /**
     * @inheritDoc
     */
    protected function getLegacyModule(): string
    {
        return 'Contacts';
    }
}
