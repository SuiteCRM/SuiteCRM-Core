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

namespace App\Engine\Model;

use App\Engine\Service\ProcessSteps\ProcessStepAlert;
use Psr\Log\LoggerInterface;

trait ProcessStepTrait
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Get Alert
     * @param array $context
     * @return ProcessStepAlert|null
     */
    public function getAlert(array &$context): ?ProcessStepAlert {
        return null;
    }

    /**
     * Run step
     * @param array $context
     * @return Feedback
     */
    public function run(array &$context): Feedback
    {
        $this->logStart();

        $feedback = $this->execute($context);

        $this->logStepFeedback($feedback);

        return $feedback;
    }

    /**
     * Log step start
     */
    protected function logStart(): void
    {
        if ($this->getLogger() === null) {
            return;
        }

        $this->logger->info('Running step: ' . $this->getKey());
    }

    /**
     * @return LoggerInterface|null
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Get step key
     * @return string
     */
    abstract public function getKey(): string;

    /**
     * Execute step
     * @param array $context
     * @return Feedback
     */
    abstract protected function execute(array &$context): Feedback;

    /**
     * Log step feedback
     * @param Feedback $feedback
     */
    protected function logStepFeedback(Feedback $feedback): void
    {
        if ($this->getLogger() === null) {
            return;
        }

        if ($feedback->isSuccess() === false) {
            $this->logger->info('step: ' . $this->getKey() . ' | status: failed');
        } else {
            $this->logger->info('step: ' . $this->getKey() . ' | status: done');
        }

        $this->logFeedbackMessages($feedback);
        $this->logFeedbackDebug($feedback);
        $this->logFeedbackErrors($feedback);
    }

    /**
     * Log feedback messages
     * @param Feedback $feedback
     */
    protected function logFeedbackMessages(Feedback $feedback): void
    {
        $messages = $feedback->getMessages() ?? [];

        if (empty($messages)) {
            $this->logger->info('step: ' . $this->getKey() . ' | messages: no messages');

            return;
        }

        $this->logger->info('step: ' . $this->getKey() . ' | messages:');

        foreach ($messages as $message) {

            $this->logger->info($message);
        }
    }

    /**
     * Log feedback debug
     * @param Feedback $feedback
     */
    protected function logFeedbackDebug(Feedback $feedback): void
    {
        $debugInfo = $feedback->getDebug() ?? [];

        if (empty($debugInfo)) {
            $this->logger->info('step: ' . $this->getKey() . ' | debug: no debug info');

            return;
        }

        $this->logger->info('step: ' . $this->getKey() . ' | debug:');

        foreach ($debugInfo as $debug) {

            $this->logger->info($debug);
        }
    }

    /**
     * Log feedback errors
     * @param Feedback $feedback
     */
    protected function logFeedbackErrors(Feedback $feedback): void
    {
        $errorsInfo = $feedback->getErrors() ?? [];

        if (empty($errorsInfo)) {
            return;
        }

        $this->logger->info('step: ' . $this->getKey() . ' | errors:');

        foreach ($errorsInfo as $errorKey => $error) {
            $this->logger->info('error key: ' . $errorKey);
            $this->logger->info('error info: ' . json_encode($error, JSON_THROW_ON_ERROR));
        }
    }


}
