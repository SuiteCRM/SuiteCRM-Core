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

namespace App\Module\Campaigns\Service\Email\Targets;

use App\Data\LegacyHandler\PreparedStatementHandler;
use Exception;
use Psr\Log\LoggerInterface;

class DefaultEmailTargetProvider implements EmailTargetProviderInterface
{
    public function __construct(
        protected PreparedStatementHandler $preparedStatementHandler,
        protected LoggerInterface $logger
    ) {
    }

    public function getTargets(string $marketingId, int $batchSize, array $options = []): array
    {
        $records = [];

        $queryBuilder = $this->preparedStatementHandler->createQueryBuilder();

        $queryBuilder
            ->select(
                [
                    'plp.related_id AS target_id',
                    'plp.related_type AS target_type',
                    'empl.email_marketing_id AS marketing_id',
                    'plp.prospect_list_id AS target_list_id'
                ]
            )
            ->from('prospect_lists_prospects', 'plp')
            ->innerJoin(
                'plp',
                'email_marketing_prospect_lists',
                'empl',
                "empl.deleted = 0
                AND empl.prospect_list_id = plp.prospect_list_id"
            )
            ->innerJoin(
                'empl',
                'prospect_lists',
                'pl',
                "pl.deleted = 0 AND pl.id = empl.prospect_list_id AND pl.list_type IN ('default', 'seed')"
            )
            // filter all that are already on email queue
            ->leftJoin(
                'plp',
                'emailman',
                'queue',
                "queue.deleted = 0
                 AND queue.related_id = plp.related_id
                 AND queue.related_type = plp.related_type
                 AND queue.marketing_id = empl.email_marketing_id"
            )
            // filter all that are already on campaign log, meaning that they were sent or were suppressed
            ->leftJoin(
                'plp',
                'campaign_log',
                'log',
                "log.deleted = 0
                AND log.target_id = plp.related_id
                AND log.target_type = plp.related_type
                AND log.marketing_id = empl.email_marketing_id"
            )
            ->where('plp.deleted = 0')
            ->andWhere('empl.email_marketing_id = :id')
            ->andWhere('queue.related_id IS NULL') // filter all that are already on email queue
            ->andWhere('log.target_id IS NULL') // filter all that are already on campaign log, meaning that they were sent or were suppressed
            ->groupBy('plp.related_type, plp.related_id, empl.email_marketing_id, plp.prospect_list_id')
            ->setMaxResults($batchSize)
            ->setParameter('id', $marketingId);

        try {
            $records = $queryBuilder->fetchAllAssociative();
        } catch (Exception $e) {
            $this->logger->error('AddEmailToQueueScheduler::getTargets Could not retrieve targets:  | email marketing id - ' . $marketingId . ' | ' . $e->getMessage());
        }

        return $records;
    }
}
