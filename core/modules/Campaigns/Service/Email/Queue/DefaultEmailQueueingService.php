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

use App\Data\Entity\Record;
use App\Data\LegacyHandler\PreparedStatementHandler;
use App\Data\Service\RecordProviderInterface;
use App\Module\Campaigns\Service\Email\Log\EmailCampaignLogManagerInterface;
use App\Module\Campaigns\Service\Email\Targets\EmailTargetProviderInterface;
use App\Module\Campaigns\Service\Email\Targets\EmailTargetValidatorManager;
use App\Module\Campaigns\Service\Email\Targets\Validation\ValidationFeedback;
use App\Module\Campaigns\Service\EmailMarketing\EmailMarketingManagerInterface;
use App\SystemConfig\LegacyHandler\SystemConfigHandler;
use App\SystemConfig\Service\SettingsProviderInterface;
use Exception;
use Psr\Log\LoggerInterface;

class DefaultEmailQueueingService implements EmailQueueingServiceInterface
{

    public function __construct(
        protected PreparedStatementHandler $preparedStatementHandler,
        protected LoggerInterface $logger,
        protected RecordProviderInterface $recordProvider,
        protected SystemConfigHandler $systemConfigHandler,
        protected SettingsProviderInterface $settingsProvider,
        protected EmailQueueManagerInterface $queueManager,
        protected EmailTargetProviderInterface $targetProvider,
        protected EmailTargetValidatorManager $targetValidatorManager,
        protected EmailCampaignLogManagerInterface $campaignLogManager,
        protected EmailMarketingManagerInterface $emailMarketingManager
    ) {
    }

    public function queueEmails(array $options = []): void
    {
        $emailMarketingRecords = $this->getRecordsForQueueing();

        foreach ($emailMarketingRecords as $emailMarketing) {
            $emailMarketingId = $emailMarketing['id'];
            $campaignId = $emailMarketing['campaign_id'];
            $sendDate = $emailMarketing['date_start'];

            $targets = $this->getTargets($emailMarketingId);
            $emRecord = $this->getEmailMarketingRecord($emailMarketingId);

            if ($emRecord === null) {
                continue;
            }

            if (empty($targets)) {
                $this->setQueueingFinished($emailMarketingId, $emRecord);
                continue;
            }

            $isQueueingInProgress = $this->emailMarketingManager->isQueueingInProgress($emRecord);
            if (!$isQueueingInProgress) {
                $this->setQueueingInProgress($emailMarketingId, $emRecord);
            }

            foreach ($targets as $target) {
                $targetType = $target['target_type'] ?? '';
                $targetId = $target['target_id'] ?? '';
                $targetListId = $target['target_list_id'] ?? '';

                $targetRecord = $this->getTargetRecord($target);

                if ($targetRecord === null) {
                    $this->handleException($campaignId, $emailMarketingId, 'blocked-target-not-found', $targetListId, $targetId, $targetType);
                    continue;
                }

                $feedback = $this->validateTarget($targetRecord, $emRecord, $campaignId, $targetListId);

                if ($feedback === null) {
                    $this->handleException($campaignId, $emailMarketingId, 'blocked-validation-exception', $targetListId, $targetId, $targetType);
                    continue;
                }

                if (!$feedback->isSuccess()) {
                    $this->handleInvalidTarget($campaignId, $emailMarketingId, $targetRecord, $feedback, $targetListId, $targetId, $targetType);
                    continue;
                }

                $this->addToQueue($campaignId, $emailMarketingId, $target, $targetRecord, $sendDate);
            }

            $nextTargets = $this->getTargets($emailMarketingId);
            if (empty($nextTargets)) {
                $this->setQueueingFinished($emailMarketingId, $emRecord);
            }
        }

    }

    protected function getTargetBatchSize(): int
    {
        $batchSize = $this->settingsProvider->get('massemailer', 'campaign_emails_per_run');

        if ($batchSize === null || $batchSize === '') {
            $batchSize = (int)($this->systemConfigHandler->getSystemConfig('campaign_emails_per_run_default')?->getValue() ?? 50);
        }

        return (int)$batchSize;
    }

