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

namespace App\Module\MergeRecords\Service;

use App\Module\MergeRecords\LegacyHandler\RecordMerger;
use App\Module\Service\ModuleNameMapperInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use InvalidArgumentException;

/**
 * Process `record-merge` of the merge records view (RecordMerger):
 *  - action `load` (module, ids): the records and their merge fields;
 *  - action `merge` (module, primary, ids, choices, values): merges `ids` into
 *    `primary` with the record chosen for each field, or the value chosen in the
 *    dropdown list of a list field.
 */
class MergeRecordsProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = 'record-merge';

    public function __construct(
        protected RecordMerger $merger,
        protected ModuleNameMapperInterface $moduleNameMapper
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
        $options = $process->getOptions();
        $actions = ($options['action'] ?? '') === 'merge' ? ['edit', 'delete'] : ['view'];

        return [
            (string)($options['module'] ?? '') => array_map(static fn ($action) => ['action' => $action], $actions),
        ];
    }

    public function configure(Process $process): void
    {
        $process->setId(self::PROCESS_TYPE);
        $process->setAsync(false);
    }

    public function validate(Process $process): void
    {
        $options = $process->getOptions();

        if (!in_array($options['action'] ?? '', ['load', 'merge'], true) || empty($options['module'])
            || empty($options['ids']) || !is_array($options['ids'])) {
            throw new InvalidArgumentException('Merge records options are not valid');
        }

        if ($options['action'] === 'merge' && empty($options['primary'])) {
            throw new InvalidArgumentException('Merge records options are not valid');
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $module = $this->moduleNameMapper->toLegacy((string)$options['module']);

        if ($options['action'] === 'load') {
            $data = $this->merger->load($module, $options['ids']);
            $this->finish($process, $data !== null, $data ?? []);

            return;
        }

        $merged = $this->merger->merge(
            $module,
            (string)$options['primary'],
            $options['ids'],
            is_array($options['choices'] ?? null) ? $options['choices'] : [],
            is_array($options['values'] ?? null) ? $options['values'] : []
        );
        $this->finish($process, $merged, ['id' => (string)$options['primary']]);
    }

    protected function finish(Process $process, bool $success, array $data): void
    {
        $process->setStatus($success ? 'success' : 'error');
        $process->setMessages($success ? [] : ['LBL_MERGE_RECORDS_ERROR']);
        $process->setData($data);
    }
}
