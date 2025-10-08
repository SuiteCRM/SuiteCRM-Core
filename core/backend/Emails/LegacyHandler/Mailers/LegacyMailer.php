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

namespace App\Emails\LegacyHandler\Mailers;

use App\Data\Entity\Record;
use App\Emails\Entity\EmailAddress;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use PHPMailer\PHPMailer\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Email;

class LegacyMailer extends LegacyHandler
{

    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        protected LoggerInterface $logger
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
        return 'legacy-mailer';
    }

    public function send(Email $message, ?Envelope $envelope = null, Record $outbound = null, array $options = []): bool
    {
        $this->init();
        $this->startLegacyApp();

        if (empty($outbound) || empty($outbound->getId())) {
            return false;
        }

        /** @var \OutboundEmailAccounts $outboundBean */
        $outboundBean = \BeanFactory::getBean('OutboundEmailAccounts', $outbound->getId());

        if (empty($outboundBean)) {
            return false;
        }

        $sugarMailer = $this->setupSugarMailer($message, $outboundBean, [], $options);
        if (empty($sugarMailer)) {
            return false;
        }

        $result = $this->sendEmail($sugarMailer);

        $this->close();
        return $result;
    }

    public function sendEmail(\SugarPHPMailer $email, $isTest = false): bool
    {

        $sent = $email->send();

        if ($isTest && $sent) {
            return true;
        }

        if (!$sent) {
            $recipients = array_keys($email->getAllRecipientAddresses());

            foreach ($recipients as $recipient) {
                $this->logger->error("Failed to send email to " . $recipient);
            }

            return false;
        }

        return true;
    }

    /**
     * @param Email $message
     * @param \OutboundEmailAccounts|null $outboundEmail
     * @param array $attachments
     * @return \SugarPHPMailer|null
     * @throws Exception
     */
    public function setupSugarMailer(
        Email $message,
        \OutboundEmailAccounts $outboundEmail = null,
        array $attachments = [],
        array $options = []
    ): ?\SugarPHPMailer {
        require_once('include/SugarPHPMailer.php');

        $mail = new \SugarPHPMailer();

        $emailObj = \BeanFactory::newBean('Emails');
        $defaults = $emailObj->getSystemDefaultEmail();

        $mail->Subject = from_html($message->getSubject() ?? '');

        $this->handleBodyInHTMLformat($mail, $message->getHtmlBody());


        if (empty($message->getFrom()[0]) && empty($message->getFrom()[0]->getAddress())) {
            $fromAddress = $defaults['email'];
            $fromName = $defaults['name'];
        } else {
            $fromAddress = $message->getFrom()[0]->getAddress() ?? '';
            $fromName = $message->getFrom()[0]->getName() ?? '';
        }

        if ($outboundEmail !== null) {
            $this->setMailer($mail, $outboundEmail);
        } else {
            $mail->setMailerForSystem();
        }


        if (!isValidEmailAddress($fromAddress ?? '')) {
            return null;
        }
        $mail->From = $fromAddress ?? '';
        $mail->FromName = $fromName ?? '';

        if (!empty($message->getTextBody() ?? '')) {
            $mail->AltBody = $message->getTextBody() ?? '';
        }

        $this->addRecipients($mail, $message);

        $mail->handleAttachments($attachments);

        $this->addAttachments($mail, $message, $attachments);

        $headers = $options['headers'] ?? [];
        foreach ($headers as $headerName => $headerValue) {
            if (is_string($headerValue)) {
                $mail->addCustomHeader($headerName, $headerValue);
            }
        }

        return $mail;
    }

    /**
     * @param \SugarPHPMailer $mail
     * @param Email $mail
     * @return void
     * @throws Exception
     */
    protected function addAttachments(\SugarPHPMailer $mail, Email $message): void
    {
        foreach ($message->getAttachments() as $key => $attachment) {
            $contents = $attachment->getBody();
            $name = $attachment->getFilename();
            $type = $attachment->getContentType() ?? 'application/octet-stream';
            $mail->addStringAttachment($contents, $name, 'base64', $type);
        }
    }


    /**
     * @param \SugarPHPMailer $mail
     * @param Email $mail
     * @return void
     * @throws Exception
     */
    protected function addRecipients(\SugarPHPMailer $mail, Email $message): void
    {
        if (is_array($message->getTo())) {
            foreach ($message->getTo() as $address) {
                $mail->addAddress($address->getAddress(), $address->getName() ?? '');
            }
        }

        if (is_array($message->getBcc())) {
            foreach ($message->getBcc() as $bcc) {
                $mail->AddBCC($bcc->getAddress(), $bcc->getName() ?? '');
            }
        }

        if (is_array($message->getCc())) {
            foreach ($message->getCc() as $cc) {
                $mail->AddCC($cc->getAddress(), $cc->getName() ?? '');
            }
        }

    }

    protected function setMailer(\SugarPHPMailer $mail, \OutboundEmailAccounts $outboundEmail): void
    {
        $mail->protocol = $outboundEmail->mail_smtpssl ? 'ssl://' : 'tcp://';

        if (isSmtp($outboundEmail->mail_sendtype ?? '')) {
            $mail->Mailer = 'smtp';
            $mail->Host = $outboundEmail->mail_smtpserver;
            $mail->Port = $outboundEmail->mail_smtpport;

            $mail->setSecureProtocol($outboundEmail->mail_smtpssl);
            $mail->initSMTPAuth(
                $outboundEmail->auth_type ?? '',
                $outboundEmail->external_oauth_connection_id ?? '',
                $outboundEmail->mail_smtpuser ?? '',
                $outboundEmail->mail_smtppass ?? '',
            );
        } else {
            $mail->Mailer = 'sendmail';
        }
    }

    protected function handleBodyInHTMLFormat(\SugarPHPMailer $mail, string $body): void
    {
        $mail->IsHTML(true);
        $body = from_html(wordwrap($body, 996));
        $mail->Body = $body;

        $plainText = from_html($body);
        $plainText = strip_tags(br2nl($plainText));
        $mail->AltBody = $plainText;
        $mail->description = $plainText;
    }

    public function getAllRecipients(Email $message): array
    {
        $recipients = [];

        if (is_array($message->getTo())) {
            foreach ($message->getTo() as $key => $address) {
                $recipients[$address->getAddress()] = 1;
            }
        }

        if (is_array($message->getBcc())) {
            foreach ($message->getBcc() as $key => $address) {
                $recipients[$address->getAddress()] = 1;
            }
        }

        if (is_array($message->getCc())) {
            foreach ($message->getCc() as $key => $address) {
                $recipients[$address->getAddress()] = 1;
            }
        }

        return $recipients;
    }


}
