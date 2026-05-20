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

namespace App\Install\Service\LegacyMigration\Steps;

use App\Engine\Model\Feedback;
use App\Engine\Model\ProcessStepTrait;
use App\Install\LegacyHandler\InstallHandler;
use App\Install\Service\Installation\InstallStatus;
use App\Install\Service\Installation\InstallStepTrait;
use App\Install\Service\LegacyMigration\LegacyMigrationStepInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class SetupEnv
 * @package App\Install\Service\LegacyMigration\Steps;
 */
class SetupEnv implements LegacyMigrationStepInterface
{
    use ProcessStepTrait;
    use InstallStepTrait;

    public const HANDLER_KEY = 'setup-env';
    public const POSITION = 200;

    /**
     * @var InstallHandler
     */
    private $handler;
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * SetupEnv constructor.
     * @param InstallHandler $handler
     * @param KernelInterface $kernel
     */
    public function __construct(InstallHandler $handler, KernelInterface $kernel)
    {
        $this->handler = $handler;
        $this->kernel = $kernel;
    }

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * @inheritDoc
     */
    public function getOrder(): int
    {
        return self::POSITION;
    }

    /**
     * @inheritDoc
     */
    public function execute(array &$context): Feedback
    {
        $config = $this->handler->loadLegacyConfig();

        $inputs = [];
        $inputs['db_password'] = $config['dbconfig']['db_password'] ?? '';
        $inputs['db_username'] = $config['dbconfig']['db_user_name'] ?? '';
        $inputs['db_name'] = $config['dbconfig']['db_name'] ?? '';
        $inputs['db_host'] = $config['dbconfig']['db_host_name'] ?? '';
        $inputs['db_port'] = $config['dbconfig']['db_port'] ?? '';
        $inputs['db_type'] = $config['dbconfig']['db_type'] ?? 'mysql';

        $inputsValid = $this->validateInputs($inputs);

        if (!$inputsValid) {
            return (new Feedback())->setSuccess(false)->setMessages(['Missing database configuration on legacy config']);
        }

        $isConnectionOk = $this->handler->checkDBConnection($inputs);

        if ($isConnectionOk === false) {
            $feedback = new Feedback();
            $feedback->setSuccess(false);
            $feedback->setStatusCode(InstallStatus::DB_CREDENTIALS_NOT_OK);
            $feedback->setMessages(['Could not connect to db']);
            $feedback->setMessageLabels(['ERR_DB_LOGIN_FAILURE_SHORT']);
            return $feedback;
        }

        $result = $this->handler->createEnv($inputs);


        $feedback = new Feedback();
        $feedback->setSuccess(true);
        $feedback->setMessages(['Created .env.local']);

        if ($result === false) {
            $feedback->setSuccess(false);
            $feedback->setMessages(['Could not create .env.local']);
            $feedback->setStatusCode(InstallStatus::FAILED);
        }

        return $feedback;
    }
}
