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
use App\Data\Service\RecordProviderInterface;
use App\Emails\LegacyHandler\EmailProcessProcessor;
use App\Module\Campaigns\Service\Email\Log\EmailCampaignLogManagerInterface;
use App\Module\Campaigns\Service\Email\Parser\CampaignEmailParserManager;
use App\Module\Campaigns\Service\Email\Targets\EmailTargetValidatorManager;
use App\Module\Campaigns\Service\Email\Targets\Validation\ValidationFeedback;
use App\Module\Campaigns\Service\EmailMarketing\EmailMarketingManagerInterface;
use App\Module\Service\ModuleNameMapperInterface;
use App\SystemConfig\LegacyHandler\SystemConfigHandler;
use App\SystemConfig\Service\SettingsProviderInterface;
use Exception;
use Psr\Log\LoggerInterface;

class DefaultEmailQueueProcessor implements EmailQueueProcessorInterface
{
    public function __construct(
        protected EmailQueueManagerInterface $queueManager,
        protected EmailProcessProcessor $emailProcessor,
        protected RecordProviderInterface $recordProvider,
        protected EmailCampaignLogManagerInterface $campaignLogManager,
        protected EmailTargetValidatorManager $targetValidatorManager,
        protected SystemConfigHandler $systemConfigHandler,
        protected SettingsProviderInterface $settingsProvider,
        protected LoggerInterface $logger,
        protected EmailMarketingManagerInterface $emailMarketingManager,
        protected EmailQueueManagerInterface $emailQueueManager,
        protected ModuleNameMapperInterface $moduleNameMapper,
        protected CampaignEmailParserManager $emailParserManager,
    ) {
    }

    public function processQueue(array $options = []): void
    {
        $emailMarketingRecords = $this->emailMarketingManager->getRecordsForQueueProcessing($this->getEmailMarketingBatchSize());

        foreach ($emailMarketingRecords as $emailMarketing) {
            $emailMarketingId = $emailMarketing['id'];
            $campaignId = $emailMarketing['campaign_id'];

            $queueEntries = $this->getQueueEntries($emailMarketingId);
            $emRecord = $this->getEmailMarketingRecord($emailMarketingId);
            $campaignRecord = $this->getCampaignRecord($campaignId);

            if ($emRecord === null) {
                continue;
            }

            $isQueueingFinished = $this->emailMarketingManager->isQueueingFinished($emRecord);

            if (empty($queueEntries) && !$isQueueingFinished) {
                $this->logger->debug(
                    'Campaigns:DefaultEmailQueueProcessor::processQueue - No entries to send and queueing in progress - skipping | email marketing id - ' . $emailMarketingId, [
                        'emailMarketingId' => $emailMarketingId,
                        'campaignId' => $campaignId,
                    ]
                );
                continue;
            }

            if (empty($queueEntries) && $isQueueingFinished) {
                $this->setSent($emRecord);
                continue;
            }

            $isSending = $this->emailMarketingManager->isSending($emRecord);
            if (!$isSending) {
                $this->setSending($emRecord);
            }

            foreach ($queueEntries as $entry) {
                $targetType = $entry['related_type'] ?? '';
                $targetId = $entry['related_id'] ?? '';
                $targetListId = $entry['list_id'] ?? '';

                $targetRecord = $this->getTargetRecord($targetType, $targetId);

                if ($targetRecord === null) {
                    $this->handleException($campaignId, $emailMarketingId, 'blocked-target-not-found', $targetListId, $targetId, $targetType);
                    continue;
                }

                $feedback = $this->validateTarget($targetRecord, $emRecord, $campaignId, $targetListId);

                if ($feedback === null) {
                    $this->handleException($campaignId, $emailMarketingId, 'blocked-validation-exception', $targetListId, $targetId, $targetType);
                    return;
                }

                if (!$feedback->isSuccess()) {
                    $this->handleInvalidTarget($campaignId, $emailMarketingId, $targetRecord, $feedback, $targetListId, $targetId, $targetType);
                    continue;
                }

                $trackerId = create_guid();

                $emailRecord = $this->buildEmailRecord($emRecord, $targetRecord);

                $result = $this->sendEmail($emailRecord, $emRecord, $targetRecord, $campaignRecord, $trackerId);

                if ($result === null || !$result['success']) {
                    $this->handlerFailedSend(
                        $campaignId,
                        $emailMarketingId,
                        $targetRecord->getAttributes()['email1'] ?? $targetRecord->getAttributes()['email'] ?? '',
                        'send error',
                        $targetListId,
                        $targetId,
                        $targetType,
                        $trackerId
                    );
                    continue;
                }

                $this->handleSuccessfulSend(
                    $campaignId,
                    $emailMarketingId,
                    $targetRecord,
                    $targetListId,
                    $targetId,
                    $targetType,
                    $trackerId,
                    $emailRecord->getAttributes()['id'] ?? '',
                    'Emails',
                );
            }

            $nextQueueEntries = $this->getQueueEntries($emailMarketingId);
            if (empty($nextQueueEntries) && !$isQueueingFinished) {
                continue;
            }

            if (empty($nextQueueEntries) && $isQueueingFinished) {
                $this->setSent($emRecord);
            }
        }
    }


