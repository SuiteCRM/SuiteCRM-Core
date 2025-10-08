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
use App\Engine\Service\ProcessSteps\ProcessStepAlert;
use App\Install\LegacyHandler\InstallHandler;
use App\Install\Service\Installation\InstallStatus;
use App\Install\Service\Installation\InstallStepTrait;
use App\Install\Service\LegacyMigration\LegacyMigrationStepInterface;

/**
 * Class ManualConfigCheck
 * @package App\Install\Service\LegacyMigration\Steps;
 */
class ManualConfigCheck implements LegacyMigrationStepInterface
{
    use ProcessStepTrait;
    use InstallStepTrait;

    public const HANDLER_KEY = 'manual-config-check';
    public const POSITION = 350;

    /**
     * @var InstallHandler
     */
    private $handler;

    /**
     * ManualConfigCheck constructor.
     * @param InstallHandler $handler
     */
    public function __construct(InstallHandler $handler)
    {
        $this->handler = $handler;
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
     * Get Alert
     * @param array $context
     * @return ProcessStepAlert
     */
    public function getAlert(array &$context): ProcessStepAlert
    {
        $alert = new ProcessStepAlert();
        $alert->setTile('Manually Check legacy config');

        $messages = [
            'Please check the following entries in your \'public/legacy/config.php\' (or config_override.php):',
            '- site_url: Update this to the location of your post-migration SuiteCRM8 instance'
        ];

        $config = $this->handler->loadLegacyConfig();

        if (!empty($config['session_dir'] ?? null)) {
            $messages[] = '- session_dir: Set this to blank for the migration. (ie: \'\'). Once the migration has been completed, this can be re-configured. See SuiteCRM 8 Session documentation for more information.';
        }

        $alert->setMessages($messages);

        return $alert;
    }

    /**
     * @inheritDoc
     */
    public function execute(array &$context): Feedback
    {
        $config = $this->handler->loadLegacyConfig();
        $feedback = new Feedback();
        $feedback->setSuccess(true);
        $feedback->setMessages(['Manual config check done']);

        if ($config === null) {
            $feedback->setSuccess(false);
            $feedback->setMessages(['Legacy config not found. Stopping migration process']);
            $feedback->setStatusCode(InstallStatus::VALIDATION_FAILED);

            return $feedback;
        }

        if (isset($config['session_dir']) && !empty($config['session_dir'])) {
            $feedback->setSuccess(false);
            $feedback->setMessages(['session_dir entry in config not set to \'\'. Aborting.']);
            $feedback->setStatusCode(InstallStatus::VALIDATION_FAILED);

            return $feedback;
        }

        return $feedback;
    }
}
