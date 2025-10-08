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


namespace App\Emails\LegacyHandler;

use App\Data\Entity\Record;
use App\Data\LegacyHandler\PreparedStatementHandler;
use App\Data\Service\RecordProviderInterface;
use App\Emails\LegacyHandler\Parsers\LegacyParser;
use App\Emails\Service\EmailParserHandler\EmailParserManager;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use Psr\Log\LoggerInterface;
use SugarEmailAddress;
use Symfony\Component\HttpFoundation\RequestStack;

class EmailProcessProcessor extends LegacyHandler
{

    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        protected SendEmailHandler $sendEmailHandler,
        protected RecordProviderInterface $recordProvider,
        protected PreparedStatementHandler $preparedStatementHandler,
        protected LoggerInterface $logger,
        protected EmailParserManager $parserManager
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

    public function getHandlerKey(): string
    {
        return 'email-process-processor';
    }

    /**
     * @param Record $emailRecord
     * @param bool $isTest
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function processEmail(Record $emailRecord, bool $isTest = false): array
    {
        $this->init();
        $this->startLegacyApp();

        $emailAttributes = $emailRecord->getAttributes() ?? [];
        $validationErrors = $this->validateInput($emailAttributes);

        if (!empty($validationErrors)) {
            return $validationErrors;
        }

        /** @var \OutboundEmailAccounts $outboundEmail */
        $outboundEmail = \BeanFactory::getBean('OutboundEmailAccounts', $emailAttributes['outbound_email_id']);

        if (empty($outboundEmail)) {
            $this->close();
            return [
                'success' => false,
                'message' => 'Outbound email not found'
            ];
        }

        $outboundRecord = $this->recordProvider->mapToRecord($outboundEmail);

        $emailRecord = $this->parseEmail($emailRecord);

        $success = false;
        try {
            $success = $this->sendEmailHandler->sendEmail($emailRecord, $outboundRecord, $isTest);
        } catch (\Exception $e) {

        }

        if (!$success) {
            $this->close();
            return [
                'success' => false,
                'message' => 'Unable to send email'
            ];
        }

        $addresses = [];

        [$addresses['to'], $addresses['cc'], $addresses['bcc']] = $this->mapAttributes($emailAttributes);

        $emailRecord = $this->recordProvider->saveRecord($emailRecord);

        $this->saveEmailAddresses($outboundRecord->getAttributes(), $emailRecord->getAttributes(), $addresses);

