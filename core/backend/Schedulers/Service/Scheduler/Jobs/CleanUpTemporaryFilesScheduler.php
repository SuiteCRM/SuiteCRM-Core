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

namespace App\Schedulers\Service\Scheduler\Jobs;

use App\Data\LegacyHandler\PreparedStatementHandler;
use App\MediaObjects\Repository\DefaultMediaObjectManager;
use App\Schedulers\Service\SchedulerInterface;
use App\SystemConfig\Service\SystemConfigProviderInterface;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;

class CleanUpTemporaryFilesScheduler implements SchedulerInterface
{
    public const SCHEDULER_KEY = 'scheduler::clean-up-temporary-files';

    public function __construct(
        protected DefaultMediaObjectManager $defaultMediaObjectManager,
        protected SystemConfigProviderInterface $systemConfigProvider,
        protected PreparedStatementHandler $preparedStatementHandler,
        protected LoggerInterface $logger,
    )
    {
    }

    public function getHandlerKey(): string
    {
        return self::SCHEDULER_KEY;
    }

    public function getKey(): string
    {
        return self::SCHEDULER_KEY;
    }

    public function run(): bool
    {
        $tableTypeMap = [
            'archived-documents' => 'archived_documents_media_objects',
            'private-documents' => 'private_documents_media_objects',
            'private-images' => 'private_images_media_objects',
            'public-documents' => 'public_documents_media_objects',
            'public-images' => 'public_images_media_objects'
        ];

        $batch = (int)($this->systemConfigProvider->getSystemConfig('max_temp_file_batch_per_table')?->getValue());

        if (empty($batch) || !is_numeric($batch)) {
            $batch = 50;
        }

        $lifetime = strtoupper($this->systemConfigProvider->getSystemConfig('max_temp_file_lifetime')?->getValue()) ?? '72 HOUR';

        if (str_ends_with($lifetime, 'S')) {
            $lifetime = rtrim($lifetime, 'S');
        }

        if (!preg_match('/^\d+\s+(HOUR|DAY|MINUTE|SECOND)$/', $lifetime)) {
            $this->logger->error('Invalid lifetime format: ' . $lifetime . '. Using default 72 HOUR.');
            $lifetime = '72 HOUR';
        }

        foreach ($tableTypeMap as $type => $table) {
            $this->removeTempFiles($table, $type, $batch, $lifetime);
            $this->logger->info('Temporary files cleanup completed for table: ' . $table);
        }

        return true;
    }

    /**
     * @param string $table
     * @param string $type
     * @param string $batch
     * @param string $lifetime
     * @return void
     */
    protected function removeTempFiles(string $table, string $type, string $batch, string $lifetime): void
    {
        $this->logger->info('Getting temporary files for table: ' . $table);

        $queryBuilder = $this->preparedStatementHandler->createQueryBuilder();

        $queryBuilder->select('*')
            ->addSelect(":type as type")
            ->from($table)
            ->where('temporary = 1')
            ->andWhere('date_entered < :expire_before')
            ->orderBy('date_entered', 'ASC')
            ->setMaxResults((int)$batch)
            ->setParameter('type', $type)
            ->setParameter('expire_before', (new \DateTimeImmutable("-$lifetime"))->format('Y-m-d H:i:s'));

        $records = [];

        try {
            $records = $queryBuilder->fetchAllAssociative();
        } catch (Exception $e) {
            $this->logger->error('Unable to retrieve media objects Error: ' . $e->getMessage());
        }

        foreach ($records as $record) {
            $type = $record['type'];
            $id = $record['id'];

            $mediaObject = $this->defaultMediaObjectManager->getMediaObject($type, $id);

            if ($mediaObject === null) {
                $this->logger->error('Unable to get media object with id:' . $id);
                continue;
            }

            $this->defaultMediaObjectManager->deleteMediaObject($type, $mediaObject);
        }
    }
}