    protected function getEmailMarketingBatchSize(): int
    {
        $batchSize = $this->settingsProvider->get('massemailer', 'campaign_marketing_items_per_run');

        if ($batchSize === null || $batchSize === '') {
            $batchSize = (int)($this->systemConfigHandler->getSystemConfig('campaign_marketing_items_per_run_default')?->getValue() ?? 3);
        }

        return (int)$batchSize;
    }

    /**
     * @return array
     */
    protected function getRecordsForQueueing(): array
    {
        $records = $this->emailMarketingManager->getRecordsForQueueing($this->getEmailMarketingBatchSize());
        $this->logger->debug('Campaigns:DefaultEmailQueueingService::getRecordsForQueueing - ' . count($records ?? []) . ' email marketing records found for queueing');
        return $records;
    }

    /**
     * @param string $emailMarketingId
     * @return array
     */
    protected function getTargets(string $emailMarketingId): array
    {
        $targets = $this->targetProvider->getTargets($emailMarketingId, $this->getTargetBatchSize());
        $this->logger->debug('Campaigns:DefaultEmailQueueingService::getTargets - ' . count($targets ?? []) . ' targets found for email marketing id - ' . $emailMarketingId);
        return $targets;
    }

    /**
     * @param Record $targetRecord
     * @param Record $emRecord
     * @param string $campaignId
     * @param string $targetListId
     * @return ValidationFeedback|null
     */
    protected function validateTarget(Record $targetRecord, Record $emRecord, string $campaignId, string $targetListId): ?ValidationFeedback
    {

        try {
            $feedback = $this->targetValidatorManager->validate(
                $targetRecord,
                $emRecord,
                $campaignId,
                $targetListId
            );

        } catch (Exception $e) {
            $this->logger->error(
                sprintf(
                    'Campaigns:DefaultEmailQueueingService::queueEmails - Exception while validating target record for email marketing ID %s, target ID %s, target type %s - %s',
                    $emRecord->getId(),
                    $targetRecord->getId() ?? 'unknown',
                    $targetRecord->getModule() ?? 'unknown',
                    $e->getMessage()
                ),
                [
                    'emailMarketingId' => $emRecord->getId(),
                    'targetId' => $targetRecord->getId() ?? 'unknown',
                    'targetType' => $targetRecord->getModule() ?? 'unknown',
                    'exceptionMessage' => $e->getMessage(),
                    'exceptionTrace' => $e->getTrace(),
                ]
            );
            return null;
        }

        $message = 'Campaigns:DefaultEmailQueueingService::validateTarget - Validation feedback for target - ' . $targetRecord->getId() . ' - isValid -  ' . $feedback->isSuccess() ? 'true' : 'false';
        if (!$feedback->isSuccess()) {
            $message .= ' - failed validator - ' . $feedback->getValidatorKey();
        }
        $this->logger->debug(
            $message,
            [
                'targetId' => $targetRecord->getId(),
                'targetType' => $targetRecord->getModule(),
                'emailMarketingId' => $emRecord->getId(),
                'campaignId' => $campaignId,
                'targetListId' => $targetListId,
            ]
        );

        return $feedback;
    }

    /**
     * @param string $emailMarketingId
     * @param Record $emRecord
     * @return void
     */
    protected function setQueueingFinished(string $emailMarketingId, Record $emRecord): void
    {
        $this->logger->debug('Campaigns:DefaultEmailQueueingService::queueEmails - No targets found for email marketing id - ' . $emailMarketingId) . ' - setting queueing status as finished';
        $this->emailMarketingManager->setQueueingFinished($emRecord);
    }

    /**
     * @param string $emailMarketingId
     * @param Record $emRecord
     * @return void
     */
    protected function setQueueingInProgress(string $emailMarketingId, Record $emRecord): void
    {
        $this->logger->debug('Campaigns:DefaultEmailQueueingService::queueEmails - Setting queueing status as in progress for email marketing id - ' . $emailMarketingId);
        $this->emailMarketingManager->setQueueingInProgress($emRecord);
    }

