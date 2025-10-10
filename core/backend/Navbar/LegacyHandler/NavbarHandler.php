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

namespace App\Navbar\LegacyHandler;

use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Module\Service\ModuleNameMapperInterface;
use App\Module\Service\ModuleRegistryInterface;
use App\Navbar\Entity\Navbar;
use App\Routes\Service\NavigationProviderInterface;
use App\Routes\Service\RouteConverterInterface;
use GroupedTabStructure;
use SugarView;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class NavbarHandler
 */
class NavbarHandler extends LegacyHandler implements NavigationProviderInterface
{
    public const HANDLER_KEY = 'navbar-handler';

    /**
     * @var ModuleNameMapperInterface
     */
    protected $moduleNameMapper;

    /**
     * @var RouteConverterInterface
     */
    protected $routeConverter;

    /**
     * @var array
     */
    protected $menuItemMap;

    /**
     * @var ModuleRegistryInterface
     */
    protected $moduleRegistry;

    /**
     * @var array
     */
    protected $moduleRouting;

    /**
     * @var array
     */
    protected $navbarAdministrationOverrides;

    /**
     * @var array
     */
    protected $navbarOverrides;


    /**
     * @var array
     */
    protected $quickActionsConfig;

    /**
     * SystemConfigHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param array $menuItemMap
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param RouteConverterInterface $routeConverter
     * @param ModuleRegistryInterface $moduleRegistry
     * @param RequestStack $session
     * @param array $moduleRouting
     * @param array $navbarAdministrationOverrides
     * @param array $quickActions
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        array $menuItemMap,
        ModuleNameMapperInterface $moduleNameMapper,
        RouteConverterInterface $routeConverter,
        ModuleRegistryInterface $moduleRegistry,
        RequestStack $session,
        array $moduleRouting,
        array $navbarAdministrationOverrides,
        array $navbarOverrides,
        array $quickActions
    ) {
        parent::__construct($projectDir, $legacyDir, $legacySessionName, $defaultSessionName, $legacyScopeState,
            $session);
        $this->moduleNameMapper = $moduleNameMapper;
        $this->routeConverter = $routeConverter;
        $this->menuItemMap = $menuItemMap;
        $this->moduleRegistry = $moduleRegistry;
        $this->moduleRouting = $moduleRouting;
        $this->navbarAdministrationOverrides = $navbarAdministrationOverrides;
        $this->navbarOverrides = $navbarOverrides;
        $this->quickActionsConfig = $quickActions;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * Get Navbar using legacy information
     * @return Navbar
     */
    public function getNavbar(): Navbar
    {
        $this->init();

        $this->disableTranslations();

        $this->startLegacyApp();

        $navbar = new Navbar();
        $sugarView = new SugarView();

        $accessibleModules = $this->getAccessibleModulesList();
        $accessibleModulesNameMap = $this->createFrontendNameMap($accessibleModules);
        $displayModules = $this->getDisplayEnabledModules();

        $displayModulesMameMap = [];

        foreach ($displayModules as $module => $value) {
            if (isset($accessibleModulesNameMap[$module])) {
                $displayModulesMameMap[$module] = $accessibleModulesNameMap[$module];
            }
        }

        $navbar->tabs = array_values($displayModulesMameMap);
        $navbar->groupedTabs = $this->fetchGroupedNavTabs($displayModules, $displayModulesMameMap);

        $navbar->modules = $this->buildModuleInfo($sugarView, $accessibleModulesNameMap);

        $navbar->userActionMenu = $this->fetchUserActionMenu();
        $navbar->maxTabs = $this->getMaxTabs();

        $navbar->type = $this->getNavigationType();

        $quickActions = $this->getQuickActions($navbar);

        $navbar->quickActions = $quickActions;

        $this->close();

        return $navbar;
    }

    /**
     * Get list of modules the user has access to
     * @return array
     */
    protected function getAccessibleModulesList(): array
    {
        return $this->moduleRegistry->getUserAccessibleModules();
    }

    /**
     * Map legacy names to front end names
     * @param array $legacyTabNames
     * @return array
     */
    protected function createFrontendNameMap(array $legacyTabNames): array
    {
        $map = [];

        foreach ($legacyTabNames as $legacyTabName) {
            $map[$legacyTabName] = $this->moduleNameMapper->toFrontEnd($legacyTabName);
        }

        return $map;
    }

    /**
     * Fetch modules that are configured to display
     * @return array
     * Based on @see SugarView::displayHeader
     */
    protected function getDisplayEnabledModules(): array
    {
        global $current_user;

        return query_module_access_list($current_user);
    }

