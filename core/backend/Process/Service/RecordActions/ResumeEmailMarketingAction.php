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

namespace App\Process\Service\RecordActions;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use BeanFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ResumeEmailMarketingAction extends LegacyHandler implements ProcessHandlerInterface
{
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options is not defined';
    protected const PROCESS_TYPE = 'record-resume-email-marketing';

    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        protected ModuleNameMapperInterface $moduleNameMapper,
        protected LoggerInterface $logger,
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $requestStack
        );
    }

    public function getProcessType(): string
    {
        return self::PROCESS_TYPE;
    }

    public function requiredAuthRole(): string
    {
        return 'ROLE_USER';
    }

    public function getHandlerKey(): string
    {
        return self::PROCESS_TYPE;
    }

    public function getRequiredACLs(Process $process): array
    {
        $options = $process->getOptions();

        $module = $options['module'] ?? '';
        $id = $options['id'] ?? '';

        return [
            $module => [
                [
                    'action' => 'edit',
                    'record' => $id
                ],
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
        if (empty($process->getOptions())) {
            throw new InvalidArgumentException(self::MSG_OPTIONS_NOT_FOUND);
        }
    }

    public function run(Process $process): void
    {
        $options = $process->getOptions();

        $id = $options['id'] ?? '';
        $module = $options['module'] ?? '';

        if (empty($id)) {
            $this->logger->error('ResumeEmailMarketingAction: No Email Marketing ID provided');
            $process->setMessages(['LBL_NO_EM_ID']);
            $process->setStatus('error');
            return;
        }

        $this->init();

        $legacyModule = $this->moduleNameMapper->toLegacy($module);
        $bean = BeanFactory::getBean($legacyModule, $id);

        $this->close();

        if (empty($bean)) {
            $this->logger->error('ResumeEmailMarketingAction: Email Marketing record not found | id - ' . $id);
            $process->setMessages(['LBL_RECORD_DOES_NOT_EXIST']);
            $process->setStatus('error');
            return;
        }

        if ($bean->status !== 'paused') {
            $process->setMessages(['LBL_EMAIL_MARKETING_NOT_PAUSED']);
            $process->setStatus('error');
            return;
        }

        $dateStart = $bean->date_start ?? '';
        $isPast = !empty($dateStart) && strtotime($dateStart) < time();

        $bean->status = $isPast ? 'pending_send' : 'scheduled';
        $bean->pause_reason = '';

        $this->init();
        $bean->save();
        $this->close();

        $process->setStatus('success');
        $process->setData(['reload' => true]);
    }
}
