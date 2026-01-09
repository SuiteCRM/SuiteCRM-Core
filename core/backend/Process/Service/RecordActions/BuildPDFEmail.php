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

namespace App\Process\Service\RecordActions;

use ApiPlatform\Exception\InvalidArgumentException;
use App\Data\Entity\Record;
use App\Data\Service\RecordProviderInterface;
use App\MediaObjects\Repository\MediaObjectManagerInterface;
use App\Process\Entity\Process;
use App\Process\LegacyHandler\Pdf\BasePDFManager;
use App\Process\Service\ProcessHandlerInterface;
use Psr\Log\LoggerInterface;

class BuildPDFEmail implements ProcessHandlerInterface
{

    protected const MSG_OPTIONS_NOT_FOUND = 'Process options is not defined';
    protected const PROCESS_TYPE = 'build-pdf-email';

    public function __construct(
        protected RecordProviderInterface $recordProvider,
        protected BasePDFManager $pdfManager,
        protected MediaObjectManagerInterface $mediaObjectManager,
        protected LoggerInterface $logger
    )
    {
    }

    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    public function requiredAuthRole(): string
    {
        return 'ROLE_USER';
    }

    public function getRequiredACLs(Process $process): array
    {
        $options = $process->getOptions();
        $module = $options['module'] ?? '';


        return [
            $module => [
                [
                    'action' => 'detail',
                    'record' => $options['id'] ?? ''
                ]
            ],
        ];
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
     *
     */
    public function validate(Process $process): void
    {
        if (empty($process->getOptions())) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }
    }


    /**
     * @throws \Exception
     */
    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $module = $options['params']['module'] ?? '';
        $id = $options['params']['recordId'] ?? '';
        $templateId = $options['params']['modalRecord']['id'] ?? '';

        if (empty($module) || empty($id) || empty($templateId)) {
            $this->logger->error('BuildPDFEmail process options are missing: module, record id or selected record id');
            $process->setStatus('error');
            $process->setMessages(['LBL_INVALID_PROCESS_OPTIONS']);
            return;
        }

        $record = $this->recordProvider->getRecord($module, $id);

        if ($record === null) {
            $this->logger->error("BuildPDFEmail process: record $module with id $id not found");
            $process->setStatus('error');
            $process->setMessages(['LBL_RECORD_NOT_FOUND']);
            return;
        }

        $to = $this->calculateToField($record);

        $pdf = $this->pdfManager->generatePdf($module, $id, $templateId);

        if ($pdf === null) {
            $process->setStatus('error');
            $process->setMessages(['LBL_PDF_GENERATION_FAILED']);
            return;
        }

        $process->setStatus('success');
        $baseOptions = $this->getEmailBaseOptions();

        $data = [
            'handler' => 'record-modal',
            'params' => [
                'record' => [
                    'id' => '',
                ],
                ...$baseOptions,
                'parentModule' => $module,
                'parentId' => $id,
                'mapFields' => [
                    'default' => [
                        'parent_type' => $module,
                        'parent_id' => $id,
                        'parent_name' => $pdf->getAttributes()['name'] ?? '',
                        'email_attachments' => [$pdf->toArray()],
                    ]
                ]
            ]
        ];

        if (!empty($to)){
            $data['params']['mapFields']['default']['to_addrs_names'] = [$to];
        }

        $process->setData($data);
    }

    /**
     * @throws \Exception
     */
    protected function calculateToField(Record $record): array
    {
        $contactKey = 'billing_contact';
        $accountKey = 'billing_account';

        if ($record->getModule() === 'AOS_Contracts') {
            $contactKey = 'contact';
            $accountKey = 'contract_account';
        }

        $contact = $this->getContact($record, $contactKey);
        if ($contact) {
            return $this->buildToField('Contacts', $contact);
        }

        $account = $this->getAccount($record, $accountKey);
        if ($account) {
            return $this->buildToField('Accounts', $account);
        }

        return $this->buildToField($record->getModule(), $record->getId());
    }

    public function getHandlerKey(): string
    {
        return self::PROCESS_TYPE;
    }

    protected function getContact(Record $record, string $key): ?string
    {
        if (!isset($record->getAttributes()[$key])) {
            return null;
        }

        if (!isset($record->getAttributes()[$key]['id'])) {
            return null;
        }

        return $record->getAttributes()[$key]['id'] ?? null;
    }

    /**
     * @throws \Exception
     */
    protected function buildToField($module, $id): array
    {
        $record = $this->recordProvider->getRecord($module, $id);

        if ($record === null) {
            return [];
        }

        $email = $record->getAttributes()['email1'] ?? $record->getAttributes()['email'] ?? '';

        if (empty($email)) {
            return [];
        }

        return [
            'id' => $record->getId(),
            'name' => $record->getAttributes()['name'] ?? '',
            'email1' => $email,
            'module_name' => $module,
        ];
    }

    protected function getAccount(Record $record, string $key): ?string
    {
        if (!isset($record->getAttributes()[$key])) {
            return null;
        }

        if (!isset($record->getAttributes()[$key]['id'])) {
            return null;
        }

        return $record->getAttributes()[$key]['id'] ?? null;
    }

    protected function getEmailBaseOptions(): array
    {
        return [
            'module' => 'emails',
            'metadataView' => 'modalComposeView',
            'detached' => true,
            'headerClass' => 'left-aligned-title',
            'dynamicTitleKey' => 'LBL_EMAIL_MODAL_DYNAMIC_TITLE',
            'modalOptions' => [
                'size' => 'lg',
                'scrollable' => true,
            ],
        ];
    }
}
