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

namespace App\Module\Documents\LegacyHandler;

use App\Data\LegacyHandler\PreparedStatementHandler;
use Doctrine\DBAL\Query\QueryBuilder;

class BaseDocumentsManager implements DocumentsManagerInterface
{
    public function __construct(
        protected PreparedStatementHandler $preparedStatementHandler,
    )
    {
    }

    public function increaseRevisionNumber(string $revision): string
    {
        $revisionParts = explode('.', $revision);
        $revisionParts[count($revisionParts) - 1] = (int)$revisionParts[count($revisionParts) - 1] + 1;
        return implode('.', $revisionParts);
    }


    public function getLatestRevision(string $documentId): string
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->select('revision')
            ->from('document_revisions')
            ->where('document_id = :document_id')
            ->setParameter('document_id', $documentId)
            ->orderBy('date_entered', 'DESC')
            ->setMaxResults(1);

        return $queryBuilder->executeQuery()->fetchOne();
    }


    public function getLatestRevisionId(string $documentId): string
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->select('id')
            ->from('document_revisions')
            ->where('document_id = :document_id')
            ->setParameter('document_id', $documentId)
            ->orderBy('date_entered', 'DESC')
            ->setMaxResults(1);

        return $queryBuilder->executeQuery()->fetchOne();
    }


    protected function createQueryBuilder(): QueryBuilder
    {
        return $this->preparedStatementHandler->createQueryBuilder();
    }
}
