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

namespace App\Module\EmailTemplates\Service\ProcessHandlers;

use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use App\Module\EmailTemplates\LegacyHandler\EmailTemplateAttachmentsHandler;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Process `email-template-attachments` of the attachments widget of the email template record
 * view (EmailTemplateAttachmentsHandler). The template access is checked there.
 *
 * Options, by action:
 *  - list: id, the attachments of the template (and the maximum upload size, as after each
 *    action);
 *  - add: id, filename, content (base64), attaches a file;
 *  - remove: id, attachment, removes an attachment.
 */
class EmailTemplateAttachmentsProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = 'email-template-attachments';

    protected const ACTIONS = ['list', 'add', 'remove'];

    public function __construct(protected EmailTemplateAttachmentsHandler $attachments, protected LoggerInterface $logger)
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
        return [];
    }

    public function configure(Process $process): void
    {
        $process->setId(self::PROCESS_TYPE);
        $process->setAsync(false);
    }

    public function validate(Process $process): void
    {
        $options = $process->getOptions();

        if (!is_array($options) || !in_array($options['action'] ?? null, self::ACTIONS, true) || empty($options['id'])) {
            throw new InvalidArgumentException('Process options are not defined');
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $id = (string)$options['id'];

        try {
            $done = match ($options['action']) {
                'add' => $this->attachments->add($id, (string)($options['filename'] ?? ''), (string)($options['content'] ?? '')),
                'remove' => $this->attachments->remove($id, (string)($options['attachment'] ?? '')),
                default => true,
            };
            $attachments = $this->attachments->list($id);
        } catch (\Throwable $throwable) {
            $this->logger->error('Email template attachments ' . $options['action'] . ' failed: ' . $throwable->getMessage());
            $done = false;
            $attachments = null;
        }

        $success = $done && $attachments !== null;
        $process->setStatus($success ? 'success' : 'error');
        $process->setMessages($success ? [] : ['LBL_TEMPLATE_ATTACHMENTS_ERROR']);
        $process->setData(['attachments' => $attachments ?? [], 'maxSize' => $this->attachments->getMaxSize()]);
    }
}
