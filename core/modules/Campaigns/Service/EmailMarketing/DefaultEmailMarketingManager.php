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

namespace App\Module\Campaigns\Service\EmailMarketing;

use App\Data\Entity\Record;
use App\Data\LegacyHandler\PreparedStatementHandler;
use App\Data\Service\RecordProviderInterface;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;

class DefaultEmailMarketingManager implements EmailMarketingManagerInterface
{

    public function __construct(
        protected RecordProviderInterface $recordProvider,
        protected PreparedStatementHandler $preparedStatementHandler,
        protected LoggerInterface $logger
    ) {
    }

    public function getRecord(string $emailMarketingId): Record
    {
        return $this->recordProvider->getRecord('EmailMarketing', $emailMarketingId);
    }

    public function isSending(Record $record): bool
    {
        $status = $record->getAttributes()['status'] ?? '';
        return $status === 'sending';
    }

    public function setSent(Record $record): void
    {
        $this->updateStatus($record, 'sent');
    }

    public function setSending(Record $record): void
    {
        $this->updateStatus($record, 'sending');
    }

    public function isQueueingFinished(Record $record): bool
    {
        $queueingStatus = $record->getAttributes()['queueing_status'] ?? '';
        return $queueingStatus === 'finished';
    }

    public function isQueueingInProgress(Record $record): bool
    {
        $queueingStatus = $record->getAttributes()['queueing_status'] ?? '';
        return $queueingStatus === 'in_progress';
    }

    public function setQueueingFinished(Record $record): void
    {
        $this->updateQueueingStatus($record, 'finished');
    }

    public function setQueueingInProgress(Record $record): void
    {
        $this->updateQueueingStatus($record, 'in_progress');
    }

    public function updateStatus(Record $record, string $status): void
    {
        $attributes = $record->getAttributes();

        $attributes['status'] = $status;

        $record->setAttributes($attributes);

        $this->recordProvider->saveRecord($record);
    }

    public function updateQueueingStatus(Record $record, string $queueingStatus): void
    {
        $attributes = $record->getAttributes();

        $attributes['queueing_status'] = $queueingStatus;

        $record->setAttributes($attributes);

        $this->recordProvider->saveRecord($record);
    }

    public function getRecordsForQueueProcessing(int $batchSize, array $options = []): array
    {
        $table = $this->getTable();
        $emailMarketingRecords = [];

        try {
            $queryBuilder = $this->preparedStatementHandler->createQueryBuilder();
            $emailMarketingRecords = $queryBuilder
                ->select('*')
                ->from($table, 'mkt')
                ->where('mkt.deleted = 0')
                ->andWhere("mkt.status IN ('scheduled', 'pending_send', 'sending')")
                ->andWhere('mkt.date_start <= NOW()')
                ->orderBy('mkt.date_start', 'ASC')
                ->setMaxResults($batchSize)
                ->executeQuery()
                ->fetchAllAssociative();
        } catch (Exception $e) {
            $this->logger->error(
                sprintf(
                    'DefaultEmailMarketingManager::getRecordsForQueueProcessing | Error fetching records from table "%s": %s',
                    $table,
                    $e->getMessage()
                ),
                ['exception' => $e]
            );
        }

        return $emailMarketingRecords;
    }

    public function getRecordsForQueueing(int $batchSize, array $options = []): array
    {
        $table = $this->getTable();
        $emailMarketingRecords = [];
        try {
            $queryBuilder = $this->preparedStatementHandler->createQueryBuilder();
            $emailMarketingRecords = $queryBuilder
                ->select('*')
                ->from($table, 'mkt')
                ->where('mkt.deleted = 0')
                ->andWhere("mkt.status IN ('scheduled', 'pending_send', 'sending')")
                ->orderBy('mkt.date_start', 'ASC')
                ->setMaxResults($batchSize)
                ->executeQuery()
                ->fetchAllAssociative();
        } catch (Exception $e) {
            $this->logger->error(
                sprintf(
                    'DefaultEmailMarketingManager::getRecordsForQueueing | Error fetching records for queueing from table "%s": %s',
                    $table,
                    $e->getMessage()
                ),
                ['exception' => $e]
            );
        }
        return $emailMarketingRecords;
    }

    protected function getTable(): string
    {
        return $this->recordProvider->getTable('email-marketing');
    }
}
