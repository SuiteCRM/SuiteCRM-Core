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


namespace App\Data\LegacyHandler\PresetDataHandlers;

use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use BeanFactory;
use SubpanelCustomQueryPort;
use Symfony\Component\HttpFoundation\RequestStack;

class SubpanelDataQueryHandler extends LegacyHandler
{
    public const HANDLER_KEY = 'subpanel-custom-query-handlers';

    /**
     * @var ModuleNameMapperInterface
     */
    protected $moduleNameMapper;

    /**
     * @var SubpanelCustomQueryPort
     */
    private $queryHandler;

    /**
     * ListDataHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param RequestStack $session
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        ModuleNameMapperInterface $moduleNameMapper,
        RequestStack $session
    ) {
        parent::__construct($projectDir, $legacyDir, $legacySessionName, $defaultSessionName, $legacyScopeState, $session);
        $this->moduleNameMapper = $moduleNameMapper;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return 'subpanel';
    }

    /**
     * @inheritDoc
     */
    public function getQueries(string $parentModule, string $parentId, string $subpanel): array
    {
        $this->initQueryHandler();

        if ($parentModule) {
            $parentModule = $this->moduleNameMapper->toLegacy($parentModule);
        }

        $this->initController($parentModule, $parentId);

        $parentBean = BeanFactory::getBean($parentModule, $parentId);

        return $this->queryHandler->getQueries($parentBean, $subpanel);
    }

    protected function initQueryHandler(): void
    {

        if ($this->queryHandler !== null) {
            return;
        }

        /* @noinspection PhpIncludeInspection */
        require_once 'include/portability/Subpanels/SubpanelCustomQueryPort.php';

        $this->queryHandler = new SubpanelCustomQueryPort();
    }

    /**
     * @param string $query
     * @return array
     */
    public function fetchRow(string $query): array
    {
        $this->initQueryHandler();

        return $this->queryHandler->fetchRow($query);
    }

    /**
     * @param string $query
     * @return array
     */
    public function fetchAll(string $query): array
    {
        $this->initQueryHandler();

        return $this->queryHandler->fetchAll($query);
    }
}
