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


namespace App\ViewDefinitions\LegacyHandler;

use ACLController;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Engine\Service\ActionAvailabilityChecker\ActionAvailabilityChecker;
use App\Engine\Service\DefinitionEntryHandlingTrait;
use App\ViewDefinitions\Service\WidgetDefinitionProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SidebarWidgetDefinitionProvider
 * @package App\Service
 */
class WidgetDefinitionProvider extends LegacyHandler implements WidgetDefinitionProviderInterface
{
    use DefinitionEntryHandlingTrait;

    public const HANDLER_KEY = 'widget-definition-provider';

    /**
     * @var ActionAvailabilityChecker
     */
    protected $actionChecker;

    /**
     * SidebarWidgetDefinitionProvider constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param RequestStack $session
     * @param ActionAvailabilityChecker $actionChecker
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $session,
        ActionAvailabilityChecker $actionChecker
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $session
        );
        $this->actionChecker = $actionChecker;
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
    public function getTopWidgets(array $config, string $module, array $moduleDefaults = []): array
    {
        $widget = $config['modules'][$module]['widget'] ?? $moduleDefaults['widget'] ?? $config['default']['widget'] ?? [];
        $widget['refreshOn'] = $widget['refreshOn'] ?? 'data-update';

        $displayedWidgets = $this->filterAccessibleWidgets([$widget]);

        if (empty($displayedWidgets)) {
            return [];
        }

        return ($displayedWidgets[0]);
    }

    /**
     * @inheritDoc
     */
    public function getSidebarWidgets(array $config, string $module, array $moduleDefaults = []): array
    {
        return $this->parseEntries($config, $module, $moduleDefaults);
    }

    /**
     * {@inheritDoc}
     */
    public function getBottomWidgets(array $config, string $module, array $moduleDefaults = []): array
    {
        return $this->parseEntries($config, $module, $moduleDefaults);
    }

    /**
     * {@inheritDoc}
     */
    public function getHeaderWidgets(array $config, string $module, array $moduleDefaults = []): array
    {
        return $this->parseEntries($config, $module, $moduleDefaults);
    }

    /**
     * @param array $config
     * @param string $module
     * @param array $moduleDefaults
     * @return array
     */
    protected function parseEntries(array $config, string $module, array $moduleDefaults): array
    {
        $config['modules'][$module] = $config['modules'][$module] ?? [];
        $config['modules'][$module]['widgets'] = $config['modules'][$module]['widgets'] ?? [];

        $config['modules'][$module]['widgets'] = array_merge(
            $moduleDefaults['widgets'] ?? [],
            $config['modules'][$module]['widgets'] ?? []
        );

        foreach ($config['modules'][$module]['widgets'] as $index => $widget) {
            $config['modules'][$module]['widgets'][$index]['availability'] = $widget['availability'] ?? [];
            $config['modules'][$module]['widgets'][$index]['refreshOn'] = $widget['refreshOn'] ?? 'data-update';
        }

        $widgets = $this->filterDefinitionEntries($module, 'widgets', $config, $this->actionChecker);

        foreach ($widgets as $index => $widget) {
            if (!is_numeric($index)) {
                $widgets[$index]['key'] = $widget['key'] ?? $index;
            }
        }

        $displayedWidgets = $this->filterAccessibleWidgets($widgets);

        return array_values($displayedWidgets);
    }

    /**
     * Filter to get list of accessible widgets
     *
     * @param array $widgets
     * @return array
     */
    protected function filterAccessibleWidgets(array $widgets): array
    {
        $accessibleWidgets = [];

        foreach ($widgets as $index => $widget) {

            $access = $this->checkWidgetACLs($widget);
            if ($access === true) {
                $accessibleWidgets[] = $widget;
            }
        }

        return $accessibleWidgets;
    }

    /**
     * Check acls for widgets
     *
     * @param array $widget
     * @return bool
     */
    protected function checkWidgetACLs(array &$widget): bool
    {
        $widgetAcls = $widget['acls'] ?? [];

        if (empty($widgetAcls)) {
            return true;
        }

        $this->init();
        $this->startLegacyApp();

        $access = true;
        foreach ($widgetAcls as $widgetModule => $acls) {
            foreach ($acls as $acl) {
                if (!ACLController::checkAccess($widgetModule, $acl, true, 'module', true)) {
                    $access = false;
                    break;
                }
            }

            if ($access === false) {
                break;
            }
        }
        $widget['access'] = $access;

        $this->close();

        return $access;
    }
}