    /**
     * Fetch Grouped Navigation tabs
     * @param array $displayModules
     * @param array $moduleNameMap
     * @return array
     * Based on @see SugarView::displayHeader
     */
    protected function fetchGroupedNavTabs(array $displayModules, array $moduleNameMap): array
    {
        $output = [];

        /* @noinspection PhpIncludeInspection */
        require_once 'include/GroupedTabs/GroupedTabStructure.php';

        $modules = get_val_array($displayModules);
        $groupedTabStructure = $this->getTabStructure($modules);

        foreach ($groupedTabStructure as $mainTab => $subModules) {
            $submoduleArray = [];

            foreach ($subModules['modules'] as $submodule => $submoduleLabel) {
                if (!empty($moduleNameMap[$submodule])) {
                    $submoduleArray[] = $moduleNameMap[$submodule];
                }
            }

            $output[] = [

                'name' => $mainTab,
                'labelKey' => $mainTab,
                'modules' => array_values($submoduleArray)

            ];
        }

        return $output;
    }

    /**
     * Get Tab Structure
     * @param array $modules
     * @return array
     */
    protected function getTabStructure(array $modules): array
    {
        return (new GroupedTabStructure())->get_tab_structure($modules, '', false, true);
    }

    /**
     * @param SugarView $sugarView
     * @param array $legacyNameMap
     * @return array
     */
    protected function buildModuleInfo(SugarView $sugarView, array $legacyNameMap): array
    {
        $modules = [];

        foreach ($legacyNameMap as $legacyName => $frontendName) {
            $menu = $this->buildSubModule($sugarView, $legacyName, $frontendName);
            $modules[$frontendName] = [
                'path' => $frontendName,
                'defaultRoute' => "./#/$frontendName",
                'name' => $frontendName,
                'labelKey' => $legacyName,
                'menu' => $menu
            ];

        }
        foreach ($this->navbarAdministrationOverrides ?? [] as $specialModule) {
            if (!empty($modules[$specialModule]) && !empty($modules['administration'])) {
                $modules[$specialModule] = $modules['administration'];
            }
        }

        foreach ($this->navbarOverrides ?? [] as $key => $item) {
            if (isset($modules[$key]) && !empty($modules[$item])) {
                $modules[$key] = $modules[$item];
            }
        }

        return $modules;
    }

    /**
     * Retrieve legacy menu information and build suite 8 entry
     * @param SugarView $sugarView
     * @param string $legacyModule
     * @param string $frontendModule
     * @return array
     */
    protected function buildSubModule(SugarView $sugarView, string $legacyModule, string $frontendModule): array
    {
        $subMenu = [];
        $legacyMenuItems = $sugarView->getMenu($legacyModule) ?? [];

        if ($frontendModule === 'administration') {

            global $current_language;
            $admin_mod_strings = return_module_language($current_language, 'Administration') ?? [];

            $admin_group_header = [];
            require 'modules/Administration/metadata/adminpaneldefs.php';

            $admin_group_header = $admin_group_header ?? [];

            $administration_menu = [];
            foreach ($admin_group_header as $adminEntry) {

                $administration_menu[] = [
                    "",
                    $admin_mod_strings[$adminEntry[0] ?? ''] ?? '',
                    'View',
                    'Administration',
                    $adminEntry[3] ?? ''
                ];
            }

            $legacyMenuItems = $administration_menu ?? [];
        }

        foreach ($legacyMenuItems as $legacyMenuItem) {

            [$url, $label, $action] = $legacyMenuItem ?? [];
            $routeInfo = [
                'module' => 'Administration',
                'route' => '',
                'params' => []
            ];

            $quickAction = $legacyMenuItem[4] ?? null;
            $type = $legacyMenuItem[5] ?? '';
            $process = $legacyMenuItem[6] ?? [];

            if (!empty($url)) {
                $routeInfo = $this->routeConverter->parseUri($url) ?? [];
            }

            $subMenuItem = [
                'name' => $action,
                'labelKey' => $this->mapEntry($frontendModule, $action, 'labelKey', $label),
                'url' => $this->mapEntry($frontendModule, $action, 'url', $routeInfo['route'] ?? ''),
                'process' => $process['process'] ?? '',
                'processParams' => $process['params'] ?? [],
                'params' => $routeInfo['params'] ?? [],
                'icon' => $this->mapEntry($frontendModule, $action, 'icon', ''),
                'actionLabelKey' => $this->mapEntry($frontendModule, $action, 'actionLabelKey', ''),
                'quickAction' => $quickAction ?? false,
                'type' => $type ?? ''
            ];

            if (!empty($subMenuItem) && (($quickAction ?? null) === null)) {
                $url = $subMenuItem['url'];
                $subMenuItem['quickAction'] = $this->isModuleQuickAction($url);
            }

            if (!empty($subMenuItem) && empty($type)) {
                $url = $subMenuItem['url'];
                $subMenuItem['type'] = $this->getActionType($url);
            }

            if (!empty($routeInfo['module'])) {
                $subMenuItem['module'] = $this->moduleNameMapper->toFrontEnd($routeInfo['module']);
            }

            if ($subMenuItem['module'] === 'security-groups' && $subMenuItem['actionLabelKey']) {
                $subMenuItem['labelKey'] = $subMenuItem['actionLabelKey'];
            }

            if ($subMenuItem['module'] === 'administration') {
                $subMenuItem['sublinks'] = $this->setLinks($legacyMenuItem[4]);
            }
            $subMenu[] = $subMenuItem;
        }

        return $subMenu;
    }

