<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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


namespace App\Process\LegacyHandler;


use App\Emails\LegacyHandler\FilterEmailListHandler;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;
use App\SystemConfig\LegacyHandler\SystemConfigHandler;
use Symfony\Component\HttpFoundation\RequestStack;

class SendTestEmailValidation extends LegacyHandler implements ProcessHandlerInterface
{
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options is not defined';
    protected const PROCESS_TYPE = 'send-test-email-validation';

    protected FilterEmailListHandler $filterEmailListHandler;
    protected SystemConfigHandler $systemConfigHandler;

    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        FilterEmailListHandler $filterEmailListHandler,
        SystemConfigHandler $systemConfigHandler,
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $requestStack
        );
        $this->filterEmailListHandler = $filterEmailListHandler;
        $this->systemConfigHandler = $systemConfigHandler;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::PROCESS_TYPE;
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
        return '';
    }

    /**
     * @inheritDoc
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
    }

    /**
     * @inheritDoc
     */
    public function run(Process $process): void
    {
        $options = $process->getOptions();

        $fields = $options['params']['fields'];

        $this->init();
        $this->startLegacyApp();

        $max = $this->systemConfigHandler->getSystemConfig('test_email_limit')?->getValue() ?? 50;

        $beans = $this->filterEmailListHandler->getBeans($fields);

        $count = 0;

        foreach ($beans as $key => $item){
            $count += count($item);
        }

        if ($count === 0){
            $process->setStatus('error');
            $process->setMessages(['LBL_NO_ADDRESSES_SELECTED']);
            $process->setData([]);
            return;
        }

        if ($count > $max) {
            $process->setStatus('error');
            $process->setMessages(['LBL_TOO_MANY_ADDRESSES']);
            $process->setData([]);
            return;
        }

        $process->setStatus('success');
    }
}
