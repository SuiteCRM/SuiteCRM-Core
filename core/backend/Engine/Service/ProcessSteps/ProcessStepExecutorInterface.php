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

namespace App\Engine\Service\ProcessSteps;

use App\Engine\Model\Feedback;
use App\Engine\Model\MultiFeedback;

interface ProcessStepExecutorInterface
{
    /**
     * Run all steps in order
     * @param array $context
     * @return MultiFeedback feedback
     */
    public function runAll(array $context): MultiFeedback;

    /**
     * @param string $stepKey
     * @param array $context
     * @return Feedback feedback
     */
    public function run(string $stepKey, array $context): Feedback;

    /**
     * Has position
     * @param int $position
     * @return bool
     */
    public function hasPosition(int $position): bool;

    /**
     * Get Alerts
     * @param int $position
     * @param array $context
     * @return ProcessStepAlert[]
     */
    public function getAlerts(int $position, array $context): array;

    /**
     * Run position
     * @param int $position
     * @param array $context
     * @return MultiFeedback
     */
    public function runPosition(int $position, array $context): MultiFeedback;

    /**
     * Get position keys
     * @param int $position
     * @return string[]
     */
    public function getPositionKeys(int $position): array;
}
