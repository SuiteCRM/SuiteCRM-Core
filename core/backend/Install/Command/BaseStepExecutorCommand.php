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

use App\Engine\Service\ProcessSteps\ProcessStepExecutorInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class BaseStepExecutorCommand extends BaseCommand
{
    /**
     * @var bool
     */
    protected $suppressWarnings = false;

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function executeCommand(InputInterface $input, OutputInterface $output, array $arguments): int
    {
        if ($this->suppressWarnings === true) {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE & ~E_WARNING);
        }

        if ($output->isDebug()) {
            error_reporting(E_ALL);
        }

        if ($this->checkRunningUser($input, $output) === 1) {
            $output->writeln([
                '',
                'Exiting as requested',
                '========================='
            ]);
            return Command::FAILURE;
        }

        $output->writeln([
            '',
            $this->getTitle(),
            '============',
            '',
        ]);

        $context = $this->getContext($arguments);

        $success = $this->runSteps($input, $output, $context);

        $output->writeln([
            '',
            '============',
        ]);

        if ($success === false) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * @return string
     */
    abstract protected function getTitle(): string;

    /**
     * @param array $arguments
     * @return array
     */
    abstract protected function getContext(array $arguments): array;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $context
     * @return bool
     */
    protected function runSteps(InputInterface $input, OutputInterface $output, array $context): bool
    {
        $position = 0;
        $success = true;

        do {
            $keys = $this->getHandler()->getPositionKeys($position);

            $output->writeln('Running: ' . implode(' | ', $keys));

            $alerts = $this->getHandler()->getAlerts($position, $context);

            foreach ($alerts as $alert) {
                $messages = $alert->getMessages();
                if (empty($messages)) {
                    continue;
                }

                $title = $alert->getTile() ?? 'Alert';
                $output->writeln($this->colorMessage('comment', $title));

                $messages = $alert->getMessages();
                $messages[] = $this->colorMessage('comment', 'Once completed, press y and enter to continue.');
                $questionMessage = implode("\n", $messages);

                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion($questionMessage, true);

                $answer = false;
                while ($answer === false) {
                    $answer = $helper->ask($input, $output, $question);
                }
            }

            $result = $this->getHandler()->runPosition($position, $context);

            foreach ($result->getFeedback() as $key => $feedback) {
                $this->writeStepFeedback($key, $output, $feedback);
            }

            $success = $success && $result->isSuccess();

            $position++;

        } while ($success === true && $this->getHandler()->hasPosition($position));

        return $success;
    }

    /**
     * @return ProcessStepExecutorInterface
     */
    abstract protected function getHandler(): ProcessStepExecutorInterface;
}
