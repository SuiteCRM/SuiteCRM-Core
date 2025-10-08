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

use App\Data\Entity\Record;
use App\Data\LegacyHandler\PreparedStatementHandler;
use App\Data\Service\RecordProviderInterface;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;

class DefaultEmailSuppressionListManager implements EmailSuppressionListManagerInterface
{
    public function __construct(
        protected PreparedStatementHandler $preparedStatementHandler,
        protected LoggerInterface $logger,
        protected RecordProviderInterface $recordProvider
    ) {
    }

    public function isOnIdSuppressionList(string $campaignId, string $emailMarketingId, string $targetId, string $targetType): bool
    {
        $records = [];

        $queryBuilder = $this->preparedStatementHandler->createQueryBuilder();

        $queryBuilder
            ->select(
                'count(plp.related_id) AS total_exclusions'
            )
            ->from('prospect_lists_prospects', 'plp')
            ->innerJoin(
                'plp',
                'prospect_list_campaigns',
                'plcp',
                "plcp.deleted = 0
                AND plcp.prospect_list_id = plp.prospect_list_id
                AND plcp.campaign_id = :campaignId"
            )
            ->innerJoin(
                'plcp',
                'prospect_lists',
                'pl',
                "pl.deleted = 0 AND pl.id = plcp.prospect_list_id AND pl.list_type = 'exempt'"
            )
            ->where('plp.deleted = 0')
            ->andWhere('plp.related_id = :targetId')
            ->andWhere('plp.related_type = :targetType')
            ->setParameter('targetId', $targetId)
            ->setParameter('targetType', $targetType)
            ->setParameter('campaignId', $campaignId);

        try {
            $records = $queryBuilder->fetchAssociative();
        } catch (Exception $e) {
            $this->logger->error('Campaigns:DefaultEmailSuppressionListManager::isOnIdSuppressionList query failed:  | email marketing id - ' . $emailMarketingId . ' | target - ' . $targetType . '-' . $targetId . ' | ' . $e->getMessage());
        }

        $count = $records['total_exclusions'] ?? 0;

        $this->logger->debug('Campaigns:DefaultEmailSuppressionListManager::isOnIdSuppressionList - suppressed count | email marketing id - ' . $emailMarketingId . ' | target - ' . $targetType . '-' . $targetId . ' | count - ' . $count);

        return $count > 0;
    }

    public function isOnEmailAddressSuppressionList(string $campaignId, string $emailMarketingId, string $targetId, string $targetType): bool
    {

        $queryBuilder = $this->preparedStatementHandler->createQueryBuilder();

        $queryBuilder
            ->select(
                'count(plp.related_id) AS total_exclusions'
            )
            ->from('prospect_lists_prospects', 'plp')
            ->innerJoin(
                'plp',
                'prospect_list_campaigns',
                'plcp',
                "plcp.deleted = 0
                AND plcp.prospect_list_id = plp.prospect_list_id
                AND plcp.campaign_id = :campaignId"
            )
            ->innerJoin(
                'plcp',
                'prospect_lists',
                'pl',
                "pl.deleted = 0 AND pl.id = plcp.prospect_list_id AND pl.list_type = 'exempt_address'"
            )
            ->innerJoin(
                'pl',
                'email_addr_bean_rel',
                'suppressed_addresses',
                "suppressed_addresses.deleted = 0
                AND suppressed_addresses.primary_address = 1
                AND suppressed_addresses.bean_id = plp.related_id
                AND suppressed_addresses.bean_module = plp.related_type"
            )
            ->innerJoin(
                'suppressed_addresses',
                'email_addr_bean_rel',
                'target_addresses',
                "target_addresses.deleted = 0
                AND target_addresses.primary_address = 1
                AND target_addresses.bean_id = :targetId
                AND target_addresses.bean_module = :targetType
                AND suppressed_addresses.email_address_id = target_addresses.email_address_id"
            )
            ->where('plp.deleted = 0')
            ->setParameter('targetId', $targetId)
            ->setParameter('targetType', $targetType)
            ->setParameter('campaignId', $campaignId);

        try {
            $records = $queryBuilder->fetchAssociative();
        } catch (Exception $e) {
            $this->logger->error('Campaigns:DefaultEmailSuppressionListManager::isOnEmailAddressSuppressionList query failed:  | email marketing id - ' . $emailMarketingId . ' | target - ' . $targetType . '-' . $targetId . ' | ' . $e->getMessage());
        }

        $count = $records['total_exclusions'] ?? 0;

        $this->logger->debug('Campaigns:DefaultEmailSuppressionListManager::isOnEmailAddressSuppressionList - suppressed count | email marketing id - ' . $emailMarketingId . ' | target - ' . $targetType . '-' . $targetId . ' | count - ' . $count);

        return $count > 0;
    }

    public function isOnDomainSuppressionList(string $campaignId, string $emailMarketingId, string $targetId, string $targetType, string $email): bool
    {
        $records = [];

        if (empty($email)) {
            return false;
        }

        $emailParts = explode('@', $email) ?? [];

        if (empty($emailParts[1]) || !is_string($emailParts[1])) {
            return false;
        }

        $emailDomain = $emailParts[1];

        $queryBuilder = $this->preparedStatementHandler->createQueryBuilder();

        $queryBuilder
            ->select(
            'count(*) AS total_exclusions'
            )
            ->from('prospect_lists', 'pl')
            ->innerJoin(
                'pl',
                'prospect_list_campaigns',
                'plcp',
                "plcp.deleted = 0
                AND plcp.prospect_list_id = pl.id
                AND plcp.campaign_id = :campaignId"
            )
            ->where('pl.deleted = 0')
            ->andWhere("pl.list_type = 'exempt_domain'")
            ->andWhere("pl.domain_name IS NOT NULL")
            ->andWhere(
                $queryBuilder->expr()->like('LOWER(pl.domain_name)', ':emailDomain')
            )
            ->setParameter('emailDomain', '%' . strtolower($emailDomain) . '%')
            ->setParameter('campaignId', $campaignId);

        try {
            $records = $queryBuilder->fetchAssociative();
        } catch (Exception $e) {
            $this->logger->error('Campaigns:DefaultEmailSuppressionListManager::isOnDomainSuppressionList query failed:  | email marketing id - ' . $emailMarketingId . ' | target - ' . $targetType . '-' . $targetId . ' | ' . $e->getMessage());
        }

        $count = $records['total_exclusions'] ?? 0;

        $this->logger->debug('Campaigns:DefaultEmailSuppressionListManager::isOnDomainSuppressionList suppressed count | email marketing id - ' . $emailMarketingId . ' | target - ' . $targetType . '-' . $targetId . ' | count - ' . $count);

        return $count > 0;
    }

    public function createCampaignUnsubscriptionList(Record $campaign): Record
    {
        $campaignName = $campaign->getAttributes()['name'] ?? '';

        $record = new Record();
        $record->setModule('prospect-lists');
        $attributes = [
            'name' => $campaignName . ' - unsubscription list',
            'list_type' => 'exempt'
        ];

        $record->setAttributes(
            $attributes
        );

        $savedRecord = $this->recordProvider->saveRecord($record);
        $this->logger->debug(
            'Campaigns:DefaultEmailSuppressionListManager::createCampaignUnsubscriptionList - Save new unsubscription list - id: ' . $savedRecord->getId(), [
                'targetListId' => $savedRecord->getId(),
                'campaignId' => $campaign->getId(),
            ]
        );

        return $savedRecord;
    }
}
