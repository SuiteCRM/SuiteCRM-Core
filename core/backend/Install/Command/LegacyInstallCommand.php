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
use App\Install\LegacyHandler\InstallHandler;
use App\Install\Service\Installation\InstallStepHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class LegacyInstallCommand
 * @package App\Command
 */
#[AsCommand(name: 'suitecrm:app:install')]
class LegacyInstallCommand extends BaseStepExecutorCommand
{
    /**
     * @var array
     */
    protected $inputs = [];

    /**
     * LegacyInstallCommand constructor.
     * @param InstallHandler $installHandler
     * @param InstallStepHandler $handler
     */
    public function __construct(
        protected InstallHandler $installHandler,
        protected InstallStepHandler $handler,
    )
    {
        $this->inputConfig['db_username'] = [
            'question' => new Question('Please enter the db username: '),
            'argument' => new InputOption(
                'db_username',
                'U',
                InputOption::VALUE_REQUIRED,
                'database username'
            )
        ];

        $dbPasswordQuestion = new Question('Please enter the db password: ');
        $dbPasswordQuestion->setHidden(true);
        $dbPasswordQuestion->setHiddenFallback(false);
        $this->inputConfig['db_password'] = [
            'question' => $dbPasswordQuestion,
            'argument' => new InputOption(
                'db_password',
                'P',
                InputOption::VALUE_REQUIRED,
                'database password'
            ),
        ];

        $this->inputConfig['db_host'] = [
            'question' => new Question('Please enter the db host: '),
            'argument' => new InputOption(
                'db_host',
                'H',
                InputOption::VALUE_REQUIRED,
                'database host'
            )
        ];

        $this->inputConfig['db_port'] = [
            'question' => new Question('Please enter the db port: '),
            'argument' => new InputOption(
                'db_port',
                'Z',
                InputOption::VALUE_REQUIRED,
                'database port'
            ),
            'default' => '',
            'required' => false
        ];

        $this->inputConfig['db_name'] = [
            'question' => new Question('Please enter the db name: '),
            'argument' => new InputOption(
                'db_name',
                'N',
                InputOption::VALUE_REQUIRED,
                'database name'
            ),
        ];

        $this->inputConfig['site_username'] = [
            'question' => new Question('Please enter the admin username: '),
            'argument' => new InputOption(
                'site_username',
                'u',
                InputOption::VALUE_REQUIRED,
                'site username'
            ),
        ];

        $adminPasswordQuestion = new Question('Please enter the admin password: ');
        $adminPasswordQuestion->setHidden(true);
        $adminPasswordQuestion->setHiddenFallback(false);
        $this->inputConfig['site_password'] = [
            'question' => $adminPasswordQuestion,
            'argument' => new InputOption(
                'site_password',
                'p',
                InputOption::VALUE_REQUIRED,
                'site password'
            ),
        ];

        $this->inputConfig['site_host'] = [
            'question' => new Question('Please enter the suite 8 address (e.g. https://<your_host/): '),
            'argument' => new InputOption(
                'site_host',
                'S',
                InputOption::VALUE_REQUIRED,
                'site host'
            ),
        ];

        $this->inputConfig['demoData'] = [
            'question' => new ChoiceQuestion(
                'Install demo data?: ',
                ['yes', 'no'],
                'no'
            ),
            'argument' => new InputOption(
                'demoData',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Install "demo data" during install process'
            ),
            'default' => 'no',
            'required' => false
        ];

        $this->inputConfig['sys_check_option'] = [
            'question' => new ChoiceQuestion(
                'Ignore system check warnings?: ',
                ['true', 'false'],
                'false'
            ),
            'argument' => new InputOption(
                'sys_check_option',
                'W',
                InputOption::VALUE_REQUIRED,
                'Ignore "system check warnings" during install system acceptance check'
            ),
            'default' => 'false',
            'required' => false
        ];

        parent::__construct();
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setDescription('Install the application')
            ->setHelp('This command will install the suite 8 and legacy application');
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
     * @return string
     */
    protected function getTitle(): string
    {
        return 'SuiteCRM Silent Install';
    }

    /**
     * @inheritDoc
     */
    protected function getHandler(): ProcessStepExecutorInterface
    {
        return $this->handler;
    }

    /**
     * @inheritDoc
     */
    protected function getAppStrings(): ?array
    {
        $appStringsEntity = $this->appStringsHandler->getInstallAppStrings('en_us');
        $appStrings = [];

        if ($appStringsEntity) {
            $appStrings = $appStringsEntity->getItems();
        }

        return $appStrings;
    }
}
