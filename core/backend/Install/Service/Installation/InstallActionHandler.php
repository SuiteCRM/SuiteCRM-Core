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


namespace App\Install\Service\Installation;

use ApiPlatform\Exception\InvalidArgumentException;
use App\Engine\Model\Feedback;
use App\Engine\Service\ProcessSteps\ProcessStepExecutorInterface;
use App\Process\Entity\Process;
use App\Process\Service\ProcessHandlerInterface;

class InstallActionHandler implements ProcessHandlerInterface
{
    protected const MSG_OPTIONS_NOT_FOUND = 'Process options is not defined';
    protected const PROCESS_TYPE = 'suitecrm-app-install';

    /**
     * @var InstallStepHandler
     */
    private $handler;

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return 'app-install-handler';
    }

    /**
     * InstallHandler constructor.
     * @param InstallStepHandler $handler
     */
    public function __construct(InstallStepHandler $handler)
    {
        $this->handler = $handler;
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
        //This process is synchronous
        //We aren't going to store a record on db
        //thus we will use process type as the id
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
    public function run(Process $process)
    {
        $options = $process->getOptions();

        $validOptions = [
            'site_host',
            'demoData',
            'site_username',
            'site_password',
            'db_username',
            'db_password',
            'db_host',
            'db_name'
        ];

        $missingOptions = [];
        foreach ($validOptions as $validOption) {
            if (empty($options['payload'][$validOption])) {
                $missingOptions[] = $validOption;
            }
        }

        if (!empty($missingOptions)) {
            $process->setStatus('error');
            $process->setMessages(['Missing required options: ' . implode(', ', $missingOptions)]);
            $process->setData([]);
            return;
        }

        global $installing;
        $installing = true;

        $result = $this->runSteps($this->getContext($options['payload'] ?? []));

        $this->setProcessData($process, $result);
    }

    /**
     * @param array $context
     * @return Feedback
     */
    protected function runSteps(array $context): Feedback
    {
        $position = 0;
        $success = true;

        do {
            $result = $this->getHandler()->runPosition($position, $context);

            $success = $success && $result->isSuccess();

            if (!$success) {
                foreach ($result->getFeedback() as $key => $feedback) {

                    if (!$feedback->isSuccess()) {
                        return $feedback;
                    }
                }
            }

            $position++;

        } while ($success === true && $this->getHandler()->hasPosition($position));

        return (new Feedback())->setSuccess(true)->setStatusCode(InstallStatus::SUCCESS);
    }

    /**
     * @param Process $process
     * @param Feedback $result
     */
    public function setProcessData(Process $process, Feedback $result): void
    {
        $process->setStatus($this->mapStatus($result));
        $process->setMessages($this->mapMessages($result));
        $responseData = [
            'statusCode' => $result->getStatusCode(),
            'errors' => $result->getErrors() ?? []
        ];

        $process->setData($responseData);
    }

    /**
     * @inheritDoc
     */
    protected function getHandler(): ProcessStepExecutorInterface
    {
        return $this->handler;
    }

    /**
     * @param array $arguments
     * @return array
     */
    protected function getContext(array $arguments): array
    {
        return [
            'inputs' => $arguments
        ];
    }

    /**
     * @param Feedback $result
     * @return string
     */
    protected function mapStatus(Feedback $result): string
    {
        return $result->isSuccess() ? 'success' : 'error';
    }

    /**
     * @param Feedback $result
     * @return string[]
     */
    protected function mapMessages(Feedback $result): array
    {
        if ($result->isSuccess()) {
            return ['LBL_SILENT_INSTALL_SUCCESS'];
        }

        $messages = [];

        if (!empty($result->getMessageLabels())) {
            $messages = $result->getMessageLabels();
        }

        if (!empty($result->getMessages())) {
            $messages = array_merge($messages, $result->getMessages());
        }

        return $messages;
    }

}