    /**
     * Convert legacy submenu data and return it
     * @param array $legacyArray
     * @return array
     */
    protected function setLinks(array $legacyArray): array
    {

        if (empty($legacyArray)) {
            return [];
        }

        foreach ($legacyArray as $linkGroupKey => $linkGroup) {
            $mappedLinkGroup = [];
            if (empty($linkGroup)) {
                continue;
            }
            foreach ($linkGroup as $linkKey => $link) {
                unset($mappedLinkGroup[$linkKey]);
                $path = $this->routeConverter->convertUri($link[3]) ?? '';

                $mappedLink = [
                    'name' => $link[0] ?? '',
                    'labelKey' => html_entity_decode($link[1] ?? '', ENT_QUOTES),
                    'actionLabelKey' => '',
                    'url' => $path,
                    'icon' => '',
                ];

                $query = parse_url($path, PHP_URL_QUERY);
                if ($query) {
                    parse_str($query, $params);
                    $mappedLink['params'] = $params;
                    $path = str_replace('?' . $query, '', $path);
                    $mappedLink['link'] = $path;
                }

                $mappedLinkGroup[$linkKey] = $mappedLink;
            }
            $mapEntry[$linkGroupKey] = $mappedLinkGroup;
            $linkGroupKeys = array_keys($mapEntry);

        }

        $linkGroups = [];
        for ($j = 0, $jMax = count($linkGroupKeys); $j < $jMax; $j++) {
            $linkGroup = $mapEntry[$linkGroupKeys[$j]];
            $links = array_values($linkGroup);
            for ($i = 0, $iMax = count($links); $i < $iMax; $i++) {
                $linkGroups[] = $links[$i];
            }
        }

        return $linkGroups;
    }

    /**
     * Map entry if defined on the configuration
     * @param string $moduleName
     * @param string $action
     * @param string $entry
     * @param string $default
     * @return string
     */
    protected function mapEntry(string $moduleName, string $action, string $entry, string $default): string
    {
        $module = $moduleName;
        if ($this->isEntryMapped($action, $entry, $module)) {
            $module = 'default';
        }

        if ($this->isEntryMapped($action, $entry, $module)) {
            return $default;
        }

        return $this->menuItemMap[$module][$action][$entry];
    }

    /**
     * Check if there is an entry mapped for the given module
     * @param string $action
     * @param string $entry
     * @param string $module
     * @return bool
     */
    protected function isEntryMapped(string $action, string $entry, string $module): bool
    {
        return empty($this->menuItemMap[$module]) ||
            empty($this->menuItemMap[$module][$action]) ||
            empty($this->menuItemMap[$module][$action][$entry]);
    }

    /**
     * Fetch the user action menu
     */
    protected function fetchUserActionMenu(): array
    {
        global $current_user;

        $actions['LBL_PROFILE'] = [
            'name' => 'profile',
            'labelKey' => 'LBL_PROFILE_EDIT',
            'url' => 'index.php?module=Users&action=EditView&record=' . $current_user->id,
            'icon' => '',
        ];

        // Order matters
        $actionLabelMap = [
            'LBL_PROFILE' => 'profile',
            'LBL_EMPLOYEES' => 'employees',
            'LBL_TRAINING' => 'training',
            'LBL_ADMIN' => 'admin',
            'LNK_ABOUT' => 'about',
            'LBL_LOGOUT' => 'logout',
        ];

        $actionKeys = array_keys($actionLabelMap);

        foreach ($this->getGlobalControlLinks() as $key => $value) {
            foreach ($value as $linkAttribute => $attributeValue) {
                // get the main link info
                if ($linkAttribute === 'linkinfo') {

                    $labelKey = key($attributeValue);
                    $name = $labelKey;
                    if (!empty($actionLabelMap[$labelKey])) {
                        $name = $actionLabelMap[$labelKey];
                    } else {
                        $actionKeys[] = $labelKey;
                    }

                    $actions[$labelKey] = [
                        'name' => $name,
                        'labelKey' => $labelKey,
                        'url' => current($attributeValue),
                        'icon' => '',
                    ];
                }
            }
        }

        $userActionMenu = [];

        foreach ($actionKeys as $key) {
            if (isset($actions[$key])) {
                $userActionMenu[] = $actions[$key];
            }
        }

        return array_values($userActionMenu);
    }

