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

namespace App\Emails\Service\Validation;

use App\Data\Entity\Record;
use App\Data\LegacyHandler\PreparedStatementHandler;

class EmailValidationHelper
{

    public function __construct(protected PreparedStatementHandler $preparedStatementHandler)
    {
    }

    public function isEmailMarkedInvalid(string $email): bool
    {
        $queryBuilder = $this->preparedStatementHandler->createQueryBuilder();

        $queryBuilder->select('e.invalid_email')
                     ->from('email_addresses', 'e')
                     ->where('e.email_address_caps = :email')
                     ->setMaxResults(1)
                     ->setParameter('email', strtoupper($email)); // Ensure case-insensitive comparison


        $result = $queryBuilder->fetchOne();

        return !empty($result) && in_array($result['invalid_email'] ?? [], ['on', '1', 1, 'true', true], true);
    }

    public function hasPrimaryEmailAddress(Record $record): bool
    {
        if (!isset($record->getAttributes()['email1'])) {
            return false;
        }

        $emailAddress = trim($record->getAttributes()['email1'] ?? '');

        if (empty($emailAddress)) {
            return false;
        }

        $queryBuilder = $this->preparedStatementHandler->createQueryBuilder();
        $queryBuilder->select('email_address_id')
                     ->from('email_addr_bean_rel')
                     ->where('bean_id = :beanId')
                     ->andWhere('primary_address = 1')
                     ->andWhere('deleted = 0')
                     ->setParameter('beanId', $record->getId());

        $result = $queryBuilder->fetchOne();

        if (!empty($result)) {
            return true;
        }

        return false;
    }

    public function isValidEmailAddress(string $emailAddress): bool
    {
        $emailAddress = trim($emailAddress);
        if (empty($emailAddress)) {
            return false;
        }

        // Use PHP's built-in filter for email validation
        return filter_var($emailAddress, FILTER_VALIDATE_EMAIL) !== false;
    }
}
