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

namespace App\SystemConfig\LegacyHandler;

use ApiPlatform\Exception\ItemNotFoundException;
use App\Currency\LegacyHandler\CurrencyHandler;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Engine\Model\Feedback;
use App\Install\LegacyHandler\InstallHandler;
use App\Module\Service\ModuleNameMapperInterface;
use App\Process\Service\ActionNameMapperInterface;
use App\Routes\LegacyHandler\ClassicViewRoutingExclusionsHandler;
use App\Routes\Service\NavigationProviderInterface;
use App\SystemConfig\Entity\SystemConfig;
use App\SystemConfig\Service\SystemConfigProviderInterface;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;

class SystemConfigHandler extends LegacyHandler implements SystemConfigProviderInterface
{
    protected const MSG_CONFIG_NOT_FOUND = 'Not able to find config key: ';
    public const HANDLER_KEY = 'system-config';

    /**
     * @var array
     */
    protected $exposedSystemConfigs = [];

    /**
     * @var array
     */
    protected $injectedSystemConfigs = [];

    /**
     * @var SystemConfigMappers
     */
    private $mappers;

    /**
     * @var array
     */
    private $systemConfigKeyMap;

    /**
     * @var CurrencyHandler
     */
    private $currencyHandler;
    /**
     * @var InstallHandler
     */
    private $installHandler;

    /**
     * SystemConfigHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param array $exposedSystemConfigs
     * @param ActionNameMapperInterface $actionNameMapper
     * @param ModuleNameMapperInterface $moduleNameMapper
     * @param ClassicViewRoutingExclusionsHandler $exclusionsManager
     * @param SystemConfigMappers $mappers
     * @param CurrencyHandler $currencyHandler
     * @param InstallHandler $installHandler
     * @param array $systemConfigKeyMap
     * @param array $cacheResetActions
     * @param array $navigationTabLimits
     * @param array $filterRangeSearchTypes
     * @param array $listViewColumnLimits
     * @param array $listViewSettingsLimits
     * @param array $listViewActionsLimits
     * @param array $recordViewActionLimits
     * @param array $recordViewSectionTabLimits
     * @param array $subpanelViewActionLimits
     * @param array $listViewLineActionsLimits
     * @param array $listViewUrlQueryFilterMapping
     * @param array $uiConfigs
     * @param array $notificationsConfigs
     * @param array $notificationsReloadActions
     * @param array $globalRecentlyViewedReloadActions
     * @param array $extensions
     * @param array $logoutConfig
     * @param array $sessionExpiredConfig
     * @param array $recordViewConvertIgnore
     * @param array $recordViewSubpanelButtonLimits
     * @param RequestStack $session
     * @param NavigationProviderInterface $navigation
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        array $exposedSystemConfigs,
        ActionNameMapperInterface $actionNameMapper,
        ModuleNameMapperInterface $moduleNameMapper,
        ClassicViewRoutingExclusionsHandler $exclusionsManager,
        SystemConfigMappers $mappers,
        CurrencyHandler $currencyHandler,
        InstallHandler $installHandler,
        array $systemConfigKeyMap,
        array $cacheResetActions,
        array $navigationTabLimits,
        array $filterRangeSearchTypes,
        array $listViewColumnLimits,
        array $listViewSettingsLimits,
        array $listViewActionsLimits,
        array $recordViewActionLimits,
        array $recordViewSectionTabLimits,
        array $subpanelViewActionLimits,
        array $listViewLineActionsLimits,
        array $listViewUrlQueryFilterMapping,
        array $uiConfigs,
        array $notificationsConfigs,
        array $notificationsReloadActions,
        array $globalRecentlyViewedReloadActions,
        array $extensions,
        array $logoutConfig,
        array $sessionExpiredConfig,
        array $recordViewConvertIgnore,
        array $recordViewDuplicateIgnore,
        array $recordViewSubpanelButtonLimits,
        RequestStack $session,
        NavigationProviderInterface $navigation
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $session
        );
        $this->exposedSystemConfigs = $exposedSystemConfigs;

        $this->injectedSystemConfigs['module_name_map'] = $moduleNameMapper->getLegacyToFrontendMap();
        $this->injectedSystemConfigs['action_name_map'] = $actionNameMapper->getMap();
        $this->injectedSystemConfigs['classicview_routing_exclusions'] = $exclusionsManager->get();
        $this->injectedSystemConfigs['cache_reset_actions'] = $cacheResetActions;
        $this->injectedSystemConfigs['module_routing'] = $navigation->getModuleRouting();
        $this->injectedSystemConfigs['navigation_tab_limits'] = $navigationTabLimits;
        $this->injectedSystemConfigs['filter_range_search_types'] = $filterRangeSearchTypes;
        $this->injectedSystemConfigs['listview_column_limits'] = $listViewColumnLimits;
        $this->injectedSystemConfigs['listview_settings_limits'] = $listViewSettingsLimits;
        $this->injectedSystemConfigs['listview_actions_limits'] = $listViewActionsLimits;
        $this->injectedSystemConfigs['recordview_actions_limits'] = $recordViewActionLimits;
        $this->injectedSystemConfigs['recordview_section_tab_limits'] = $recordViewSectionTabLimits;
        $this->injectedSystemConfigs['recordview_subpanel_button_limits'] = $recordViewSubpanelButtonLimits;
        $this->injectedSystemConfigs['convert_ignore'] = $recordViewConvertIgnore;
        $this->injectedSystemConfigs['duplicate_ignore'] = $recordViewDuplicateIgnore;
        $this->injectedSystemConfigs['subpanelview_actions_limits'] = $subpanelViewActionLimits;
        $this->injectedSystemConfigs['listview_line_actions_limits'] = $listViewLineActionsLimits;
        $this->injectedSystemConfigs['listview_url_query_filter_mapping'] = $listViewUrlQueryFilterMapping;
        $this->injectedSystemConfigs['ui'] = $uiConfigs ?? [];
        $this->injectedSystemConfigs['ui']['notifications'] = $notificationsConfigs ?? [];
        $this->injectedSystemConfigs['ui']['notifications_reload_actions'] = $notificationsReloadActions ?? [];
        $this->injectedSystemConfigs['ui']['global_recently_viewed_reload_actions'] = $globalRecentlyViewedReloadActions ?? [];
        $this->injectedSystemConfigs['list_max_entries_per_record_thread'] = $uiConfigs['list_max_entries_per_record_thread'] ?? null;
        $this->injectedSystemConfigs['extensions'] = $extensions;

        $logoutConfig = $logoutConfig ?? [];
        $this->injectedSystemConfigs['logout'] = $logoutConfig;

        $sessionExpiredConfig = $sessionExpiredConfig ?? [];
        $this->injectedSystemConfigs['session-expired'] = $sessionExpiredConfig;

        $this->mappers = $mappers;
        $this->systemConfigKeyMap = $systemConfigKeyMap;
        $this->currencyHandler = $currencyHandler;
        $this->installHandler = $installHandler;
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
    public function getConfigs(): ?array
    {
        $this->init();

        $config = $this->getLegacyConfig($this->legacyDir);

        $this->close();

        return $config;
    }

    /**
     * @inheritDoc
     */
    public function getConfigDefaults(): ?array
    {
        $this->init();

        try {
            $defaults = get_sugar_config_defaults();
        } catch (Exception $exception) {
            return null;
        }

        $this->close();

        return $defaults;
    }

