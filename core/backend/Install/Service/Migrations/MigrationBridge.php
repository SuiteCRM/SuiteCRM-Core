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


namespace App\Install\Service\Migrations;

use App\Engine\Model\Feedback;
use Doctrine\Migrations\DependencyFactory;
use Exception;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

class MigrationBridge
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var DependencyFactory
     */
    private $dependencyFactory;

    /**
     * MigrationBridge constructor.
     * @param KernelInterface $kernel
     * @param DependencyFactory $dependencyFactory
     */
    public function __construct(
        KernelInterface $kernel,
        DependencyFactory $dependencyFactory
    ) {
        $this->kernel = $kernel;
        $this->dependencyFactory = $dependencyFactory;
    }

    /**
     * @return Feedback
     * @throws Exception
     */
    public function migrate(): Feedback
    {
        $statusCalculator = $this->dependencyFactory->getMigrationStatusCalculator();
        $newMigrations = $statusCalculator->getNewMigrations();

        if ($newMigrations === null || count($newMigrations) < 1) {
            $feedback = new Feedback();
            $feedback->setSuccess(true)->setMessages(['No new migrations. Skipping']);

            return $feedback;
        }

        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'doctrine:migrations:migrate',
            '--no-interaction' => '',
            '--allow-no-migration' => '',
        ]);

        $output = new BufferedOutput();
        $result = $application->run($input, $output);

        $content = $output->fetch();

        $feedback = new Feedback();
        $feedback->setDebug([$content]);

        if ($result === 0) {
            $feedback->setSuccess(true)->setMessages(['Successfully run migrations']);

            return $feedback;
        }

        $feedback->setSuccess(false)->setMessages(['Error running migrations']);

        return $feedback;
    }
}
