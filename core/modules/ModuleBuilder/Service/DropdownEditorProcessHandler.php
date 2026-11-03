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

namespace App\Module\ModuleBuilder\Service;

use App\Module\ModuleBuilder\LegacyHandler\DropdownEditorHandler;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Process `dropdown-editor` of the dropdown editor view (DropdownEditorHandler). Admins only, as
 * the legacy editor.
 *
 * Options, by action:
 *  - lists: the lists, the languages and the labels of the page;
 *  - load: name, language, the items of the list;
 *  - save: name, language, items ({key, label}, in their order), new (a new list).
 */
class DropdownEditorProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = 'dropdown-editor';

    protected const ACTIONS = ['lists', 'load', 'save'];

    public function __construct(protected DropdownEditorHandler $editor, protected LoggerInterface $logger)
    {
    }

    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    public function requiredAuthRole(): string
    {
        return 'ROLE_ADMIN';
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
            throw new InvalidArgumentException('Process options are not defined');
        }
        if ($options['action'] !== 'lists' && trim((string)($options['name'] ?? '')) === '') {
            throw new InvalidArgumentException('Process options are not defined');
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $name = trim((string)($options['name'] ?? ''));
        $language = (string)($options['language'] ?? '');

        try {
            $data = match ($options['action']) {
                'lists' => $this->editor->getLists(),
                'load' => ['items' => $this->editor->getItems($name, $language)],
                'save' => $this->editor->save(
                    $name,
                    $language,
                    is_array($options['items'] ?? null) ? $options['items'] : [],
                    !empty($options['new'])
                ),
            };
            $success = !($options['action'] === 'load' && $data['items'] === null);
        } catch (\Throwable $throwable) {
            $this->logger->error('Dropdown editor ' . $options['action'] . ' failed: ' . $throwable->getMessage());
            $data = [];
            $success = false;
        }

        $process->setStatus($success ? 'success' : 'error');
        $process->setMessages([]);
        $process->setData($data);
    }
}
