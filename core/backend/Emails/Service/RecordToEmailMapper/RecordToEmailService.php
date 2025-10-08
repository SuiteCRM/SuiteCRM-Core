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

namespace App\Emails\Service\RecordToEmailMapper;

use App\Data\Entity\Record;
use App\FieldDefinitions\Service\FieldDefinitionsProviderInterface;
use App\MediaObjects\Repository\DefaultMediaObjectManager;
use App\MediaObjects\Services\MediaObjectFileHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class RecordToEmailService
{

    public function __construct(
        protected DefaultMediaObjectManager $defaultMediaObjectManager,
        protected FieldDefinitionsProviderInterface $fieldDefinitionsProvider,
        protected LoggerInterface $logger,
        protected MediaObjectFileHandler $mediaObjectFileHandler
    )
    {
    }

    /**
     * @param Record $emailRecord
     * @param Record $outbound
     * @return Email
     */
    public function map(Record $emailRecord, Record $outbound): Email
    {
        $emailRecordAttributes = $emailRecord->getAttributes() ?? [];
        $outboundAttributes = $outbound->getAttributes() ?? [];

        $email = new Email();
        $email->from(new Address($outboundAttributes['smtp_from_addr'] ?? '', $outboundAttributes['smtp_from_name'] ?? ''));

        if (!empty($outboundAttributes['reply_to_addr'] ?? '')) {
            $email->replyTo(new Address($outboundAttributes['reply_to_addr'] ?? '', $outboundAttributes['reply_to_name'] ?? ''));
        }

        $this->addRecipients($emailRecordAttributes, $email);
        $email->subject($emailRecordAttributes['name'] ?? '');
        $email->text($emailRecordAttributes['description'] ?? '');
        $email->html($emailRecordAttributes['description_html'] ?? '');

        $this->addAttachment($emailRecord, $emailRecordAttributes, $email);

        $headers = $emailRecordAttributes['headers'] ?? [];

        foreach ($headers as $headerName => $headerValue) {
            if (is_string($headerValue)) {
                $email->getHeaders()->addHeader($headerName, $headerValue);
            }
        }
        return $email;
    }

    /**
     * @param array $entities
     * @return Address[]
     */
    protected function buildEmailAddresses(array $entities): array
    {
        $emailAddressesArray = [];
        foreach ($entities as $fromItem) {
            if (!empty($fromItem['email1']) && is_string($fromItem['email1'])) {
                $emailAddressesArray[] = new Address($fromItem['email1']);
                continue;
            }

            if (!empty($fromItem['email']) && is_string($fromItem['email'])) {
                $emailAddressesArray[] = new Address($fromItem['email']);
            }
        }

        return $emailAddressesArray;
    }

    /**
     * @param array $recordAttributes
     * @param Email $email
     * @return array
     */
    protected function addRecipients(array $recordAttributes, Email $email): void
    {
        $toAddresses = !empty($recordAttributes['to_addrs_names']) ? $recordAttributes['to_addrs_names'] : [];
        $ccAddresses = !empty($recordAttributes['cc_addrs_names']) ? $recordAttributes['cc_addrs_names'] : [];
        $bccAddresses = !empty($recordAttributes['bcc_addrs_names']) ? $recordAttributes['bcc_addrs_names'] : [];

        $to = $this->buildEmailAddresses($toAddresses);
        $cc = $this->buildEmailAddresses($ccAddresses);
        $bcc = $this->buildEmailAddresses($bccAddresses);

        foreach ($to as $address) {
            $email->addTo($address);
        }

        foreach ($cc as $address) {
            $email->addCc($address);
        }

        foreach ($bcc as $address) {
            $email->addBcc($address);
        }
    }

    protected function getMediaObjects(array $emailRecordAttributes, string $storageType): array
    {
        $attachments = $emailRecordAttributes['email_attachments'] ?? [];

        $mediaObjects = [];

        foreach ($attachments as $key => $attachment) {
            $mediaObject = $this->defaultMediaObjectManager->getMediaObject($storageType, $attachment['id'] ?? '');
            if (!$mediaObject) {
                $this->logger->warning('Attachment with id '.$attachment['id'].' not found');
                continue;
            }
            $mediaObject->contentUrl = $this->defaultMediaObjectManager->buildContentUrl($storageType, $mediaObject);
            $mediaObjects[] = $mediaObject;
        }

        return $mediaObjects;
    }

    protected function getStorageType(Record $emailRecord): string
    {
        $definition = $this->fieldDefinitionsProvider->getVardef($emailRecord->getModule());
        $vardefs = $definition->getVardef() ?? [];

        if (!isset($vardefs['email_attachments']['metadata']['storage_type'])) {
            $this->logger->warning('No storage type found for attachments field in module '.$emailRecord->getModule());
            return '';
        }

        return $vardefs['email_attachments']['metadata']['storage_type'];
    }

    /**
     * @param Record $emailRecord
     * @param array $emailRecordAttributes
     * @param Email $email
     * @return void
     */
    protected function addAttachment(Record $emailRecord, array $emailRecordAttributes, Email $email): void
    {
        $mediaObjectStorageType = $this->getStorageType($emailRecord);
        $mediaObjects = $this->getMediaObjects($emailRecordAttributes, $mediaObjectStorageType);

        foreach ($mediaObjects as $key => $mediaObject) {
            $fileInfo = $this->mediaObjectFileHandler->getObjectStream(
                $mediaObject,
                'file',
                $mediaObject::class,
                $mediaObject->originalName
            );
            $email->attach($fileInfo['stream'], $fileInfo['fileName'], $fileInfo['mimeType']);
        }
    }


}
