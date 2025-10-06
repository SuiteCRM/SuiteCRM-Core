<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2025 SuiteCRM Ltd.
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

namespace App\Install\Service\Installation\Steps;

use App\Engine\Model\Feedback;
use App\Engine\Model\ProcessStepTrait;
use App\Install\LegacyHandler\InstallHandler;
use App\Install\Service\Installation\InstallStatus;
use App\Install\Service\Installation\InstallStepInterface;
use App\Install\Service\Installation\InstallStepTrait;
use App\Schedulers\LegacyHandler\CronHandler;
use App\SystemConfig\LegacyHandler\SystemConfigHandler;

/**
 * Class CheckCronUser
 * @package App\Install\Service\Installation\Steps;
 */
class CheckCronUser implements InstallStepInterface
{
    use ProcessStepTrait;
    use InstallStepTrait;

    public const HANDLER_KEY = 'check-cron-user';
    public const POSITION = 1000;

    /**
     * CheckCronUser constructor.
     */
    public function __construct(
        protected SystemConfigHandler $systemConfigHandler,
    )
    {
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
        $feedback = new Feedback();
        $feedback->setSuccess(true);

        $allowedUsers = $this->systemConfigHandler->getSystemConfig('cron')->getItems()['allowed_cron_users'] ?? [];
        foreach ($allowedUsers as $key => $user) {
            if ($user === 'root_REMOVE_THIS_NOTICE_IF_YOU_REALLY_WANT_TO_ALLOW_ROOT'){
                $feedback->setWarnings([
                   'root has been added to the allowed_cron_users list. This is not recommended.',
                   'However, if you really want to allow root to run cron jobs, please remove the suffix _REMOVE_THIS_NOTICE_IF_YOU_REALLY_WANT_TO_ALLOW_ROOT from the entry.',
                ]);
            }
        }
        return $feedback;
    }

}
