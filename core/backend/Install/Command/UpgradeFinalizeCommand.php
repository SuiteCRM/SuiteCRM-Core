<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2022 SuiteCRM Ltd.
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
use App\Engine\Service\ProcessSteps\ProcessStepExecutorInterface;
use App\Install\Service\Upgrade\UpgradeFinalizeHandlerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class UpgradeFinalizeCommand
 * @package App\Install\Command
 */
#[AsCommand(name: 'suitecrm:app:upgrade-finalize')]
class UpgradeFinalizeCommand extends BaseStepExecutorCommand
{
    /**
     * @inheritdoc
     */
    protected $suppressWarnings = true;

    /**
     * @var UpgradeFinalizeHandlerInterface
     */
    protected $handler;

    /**
     * UpgradeFinalizeCommand constructor.
     * @param UpgradeFinalizeHandlerInterface $handler
     * @param DefaultLegacyHandler $legacyHandler
     */
    public function __construct(
        UpgradeFinalizeHandlerInterface $handler,
        DefaultLegacyHandler $legacyHandler
    ) {
        $this->handler = $handler;
        $this->legacyHandler = $legacyHandler;

        $this->initSession = true;

        $this->inputConfig['target-version'] = [
            'question' => new Question('Please enter the version to move to: '),
            'argument' => new InputOption(
                'target-version',
                't',
                InputOption::VALUE_REQUIRED,
                'Target Version'
            )
        ];

        $this->inputConfig['metadata-merge'] = [
            'question' => new ChoiceQuestion(
                'Please enter the merge strategy to use: ',
                ['keep', 'override', 'merge'],
                'keep'
            ),
            'argument' => new InputOption(
                'metadata-merge',
                'm',
                InputOption::VALUE_OPTIONAL,
                'Merge strategy to use, options [\'keep\', \'override\', \'merge\']',
                'keep'
            ),
            'default' => 'keep',
            'required' => false
        ];

        parent::__construct();
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Finalize the application upgrade')
             ->setHelp('This command will finalize the upgrade of the SuiteCRM 8 and legacy application');
    }

    /**
     * @inheritDoc
     */
    protected function getContext(array $arguments): array
    {
        $mergeStrategy = $arguments['metadata-merge'];
        $version = $arguments['target-version'];

        return [
            'version' => $version,
            'metadata-merge' => $mergeStrategy,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getTitle(): string
    {
        return 'SuiteCRM Finalize Upgrade ';
    }

    /**
     * @inheritDoc
     */
    protected function getHandler(): ProcessStepExecutorInterface
    {
        return $this->handler;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param array $context
     * @return bool
     */
    protected function runSteps(InputInterface $input, OutputInterface $output, array $context): bool
    {
        $result = parent::runSteps($input, $output, $context);
        $status = $result ? 0 : 1;

        if ($result === true) {
            $output->writeln($this->colorMessage('comment', 'Warning! Please re-set permissions after this upgrade step has completed'));
        }

        // Force exit to avoid errors
        // Avoid having Symfony use the kernel while in the same process
        exit($status);
    }
}
