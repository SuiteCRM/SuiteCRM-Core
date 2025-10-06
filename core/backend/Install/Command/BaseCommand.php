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

namespace App\Install\Command;

use App\Engine\LegacyHandler\DefaultLegacyHandler;
use App\Engine\Model\Feedback;
use App\Languages\LegacyHandler\AppStringsHandler;
use App\Schedulers\LegacyHandler\CronHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class BaseCommand extends Command
{
    /**
     * @var array
     */
    protected $inputConfig = [];

    /**
     * @var bool
     */
    protected $initSession = false;

    /**
     * @var string
     */
    protected $defaultSessionName;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var string
     */
    protected $legacySessionName;

    /**
     * @var AppStringsHandler
     */
    protected $appStringsHandler;

    /**
     * @var CronHandler
     */
    protected $cronHandler;

    /**
     * @var DefaultLegacyHandler
     */
    protected DefaultLegacyHandler $legacyHandler;

    /**
     * @required
     * @param string $defaultSessionName
     */
    public function setDefaultSessionName(string $defaultSessionName): void
    {
        $this->defaultSessionName = $defaultSessionName;
    }

    /**
     * @required
     * @param string $legacySessionName
     */
    public function setLegacySessionName(string $legacySessionName): void
    {
        $this->legacySessionName = $legacySessionName;
    }

    /**
     * @required
     * @param RequestStack $requestStack
     */
    public function setRequestStack(RequestStack $requestStack): void
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @required
     * @param AppStringsHandler $appStringsHandler
     */
    public function setAppStringsHandler(AppStringsHandler $appStringsHandler): void
    {
        $this->appStringsHandler = $appStringsHandler;
    }
    /**
     * @required
     * @param CronHandler $cronHandler
     */
    public function setCronHandler(CronHandler $cronHandler): void
    {
        $this->cronHandler = $cronHandler;
    }

    /**
     * @return DefaultLegacyHandler
     */
    public function getLegacyHandler(): DefaultLegacyHandler
    {
        return $this->legacyHandler;
    }

    /**
     * @param DefaultLegacyHandler $legacyHandler
     * @return void
     */
    public function setLegacyHandler(DefaultLegacyHandler $legacyHandler): void
    {
        $this->legacyHandler = $legacyHandler;
    }

    /**
     * @inheritDoc
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $arguments = [];

        $helper = $this->getHelper('question');

        foreach ($this->inputConfig as $key => $option) {
            $value = $input->getOption($key);

            $default = $option['default'] ?? '';
            $required = $option['required'] ?? true;

            if (empty($value) && $required === false && isset($option['default'])) {
                $value = $default;
            } elseif (empty($value)) {
                $value = $helper->ask($input, $output, $option['question']);
            }

            $arguments[$key] = $value;
        }

        if ($this->initSession) {
            $this->startSession();
        }

        return $this->executeCommand($input, $output, $arguments);
    }

    /**
     * Execute command actions
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $inputs
     * @return int
     */
    abstract protected function executeCommand(InputInterface $input, OutputInterface $output, array $inputs): int;

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $inputs = [];

        foreach ($this->inputConfig as $key => $item) {
            $inputs[$key] = $item['argument'];
        }

        $this->setDefinition(
            $inputs
        );
    }

    /**
     * Start Session
     */
    protected function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $this->legacyHandler->init(); // will start session
        $this->legacyHandler->close();
    }

    /**
     * Write feedback for a given step
     * @param string $step
     * @param OutputInterface $output
     * @param Feedback $feedback
     */
    protected function writeStepFeedback(string $step, OutputInterface $output, Feedback $feedback): void
    {
        $status = '<info>done</info>';
        if ($feedback->isSuccess() === false) {
            $status = '<error>failed</error>';
        }

        $output->writeln('step: ' . $step . ' | status: ' . $status);

        $this->writeFeedbackMessages($output, $feedback);
        $this->writeFeedbackWarnings($output, $feedback);
        $this->writeFeedbackErrors($output, $feedback);
    }

    /**
     * Write feedback messages
     * @param OutputInterface $output
     * @param Feedback $feedback
     */
    protected function writeFeedbackMessages(OutputInterface $output, Feedback $feedback): void
    {
        $messages = $feedback->getMessages() ?? [];

        foreach ($messages as $message) {
            $output->writeln($this->colorFeedbackMessage($feedback, $message));
        }
    }

    /**
     * Write feedback messages
     * @param OutputInterface $output
     * @param Feedback $feedback
     */
    protected function writeFeedbackErrors(OutputInterface $output, Feedback $feedback): void
    {
        $errors = $feedback->getErrors()['data'] ?? [];

        if (empty($errors)) {
            return;
        }

        $appStrings = $this->getAppStrings();

        foreach ($errors as $error) {

            $details = [];
            $parts = ['label', 'status', 'error', 'info'];

            foreach ($parts as $key) {
                $part = $error[$key] ?? '';
                if ($part === '') {
                    continue;
                }

                $translation = $appStrings[$part] ?? $part;

                $details[] = $translation;
            }

            $message = implode(' | ', $details);

            if ($error['status'] === 'error') {
                $message = '<error>' . $message . '</error>';
            }

            if ($error['status'] === 'warning') {
                $message = '<fg=yellow>' . $message . '</>';
            }

            $output->writeln($message);
        }
    }

    protected function writeFeedbackWarnings(OutputInterface $output, Feedback $feedback): void
    {
        $warnings = $feedback->getWarnings() ?? [];
        foreach ($warnings as $warning) {
            $output->writeln('<fg=yellow>' . $warning . '</>');
        }
    }

    /**
     * @param Feedback $feedback
     * @param string $message
     * @return string
     */
    protected function colorFeedbackMessage(Feedback $feedback, string $message): string
    {
        $colorTag = 'info';
        if ($feedback->isSuccess() === false) {
            $colorTag = 'error';
        }

        return implode('', ["<$colorTag>", $message, "</$colorTag>"]);
    }

    /**
     * @param string $type
     * @param string $message
     * @return string
     */
    protected function colorMessage(string $type, string $message): string
    {
        return implode('', ["<$type>", $message, "</$type>"]);
    }

    protected function colorText(string $color, string $message): string
    {
        return implode('', ["<fg=$color>", $message, "</>"]);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function checkRunningUser(InputInterface $input, OutputInterface $output): int
    {
        if ($this->cronHandler->getRunningUser() === 'root') {
            $helper = $this->getHelper('question');
            $question = $this->getAppStrings()['LBL_CRON_UNRECOMMENDED_USER'];
            $output->writeln([
                'Checking Running User',
                '=========================',
            ]);
            $question = new ConfirmationQuestion($question, false);
            $answer = $helper->ask($input, $output, $question);
            if ($answer === false) {
                return 1;
            }
        }

        return 0;
    }


    /**
     * @return array|null
     */
    protected function getAppStrings(): ?array
    {
        $appStringsEntity = $this->appStringsHandler->getAppStrings('en_us');
        $appStrings = [];

        if ($appStringsEntity) {
            $appStrings = $appStringsEntity->getItems();
        }

        return $appStrings;
    }
}
