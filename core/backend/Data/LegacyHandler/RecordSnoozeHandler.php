<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2023 SuiteCRM Ltd.
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

namespace App\Data\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Data\Service\RecordSnoozeServiceInterface;
use BeanFactory;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ListViewHandler
 * @package App\Legacy
 */
class RecordSnoozeHandler extends LegacyHandler implements RecordSnoozeServiceInterface
{
    public const HANDLER_KEY = 'snooze-records';

    /**
     * RecordDeletionHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $session
    )
    {
        parent::__construct($projectDir, $legacyDir, $legacySessionName, $defaultSessionName, $legacyScopeState, $session);
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * Delete record
     *
     * @param string $moduleName
     * @param string $id
     * @return bool
     */
    public function snoozeRecord(string $moduleName, string $id): bool
    {
        $this->init();
        $this->startLegacyApp();

        $success = true;
        if (!$this->snooze($moduleName, $id)) {
            $success = false;
        }

        $this->close();

        return $success;
    }

    /**
     * @param string $moduleName
     * @param string $id
     * @return bool
     */
    protected function snooze(string $moduleName, string $id): bool
    {

        $bean = BeanFactory::newBean($moduleName);
        $bean->retrieve($id);

        if ($bean && $bean->id) {
            $snooze = $bean->snoozeUntil();
            $bean->snooze = $snooze;
            $bean->is_read = 0;
            $bean->save();

            return true;
        }

        return false;
    }
}
