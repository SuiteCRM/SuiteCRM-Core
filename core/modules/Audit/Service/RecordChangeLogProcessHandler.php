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

namespace App\Module\Audit\Service;

use App\Module\Audit\LegacyHandler\RecordChangeLogHandler;
use App\Module\Service\ModuleNameMapperInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Process `record-change-log` of the change log modal (RecordChangeLogHandler): the change log of
 * a record, for the users who can view it (as the record-audit process).
 *
 * Options: module (front end or legacy name), id.
 */
class RecordChangeLogProcessHandler implements ProcessHandlerInterface
{
    public const PROCESS_TYPE = 'record-change-log';

    public function __construct(
        protected RecordChangeLogHandler $changeLog,
        protected ModuleNameMapperInterface $moduleNameMapper,
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
        $options = $process->getOptions();

        return [
            $this->moduleNameMapper->toFrontEnd((string)($options['module'] ?? '')) => [
                ['action' => 'view', 'record' => (string)($options['id'] ?? '')],
            ],
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

        if (empty($options['module']) || empty($options['id'])) {
            throw new InvalidArgumentException('Process options are not defined');
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();
        $module = $this->moduleNameMapper->toLegacy((string)$options['module']);

        try {
            $data = $this->changeLog->load($module, (string)$options['id']);
        } catch (\Throwable $throwable) {
            $this->logger->error('Record change log failed: ' . $throwable->getMessage());
            $data = null;
        }

        $process->setStatus($data !== null ? 'success' : 'error');
        $process->setMessages([]);
        $process->setData($data ?? []);
    }
}
