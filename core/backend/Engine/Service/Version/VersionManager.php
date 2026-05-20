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

namespace App\Engine\Service\Version;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;

class VersionManager implements VersionManagerInterface
{
    private const CATEGORY = 'info';
    private const NAME = 'suitecrm_version';

    public function __construct(
        protected Connection $connection,
        protected string $projectDir,
        protected LoggerInterface $logger
    ) {
    }

    public function getInstalledVersion(): string
    {
        try {
            $result = $this->connection->createQueryBuilder()
                ->select('value')
                ->from('config')
                ->where('category = :category')
                ->andWhere('name = :name')
                ->setParameter('category', self::CATEGORY)
                ->setParameter('name', self::NAME)
                ->setMaxResults(1)
                ->fetchAssociative();

            if ($result && isset($result['value'])) {
                return $result['value'];
            }
        } catch (Exception $e) {
            $this->logger->error('Failed to read SuiteCRM version from config: ' . $e->getMessage());
        }

        return $this->getVersionFromFile();
    }

    public function saveVersion(string $version, $connection = null): void
    {
        if ($connection === null) {
            $connection = $this->connection;
        }

        $this->upsertVersion($connection, $version);
    }

    public function getVersionFromFile(): string
    {
        $versionFile = $this->projectDir . '/VERSION';

        return trim((string) file_get_contents($versionFile));
    }


    protected function upsertVersion(Connection $connection, string $version): void
    {
        try {
            $result = $connection->createQueryBuilder()
                ->select('COUNT(*) as count')
                ->from('config')
                ->where('category = :category')
                ->andWhere('name = :name')
                ->setParameter('category', self::CATEGORY)
                ->setParameter('name', self::NAME)
                ->fetchAssociative();

            $exists = isset($result['count']) && $result['count'] > 0;

            if ($exists) {
                $connection->createQueryBuilder()
                    ->update('config')
                    ->set('value', ':value')
                    ->where('category = :category')
                    ->andWhere('name = :name')
                    ->setParameter('category', self::CATEGORY)
                    ->setParameter('name', self::NAME)
                    ->setParameter('value', $version)
                    ->executeQuery();

                return;
            }

            $connection->createQueryBuilder()
                ->insert('config')
                ->values(
                    [
                        'category' => ':category',
                        'name' => ':name',
                        'value' => ':value',
                    ]
                )
                ->setParameter('category', self::CATEGORY)
                ->setParameter('name', self::NAME)
                ->setParameter('value', $version)
                ->executeQuery();
        } catch (Exception $e) {
            $this->logger->error('Failed to save SuiteCRM version to config: ' . $e->getMessage());
        }
    }
}
