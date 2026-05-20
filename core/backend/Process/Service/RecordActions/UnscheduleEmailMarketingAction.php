<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
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


namespace App\Process\Service\RecordActions;

use ApiPlatform\Exception\InvalidArgumentException;
use App\Data\LegacyHandler\PreparedStatementHandler;
use App\Data\Service\RecordProviderInterface;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use BeanFactory;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class UnscheduleEmailMarketingAction extends LegacyHandler implements ProcessHandlerInterface
{
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options is not defined';
    protected const PROCESS_TYPE = 'record-unschedule-email-marketing';

    protected ModuleNameMapperInterface $moduleNameMapper;
    protected PreparedStatementHandler $preparedStatementHandler;
    protected LoggerInterface $logger;
    protected RecordProviderInterface $recordProvider;

    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        ModuleNameMapperInterface $moduleNameMapper,
        PreparedStatementHandler $preparedStatementHandler,
        LoggerInterface $logger,
        RecordProviderInterface $recordProvider
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $requestStack
        );
        $this->moduleNameMapper = $moduleNameMapper;
        $this->preparedStatementHandler = $preparedStatementHandler;
        $this->logger = $logger;
        $this->recordProvider = $recordProvider;
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

    public function getHandlerKey(): string
    {
        return self::PROCESS_TYPE;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredACLs(Process $process): array
    {
        $options = $process->getOptions();

        $module = $options['module'] ?? '';
        $id = $options['id'] ?? '';

        return [
            $module => [
                [
                    'action' => 'create',
                    'record' => $id
                ],
                [
                    'action' => 'detail',
                    'record' => $id
                ]
            ],
        ];
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
     *
     */
    public function validate(Process $process): void
    {
        if (empty($process->getOptions())) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function run(Process $process): void
    {
        $options = $process->getOptions();

        if (empty($options['module']) || $options['module'] !== 'email-marketing') {
            $process->setStatus('failure');
            $process->setData([]);
            $process->setMessages(['LBL_WRONG_MODULE_PROVIDED']);
        }

        if (empty($options['id'])) {
            $process->setStatus('failure');
            $process->setData([]);
            $process->setMessages(['LBL_INCORRECT_RECORD_ID']);
        }

        $module = $this->moduleNameMapper->toLegacy($options['module']);
        $id = $options['id'];

        $this->init();

        $bean = BeanFactory::getBean($module, $id);

        if (empty($bean)) {
            $process->setStatus('failure');
            $process->setData([]);
            $process->setMessages(['LBL_INCORRECT_RECORD_ID']);
        }

        if (!$this->isAllowedToUnSchedule($bean->status ?? '')) {
            $process->setStatus('error');
            $process->setMessages(['LBL_UNABLE_TO_UNSCHEDULE']);
            return;
        }

        $this->removeFromQueue($id);
        $this->removeFromCampaignLog($id);

        $bean->status = 'draft';
        $bean->queueing_status = 'not_started';

        $bean->save();

        $this->close();

        $process->setStatus('success');
        $process->setData(['reload' => true]);
    }

    protected function removeFromQueue(string $id): void
    {
        $query = 'DELETE FROM emailman WHERE marketing_id = :id';

        try {
            $result = $this->preparedStatementHandler->update(
                $query, [
                'id' => $id
            ], [
                    ['param' => 'id', 'type' => 'string']
                ]
            );
        } catch (Exception $e) {
            $result = '';
            $this->logger->error($e->getMessage());
        }

        if ($result !== 0 && empty($result)) {
            $this->logger->error('Records in EmailMan may not have deleted with marketing_id:' . $id);
        }
    }

    protected function removeFromCampaignLog(string $id): void
    {
        $query = 'DELETE FROM campaign_log WHERE marketing_id = :id';

        try {
            $result = $this->preparedStatementHandler->update(
                $query, [
                'id' => $id
            ], [
                    ['param' => 'id', 'type' => 'string']
                ]
            );
        } catch (Exception $e) {
            $result = '';
            $this->logger->error($e->getMessage());
        }

        if ($result !== 0 && empty($result)) {
            $this->logger->error('Records in Campaign log may not have deleted with marketing_id:' . $id);
        }
    }

    /**
     * @throws \Exception
     */
    protected function isAllowedToUnSchedule(string $status): bool
    {
        return $status === 'scheduled';
    }

}
