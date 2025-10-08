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

namespace App\Engine\LegacyHandler\ActionAvailabilityChecker\Checkers;

use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Engine\Service\ActionAvailabilityChecker\ActionAvailabilityCheckerInterface;
use App\Module\Service\ModuleNameMapperInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PortalUserActionChecker extends LegacyHandler implements ActionAvailabilityCheckerInterface
{

    /**
     * @var ModuleNameMapperInterface
     */
    protected $moduleNameMapper;

    /**
     * PortalUserActionChecker constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param RequestStack $requestStack
     * @param ModuleNameMapperInterface $moduleNameMapper
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $requestStack,
        ModuleNameMapperInterface $moduleNameMapper
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $requestStack
        );
        $this->moduleNameMapper = $moduleNameMapper;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return 'portal-user-availability-checker';
    }

    /**
     * key corresponds to the definition of the action/function as defined in module vardefs
     *
     * @return string
     */
    public function getType(): string
    {
        return 'portal-enabled';
    }

    /**
     * check availability status of the action/function as defined under module vardefs
     *
     * @param string $module - the active module
     * @param array|null $entry
     * @param array|null $context
     * @return bool
     */
    public function checkAvailability(string $module, ?array $entry = [], ?array $context = []): bool
    {
        $this->init();
        global $sugar_config;
        if (array_key_exists("aop", $sugar_config)) {
            $result = !empty($sugar_config['aop']['enable_portal']) &&
                !empty($sugar_config['aop']['enable_aop']) &&
                !empty($sugar_config['aop']['joomla_url']);
        } else {
            $result = false;
        }
        $this->close();

        return $result;
    }
}
