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

namespace App\Module\Administration\Service;

use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use App\Module\Administration\LegacyHandler\AdminSettings\AdminSettingsPage;
use Psr\Log\LoggerInterface;

/**
 * `admin-settings` process of the admin settings view: the settings
 * pages of the administration (AdminSettingsPage services, by key). Admins only, as the legacy
 * pages.
 *
 * Options: page (key), and by action:
 * - load: returns the page;
 * - save: values (field => value); returns the page, or the error as message;
 * - run: name (action of a button), values; returns its result (status, message, html).
 */
class AdminSettingsProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = 'admin-settings';

    protected const ACTIONS = ['load', 'save', 'run'];

    /**
     * @var array<string, AdminSettingsPage>
     */
    protected array $pages = [];

    /**
     * @param iterable<AdminSettingsPage> $pages
     */
    public function __construct(iterable $pages, protected LoggerInterface $logger)
    {
        foreach ($pages as $page) {
            $this->pages[$page->getPageKey()] = $page;
        }
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

        if (!is_array($options) || !isset($this->pages[$options['page'] ?? ''])
            || !in_array($options['action'] ?? null, self::ACTIONS, true)) {
            throw new \InvalidArgumentException('Process options are not defined');
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $page = $this->pages[$options['page']];
        $values = is_array($options['values'] ?? null) ? $options['values'] : [];
        $error = null;
        $data = [];

        try {
            if ($options['action'] === 'run') {
                $data = ['result' => $page->run((string)($options['name'] ?? ''), $values)];
            } else {
                if ($options['action'] === 'save') {
                    $error = $page->save($values);
                }
                $data = $page->load();
            }
        } catch (\Throwable $throwable) {
            $this->logger->error('Admin settings ' . $options['page'] . ' ' . $options['action'] . ' failed: ' . $throwable->getMessage());
            $error = '';
        }

        $process->setStatus($error === null && !empty($data) ? 'success' : 'error');
        $process->setMessages($error !== null && $error !== '' ? [$error] : []);
        $process->setData($data);
    }
}
