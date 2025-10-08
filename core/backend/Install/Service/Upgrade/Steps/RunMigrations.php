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

namespace App\Install\Service\Upgrade\Steps;

use App\Engine\Model\Feedback;
use App\Engine\Model\ProcessStepTrait;
use App\Install\Service\Migrations\MigrationBridge;
use App\Install\Service\Upgrade\UpgradeStepInterface;
use Exception;

/**
 * Class RunMigrations
 * @package App\Install\Service\Upgrade\Steps;
 */
class RunMigrations implements UpgradeStepInterface
{
    use ProcessStepTrait;

    public const HANDLER_KEY = 'run-migrations';
    public const POSITION = 900;
    public const STAGE = 'upgrade-finalize';

    /**
     * @var MigrationBridge
     */
    private $bridge;

    /**
     * RunMigrations constructor.
     * @param MigrationBridge $bridge
     */
    public function __construct(MigrationBridge $bridge)
    {
        $this->bridge = $bridge;
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
    public function getStage(): string
    {
        return self::STAGE;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function execute(array &$context): Feedback
    {
        return $this->bridge->migrate();
    }
}
