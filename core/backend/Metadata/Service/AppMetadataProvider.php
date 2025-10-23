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

namespace App\Metadata\Service;

use App\Authentication\LegacyHandler\UserHandler;
use App\Engine\Service\CacheManagerInterface;
use App\Install\LegacyHandler\InstallHandler;
use App\Languages\LegacyHandler\AppListStringsHandler;
use App\Languages\LegacyHandler\AppStringsHandler;
use App\Languages\LegacyHandler\ModStringsHandler;
use App\Metadata\Entity\AppMetadata;
use App\Module\LegacyHandler\ModuleRegistryHandler;
use App\Module\LegacyHandler\RecentlyViewed\RecentlyViewedHandler;
use App\Module\Service\ModuleNameMapperInterface;
use App\Navbar\Entity\Navbar;
use App\Routes\Service\NavigationProviderInterface;
use App\SystemConfig\Entity\SystemConfig;
use App\SystemConfig\Service\SystemConfigProviderInterface;
use App\Themes\Service\ThemeImageService;
use App\UserPreferences\Entity\UserPreference;
use App\UserPreferences\Service\UserPreferencesProviderInterface;
use App\ViewDefinitions\Service\AdminPanelDefinitionProviderInterface;
use Exception;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Class AppMetadataProvider
 */
class AppMetadataProvider implements AppMetadataProviderInterface
{