    /**
     * @param string $campaignId
     * @param string $emailMarketingId
     * @param array $target
     * @param Record $targetRecord
     * @param string $sendDate
     * @return void
     */
    protected function addToQueue(string $campaignId, string $emailMarketingId, array $target, Record $targetRecord, string $sendDate): void
    {
        try {
            $this->queueManager->addToQueue(
                $campaignId,
                $emailMarketingId,
                $target['target_list_id'],
                $target['target_id'],
                $target['target_type'],
                $targetRecord->getAttributes()['email1'] ?? $targetRecord->getAttributes()['email'],
                $sendDate
            );
        } catch (Exception $e) {
            $this->logger->error(
                sprintf(
                    'Campaigns:DefaultEmailQueueingService::queueEmails - Error queueing target record for email marketing ID %s, target ID %s, target type %s - %s',
                    $emailMarketingId,
                    $target['target_id'] ?? 'unknown',
                    $target['target_type'] ?? 'unknown',
                    $e->getMessage()
                ),
                [
                    'emailMarketingId' => $emailMarketingId,
                    'targetId' => $target['target_id'] ?? 'unknown',
                    'targetType' => $target['target_type'] ?? 'unknown',
                    'exceptionMessage' => $e->getMessage(),
                    'exceptionTrace' => $e->getTrace(),
                ]
            );
        }
    }

    /**
     * @param array $target
     * @return Record|null
     */
    protected function getTargetRecord(array $target): ?Record
    {
        $record = null;
        try {
            $record = $this->recordProvider->getRecord($target['target_type'], $target['target_id']);
        } catch (Exception $e) {
            $this->logger->error(
                sprintf(
                    'Campaigns:DefaultEmailQueueingService::queueEmails - Exception while retrieving target record target ID %s, target type %s - %s',
                    $target['target_id'] ?? 'unknown',
                    $target['target_type'] ?? 'unknown',
                    $e->getMessage()
                ),
                [
                    'targetId' => $target['target_id'] ?? 'unknown',
                    'targetType' => $target['target_type'] ?? 'unknown',
                    'exceptionMessage' => $e->getMessage(),
                    'exceptionTrace' => $e->getTrace(),
                ]
            );
        }

        return $record;
    }

    /**
     * @param string $emailMarketingId
     * @return Record|null
     */
    protected function getEmailMarketingRecord(string $emailMarketingId): ?Record
    {
        $record = null;
        try {
            $record = $this->emailMarketingManager->getRecord($emailMarketingId);
        } catch (Exception $e) {
            $this->logger->error(
                sprintf(
                    'Campaigns:DefaultEmailQueueingService::queueEmails - Exception while retrieving email marketing record with ID %s - %s',
                    $emailMarketingId ?? '',
                    $e->getMessage()
                ),
                [
                    '$emailMarketingId' => $emailMarketingId ?? 'unknown',
                    'exceptionMessage' => $e->getMessage(),
                    'exceptionTrace' => $e->getTrace(),
                ]
            );
        }

        return $record;
    }

    /**
     * @param string $campaignId
     * @param string $emailMarketingId
     * @param string $reason
     * @param string $targetListId
     * @param string $targetId
     * @param string $targetType
     * @return void
     */
    protected function handleException(string $campaignId, string $emailMarketingId, string $reason, string $targetListId, string $targetId, string $targetType): void
    {
        $this->campaignLogManager->createCampaignLogEntry(
            $campaignId,
            $emailMarketingId,
            '',
            $reason,
            $targetListId,
            $targetId,
            $targetType
        );
    }

    /**
     * @param string $campaignId
     * @param string $emailMarketingId
     * @param Record $targetRecord
     * @param ValidationFeedback $feedback
     * @param string $targetListId
     * @param string $targetId
     * @param string $targetType
     * @return void
     */
    protected function handleInvalidTarget(string $campaignId, string $emailMarketingId, Record $targetRecord, ValidationFeedback $feedback, string $targetListId, string $targetId, string $targetType): void
    {
        $this->campaignLogManager->createCampaignLogEntry(
            $campaignId,
            $emailMarketingId,
            $targetRecord->getAttributes()['email1'] ?? $targetRecord->getAttributes()['email'],
            'blocked-' . $feedback->getValidatorKey(),
            $targetListId,
            $targetId,
            $targetType
        );
    }
}
