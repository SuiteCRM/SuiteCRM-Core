<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2021 SuiteCRM Ltd.
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


namespace App\Process\Service\BulkActions;

use ApiPlatform\Exception\InvalidArgumentException;
use App\Data\Service\RecordMassUpdateServiceInterface;
use App\Module\Service\ModuleNameMapperInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class MassUpdateBulkAction implements ProcessHandlerInterface, LoggerAwareInterface
{
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options is not defined';

    protected const PROCESS_TYPE = 'bulk-massupdate';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ModuleNameMapperInterface
     */
    private $moduleNameMapper;

    /**
     * @var RecordMassUpdateServiceInterface
     */
    private $massUpdate;

    /**
     * MassUpdateBulkAction constructor.
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param RecordMassUpdateServiceInterface $massUpdate
     */
    public function __construct(
        ModuleNameMapperInterface $moduleNameMapper,
        RecordMassUpdateServiceInterface $massUpdate
    ) {
        $this->moduleNameMapper = $moduleNameMapper;
        $this->massUpdate = $massUpdate;
    }

    /**
     * @inheritDoc
     */
    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    /**
     * @inheritDoc
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
        $ids = $options['ids'] ?? [];

        return [
            $module => [
                [
                    'action' => 'edit',
                    'ids' => $ids
                ],
                [
                    'action' => 'massupdate'
                ]
            ],
        ];
    }


    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function validate(Process $process): void
    {
        if (empty($process->getOptions())) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }

        $options = $process->getOptions();

        if (empty($options['module']) || empty($options['action'])) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }
    }

    /**
     * @inheritDoc
     */
    public function run(Process $process)
    {
        $options = $process->getOptions();
        $attributes = $options['payload']['panelRecord']['attributes'] ?? [];

        if (empty($attributes)) {
            $process->setStatus('error');
            $process->setMessages(['LBL_BULK_ACTION_MASS_UPDATE_NO_FIELDS']);

            return;
        }

        $result = $this->massUpdate($process);


        $responseData = [
            'reload' => true,
            'dataUpdated' => true,
        ];

        $process->setStatus('success');
        $process->setMessages($result['messages'] ?? ['LBL_BULK_ACTION_MASS_UPDATE_SUCCESS']);
        if (!$result) {
            $process->setStatus('error');
            $process->setMessages(['LBL_ACTION_ERROR']);
        }

        $process->setData($responseData);
    }

    /**
     * @param Process $process
     * @return array
     */
    protected function massUpdate(Process $process): array
    {
        $options = $process->getOptions();

        $attributes = $options['payload']['panelRecord']['attributes'] ?? [];

        if (is_array($options['criteria'])) {
            $criteria = $options['criteria'];
            $sort = $options['sort'] ?? [];

            return $this->massUpdate->massUpdateFromCriteria(
                $this->moduleNameMapper->toLegacy($options['module']),
                $attributes,
                $criteria,
                $sort
            );
        }

        if (is_array($options['ids']) && count($options['ids'])) {
            return $this->massUpdate->massUpdate(
                $this->moduleNameMapper->toLegacy($options['module']),
                $attributes,
                $options['ids']
            );
        }

        return [
            'success' => false,
            'messages' => ['LBL_BULK_ACTION_MASS_UPDATE_NO_RECORDS']
        ];
    }

    /**
     * @inheritDoc
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