    /**
     * AppMetadataProvider constructor.
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param SystemConfigProviderInterface $systemConfigProvider
     * @param UserPreferencesProviderInterface $userPreferenceService
     * @param NavigationProviderInterface $navigationService
     * @param AppStringsHandler $appStringsHandler
     * @param AppListStringsHandler $appListStringsHandler
     * @param ModStringsHandler $modStringsHandler
     * @param ThemeImageService $themeImageService
     * @param ModuleMetadataProviderInterface $moduleMetadata
     * @param Security $security
     * @param UserHandler $userHandler
     * @param AdminPanelDefinitionProviderInterface $adminPanelDefinitions
     * @param CacheInterface $cache
     * @param InstallHandler $installHandler
     * @param RecentlyViewedHandler $recentlyViewedHandler
     * @param ModuleRegistryHandler $moduleRegistryHandler
     */
    public function __construct(
        protected ModuleNameMapperInterface $moduleNameMapper,
        protected SystemConfigProviderInterface $systemConfigProvider,
        protected UserPreferencesProviderInterface $userPreferenceService,
        protected NavigationProviderInterface $navigationService,
        protected AppStringsHandler $appStringsHandler,
        protected AppListStringsHandler $appListStringsHandler,
        protected ModStringsHandler $modStringsHandler,
        protected ThemeImageService $themeImageService,
        protected ModuleMetadataProviderInterface $moduleMetadata,
        protected Security $security,
        protected UserHandler $userHandler,
        protected AdminPanelDefinitionProviderInterface $adminPanelDefinitions,
        protected CacheInterface $cache,
        protected CacheManagerInterface $cacheManager,
        protected InstallHandler $installHandler,
        protected RecentlyViewedHandler $recentlyViewedHandler,
        protected ModuleRegistryHandler $moduleRegistryHandler,
    )
    {
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getMetadata(string $moduleName, array $exposed = []): AppMetadata
    {
        if ($this->security->isGranted('ROLE_USER')) {
            return $this->getUserMetadata($moduleName, $exposed);
        }

        return $this->getDefaultMetadata($exposed);
    }

    /**
     * @param string $moduleName
     * @param array $exposed
     * @return AppMetadata
     */
    protected function getUserMetadata(string $moduleName, array $exposed = []): AppMetadata
    {
        $userId = $this->userHandler->getCurrentUser()->id;
        $metadata = new AppMetadata();
        $metadata->setId('app');
        $language = $this->getLanguage();
        $theme = $this->getTheme();
        $keys = [
            'app-metadata-system-configs',
            'app-metadata-user-preferences-' . $userId,
            'app-metadata-language-strings-' . $language,
            'app-metadata-theme-images',
            'app-metadata-navigation-' . $userId,
            'app-metadata-module-metadata-' . $moduleName . '-' . $userId,
            'all-module-metadata-' . $userId
        ];

        if (!$this->isInstalled()){
            return $this->getMetadataWithoutCache($metadata, $moduleName, $language, $exposed, $theme);
        }

        $modules = $this->getModules($moduleName);
        $this->cacheManager->checkForCacheUpdate($keys, $modules);

        $metadata->setSystemConfig([]);
        if (in_array('systemConfig', $exposed, true)) {
            $systemConfig = $this->cache->get('app-metadata-system-configs', function () {
                return $this->getSystemConfigs();
            });
            $metadata->setSystemConfig($systemConfig);
        }

        $metadata->setUserPreferences([]);
        if (in_array('userPreferences', $exposed, true)) {
            $userPreferences = $this->cache->get('app-metadata-user-preferences-' . $userId, function () {
                return $this->getUserPreferences();
            });
            $metadata->setUserPreferences($userPreferences);
        }

        $metadata->setLanguage([]);
        if (in_array('language', $exposed, true)) {
            $languageStrings = $this->cache->get('app-metadata-language-strings-' . $language, function () use ($language) {
                return $this->getLanguageStrings($language) ?? [];
            });
            $metadata->setLanguage($languageStrings);
        }

        $metadata->setThemeImages([]);
        if (in_array('themeImages', $exposed, true)) {
            $themeImages = $this->cache->get('app-metadata-theme-images', function () use ($theme) {
                return $this->themeImageService->get($theme)->toArray();
            });
            $metadata->setThemeImages($themeImages);
        }


        $metadata->setNavigation([]);
        $navigation = $this->getNavigation($userId);
        if (in_array('navigation', $exposed, true)) {
            $metadata->setNavigation($navigation);
        }

        $metadata->setModuleMetadata([]);
        $metadata->setMinimalModuleMetadata([]);
        if (in_array('moduleMetadata', $exposed, true)) {
            $moduleMetadata = $this->getModuleMetadata($moduleName, $navigation) ?? [];
            $metadata->setModuleMetadata($moduleMetadata);
        } elseif (in_array('minimalModuleMetadata', $exposed, true)) {
            $metadata->setMinimalModuleMetadata($this->getMinimalModuleMetadata($moduleName));
        }

        $metadata->setGlobalRecentlyViewedMetadata([]);
        if (in_array('globalRecentlyViewed', $exposed, true)) {
            $metadata->setGlobalRecentlyViewedMetadata($this->getGlobalRecentlyViewedMetadata($navigation));
        }

        /** @var \User $currentUser */
        $currentUser = $this->userHandler->getCurrentUser() ?? null;

        if (in_array('adminMetadata', $exposed, true) && !empty($currentUser) && $currentUser->isAdmin()) {
            $adminMetadata = [
                'adminPanel' => $this->adminPanelDefinitions->getAdminPanelDef()
            ];
            $metadata->setAdminMetadata($adminMetadata);
        } elseif (!$currentUser->isAdmin()) {
            $adminMetadata = ['adminPanel' => []];
            $metadata->setAdminMetadata($adminMetadata);
        }


        return $metadata;
    }

    /**
     * @return string
     */
    protected function getLanguage(): string
    {
        $language = $this->getDefaultLanguage();

        $prefLanguage = $this->userHandler->getUserPreferencesLanguage();
        if ($prefLanguage !== '') {
            $language = $prefLanguage;
        }

        $sessionLanguage = $this->userHandler->getSessionLanguage();
        if (!empty($sessionLanguage) && empty($prefLanguage)) {
            $language = $sessionLanguage;
        }

        return $language;
    }

    /**
     * @return string|null
     */
    protected function getDefaultLanguage(): ?string
    {
        $language = 'en_us';

        $configLanguage = $this->userHandler->getSystemLanguage();
        if ($configLanguage !== '') {
            $language = $configLanguage;
        }

        return $language;
    }

    /**
     * @return string|null
     */
    protected function getDefaultMetadataLanguage(): ?string
    {
        $language = $this->getDefaultLanguage();
        $sessionLanguage = $this->userHandler->getSessionLanguage();
        if (!empty($sessionLanguage)) {
            $language = $sessionLanguage;
        }

        return $language;
    }

    /**
     * @return string
     */
    protected function getTheme(): string
    {
        $theme = $this->getDefaultTheme();

        $preference = $this->userPreferenceService->getUserPreference('global');
        if ($preference !== null && !empty($preference->getItems())) {
            $prefTheme = $preference->getItems()['user_theme'] ?? '';
            if ($prefTheme !== '') {
                $theme = $prefTheme;
            }
        }

        return $theme;
    }

    /**
     * @return string|null
     */
    protected function getDefaultTheme(): ?string
    {
        $theme = 'suite8';

        $config = $this->systemConfigProvider->getSystemConfig('default_theme');
        if ($config !== null) {
            $configTheme = $config->getValue() ?? '';
            if ($configTheme !== '') {
                $theme = $configTheme;
            }
        }

        return $theme;
    }

    /**
     * @return array
     */
    protected function getSystemConfigs(): array
    {
        $configs = [];
        /** @var SystemConfig[] $configEntities */
        $configEntities = $this->systemConfigProvider->getAllSystemConfigs() ?? [];

        foreach ($configEntities as $configEntity) {
            if ($configEntity === null) {
                continue;
            }
            $configs[$configEntity->getId()] = $configEntity->toArray();
        }

        return $configs;
    }

    /**
     * @return array
     */
    protected function getUserPreferences(): array
    {
        $preferences = [];
        /** @var UserPreference[] $preferencesEntities */
        $preferencesEntities = $this->userPreferenceService->getAllUserPreferences() ?? [];

        foreach ($preferencesEntities as $preferenceEntity) {
            if ($preferenceEntity === null) {
                continue;
            }
            $preferences[$preferenceEntity->getId()] = $preferenceEntity->toArray();
        }

        return $preferences;
    }

    /**
     * @param string $language
     * @return array
     */
    protected function getLanguageStrings(string $language): array
    {
        $appStrings = $this->getAppStrings($language);

        $appListStrings = [];
        $appListStringsEntity = $this->appListStringsHandler->getAppListStrings($language);
        if ($appListStringsEntity !== null) {
            $appListStrings = $appListStringsEntity->toArray() ?? [];
        }

        $modStrings = [];
        $modStringsEntity = $this->modStringsHandler->getModStrings($language);
        if ($modStringsEntity !== null) {
            $modStrings = $modStringsEntity->toArray() ?? [];
        }

        return [
            'appStrings' => $appStrings,
            'appListStrings' => $appListStrings,
            'modStrings' => $modStrings,
        ];
    }

    /**
     * @param string $language
     * @return array
     */
    protected function getAppStrings(string $language): array
    {
        $appStrings = [];
        $appStringsEntity = $this->appStringsHandler->getAppStrings($language);
        if ($appStringsEntity !== null) {
            $appStrings = $appStringsEntity->toArray() ?? [];
        }

        return $appStrings;
    }

    /**
     * @param string $module
     * @param array $navigation
     * @return array
     */
    protected function getModuleMetadata(string $module, array $navigation): array
    {
        $max = $navigation['maxTabs'];
        $modules = $navigation['tabs'] ?? [];

        if (!in_array($module, $modules, true)) {
            $modules[] = $module;
        }

        $toExclude = [
            'login' => true,
            'Login' => true,
            'home' => true,
            'calendar' => true,
        ];

        $type = $navigation['type'] ?? '';
        if ($type === 'gm') {
            $groupedTabsModules = $this->getGroupedTabModules($navigation, $module);

            return $this->loadModuleMetadata($modules, $toExclude, count($groupedTabsModules));
        }

        return $this->loadModuleMetadata($modules, $toExclude, $max);
    }

    /**
     * @param array $modules
     * @param array $toExclude
     * @param int $max
     * @return array
     */
    protected function loadModuleMetadata(array $modules, array $toExclude, int $max): array
    {
        $moduleMetadata = [];
        $i = 0;
        foreach ($modules as $module) {
            $isToExclude = $toExclude[$module] ?? false;
            if ($isToExclude) {
                continue;
            }

            if (!in_array($this->moduleNameMapper->toLegacy($module), $this->moduleRegistryHandler->getUserAccessibleModules())) {
                continue;
            }

            $moduleMetadata[$module] = $this->moduleMetadata->getMetadata($module)->toArray();
            if ($i >= $max) {
                break;
            }
            $i++;
        }

        return $moduleMetadata;
    }

    /**
     * @param string $module
     * @return array
     */
    protected function getMinimalModuleMetadata(string $module): array
    {
        $modules = [
            $module,
            'saved-search',
            'emails',
        ];

        $toExclude = [
            'login' => true,
            'Login' => true,
            'home' => true,
            'calendar' => true,
        ];

        return $this->loadModuleMetadata($modules, $toExclude, 2);
    }

    /**
     * @param array $navigation
     * @return array
     */
    protected function getGlobalRecentlyViewedMetadata(array $navigation): array
    {
        $modules = $navigation['tabs'] ?? [];
        $legacyModuleNames = [];
        foreach ($modules as $module) {
            $legacyModuleNames[] = $this->moduleNameMapper->toLegacy($module);
        }
        return $this->recentlyViewedHandler->getGlobalTrackers($legacyModuleNames) ?? [];
    }


    /**
     * @param array $exposed
     * @return AppMetadata
     */
    protected function getDefaultMetadata(array $exposed = []): AppMetadata
    {
        $metadata = new AppMetadata();
        $metadata->setId('app');
        $metadata->setUserPreferences([]);
        $metadata->setModuleMetadata([]);
        $metadata->setNavigation([]);
        $metadata->setGlobalRecentlyViewedMetadata([]);

        $language = $this->getDefaultMetadataLanguage();

        $theme = $this->getDefaultTheme();

        $metadata->setSystemConfig([]);
        if (in_array('systemConfig', $exposed, true)) {
            $metadata->setSystemConfig($this->getSystemConfigs());
        }

        $appStrings = $this->getAppStrings($language);

        $languages = [
            'appStrings' => $appStrings,
            'appListStrings' => [],
            'modStrings' => [],
        ];

        $metadata->setLanguage([]);
        if (in_array('language', $exposed, true)) {
            $metadata->setLanguage($languages);
        }

        $metadata->setThemeImages([]);
        if (in_array('themeImages', $exposed, true)) {
            $metadata->setThemeImages($this->themeImageService->get($theme)->toArray());
        }

        $metadata->setAdminMetadata([]);

        return $metadata;
    }

    /**
     * @param array $navigation
     * @param string $module
     * @return string[]
     */
    protected function getGroupedTabModules(array $navigation, string $module): array
    {
        $groupedTabs = $navigation['groupedTabs'] ?? [];
        $groupedTabsModules = [];

        foreach ($groupedTabs as $groupedTab) {
            foreach ($groupedTab['modules'] ?? [] as $groupedTabsModule) {
                $groupedTabsModules[$groupedTabsModule] = true;
            }
        }

        return array_keys($groupedTabsModules);
    }

    /**
     * @return bool
     */
    protected function isInstalled(): bool
    {
        return $this->installHandler->isLegacyInstalled();
    }

    protected function getMetadataWithoutCache($metadata, $moduleName, $language, $exposed, $theme): AppMetadata
    {
        $metadata->setSystemConfig([]);
        if (in_array('systemConfig', $exposed, true)) {
            $metadata->setSystemConfig($this->getSystemConfigs());
        }

        $metadata->setUserPreferences([]);
        if (in_array('userPreferences', $exposed, true)) {
            $metadata->setUserPreferences($this->getUserPreferences());
        }

        $metadata->setLanguage([]);
        if (in_array('language', $exposed, true)) {
            $metadata->setLanguage($this->getLanguageStrings($language) ?? []);
        }

        $metadata->setThemeImages([]);
        if (in_array('themeImages', $exposed, true)) {
            $metadata->setThemeImages($this->themeImageService->get($theme)->toArray());
        }


        $navigation = $this->navigationService->getNavbar();
        $metadata->setNavigation([]);
        if (in_array('navigation', $exposed, true)) {
            $metadata->setNavigation($navigation->toArray());
        }

        $metadata->setModuleMetadata([]);
        $metadata->setMinimalModuleMetadata([]);
        if (in_array('moduleMetadata', $exposed, true)) {
            $metadata->setModuleMetadata($this->getModuleMetadata($moduleName, $navigation));
        } elseif (in_array('minimalModuleMetadata', $exposed, true)) {
            $metadata->setMinimalModuleMetadata($this->getMinimalModuleMetadata($moduleName));
        }

        /** @var \User $currentUser */
        $currentUser = $this->userHandler->getCurrentUser() ?? null;

        if (in_array('adminMetadata', $exposed, true) && !empty($currentUser) && $currentUser->isAdmin()) {
            $adminMetadata = [
                'adminPanel' => $this->adminPanelDefinitions->getAdminPanelDef()
            ];
            $metadata->setAdminMetadata($adminMetadata);
        } elseif (!$currentUser->isAdmin()) {
            $adminMetadata = ['adminPanel' => []];
            $metadata->setAdminMetadata($adminMetadata);
        }

        return $metadata;
    }

    protected function getModules($moduleName = ''): array
    {
        $moduleList = $this->moduleRegistryHandler->getModuleList();

        $allModules = [];
        foreach ($moduleList as $key => $module) {
            $allModules[] = strtolower($module);
        }

        $toExclude = [
            'login' => true,
            'Login' => true,
            'home' => true,
            'calendar' => true,
        ];

        $modules = ['saved-search'];

        if (!in_array($moduleName, $allModules, true)) {
            $allModules[] = $moduleName;
        }

        foreach ($allModules as $module) {
            if (empty($module)) {
                continue;
            }
            $isToExclude = $toExclude[$module] ?? false;
            if ($isToExclude) {
                continue;
            }

            $modules[] = $module;

        }

        return $modules;
    }

    /**
     * @param string $userId
     * @return array
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function getNavigation(string $userId): array
    {
        $navigation = $this->cache->get('app-metadata-navigation-' . $userId, function () {
            return $this->navigationService->getNavbar()->toArray();
        });
        return $navigation ?? [];
    }
}
