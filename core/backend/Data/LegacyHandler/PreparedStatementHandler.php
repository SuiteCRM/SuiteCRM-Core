<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2021 SuiteCRM Ltd.
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


namespace App\Data\LegacyHandler;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;

class PreparedStatementHandler
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        EntityManagerInterface $entityManager
    )
    {
        $this->entityManager = $entityManager;
    }


    /**
     * @param string $query
     * @param array $params
     * @param array $binds
     * @return mixed|false
     * @throws Exception
     */
    public function fetch(
        string $query,
        array  $params,
        array  $binds = []
    )
    {
        $stmt = $this->entityManager->getConnection()->prepare($query);

        if (!empty($binds)) {
            foreach ($binds as $bind) {
                $stmt->bindValue($bind['param'], $params[$bind['param']], $bind['type']);
            }
        }

        return $stmt->executeQuery($params)->fetchAssociative();
    }

    /**
     * @param string $query
     * @param array $params
     * @param array $binds
     * @return mixed|false
     * @throws Exception
     */
    public function fetchAll(
        string $query,
        array  $params,
        array  $binds = []
    )
    {
        $stmt = $this->entityManager->getConnection()->prepare($query);

        if (!empty($binds)) {
            foreach ($binds as $bind) {
                $stmt->bindValue($bind['param'], $params[$bind['param']], $bind['type']);
            }
        }

        return $stmt->executeQuery($params)->fetchAllAssociative();
    }

    /**
     * @throws Exception
     */
    public function update (
        string $query,
        array  $params,
        array  $binds
    ): int {
        $stmt = $this->entityManager->getConnection()->prepare($query);

        if (!empty($binds)) {
            foreach ($binds as $bind) {
                $stmt->bindValue($bind['param'], $params[$bind['param']], $bind['type']);
            }
        }
        $result =  $stmt->executeStatement();
        $this->entityManager->flush();

        return $result;
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return $this->entityManager->getConnection()->createQueryBuilder();
    }
}
