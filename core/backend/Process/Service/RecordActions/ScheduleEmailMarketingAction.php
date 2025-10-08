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
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\EmailMarketing\Service\Actions\DeleteTestMailMarketingEntriesService;
use App\Process\Entity\Process;
use App\Module\Service\ModuleNameMapperInterface;
use App\Process\Service\ProcessHandlerInterface;
use BeanFactory;
use Symfony\Component\HttpFoundation\RequestStack;

class ScheduleEmailMarketingAction extends LegacyHandler implements ProcessHandlerInterface
{
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options is not defined';
    protected const PROCESS_TYPE = 'record-schedule-email-marketing';

    protected ModuleNameMapperInterface $moduleNameMapper;

    /**
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param RequestStack $requestStack
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param DeleteTestMailMarketingEntriesService $deleteTestEntriesService
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        ModuleNameMapperInterface $moduleNameMapper,
        protected DeleteTestMailMarketingEntriesService $deleteTestEntriesService,
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
    }

    /**
     * @inheritDoc
     */
    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    public function getHandlerKey(): string
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

        if ($bean->status !== 'draft') {
            $process->setStatus('failure');
            $process->setData([]);
            $process->setMessages(['LBL_EMAIL_MARKETING_NOT_UNSCHEDULED']);
            return;
        }

        $bean->status = 'scheduled';
        $scheduleDate = strtotime($bean->date_start ?? '');

        if ($scheduleDate && (strtotime($bean->date_start) < time())) {
            $bean->status = 'pending_send';
        }

        $bean->has_test_data = 0;

        $bean->save();

        $this->deleteTestEntriesService->deleteTestEntries($id);

        $this->close();

        $process->setStatus('success');
        $process->setData(['reload' => true]);
    }
}
