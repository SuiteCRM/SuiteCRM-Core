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

namespace App\Module\Home\Service\ProcessHandlers;

use App\Module\Home\LegacyHandler\DashboardEditHandler;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * `dashboard-edit` process: editing of the user's Home dashboard from the
 * SuiteCRM 8 dashboard page (see DashboardEditHandler).
 *
 * Options, by action:
 * - catalog → dashlets the user can add, by category, and the maximum per tab;
 * - add: className, page → id of the new dashlet (or error: max, locked...);
 * - remove: id;
 * - layout: page, columns (dashlet ids of each column, in order);
 * - addTab: title, columns → page; renameTab: page, title; removeTab: page;
 * - columns: page, columns (1 to 3);
 * - options: id → options form of the dashlet;
 * - saveOptions: id, values;
 * - reportOptions: reportId → charts and parameters of a report (report dashlet options).
 * The other actions return {done: bool}.
 */
class DashboardEditProcessHandler implements ProcessHandlerInterface
{
    protected const PROCESS_TYPE = 'dashboard-edit';

    protected const ACTIONS = [
        'catalog', 'add', 'remove', 'layout', 'addTab', 'renameTab', 'removeTab', 'columns', 'options', 'saveOptions',
        'reportOptions',
    ];

    public function __construct(
        protected DashboardEditHandler $editor,
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

    /**
     * @inheritDoc
     * The dashboard is the user's own; the dashlets offered are checked by the
     * editor (module access of the legacy "Add Dashlets" dialog).
     */
    public function getRequiredACLs(Process $process): array
    {
        return [];
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
     */
    public function validate(Process $process): void
    {
        $options = $process->getOptions();

        if (!is_array($options) || !in_array($options['action'] ?? null, self::ACTIONS, true)) {
            throw new \InvalidArgumentException('Process options are not defined');
        }
    }

    /**
     * @inheritDoc
     */
    public function run(Process $process)
    {
        $options = $process->getOptions();

        try {
            $data = $this->runAction($options);
        } catch (\Throwable $throwable) {
            $this->logger->error('Dashboard ' . $options['action'] . ' failed: ' . $throwable->getMessage());
            $process->setStatus('error');
            $process->setMessages([]);
            $process->setData(['error' => true]);

            return;
        }

        $process->setStatus('success');
        $process->setMessages([]);
        $process->setData($data);
    }

    protected function runAction(array $options): array
    {
        $id = (string)($options['id'] ?? '');
        $page = (int)($options['page'] ?? 0);

        return match ($options['action']) {
            'catalog' => $this->editor->getCatalog(),
            'add' => $this->editor->addDashlet((string)($options['className'] ?? ''), $page),
            'remove' => ['done' => $this->editor->removeDashlet($id)],
            'layout' => ['done' => $this->editor->saveLayout($page, is_array($options['columns'] ?? null) ? $options['columns'] : [])],
            'addTab' => $this->editor->addTab((string)($options['title'] ?? ''), (int)($options['columns'] ?? 0)),
            'renameTab' => ['done' => $this->editor->renameTab($page, (string)($options['title'] ?? ''))],
            'removeTab' => ['done' => $this->editor->removeTab($page)],
            'columns' => ['done' => $this->editor->setTabColumns($page, (int)($options['columns'] ?? 0))],
            'options' => $this->editor->getDashletOptions($id),
            'saveOptions' => ['done' => $this->editor->saveDashletOptions($id, is_array($options['values'] ?? null) ? $options['values'] : [])],
            'reportOptions' => $this->editor->getReportOptions((string)($options['reportId'] ?? '')),
        };
    }
}
