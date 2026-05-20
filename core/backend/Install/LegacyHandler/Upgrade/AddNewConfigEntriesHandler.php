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

namespace App\Install\LegacyHandler\Upgrade;

use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Engine\Model\Feedback;
use App\SystemConfig\LegacyHandler\SystemConfigHandler;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;

class AddNewConfigEntriesHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'add-new-config-entries';

    /**
     * @var SystemConfigHandler
     */
    protected $configHandler;

    /**
     * AddNewConfigEntriesHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param RequestStack $session
     * @param SystemConfigHandler $configHandler
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $session,
        SystemConfigHandler $configHandler
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $session
        );
        $this->configHandler = $configHandler;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * @return Feedback
     */
    public function run(): Feedback
    {
        $this->init();

        global $sugar_config;

        $feedback = new Feedback();
        $feedback->setSuccess(false);
        $feedback->setMessages(['Unable to add new entries to config']);

        try {
            $defaults = get_sugar_config_defaults();
        } catch (Exception $exception) {
            $feedback->setDebug(['Exception occurred when trying to load config defaults']);
            $feedback->setMessages(['Exception occurred when trying to load config defaults']);
            $this->close();

            return $feedback;
        }

        $newEntries = $this->getNewEntries($defaults, $sugar_config, $feedback);

        $updateFeedback = null;

        if (empty($newEntries)) {

            $feedback->setSuccess(true);
            $feedback->setMessages(['No new entries to add to the config file']);

        } else {
            $newConfig = array_merge($sugar_config, $newEntries);
            $updateFeedback = $this->configHandler->updateSystemConfig($newConfig);

            if ($updateFeedback->isSuccess()) {
                $feedback->setSuccess(true);
                $feedback->setMessages(['Added new config entries to config file']);
            }
        }

        $updateDebugMessages = [];
        if ($updateFeedback !== null) {
            $updateDebugMessages = $updateFeedback->getDebug() ?? [];
        }

        $feedback->setDebug(array_merge($feedback->getDebug() ?? [], $updateDebugMessages));

        $this->close();

        return $feedback;
    }

    /**
     * @param array $defaults
     * @param array $config
     * @param Feedback $feedback
     * @return array
     */
    protected function getNewEntries(array $defaults, array $config, Feedback $feedback): array
    {
        $newEntries = [];
        foreach ($defaults as $key => $default) {
            if (!isset($config[$key])) {
                $feedback->appendDebug("Adding entry $key");
                $newEntries[$key] = $default;
            }
        }

        return $newEntries;
    }
}
