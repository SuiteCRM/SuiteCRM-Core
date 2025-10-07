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

namespace App\Install\Service;

use App\Data\LegacyHandler\PreparedStatementHandler;
use App\DateTime\LegacyHandler\DateTimeHandler;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use Psr\Log\LoggerInterface;

class CommandLastRunTracker {

    public function __construct(
        protected PreparedStatementHandler $preparedStatementHandler,
        protected LoggerInterface $logger,
        protected DateTimeHandler $dateTimeHandler
    )
    {
    }

    public function setLastRun(string $commandName, $user = null, $category = 'schedulers'): void
    {
        $name = 'last_run::' . $commandName;
        $value = [
          'user' => $user,
          'last_run' => time()
        ];
        $serializedValue = json_encode($value);

        $queryBuilder = $this->getInsertQuery($name, $category, $serializedValue);

        if ($this->existsOnConfig($name, $category)){
            $queryBuilder = $this->getUpdateQuery($name, $category, $serializedValue);
        }

        try {
            $queryBuilder->executeQuery();
        } catch (Exception $e) {
            $this->logger->error('Failed to set last run time for command ' . $commandName . ': ' . $e->getMessage());
        }
    }

    public function getLastRunInfo(string $commandName, $category = 'schedulers'): ?array
    {
        $queryBuilder = $this->getQueryBuilder();
        $name = 'last_run::' . $commandName;
        $queryBuilder->select('c.value')
            ->from('config', 'c')
            ->where('category = :category')
            ->andWhere('name = :name')
            ->setParameter('category', $category)
            ->setParameter('name', $name)
            ->setMaxResults(1);

        try {
            $queryBuilder->executeQuery();
            $result = $queryBuilder->fetchAssociative();
            if ($result && isset($result['value'])) {
                return json_decode($result['value'], true);
            }
        } catch (Exception $e) {
            $this->logger->error('Failed to get last run time for command ' . $commandName . ': ' . $e->getMessage());
            return null;
        }

        return null;
    }

    public function getFormattedLastRunInfo(string $commandName, string $category = 'schedulers',  string $format = 'Y-m-d H:i:s'): array
    {
        $getLastRunInfo = $this->getLastRunInfo($commandName, $category);
        if (!$getLastRunInfo || !isset($getLastRunInfo['last_run'])) {
            $getLastRunInfo['last_run'] = 'Never';
            return $getLastRunInfo;
        }


        $dateTime = new \DateTime();
        $dateTime->setTimestamp($getLastRunInfo['last_run']);
        $getLastRunInfo['last_run'] = $dateTime->format($format);
        $getLastRunInfo['last_run'] = $this->dateTimeHandler->toUserDateTime($getLastRunInfo['last_run']);
        return $getLastRunInfo;
    }

    protected function getInsertQuery($name, $category, $serializedValue): QueryBuilder
    {
        $queryBuilder = $this->getQueryBuilder();
        return $queryBuilder
            ->insert('config')
            ->values([
                'category' => ':category',
                'name' => ':name',
                'value' => ':value'
            ])
            ->setParameter('category', $category)
            ->setParameter('name', $name)
            ->setParameter('value', $serializedValue);
    }

    protected function getUpdateQuery($name, $category, $serializedValue): QueryBuilder {
        $queryBuilder = $this->getQueryBuilder();
        return $queryBuilder
            ->update('config')
            ->set('name', ':name')
            ->set('category', ':category')
            ->set('value', ':value')
            ->where('category = :category')
            ->andWhere('name = :name')
            ->setParameter('name', $name)
            ->setParameter('category', $category)
            ->setParameter('value', $serializedValue);
    }

    protected function getQueryBuilder(): QueryBuilder {
        return $this->preparedStatementHandler->createQueryBuilder();
    }

    protected function existsOnConfig(string $name, string $category): bool
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->select('COUNT(*) as count')
            ->from('config', 'c')
            ->where('category = :category')
            ->andWhere('name = :name')
            ->setParameter('category', $category)
            ->setParameter('name', $name)
            ->setMaxResults(1);

        try {
            $queryBuilder->executeQuery();
            $result = $queryBuilder->fetchAssociative();
            return isset($result['count']) && $result['count'] > 0;
        } catch (Exception $e) {
            $this->logger->error('Failed to check existence in config for ' . $name . ': ' . $e->getMessage());
            return false;
        }
    }
}