    protected function getBatchSize(): int
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

    protected function buildEmailRecord(Record $record, Record $prospect): Record
    {
        $recordAttributes = $record->getAttributes() ?? [];
        $prospectAttr = $prospect->getAttributes() ?? [];

        $emailRecord = new Record();

        $attributes = [
            'name' => $recordAttributes['subject'] ?? '',
            'description' => $recordAttributes['body'],
            'description_html' => $recordAttributes['body'],
            'outbound_email_id' => $recordAttributes['outbound_email_id'] ?? '',
            'parent_type' => $prospectAttr['module_name'] ?? '',
            'parent_id' => $prospectAttr['id'] ?? '',
            'survey_id' => $recordAttributes['survey_id'] ?? null,
            'to_addrs_names' => [
                [
                    'email1' => $prospectAttr['email1'] ?? $prospectAttr['email'] ?? '',
                ]
            ],
        ];

        $emailRecord->setId(create_guid());
        $emailRecord->setAttributes($attributes);
        $emailRecord->setModule('Emails');

        return $emailRecord;
    }

    public function handlerFailedSend(
        string $campaignId,
        string $marketingId,
        string $email,
        string $activityType,
        string $prospectListId,
        string $targetId,
        string $targetType,
        string $trackerId = '',
        string $relatedId = '',
        string $relatedType = ''
    ): void {

        $entry = $this->emailQueueManager->getQueueEntry($marketingId, $targetId, $targetType);
        $sendAttempts = (int)($entry['send_attempts'] ?? 0);

        if ($sendAttempts > 5) {
            $this->campaignLogManager->createCampaignLogEntry(
                $campaignId,
                $marketingId,
                $email,
                $activityType,
                $prospectListId,
                $targetId,
                $targetType,
                $trackerId,
                $relatedId,
                $relatedType
            );

            $this->emailQueueManager->deleteFromQueue($marketingId, $targetId, $targetType);
            $this->logger->debug(
                'Campaigns:DefaultEmailQueueProcessor::handlerFailedSend - Failed to send email after 5 attempts | email marketing id - ' . $marketingId . ' | target - ' . $targetType . '-' . $targetId, [
                    'emailMarketingId' => $marketingId,
                    'targetId' => $targetId,
                    'targetType' => $targetType,
                    'campaignId' => $campaignId,
                ]
            );
            return;
        }

        $this->emailQueueManager->updateSendAttempts($entry['id']);
        $this->logger->debug(
            'Campaigns:DefaultEmailQueueProcessor::handlerFailedSend - Failed to send email - increasing attempt count | email marketing id - ' . $marketingId . ' | target - ' . $targetType . '-' . $targetId, [
                'emailMarketingId' => $marketingId,
                'targetId' => $targetId,
                'targetType' => $targetType,
                'campaignId' => $campaignId,
            ]
        );
    }

    /**
     * @param Record $emRecord
     * @return void
     */
    protected function setSent(Record $emRecord): void
    {
        $this->logger->debug(
            'Campaigns:DefaultEmailQueueProcessor::setSent - No entries on queue and queueing finished - setting email marketing as sent | email marketing id - ' . $emRecord->getId(), [
                'emailMarketingId' => $emRecord->getId(),
            ]
        );
        $this->emailMarketingManager->setSent($emRecord);
    }

    /**
     * @param Record $emRecord
     * @return void
     */
    protected function setSending(Record $emRecord): void
    {
        $this->logger->debug(
            'Campaigns:DefaultEmailQueueProcessor::setSending - Queue entries found - setting email marketing as sending | email marketing id - ' . $emRecord->getId(), [
                'emailMarketingId' => $emRecord->getId(),
            ]
        );
        $this->emailMarketingManager->setSending($emRecord);
    }

