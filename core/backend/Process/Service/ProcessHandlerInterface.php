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


namespace App\Process\Service;

use App\Process\Entity\Process;

interface ProcessHandlerInterface
{
    /**
     * Get the Process Type
     * @return string
     */
    public function getProcessType(): string;

    /**
     * Required Auth role, empty string means no authentication needed
     *
     * @return string
     */
    public function requiredAuthRole(): string;

    /**
     * Get required acls list per module, empty array means no acl check needed
     *
     * @param Process $process
     * @return array
     */
    public function getRequiredACLs(Process $process): array;

    /**
     * Configure process for given type
     * @param Process $process
     */
    public function configure(Process $process): void;

    /**
     * Validate received options
     * @param Process $process
     * @return void
     */
    public function validate(Process $process): void;

    /**
     * Run process
     * @param Process $process
     * @return mixed
     */
    public function run(Process $process);
}