    /**
     * Get system config
     * @param array $config
     * @return Feedback
     */
    public function updateSystemConfig(array $config): Feedback
    {
        $this->init();

        /* @noinspection PhpIncludeInspection */
        require_once 'include/portability/System/Config/ConfigHandler.php';

        $feedback = new Feedback();

        $handler = new \ConfigHandler();

        $feedback->setSuccess(true);
        $feedback->setMessages(['Updated system config']);

        try {
            $handler->updateConfig($config);

        } catch (Exception $exception) {
            $feedback->setSuccess(false);
            $feedback->setMessages(['Unable to update config']);
            $feedback->setDebug([$exception->getMessage(), $exception->getTraceAsString()]);
        }


        $this->close();

        return $feedback;
    }

    /**
     * Get all exposed system configs
     * @return array
     */
    public function getAllSystemConfigs(): array
    {
        if (!$this->isInstalled()) {
            return $this->getAllInstallConfigs();
        }

        $this->init();
        $user = $GLOBALS['current_user'] ?? null;
        $this->loadSystemUser();

        $this->initInjectedConfigs();

        $configs = $this->loadSystemConfigs();
        if ($user !== null) {
            $GLOBALS['current_user'] = $user;
        }
        $this->close();

        return $configs;
    }

    /**
     * Get all exposed install system configs
     * @return array
     */
    public function getAllInstallConfigs(): array
    {
        $this->installHandler->initLegacy();

        $this->loadInstallConfig();

        $configs = $this->loadSystemConfigs();

        $this->installHandler->closeLegacy();

        return $configs;
    }

    /**
     * Load all exposed system configs
     * @return array
     */
    protected function loadSystemConfigs(): array
    {
        $configs = [];

        foreach ($this->exposedSystemConfigs as $configKey => $value) {
            $config = $this->loadSystemConfig($configKey);
            $this->mapConfigValues($config);
            $this->mapKey($config);
            if ($config !== null) {
                $configs[] = $config;
            }
        }

        return $configs;
    }