    /**
     * @param string $emailMarketingId
     * @return array
     */
    protected function getQueueEntries(string $emailMarketingId): array
    {
        $queueEntries = $this->queueManager->getEntriesToSend($emailMarketingId, $this->getBatchSize());
        $this->logger->debug('Campaigns:DefaultEmailQueueProcessor::getQueueEntries - ' . count($queueEntries ?? []) . ' entries found for email marketing id - ' . $emailMarketingId);
        return $queueEntries;
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
                    'Campaigns:DefaultEmailQueueProcessor::processQueue - Exception while validating target record for email marketing ID %s, target ID %s, target type %s - %s',
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


        $message = 'Campaigns:DefaultEmailQueueProcessor::validateTarget - Validation feedback for target - ' . $targetRecord->getId() . ' - isValid -  ' . $feedback->isSuccess() ? 'true' : 'false';
        if (!$feedback->isSuccess()) {
            $message .= ' - failed validator - ' . $feedback->getValidatorKey();
        }

        $this->logger->debug(
            $message, [
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
     * @param string $campaignId
     * @param string $emailMarketingId
     * @param Record $targetRecord
     * @param string $targetListId
     * @param string $targetId
     * @param string $targetType
     * @param string $trackerId
     * @return void
     */
    protected function handleSuccessfulSend(
        string $campaignId,
        string $emailMarketingId,
        Record $targetRecord,
        string $targetListId,
        string $targetId,
        string $targetType,
        string $trackerId = '',
        string $relatedId = '',
        string $relatedType = ''
    ): void {
        $this->campaignLogManager->createCampaignLogEntry(
            $campaignId,
            $emailMarketingId,
            $targetRecord->getAttributes()['email1'] ?? $targetRecord->getAttributes()['email'] ?? '',
            'targeted',
            $targetListId,
            $targetId,
            $targetType,
            $trackerId,
            $relatedId,
            $relatedType
        );

        $this->emailQueueManager->deleteFromQueue($emailMarketingId, $targetId, $targetType);

        $this->logger->debug(
            'Campaigns:DefaultEmailQueueProcessor::processQueue - Email sent successfully | email marketing id - ' . $emailMarketingId . ' | target - ' . $targetType . '-' . $targetId,
            [
                'emailMarketingId' => $emailMarketingId,
                'targetId' => $targetId,
                'targetType' => $targetType,
                'campaignId' => $campaignId,
            ]
        );
    }

    /**
     * @param Record $emRecord
     * @param Record $targetRecord
     * @param Record|null $campaignRecord
     * @param string $trackerId
     * @return array|null
     */
    protected function sendEmail(Record $emailRecord, Record $emRecord, Record $targetRecord, ?Record $campaignRecord, string $trackerId): ?array
    {
        $result = null;
        try {
            $this->logger->debug(
                'Campaigns:DefaultEmailQueueProcessor::sendEmail - Sending email | email marketing id - ' . $emRecord->getId() . ' | target - ' . $targetRecord->getModule() . '-' . $targetRecord->getId(),
                [
                    'emailMarketingId' => $emRecord->getId(),
                    'targetId' => $targetRecord->getId(),
                    'targetType' => $targetRecord->getModule(),
                ]
            );


            $this->emailParserManager->parse(
                $emailRecord,
                [
                    'targetRecord' => $targetRecord,
                    'emailMarketingRecord' => $emRecord,
                    'campaignRecord' => $campaignRecord,
                    'trackerId' => $trackerId,
                ]
            );

            $result = $this->emailProcessor->processEmail($emailRecord);
        } catch (Exception $e) {
            $this->logger->error(
                sprintf(
                    'Campaigns:DefaultEmailQueueProcessor::processQueue - Exception while trying to send email for email marketing ID %s, target ID %s, target type %s - %s',
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
        }

        return $result;
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
            $targetRecord->getAttributes()['email1'] ?? $targetRecord->getAttributes()['email'] ?? '',
            'blocked-' . $feedback->getValidatorKey(),
            $targetListId,
            $targetId,
            $targetType
        );

        $this->emailQueueManager->deleteFromQueue($emailMarketingId, $targetId, $targetType);
        $this->logger->debug(
            'Campaigns:DefaultEmailQueueProcessor::processQueue - Email not sent - target blocked/invalid - deleted from queue | email marketing id - ' . $emailMarketingId . ' | target - ' . $targetType . '-' . $targetId,
            [
                'emailMarketingId' => $emailMarketingId,
                'targetId' => $targetId,
                'targetType' => $targetType,
                'campaignId' => $campaignId,
            ]
        );
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
                    'Campaigns:DefaultEmailQueueProcessor::processQueue - Exception while retrieving email marketing record with ID %s - %s',
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
     * @param string $targetType
     * @param string $targetId
     * @return Record|null
     */
    protected function getTargetRecord(string $targetType, string $targetId): ?Record
    {
        $record = null;
        try {
            $record = $this->recordProvider->getRecord($this->moduleNameMapper->toFrontEnd($targetType), $targetId);
        } catch (Exception $e) {
            $this->logger->error(
                sprintf(
                    'Campaigns:DefaultEmailQueueProcessor::processQueue - Exception while retrieving target record target ID %s, target type %s - %s',
                    $targetId ?? '',
                    $targetType ?? '',
                    $e->getMessage()
                ),
                [
                    'targetId' => $targetId ?? 'unknown',
                    'targetType' => $targetType ?? 'unknown',
                    'exceptionMessage' => $e->getMessage(),
                    'exceptionTrace' => $e->getTrace(),
                ]
            );
        }

        return $record;
    }

    /**
     * @param string $id
     * @return Record|null
     */
    protected function getCampaignRecord(string $id): ?Record
    {
        $record = null;
        try {
            $record = $this->recordProvider->getRecord('campaigns', $id);
        } catch (Exception $e) {
            $this->logger->error(
                sprintf(
                    'Campaigns:DefaultEmailQueueProcessor::processQueue - Exception while retrieving campaign record ID %s - %s',
                    $id ?? '',
                    $e->getMessage()
                ),
                [
                    'id' => $id ?? 'unknown',
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

        $this->emailQueueManager->deleteFromQueue($emailMarketingId, $targetId, $targetType);
    }

}
