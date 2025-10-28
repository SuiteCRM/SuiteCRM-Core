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

namespace App\Module\Campaigns\Service\Email\Queue;

use App\Authentication\LegacyHandler\UserHandler;
use App\Data\LegacyHandler\PreparedStatementHandler;
use App\DateTime\LegacyHandler\DateTimeHandler;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;

class DefaultEmailQueueManager implements EmailQueueManagerInterface
{
    public function __construct(
        protected PreparedStatementHandler $preparedStatementHandler,
        protected LoggerInterface $logger,
        protected UserHandler $userHandler,
        protected DateTimeHandler $dateTimeHandler
    ) {
    }


    public function addToQueue(
        string $campaignId,
        string $emailMarketingId,
        string $targetListId,
        string $targetId,
        string $targetType,
        string $targetEmail,
        string $sendDate
    ): void {
        $timedate = $this->dateTimeHandler->getDateTime();
        $user = $this->userHandler->getCurrentUser();
        $queryBuilder = $this->preparedStatementHandler->createQueryBuilder();

        $queryBuilder->insert('emailman')
                     ->values(
                         [
                             'date_entered' => '?',
                             'user_id' => '?',
                             'campaign_id' => '?',
                             'marketing_id' => '?',
                             'list_id' => '?',
                             'related_id' => '?',
                             'related_type' => '?',
                             'send_date_time' => '?',
                             'more_information' => '?'
                         ]
                     )
                     ->setParameter(0, $timedate->nowDb())
                     ->setParameter(1, $user?->id)
                     ->setParameter(2, $campaignId)
                     ->setParameter(3, $emailMarketingId)
                     ->setParameter(4, $targetListId)
                     ->setParameter(5, $targetId)
                     ->setParameter(6, $targetType)
                     ->setParameter(7, $sendDate)
                     ->setParameter(8, $targetEmail);


        try {
            $queryBuilder->executeStatement();
            $this->logger->debug('Campaigns:DefaultQueueManager::addToQueue - Added to queue | email marketing id - ' . $emailMarketingId . ' | target - ' . $targetType . '-' . $targetId , [
                'emailMarketingId' => $emailMarketingId,
                'targetId' => $targetId,
                'targetType' => $targetType,
                'campaignId' => $campaignId,
                'sendDate' => $sendDate,
            ]);
        } catch (Exception $e) {
            $this->logger->error('Campaigns:DefaultQueueManager::addToQueue unable to add to queue:  | email marketing id - ' . $emailMarketingId . ' | ' . $e->getMessage());
        }
    }

    public function deleteFromQueue(
        string $emailMarketingId,
        string $targetId,
        string $targetType,
    ): void {
        $queryBuilder = $this->preparedStatementHandler->createQueryBuilder();
        $queryBuilder->delete('emailman')
                     ->where('related_id = :related_id')
                     ->andWhere('related_type = :related_type')
                     ->andWhere('marketing_id = :marketing_id')
                     ->setParameter('related_id', $targetId)
                     ->setParameter('related_type', $targetType)
                     ->setParameter('marketing_id', $emailMarketingId);
        try {
            $queryBuilder->executeStatement();
        } catch (Exception $e) {
            $this->logger->error(
                sprintf(
                    'Campaigns:DefaultEmailQueueManager::deleteFromQueue | Unable to delete record from emailman. Related ID: %s, Marketing ID: %s, Error: %s',
                    $targetId,
                    $emailMarketingId,
                    $e->getMessage()
                )
            );
        }
    }

    public function getEntriesToSend(
        string $marketingId,
        int $batchSize,
        array $options = []
    ): array {

        $timedate = $this->dateTimeHandler->getDateTime();
        $now = $timedate->nowDb();
        $str = $timedate->fromString("-1 day")?->asDb();

        $queryBuilder = $this->preparedStatementHandler->createQueryBuilder();
        $queryBuilder->select('*')
                     ->from('emailman')
                     ->where('marketing_id = :mkt_id')
                     ->andWhere('send_date_time <= :now')
                     ->andWhere('deleted = 0')
                     ->andWhere(
                         $queryBuilder->expr()->or(
                             $queryBuilder->expr()->isNull('in_queue'),
                             $queryBuilder->expr()->eq('in_queue', '0'),
                             $queryBuilder->expr()->and(
                                 $queryBuilder->expr()->eq('in_queue', '1'),
                                 $queryBuilder->expr()->lte('in_queue_date', ':queue_date')
                             )
                         )
                     )
                     ->orderBy('send_date_time', 'ASC')
                     ->addOrderBy('user_id', 'ASC')
                     ->addOrderBy('list_id', 'ASC')
                     ->setMaxResults($batchSize)
                     ->setParameter('mkt_id', $marketingId)
                     ->setParameter('now', $now)
                     ->setParameter('queue_date', $str);

        try {
            $results = $queryBuilder->fetchAllAssociative();
        } catch (Exception $e) {
            $results = [];
            $this->logger->error('Campaigns:SendEmailScheduler::getEmailsToSend | Exception emails to send for email_marketing_id - ' . $marketingId . ' | message | ' . $e->getMessage(), ['trace' => $e->getTrace()]);
        }

        return $results;
    }

    public function getQueueEntry(
        string $emailMarketingId,
        string $targetId,
        string $targetType,
    ): array {
        $queryBuilder = $this->preparedStatementHandler->createQueryBuilder();
        $queryBuilder->select('*')
                     ->from('emailman')
                     ->where('related_id = :targetId')
                     ->andWhere('related_type = :targetType')
                     ->andWhere('marketing_id = :marketing_id')
                     ->andWhere('deleted = 0')
                     ->setMaxResults(1)
                     ->setParameter('targetId', $targetId)
                     ->setParameter('targetType', $targetType)
                     ->setParameter('marketing_id', $emailMarketingId);

        $result = [];
        try {
            $result = $queryBuilder->fetchAssociative();
        } catch (Exception $e) {
            $this->logger->error(
                sprintf(
                    'Campaigns:DefaultEmailQueueManager::getQueueEntry | Unable to retrieve record from emailman. Related ID: %s, Marketing ID: %s, Error: %s',
                    $targetId,
                    $emailMarketingId,
                    $e->getMessage()
                )
            );
        }

        return $result;
    }

    /**
     * @param mixed $id
     * @return void
     */
    public function updateSendAttempts(string $id): void
    {

        $timedate = $this->dateTimeHandler->getDateTime();
        $queryBuilder = $this->preparedStatementHandler->createQueryBuilder();

        $queryBuilder->update('emailman')
            ->set('in_queue', ':in_queue')
            ->set('send_attempts', 'send_attempts + 1')
            ->set('in_queue_date', ':in_queue_date')
            ->where('id = :id')
            ->setParameter('in_queue', '1')
            ->setParameter('in_queue_date', (new \DateTime())->format('Y-m-d H:i:s'))
            ->setParameter('id', $id);

        try {
            $queryBuilder->executeStatement();
        } catch (Exception $e) {
            $this->logger->error('DefaultEmailQueueManager::updateSendAttempts | Failed to update send attempts for ID: ' . $id . ' | Error: ' . $e->getMessage(), ['trace' => $e->getTrace()]);
        }
    }
}
