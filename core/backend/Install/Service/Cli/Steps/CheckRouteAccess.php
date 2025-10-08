<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2024 SuiteCRM Ltd.
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

namespace App\Install\Service\Cli\Steps;

use App\Engine\Model\Feedback;
use App\Engine\Model\ProcessStepTrait;
use App\Install\LegacyHandler\InstallHandler;
use App\Install\Service\Cli\CliStepInterface;
use App\Install\Service\Cli\CliStepTrait;

class CheckRouteAccess implements CliStepInterface {

    use ProcessStepTrait;
    use CliStepTrait;

    public const HANDLER_KEY = 'check-route-access';
    public const POSITION = 50;

    /**
     * @var InstallHandler
     */
    protected $handler;

    public function __construct(InstallHandler $handler)
    {
        $this->handler = $handler;
    }

    public function getKey(): string
    {
        return self::HANDLER_KEY;
    }

    public function getOrder(): int
    {
        return self::POSITION;
    }

    public function execute(array &$context): Feedback
    {
        $inputs = $this->getInputs($context);

        $inputsValid = $this->validateInputs($inputs);

        if (!$inputsValid) {
            return (new Feedback())->setSuccess(false)->setMessages(['Missing inputs']);
        }

        if (!isset($inputs['site_host'])){
            return (new Feedback())->setSuccess(false)->setMessages(['Site URL not set.']);
        }

        return $this->handler->runCheckRouteAccess($inputs);
    }
}
