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


namespace App\Module\Emails\Service\RecordThreadModalActions;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\Data\Entity\Record;
use App\Data\Service\RecordProviderInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;

class OpenDraftAction implements ProcessHandlerInterface
{
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options is not defined';
    protected const PROCESS_TYPE = 'record-thread-item-open-draft';

    public function __construct(
        protected RecordProviderInterface $recordProvider,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    public function getHandlerKey(): string
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
        $options = $process->getOptions();
        $module = 'Emails';
        $id = $options['id'] ?? '';
        return [
            $module => [
                'action' => 'edit',
                'id' => $id,
            ]
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
        $options = $process->getOptions();

        if (empty($options)) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }

        $module = $options['module'] ?? '';

        if (empty($module)) {
            throw new InvalidArgumentException('Process option "module" is not defined');
        }

        if ($module !== 'emails'){
            throw new InvalidArgumentException('Module is not supported for draft saving');
        }
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $module = 'Emails';
        $id = $options['id'] ?? '';

        $emailRecord = $this->recordProvider->getRecord($module, $id);

        $attributes = $this->getMapFields($emailRecord);

        $attributes['parent_name'] = [
            'id' => $attributes['parent_id'] ?? '',
            'name' => $attributes['parent_name'] ?? '',
        ];

        $emailRecord->setAttributes($attributes);

        $emailRecord = $this->recordProvider->saveRecord($emailRecord);

        $params = $this->getModalData($emailRecord);

        $responseData = [
            'hasMultipleHandlers' => true,
            'handlers' => [
                [
                    'handler' => 'record-modal',
                    'params' => [
                        ...$params,
                    ]
                ],
                [
                    'handler' => 'emit-event',
                    'params' => [
                        'event' => 'refresh-drafts',
                        'payload' => true,
                    ],
                ]
            ]

        ];

        $process->setStatus('success');
        $process->setMessages([]);
        $process->setData($responseData);
    }

    /**
     * @throws \Exception
     */
    protected function getModalData(Record $record): array
    {
        return [
            'module' => 'emails',
            'metadataView' => 'modalComposeView',
            'detached' => true,
            'mode' => 'edit',
            'closable' => false,
            'record' => $record->toArray(),
            'recordId' => $record->getId(),
            'parentId' => $record->getAttributes()['parent_name']['id'] ?? null,
            'parentType' => $record->getAttributes()['parent_type'] ?? null,
            'headerActionsKlass' => 'draft-modal-action',
            'headerClass' => 'left-aligned-title',
            'dynamicTitleKey' => 'LBL_EMAIL_MODAL_DRAFT_DYNAMIC_TITLE',
            'modalOptions' => [
                'size' => 'lg',
                'scrollable' => false,
            ],
            'mapFields' => [
                'default' => [
                    ...$this->getMapFields($record),
                ]
            ]
        ];
    }

    /**
     * @throws \Exception
     */
    protected function getMapFields(Record $record = null): array
    {
        $attributes = $record?->getAttributes() ?? [];
        $name = $attributes['name'] ?? '';
        $bodyHtml = $attributes['description_html'] ?? '';
        $outboundEmailId = $attributes['outbound_email_id'] ?? '';
        $fromName = $attributes['outbound_email_name']['from_addr'] ?? '';

        $recipients = $this->getRecipients($attributes);

        return [
            'name' => $name,
            'description_html' => $bodyHtml,
            'outbound_email_id' => $outboundEmailId,
            'to_addrs_names' => $recipients['to_addrs_names'],
            'cc_addrs_names' => $recipients['cc_addrs_names'],
            'bcc_addrs_names' => $recipients['bcc_addrs_names'],
            'outbound_email_name' => $fromName,
            'type' => $attributes['type'] ?? '',
            'status' => $attributes['status'] ?? '',
            'outbound_email_name_record' => $this->getOutboundEmailRecord($outboundEmailId, $fromName),
            'parent_name' => $attributes['parent_name']['name'] ?? '',
            'parent_type' => $attributes['parent_type'] ?? '',
            'parent_id' => $attributes['parent_name']['id'] ?? '',
            'email_attachments' => $attributes['email_attachments'] ?? [],
        ];
    }

    /**
     * @throws \Exception
     */
    protected function getRecipients(array $attributes): array
    {
        $recipients = [
            'to_addrs_names' => [],
            'cc_addrs_names' => [],
            'bcc_addrs_names' => [],
        ];
        if (!empty($attributes['to_addrs_names'])) {
            foreach ($attributes['to_addrs_names'] as $toAddr) {
                $this->mapRecipients($toAddr, $recipients, 'to_addrs_names');
            }
        }

        if (!empty($attributes['cc_addrs_names'])) {
            foreach ($attributes['cc_addrs_names'] as $toAddr) {
                $this->mapRecipients($toAddr, $recipients, 'cc_addrs_names');
            }
        }

        if (!empty($attributes['bcc_addrs_names'])) {
            foreach ($attributes['bcc_addrs_names'] as $toAddr) {
                $this->mapRecipients($toAddr, $recipients, 'bcc_addrs_names');
            }
        }

        return $recipients;
    }


    /**
     * @throws \Exception
     */
    protected function getRecord(string $module, string $id): Record
    {
        return $this->recordProvider->getRecord($module, $id);
    }

    /**
     * @throws \Exception
     */
    protected function getOutboundEmailRecord(string $id, string $fromAddr): array
    {
        $record = $this->getRecord('OutboundEmailAccounts', $id);
        $attributes = $record->getAttributes();
        $attributes['from_addr'] = $fromAddr;
        $record->setAttributes($attributes);
        return $record->toArray();
    }

    /**
     * @throws \Exception
     */
    protected function mapRecipients(array $toAddr, array &$recipients, string $key): void
    {
        $record = $this->getRecord($toAddr['module_name'], $toAddr['id'])->toArray();
        if (empty($record)) {
            return;
        }

        $recipients[$key][] = $record;
    }
}