        $this->close();
        return [
            'success' => true,
            'message' => ''
        ];
    }

    protected function validateInput(array $emailAttributes): array
    {
        if (empty($emailAttributes['to_addrs_names']) && empty($emailAttributes['cc_addrs_names']) && empty($emailAttributes['bcc_addrs_names'])) {
            $this->close();
            return [
                'success' => false,
                'message' => 'No email addresses provided'
            ];
        }

        if (empty($emailAttributes['outbound_email_id'] ?? '')) {
            $this->close();
            return [
                'success' => false,
                'message' => 'No outbound email provided'
            ];
        }

        return [];
    }

    /**
     * @param $emailAttributes
     * @return array[]
     */
    protected function mapAttributes($emailAttributes): array
    {
        $to = [];
        $bcc = [];
        $cc = [];

        $toAddresses = !empty($emailAttributes['to_addrs_names']) ? $emailAttributes['to_addrs_names'] : [];
        $ccAddresses = !empty($emailAttributes['cc_addrs_names']) ? $emailAttributes['cc_addrs_names'] : [];
        $bccAddresses = !empty($emailAttributes['bcc_addrs_names']) ? $emailAttributes['bcc_addrs_names'] : [];

        foreach ($toAddresses as $key => $value) {
            $to[] = $value['email1'] ?? $value['email'];
        }
        foreach ($ccAddresses as $key => $value) {
            $cc[] = $value['email1'] ?? $value['email'];
        }
        foreach ($bccAddresses as $key => $value) {
            $bcc[] = $value['email1'] ?? $value['email'];
        }

        return [$to, $cc, $bcc];
    }

    protected function saveEmailAddresses(?array $outboundAttributes, array $emailAttributes, array $addresses): void
    {

        $id = $emailAttributes['id'] ?? null;
        $emailId = $this->getEmailId($outboundAttributes['smtp_from_addr']);

        if (!empty($emailId)) {
            $this->linkEmailToAddress($id, $emailId, 'from');
        }

        $this->mapAddresses($id, $addresses['to'], 'to');
        $this->mapAddresses($id, $addresses['cc'], 'cc');
        $this->mapAddresses($id, $addresses['bcc'], 'bcc');
    }

    protected function linkEmailToAddress(string $emailId, string $email, string $type): void
    {
        $query = 'SELECT * FROM emails_email_addr_rel WHERE email_id = :email_id ';
        $query .= 'AND email_address_id = :email_address_id AND address_type = :type AND deleted = 0';
        try {
            $records = $this->preparedStatementHandler->fetch($query,
                [
                    'email_id' => $emailId,
                    'email_address_id' => $email,
                    'type' => $type
                ],
                [
                    ['param' => 'email_id', 'type' => 'string'],
                    ['param' => 'email_address_id', 'type' => 'string'],
                    ['param' => 'type', 'type' => 'string']
                ]);
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        if (empty($records)) {
            $id = create_guid();

            try {
                $this->preparedStatementHandler->update(
                    'INSERT INTO emails_email_addr_rel VALUES(:id, :email_id, :type, :email, 0)',
                    ['id' => $id, 'email_id' => $emailId, 'email' => $email, 'type' => $type],
                    [
                        ['param' => 'email_id', 'type' => 'string'],
                        ['param' => 'id', 'type' => 'string'],
                        ['param' => 'email', 'type' => 'string'],
                        ['param' => 'type', 'type' => 'string']
                    ]
                );
            } catch (\Doctrine\DBAL\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }

    protected function getEmailId(string $value): string
    {
        $sugarEmailAddresses = new SugarEmailAddress();
        return $sugarEmailAddresses->getEmailGUID($value) ?? '';
    }

    protected function mapAddresses(string $id, array $address, string $type): void
    {
        foreach ($address as $key => $value) {
            $emailId = $this->getEmailId($value);
            if (!empty($emailId)) {
                $this->linkEmailToAddress($id, $emailId, $type);
            }
        }
    }

    protected function parseEmail(Record $emailRecord): Record
    {
        $attributes = $emailRecord->getAttributes();

        $this->init();

        $isSurvey = !empty($attributes['survey_id']);
        $hasParent = isset($attributes['parent_type'], $attributes['parent_id']);

        $replaceEmpty = true;

        if ($isSurvey && $hasParent) {
            $replaceEmpty = false;
        }

        if ($isSurvey) {
            $survey = \BeanFactory::getBean('Surveys', $attributes['survey_id']);
            $attributes = $this->parseBean($attributes, $survey, $replaceEmpty);
        }

        if (!$hasParent) {
            $this->close();
            $emailRecord->setAttributes($attributes);
            return $emailRecord;
        }


        $bean = \BeanFactory::getBean($attributes['parent_type'], $attributes['parent_id']);

        $this->close();

        $attributes = $this->parseBean($attributes, $bean);

        $emailRecord->setAttributes($attributes);

        return $emailRecord;
    }

    protected function parseBean(array $attributes, \SugarBean|bool $bean, bool $replaceEmpty = true): array
    {
        if ($bean === false) {
            return $attributes;
        }
        $attributes['name'] = $this->parserManager->parse(
            $attributes['name'] ?? '',
            $bean,
            'default',
            $replaceEmpty
        );
        $attributes['description_html'] = $this->parserManager->parse(
            $attributes['description_html'] ?? '',
            $bean,
            'default',
            $replaceEmpty
        );
        $attributes['description'] = $this->parserManager->parse(
            $attributes['description'] ?? '',
            $bean,
            'default',
            $replaceEmpty
        );

        return $attributes;
    }

}