    /**
     * Load system config with given $key
     * @param string $configKey
     * @param bool $filterNonExposed
     * @return SystemConfig|null
     */
    protected function loadSystemConfig(string $configKey, bool $filterNonExposed = false): ?SystemConfig
    {
        global $sugar_config;

        if (empty($configKey)) {
            return null;
        }

        if ($filterNonExposed && !isset($this->exposedSystemConfigs[$configKey])) {
            throw new ItemNotFoundException(self::MSG_CONFIG_NOT_FOUND . "'$configKey'");
        }

        $config = new SystemConfig();
        $config->setId($configKey);


        if (!empty($this->injectedSystemConfigs[$configKey])) {
            if (is_array($this->injectedSystemConfigs[$configKey])) {
                $config->setItems($this->injectedSystemConfigs[$configKey]);
            } else {
                $config->setValue($this->injectedSystemConfigs[$configKey]);
            }

            return $config;
        }

        if (!isset($sugar_config[$configKey])) {
            return $config;
        }

        if (is_array($sugar_config[$configKey])) {
            $items = $sugar_config[$configKey];

            if ($filterNonExposed && is_array($this->exposedSystemConfigs[$configKey])) {
                $items = $this->filterItems($sugar_config[$configKey], $this->exposedSystemConfigs[$configKey]);
            }

            $config->setItems($items);

            return $config;
        }

        $config->setValue($sugar_config[$configKey]);

        return $config;
    }

    /**
     * Filter to retrieve only exposed items
     * @param array $allItems
     * @param array $exposed
     * @return array
     */
    protected function filterItems(array $allItems, array $exposed): array
    {
        $items = [];

        if (empty($exposed)) {
            return $items;
        }

        foreach ($allItems as $configKey => $configValue) {
            if (!isset($exposed[$configKey])) {
                continue;
            }

            if (is_array($allItems[$configKey])) {
                $subItems = $allItems[$configKey];

                if (is_array($exposed[$configKey])) {
                    $subItems = $this->filterItems($allItems[$configKey], $exposed[$configKey]);
                }

                $items[$configKey] = $subItems;

                continue;
            }

            $items[$configKey] = $configValue;
        }

        return $items;
    }

    /**
     * Map config values using mappers registered in the mapper registry
     * @param SystemConfig $config |null
     */
    protected function mapConfigValues(?SystemConfig $config): void
    {
        if ($config === null || empty($config->getId())) {
            return;
        }

        if ($this->mappers->hasMapper($config->getId())) {
            $mapper = $this->mappers->get($config->getId());
            $mapper->map($config);
        }
    }

    /**
     * Map config key
     * @param SystemConfig|null $config
     */
    protected function mapKey(?SystemConfig $config): void
    {
        if ($config === null || empty($config->getId())) {
            return;
        }

        if (isset($this->systemConfigKeyMap[$config->getId()])) {
            $config->setId($this->systemConfigKeyMap[$config->getId()]);
        }
    }

    /**
     * Get system config
     * @param string $configKey
     * @param bool $filterNonExposed
     * @return SystemConfig|null
     */
    public function getSystemConfig(string $configKey, bool $filterNonExposed = false): ?SystemConfig
    {
        if (!$this->isInstalled()) {
            return $this->getInstallConfig($configKey);
        }

        $this->init();
        $user = $GLOBALS['current_user'] ?? null;
        $this->loadSystemUser();
        $this->initInjectedConfigs();

        $config = $this->loadSystemConfig($configKey, $filterNonExposed);

        $this->mapConfigValues($config);
        $this->mapKey($config);

        if ($user !== null) {
            $GLOBALS['current_user'] = $user;
        }
        $this->close();

        return $config;
    }

    /**
     * Get system config
     * @param string $configKey
     * @return SystemConfig|null
     */
    public function getInstallConfig(string $configKey): ?SystemConfig
    {

        $this->installHandler->initLegacy();

        $this->loadInstallConfig();

        $config = $this->loadSystemConfig($configKey);

        $this->mapConfigValues($config);
        $this->mapKey($config);

        $this->installHandler->closeLegacy();

        return $config;
    }

    /**
     * Load install configs
     */
    protected function loadInstallConfig(): void
    {
        global $sugar_config;

        // load minimal sugar config required to provide basic data to Suite8 application
        $sugar_config = array(
            'cache_dir' => 'cache/',
            'default_currency_iso4217' => 'USD',
            'default_currency_symbol' => '$',
            'default_language' => 'en_us',
            'default_theme' => 'suite8',
            'languages' =>
                array(
                    'en_us' => 'English (US)'
                ),
            'translation_string_prefix' => false,
        );
    }

    /**
     * Init injected configs
     */
    protected function initInjectedConfigs(): void
    {
        $this->injectedSystemConfigs['currencies'] = $this->currencyHandler->getCurrencies();
    }

    /**
     * @return bool
     */
    protected function isInstalled(): bool
    {
        return $this->installHandler->isLegacyInstalled();
    }
}
