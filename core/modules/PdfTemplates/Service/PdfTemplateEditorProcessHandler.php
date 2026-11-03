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

namespace App\Module\PdfTemplates\Service;

use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use App\Module\PdfTemplates\LegacyHandler\PdfTemplateEditorHandler;
use Psr\Log\LoggerInterface;

/**
 * Process `pdf-template-editor` of the PDF template record view
 * (PdfTemplateEditorHandler). The PDF template access is checked there.
 *
 * Options, by action:
 *  - variables: type, the variables of the template type, by group (HTML editors);
 *  - samples: the sample templates (Load Sample action).
 */
class PdfTemplateEditorProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = 'pdf-template-editor';

    protected const ACTIONS = ['variables', 'samples'];

    public function __construct(protected PdfTemplateEditorHandler $editor, protected LoggerInterface $logger)
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

        if (!is_array($options) || !in_array($options['action'] ?? null, self::ACTIONS, true)) {
            throw new \InvalidArgumentException('Process options are not defined');
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();

        try {
            $data = $options['action'] === 'samples'
                ? ['samples' => $this->editor->getSamples()]
                : ['groups' => $this->editor->getVariables((string)($options['type'] ?? ''))];
        } catch (\Throwable $throwable) {
            $this->logger->error('PDF template editor ' . $options['action'] . ' failed: ' . $throwable->getMessage());
            $data = [];
        }

        $success = !empty($data) && !in_array(null, $data, true);
        $process->setStatus($success ? 'success' : 'error');
        $process->setMessages([]);
        $process->setData($success ? $data : []);
    }
}
