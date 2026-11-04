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

namespace App\Module\Import\Service\ProcessHandlers;

use App\Module\Import\LegacyHandler\ImportWizardHandler;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * `import-wizard` process of the SuiteCRM 8 import view (ImportWizardHandler). The steps of the
 * wizard are the legacy ones: the view posts their forms to the legacy actions.
 *
 * Options, by action:
 * - labels: module → the strings of the legacy wizard, the name of the module, isAdmin;
 * - imported: module, offset → the records of the last import of the user, by type of record;
 * - prospectLists: term → the target lists whose name starts with it;
 * - addToProspectList: listId → the targets of the last import added to the list;
 * - vcard: module, content (the vCard in base64) → the record made of it (none: the required
 *   values are missing).
 */
class ImportWizardProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = ImportWizardHandler::HANDLER_KEY;

    protected const ACTIONS = ['labels', 'imported', 'prospectLists', 'addToProspectList', 'vcard'];

    public function __construct(
        protected ImportWizardHandler $wizard,
        protected LoggerInterface $logger
    ) {
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
        if (in_array($options['action'], ['labels', 'imported', 'vcard'], true) && !preg_match('/^\w*$/', (string)($options['module'] ?? ''))) {
            throw new \InvalidArgumentException('Process options are not defined');
        }
        if ($options['action'] === 'addToProspectList' && !preg_match('/^[\w-]+$/', (string)($options['listId'] ?? ''))) {
            throw new \InvalidArgumentException('Process options are not defined');
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $module = (string)($options['module'] ?? '');

        try {
            $data = match ($options['action']) {
                'labels' => $this->wizard->getLabels($module),
                'imported' => $this->wizard->getImported($module, (int)($options['offset'] ?? 0)),
                'prospectLists' => $this->wizard->getProspectLists(trim((string)($options['term'] ?? ''))),
                'addToProspectList' => $this->wizard->addToProspectList((string)$options['listId']),
                'vcard' => $this->wizard->importVCard($module, (string)($options['content'] ?? '')),
            };
            $success = true;
        } catch (\Throwable $throwable) {
            $this->logger->error('Import ' . $options['action'] . ' failed: ' . $throwable->getMessage());
            $data = [];
            $success = false;
        }

        $process->setStatus($success ? 'success' : 'error');
        $process->setMessages([]);
        $process->setData($data);
    }
}
