<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2023 SuiteCRM Ltd.
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

namespace App\Process\Service\RecordThreadItemActions;

use ApiPlatform\Exception\InvalidArgumentException;
use App\Data\LegacyHandler\RecordSnoozeHandler;
use App\Module\Service\ModuleNameMapperInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;

class SnoozeRecordThreadItemAction implements ProcessHandlerInterface
{
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options are not defined';

    protected const PROCESS_TYPE = 'record-thread-item-snooze';

    /**
     * @var ModuleNameMapperInterface
     */
    protected $moduleNameMapper;

    /**
     * @var RecordSnoozeHandler
     */
    protected $recordSnoozeHandler;

    /**
     * DeleteRecordsBulkAction constructor.
     * @param ModuleNameMapperInterface $moduleNameMapper
     */
    public function __construct(
        ModuleNameMapperInterface $moduleNameMapper,
        RecordSnoozeHandler       $recordSnoozeHandler
    )
    {
        $this->moduleNameMapper = $moduleNameMapper;
        $this->recordSnoozeHandler = $recordSnoozeHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function requiredAuthRole(): string
    {
        return 'ROLE_USER';
    }

    /**
     * @inheritDoc
     */
    public function getRequiredACLs(Process $process): array
    {
        $options = $process->getOptions();
        $module = $options['module'] ?? '';

        return [
            $module => [
                [
                    'action' => 'view',
                    'record' => $options['id'] ?? ''
                ]
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function configure(Process $process): void
    {
        //This process is synchronous
        //We aren't going to store a record on db
        //thus we will use process type as the id
        $process->setId(self::PROCESS_TYPE);
        $process->setAsync(false);
    }

    /**
     * {@inheritDoc}
     */
    public function validate(Process $process): void
    {
        if (empty($process->getOptions())) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }

        $options = $process->getOptions();

        if (empty($options['module']) || empty($options['action']) || empty($options['id'])) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function run(Process $process)
    {
        $result = $this->snoozeRecord($process);

        $process->setStatus('success');
        $process->setMessages(['LBL_RECORD_SNOOZE_SUCCESS']);
        if (!$result) {
            $process->setStatus('error');
            $process->setMessages(['LBL_ACTION_ERROR']);

            return;
        }

        $responseData = [
            'reloadThread' => true,
        ];

        $process->setData($responseData);
    }

    protected function snoozeRecord(Process $process): bool
    {
        $options = $process->getOptions();

        $module = $this->moduleNameMapper->toLegacy($options['module']);

        return $this->recordSnoozeHandler->snoozeRecord($module, $options['id']);
    }
}