    /**
     * Get global control links from legacy
     * @return array
     */
    protected function getGlobalControlLinks(): array
    {
        $global_control_links = [];

        /* @noinspection PhpIncludeInspection */
        require 'include/globalControlLinks.php';

        return $global_control_links;
    }

    /**
     * Get max number of tabs
     * @return int
     * Based on @link SugarView
     */
    protected function getMaxTabs(): int
    {
        global $current_user;

        $maxTabs = $current_user->getPreference('max_tabs');

        // If the max_tabs isn't set incorrectly, set it within the range, to the default max sub tabs size
        if (!isset($maxTabs) || $maxTabs <= 0 || $maxTabs > 10) {
            // We have a default value. Use it
            if (isset($GLOBALS['sugar_config']['default_max_tabs'])) {
                $maxTabs = $GLOBALS['sugar_config']['default_max_tabs'];
            } else {
                $maxTabs = 8;
            }
        }

        return $maxTabs;
    }

    /**
     * @return string
     */
    protected function getNavigationType(): string
    {
        global $current_user;
        $navigationType = $current_user->getPreference('navigation_paradigm');

        if (empty($navigationType)) {
            global $sugar_config;
            $navigationType = $sugar_config['default_navigation_paradigm'] ?? 'm';
        }

        if (empty($navigationType)) {
            $navigationType = 'm';
        }

        return $navigationType;
    }

    /**
     * @inheritDoc
     */
    public function getModuleRouting(): array
    {
        $this->init();

        $visibleModules = $this->moduleNameMapper->getVisibleModules();
        $routes = $this->moduleRouting;

        foreach ($visibleModules as $visibleModule) {
            $frontendName = $this->moduleNameMapper->toFrontEnd($visibleModule);

            $name = $frontendName ?? $visibleModule;

            if (empty($routes[$name])) {
                $routes[$name] = [
                    'index' => true,
                    'list' => true,
                    'record' => true
                ];
            }
        }

        $this->close();

        return $routes;
    }

    /**
     * @param $url
     * @return bool
     */
    protected function isModuleQuickAction($url): bool
    {
        $regex = [
            '/(edit(\/)?$)|(edit(\/)?\?)/',
            '/(create(\/)?$)|(create(\/)?\?)/',
            '/\/import\//',
            '/(compose(\/)?$)|(compose(\/)?\?)/',
            '/(wizard-home(\/)?$)|(wizard-home(\/)?\?)/'
        ];
        foreach ($regex as $item) {
            if (preg_match($item, $url)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $url
     * @return string
     */
    protected function getActionType($url): string
    {
        $regex = [
            '/(edit(\/)?$)|(edit(\/)?\?)/',
            '/(create(\/)?$)|(create(\/)?\?)/',
            '/(compose(\/)?$)|(compose(\/)?\?)/',
            '/(wizard-home(\/)?$)|(wizard-home(\/)?\?)/'
        ];
        foreach ($regex as $item) {
            if (preg_match($item, $url)) {
                return 'create';
            }
        }
        return '';
    }

    /**
     * @param Navbar $navbar
     * @return array
     */
    protected function getQuickActions(Navbar $navbar): array
    {
        $useNavigationModules = $this->quickActionsConfig['use_navigation_modules'] ?? true;
        $preDefinedQuickActions = $this->quickActionsConfig['actions'] ?? [];
        $maxQuickActions = $this->quickActionsConfig['max_number'] ?? 7;

        if ($useNavigationModules === false && !empty($preDefinedQuickActions)) {
            return $preDefinedQuickActions;
        }

        $quickActions = [];
        $count = 0;
        foreach ($navbar->modules ?? [] as $module => $entry) {
            $menu = $entry['menu'] ?? [];

            if (empty($menu)) {
                continue;
            }

            foreach ($menu as $menuEntry) {
                $type = $menuEntry['type'] ?? '';
                if (empty($menuEntry) || $type !== 'create') {
                    continue;
                }

                $count++;

                $quickActions[] = [
                    'name' => $menuEntry['name'] ?? '',
                    'labelKey' => $menuEntry['labelKey'] ?? '',
                    'url' => str_replace('/#/', '/', $menuEntry['url'] ?? ''),
                    'params' => $menuEntry['params'] ?? [],
                    'icon' => $menuEntry['icon'] ?? '',
                    'quickAction' => $menuEntry['quickAction'] ?? false,
                    'type' => $type,
                    'module' => $module,
                    'process' => $menuEntry['process'] ?? null
                ];

                if ($count >= $maxQuickActions) {
                    break;
                }
            }

            if ($count >= $maxQuickActions) {
                break;
            }


        }

        return $quickActions;
    }
}
