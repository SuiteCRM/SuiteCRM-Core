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
use App\Install\Service\LegacyMigration\LegacyMigrationHandlerInterface;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Class SetupLegacyMigrationCommand
 * @package App\Install\Command
 */
#[AsCommand(name: 'suitecrm:app:setup-legacy-migration')]
class SetupLegacyMigrationCommand extends BaseStepExecutorCommand
{
    /**
     * @var LegacyMigrationHandlerInterface
     */
    protected $handler;

    /**
     * @inheritdoc
     */
    protected $suppressWarnings = true;

    /**
     * SetupLegacyMigrationCommand constructor.
     * @param LegacyMigrationHandlerInterface $handler
     * @param DefaultLegacyHandler $legacyHandler
     */
    public function __construct(
        LegacyMigrationHandlerInterface $handler,
        DefaultLegacyHandler $legacyHandler
    )
    {
        $this->handler = $handler;
        $this->legacyHandler = $legacyHandler;

        $this->initSession = true;

        parent::__construct();
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Setup for migrating from a SuiteCRM 7.14.x+ instance')
            ->setHelp('This command will setup the migration from your SuiteCRM 7.14.x+ instance to SuiteCRM 8.x');
    }

    /**
     * @inheritDoc
     */
    protected function getContext(array $arguments): array
    {
        return [
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getTitle(): string
    {
        return 'SuiteCRM Setup SuiteCRM 7.14.x+ Migration';
    }

    /**
     * @inheritDoc
     */
    protected function getHandler(): ProcessStepExecutorInterface
    {
        return $this->handler;
    }
}
