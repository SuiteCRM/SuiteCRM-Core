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

namespace App\Module\Reports\Service\ProcessHandlers;

use App\Module\Reports\LegacyHandler\ReportViewerHandler;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use InvalidArgumentException;

/**
 * Process `report-viewer` of the report page (ReportViewerHandler), with the
 * parameter values chosen on the page (`parameters`, by parameter key):
 *  - action `load` (id, parameters): parameters, charts and report;
 *  - action `page` (id, parameters, group, offset): another page of a table;
 *  - action `targetList` (id, parameters, targetListId): adds the records of the report
 *    to a target list;
 *  - action `export` (id, parameters, format csv or pdf, charts: chart images of the page for
 *    the PDF): the file of the report (name, type, base64 content), downloaded by the page.
 */
class ReportViewerProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = 'report-viewer';

    protected const ACTIONS = ['load', 'page', 'targetList', 'export'];

    public function __construct(protected ReportViewerHandler $viewer)
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
        $acls = ['reports' => [['action' => 'view', 'record' => (string)($options['id'] ?? '')]]];
        if (($options['action'] ?? '') === 'targetList') {
            $acls['prospect-lists'] = [['action' => 'edit', 'record' => (string)($options['targetListId'] ?? '')]];
        }
        if (($options['action'] ?? '') === 'export') {
            $acls['reports'][] = ['action' => 'export'];
        }

        return $acls;
    }

    public function configure(Process $process): void
    {
        $process->setId(self::PROCESS_TYPE);
        $process->setAsync(false);
    }

    public function validate(Process $process): void
    {
        $options = $process->getOptions();

        if (!in_array($options['action'] ?? '', self::ACTIONS, true) || empty($options['id'])
            || (isset($options['parameters']) && !is_array($options['parameters']))) {
            throw new InvalidArgumentException('Report viewer options are not valid');
        }

        if ($options['action'] === 'targetList' && empty($options['targetListId'])) {
            throw new InvalidArgumentException('Report viewer options are not valid');
        }

        if ($options['action'] === 'export' && (!in_array($options['format'] ?? '', ['csv', 'pdf'], true)
            || (isset($options['charts']) && !is_array($options['charts'])))) {
            throw new InvalidArgumentException('Report viewer options are not valid');
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $id = (string)$options['id'];
        $parameters = $options['parameters'] ?? [];

        if ($options['action'] === 'targetList') {
            $count = $this->viewer->addToTargetList($id, $parameters, (string)$options['targetListId']);
            $this->finish($process, $count !== null, ['count' => $count ?? 0], 'LBL_REPORT_VIEWER_TARGET_LIST_ERROR');

            return;
        }

        if ($options['action'] === 'export') {
            $file = $this->viewer->export($id, $parameters, (string)$options['format'], $options['charts'] ?? []);
            $this->finish($process, $file !== null, $file ?? [], 'LBL_REPORT_VIEWER_EXPORT_ERROR');

            return;
        }

        $data = $options['action'] === 'page'
            ? $this->viewer->page($id, $parameters, isset($options['group']) ? (string)$options['group'] : null, (int)($options['offset'] ?? 0))
            : $this->viewer->load($id, $parameters);
        $this->finish($process, $data !== null, $data ?? [], 'LBL_REPORT_VIEWER_ERROR');
    }

    protected function finish(Process $process, bool $success, array $data, string $error): void
    {
        $process->setStatus($success ? 'success' : 'error');
        $process->setMessages($success ? [] : [$error]);
        $process->setData($data);
    }
}
