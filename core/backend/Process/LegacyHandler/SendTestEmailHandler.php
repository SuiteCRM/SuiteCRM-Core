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


namespace App\Process\LegacyHandler;


use ApiPlatform\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use App\Data\Entity\Record;
use App\Data\Service\RecordProviderInterface;
use App\Emails\LegacyHandler\EmailProcessProcessor;
use App\Emails\LegacyHandler\FilterEmailListHandler;
use App\Emails\LegacyHandler\SendEmailHandler;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Campaigns\Service\Email\Log\EmailCampaignLogManagerInterface;
use App\Module\Campaigns\Service\Email\Parser\CampaignEmailParserManager;
use App\Module\Campaigns\Service\Email\Targets\EmailTargetValidatorManager;
use App\Module\Campaigns\Service\Email\Targets\Validation\ValidationFeedback;
use App\Module\Service\ModuleNameMapperInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use App\SystemConfig\LegacyHandler\SystemConfigHandler;
use PHPMailer\PHPMailer\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class SendTestEmailHandler extends LegacyHandler implements ProcessHandlerInterface
{
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options is not defined';
    protected const ID_NOT_FOUND = 'Unable to retrieve ID';
    protected const PROCESS_TYPE = 'record-send-test-email';



    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        protected EmailCampaignLogManagerInterface $campaignLogManager,
        protected FilterEmailListHandler $filterEmailListHandler,
        protected ModuleNameMapperInterface $moduleNameMapper,
        protected SendEmailHandler $sendEmailHandler,
        protected SystemConfigHandler $systemConfigHandler,
        protected EmailProcessProcessor $emailProcessProcessor,
        protected LoggerInterface $logger,
        protected RecordProviderInterface $recordProvider,
        protected CampaignEmailParserManager $emailParserManager,
        protected EmailTargetValidatorManager $targetValidatorManager
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $requestStack
        );
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::PROCESS_TYPE;
    }

    /**
     * @inheritDoc
     */
    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    /**
     * @inheritDoc
     */
    public function requiredAuthRole(): string
    {
        return 'ROLE_USER';
    }

    /**
     * @inheritDoc
     */
    public function getRequiredACLs(Process $process): array
    {
        return [];
    }


    /**
     * @inheritDoc
     */
    public function configure(Process $process): void
    {
        $process->setId(self::PROCESS_TYPE);
        $process->setAsync(false);
    }

    /**
     * @inheritDoc
     */
    public function validate(Process $process): void
    {
        if (empty($process->getOptions())) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }

        $options = $process->getOptions();

        if (empty($options['id'])) {
            throw new ItemNotFoundException(self::ID_NOT_FOUND);
        }
    }

    /**
     * @inheritDoc
     * @throws Exception
     * @throws \Exception
     */
    public function run(Process $process): void
    {
        $options = $process->getOptions();

        $fields = $options['params']['fields'];

        $this->init();
        $this->startLegacyApp();

        $module = $this->moduleNameMapper->toLegacy($options['module']);
        $emRecord = $this->recordProvider->getRecord($module, $options['id']);
        $campaignId = $emRecord->getAttributes()['campaign_id'] ?? '';
        $emailMarketingId = $emRecord->getAttributes()['id'] ?? '';

        $beans = $this->filterEmailListHandler->getBeans($fields);
        $targets = $this->getTargetRecords($beans, $emRecord);

        $this->close();

        $validatedTargets = $this->validateTargets($targets, $emRecord, $campaignId, $emailMarketingId);

        if (empty($validatedTargets)) {
            $process->setStatus('error');
            $process->setMessages(['LBL_NO_VALID_TARGETS']);
            $process->setData(['reload' => true]);
        }

        $allSent = true;

        foreach ($validatedTargets as $key => $value) {
            $sent = $this->sendEmail($value, $emRecord);

            if (!$sent){
                $allSent = false;
            }
        }

        if (!$allSent) {
            $process->setStatus('error');
            $process->setMessages(['LBL_NOT_ALL_SENT']);
            $process->setData(['reload' => true]);
            return;
        }

        $attributes =  $emRecord->getAttributes() ?? [];
        $attributes['has_test_data'] = 1;
        $emRecord->setAttributes($attributes);
        $this->recordProvider->saveRecord($emRecord);

        $process->setStatus('success');
        $process->setMessages(['LBL_ALL_EMAILS_SENT']);
        $process->setData(['reload' => true]);
    }

    protected function buildEmailRecord($record, $emAttributes): Record
    {
        $emailRecord = new Record();
        $emailRecord->setId(create_guid());
        $emailRecord->setModule('emails');
        $emailRecord->setType('Email');

        $outboundId = $emAttributes['outbound_email_id'] ?? '';
        $survey = $emAttributes['survey_id'] ?? '';
        $subject = $emAttributes['subject'] ?? '';
        $body = $emAttributes['body'] ?? '';

        if (is_string($record)){
            $emailRecord->setAttributes(
                [
                    'to_addrs_names' => [
                        [
                            'email1' => $record,
                        ]
                    ],
                    'cc_addrs_names' => [],
                    'bcc_addrs_names' => [],
                    'name' => $subject,
                    'description_html' => $body,
                    'outbound_email_id' => $outboundId,
                    'survey_id' => $survey,
                ]
            );

            return $emailRecord;
        }

        $recordAttributes = $record->getAttributes();

        $emailRecord->setAttributes(
            [
                'to_addrs_names' => [
                    [
                        'email1' => $recordAttributes['email1'] ?? $recordAttributes['email'],
                    ]
                ],
                'parent_id' => $recordAttributes['id'],
                'parent_type' => $recordAttributes['module_name'],
                'cc_addrs_names' => [],
                'bcc_addrs_names' => [],
                'name' => $subject,
                'description_html' => $body,
                'outbound_email_id' => $outboundId,
                'survey_id' => $survey,
            ]
        );

        return $emailRecord;
    }

    /**
     * @throws \Exception
     */
    protected function sendEmail($value, $emRecord): bool
    {
        $emAttributes = $emRecord->getAttributes();
        $campaignRecord = $this->recordProvider->getRecord('Campaigns', $emAttributes['campaign_id'] ?? '');
        $emailRecord = $this->buildEmailRecord($value, $emAttributes);
        $targetRecord = null;
        $trackerId = create_guid();

        $email = $value;

        if (!is_string($value)) {
            $email = $value->getAttributes()['email1'] ?? $value->getAttributes()['email'];
        }

        $this->emailParserManager->parse($emailRecord,
        [
            'targetRecord' => $targetRecord,
            'emailMarketingRecord' => $emRecord,
            'campaignRecord' => $campaignRecord,
            'trackerId' => $trackerId
        ]);

        $success = false;

        try {
            $success = $this->emailProcessProcessor->processEmail($emailRecord, true);
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        if ($success) {
            $this->handleEmail($value, $campaignRecord, 'targeted', $emAttributes, $email, $emailRecord, $trackerId);
            return true;
        }

        $this->handleEmail($value, $campaignRecord,'send-error', $emAttributes, $email, $emailRecord, $trackerId);;
        return false;
    }

    /**
     * @throws \Exception
     */
    protected function getTargetRecord($bean): Record
    {
        return $this->recordProvider->getRecord($bean->module_dir, $bean->id);
    }

    /**
     * @throws \Exception
     */
    protected function getTargetRecords(array $beans, Record $emRecord): array
    {
        $targets = [];
        foreach ($beans as $key => $value) {
            foreach ($value as $item) {

                if (is_string($item)) {
                    $targets['emails'][] = $item;
                    continue;
                }

                $targets['records'][] = $this->getTargetRecord($item);
            }
        }

        return $targets;
    }

    protected function handleException(
        string $campaignId,
        string $emailMarketingId,
        string $email,
        string $reason,
        string $targetListId,
        string $targetId,
        string $targetType,
        string $trackerId = '',
        string $relatedId = '',
        string $relatedType = '',
    ): void
    {
        $this->campaignLogManager->createCampaignLogEntry(
            $campaignId,
            $emailMarketingId,
            $email,
            $reason,
            $targetListId,
            $targetId,
            $targetType,
            $trackerId,
            $relatedId,
            $relatedType,
            true
        );
    }
    protected function handleInvalidTarget(
        string $campaignId,
        string $emailMarketingId,
        Record $targetRecord,
        ValidationFeedback $feedback,
        string $targetListId,
        string $targetId,
        string $targetType,
        string $trackerId = '',
    ): void
    {
        $this->campaignLogManager->createCampaignLogEntry(
            $campaignId,
            $emailMarketingId,
            $targetRecord->getAttributes()['email1'] ?? $targetRecord->getAttributes()['email'],
            'blocked-' . $feedback->getValidatorKey(),
            $targetListId,
            $targetId,
            $targetType,
            $trackerId,
            '',
            '',
            true
        );
    }

    /**
     * @param array $targets
     * @param Record $emRecord
     * @param mixed $campaignId
     * @param mixed $emailMarketingId
     * @return array
     */
    public function validateTargets(array $targets, Record $emRecord, string $campaignId, string $emailMarketingId): array
    {
        $validatedTargets = [];
        $sentEmails = [];
        $dupeCheck = $emRecord->getAttributes()['duplicate'] ?? '';

        foreach (($targets['records'] ?? []) as $key => $record) {

            $email = $record->getAttributes()['email1'] ?? $record->getAttributes()['email'];
            if ($dupeCheck === 'email' && in_array($email, $sentEmails, true)){
                $this->handleException(
                    $campaignId,
                    $emailMarketingId,
                    $email,
                    'blocked-duplicate-email',
                    '',
                    $record->getAttributes()['id'] ?? '',
                    $record->getAttributes()['module_name'] ?? '',
                );
                continue;
            }

            $feedback = $this->targetValidatorManager->validate(
                $record,
                $emRecord,
                $campaignId,
                ''
            );

            if ($feedback === null) {
                unset($targets['records'][$key]);
                $this->handleException(
                    $campaignId,
                    $emailMarketingId,
                    $email,
                    'blocked-validation-exception',
                    '',
                    $record->getAttributes()['id'] ?? '',
                    $record->getAttributes()['module_name'] ?? '',
                );
                continue;
            }

            if (!$feedback->isSuccess()) {
                unset($targets['records'][$key]);
                $this->handleInvalidTarget(
                    $campaignId,
                    $emailMarketingId,
                    $record,
                    $feedback,
                    '',
                    $record->getAttributes()['id'] ?? '',
                    $record->getAttributes()['module_name'] ?? '',
                );
                continue;
            }

            $sentEmails[] = $email;
            $validatedTargets[] = $record;
        }

        $sentEmails = [];
        foreach (($targets['emails'] ?? []) as $key => $email) {
            if (!isValidEmailAddress($email)){
                continue;
            }

            if ($dupeCheck === 'email' && in_array($email, $sentEmails, true)){
                $this->handleException(
                    $campaignId,
                    $emailMarketingId,
                    $email,
                    'blocked-duplicate-email',
                    '',
                    '',
                    '',
                );
                continue;
            }

            $sentEmails[] = $email;
            $validatedTargets[] = $email;
        }

        return $validatedTargets;
    }

    protected function handleEmail(
        $value,
        Record $campaignRecord,
        string $activityType,
        array $emAttributes,
        string $email,
        Record $emailRecord,
        string $trackerId
    ): void
    {
        if (is_string($value)){
            $this->campaignLogManager->createCampaignLogEntry(
                $campaignRecord->getAttributes()['id'] ?? null,
                $emAttributes['id'],
                $email,
                $activityType,
                '',
                '',
                '',
                $trackerId,
                $emailRecord->getAttributes()['id'] ?? '',
                'Emails',
                true
            );
            return;
        }

        $this->campaignLogManager->createCampaignLogEntry(
            $campaignRecord->getAttributes()['id'] ?? null,
            $emAttributes['id'],
            $email,
            $activityType,
            '',
            $value?->getAttributes()['id'] ?? null,
            $value?->getAttributes()['module_name'] ?? null,
            $trackerId,
            $emailRecord->getAttributes()['id'] ?? '',
            'Emails',
            true
        );
    }
}
