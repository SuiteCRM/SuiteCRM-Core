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

namespace App\Module\LegacyHandler\RecentlyViewed;

use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use TrackerManagerPort;

/**
 * Class RecentlyViewedHandler
 * @package App\Module\LegacyHandler\RecentlyViewed
 */
class RecentlyViewedHandler extends LegacyHandler
{
    protected const HANDLER_KEY = 'recently-viewed-handler';

    /**
     * @var ModuleNameMapperInterface
     */
    private $moduleNameMapper;
    /**
     * @var array
     */
    private $uiConfigs;

    /**
     * LegacyHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param array $uiConfigs
     * @param LegacyScopeState $legacyScopeState
     * @param RequestStack $session
     * @param ModuleNameMapperInterface $moduleNameMapper
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        array $uiConfigs,
        LegacyScopeState $legacyScopeState,
        RequestStack $session,
        ModuleNameMapperInterface $moduleNameMapper
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $session
        );
        $this->moduleNameMapper = $moduleNameMapper;
        $this->uiConfigs = $uiConfigs;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * @param string $module
     * @return array
     */
    public function getModuleTrackers(string $module): ?array
    {
        $this->init();
        $this->startLegacyApp();

        $legacyModule = $this->moduleNameMapper->toLegacy($module);

        /* @noinspection PhpIncludeInspection */
        require_once 'include/portability/Services/Trackers/TrackerManagerPort.php';

        $trackerManager = new TrackerManagerPort();

        $result = $trackerManager->getModule([$legacyModule]);

        $this->close();

        return $result;
    }

    /**
     * @return array
     */
    public function getGlobalTrackers(array $modules): ?array
    {
        $this->init();
        $this->startLegacyApp();

        /* @noinspection PhpIncludeInspection */
        require_once 'include/portability/Services/Trackers/TrackerManagerPort.php';

        $trackerManager = new TrackerManagerPort();

        $historyViewedLimit =  $this->uiConfigs['global_recently_viewed'] ?? 10;

        $result = $trackerManager->getModule($modules, $historyViewedLimit);

        $this->close();

        return $result ?? [];
    }

}
