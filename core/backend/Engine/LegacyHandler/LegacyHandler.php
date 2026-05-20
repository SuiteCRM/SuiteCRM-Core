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

namespace App\Engine\LegacyHandler;

use App\Install\Service\InstallationUtilsTrait;
use BeanFactory;
use ControllerFactory;
use SugarApplication;
use SugarController;
use SugarThemeRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use User;

/**
 * Class LegacyHandler
 */
abstract class LegacyHandler
{
    use InstallationUtilsTrait;

    protected const MSG_LEGACY_BOOTSTRAP_FAILED = 'Running legacy entry point failed';

    /**
     * @var string
     *
     */
    protected $projectDir;

    /**
     * @var string
     */
    protected $legacyDir;

    /**
     * @var string
     */
    protected $legacySessionName;

    /**
     * @var string
     */
    protected $defaultSessionName;

    /**
     * @var LegacyScopeState
     */
    protected $state;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * LegacyHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param RequestStack $requestStack
     */
    public function __construct(
        string           $projectDir,
        string           $legacyDir,
        string           $legacySessionName,
        string           $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack     $requestStack
    )
    {
        $this->projectDir = $projectDir;
        $this->legacyDir = $legacyDir;
        $this->legacySessionName = $legacySessionName;
        $this->defaultSessionName = $defaultSessionName;
        $this->state = $legacyScopeState;
        $this->requestStack = $requestStack;
    }

    /**
     * Legacy handler initialization method
     */
    public function init(): void
    {
        if (!empty($this->state->getActiveScope())) {
            return;
        }

        // Set working directory for legacy
        chdir($this->legacyDir);

        $this->startSession();

        // force legacy dir
        chdir($this->legacyDir);

        $this->state->setActiveScope($this->getHandlerKey());
    }

    /**
     * Bootstraps legacy suite
     * @return bool
     */
    public function runLegacyEntryPoint(): bool
    {
        if ($this->state->isLegacyBootstrapped()) {
            return true;
        }

        // Set up sugarEntry
        if (!defined('sugarEntry')) {
            define('sugarEntry', true);
        }

        if (!$this->isAppInstalled($this->legacyDir)) {
            global $installing;
            $installing = true;
        }

        // Load in legacy
        /* @noinspection PhpIncludeInspection */
        require_once 'include/MVC/preDispatch.php';
        /* @noinspection PhpIncludeInspection */
        require_once 'include/entryPoint.php';

        $this->state->setLegacyBootstrapped(true);

        return true;
    }

    public function getProjectDir(): string
    {
        return $this->projectDir;
    }

    public function setProjectDir(string $projectDir): void
    {
        $this->projectDir = $projectDir;
    }

    /**
     * Swap symfony session with legacy suite session
     * @param string $sessionName
     * @param array $keysToSync
     */
    protected function switchSession(string $sessionName, array $keysToSync = []): void
    {
        $carryOver = [];

        foreach ($keysToSync as $key) {
            if (!empty($_SESSION[$key])) {
                $carryOver[$key] = $_SESSION[$key];
            }
        }

        session_write_close();
        session_name($sessionName);

        if (!isset($_COOKIE[$sessionName])) {
            $_COOKIE[$sessionName] = session_create_id();
        }

        session_id($_COOKIE[$sessionName]);
        session_start();

        foreach ($carryOver as $key => $value) {
            $_SESSION[$key] = $value;
        }
    }

    /**
     * Get handler key
     * @return string
     */
    abstract public function getHandlerKey(): string;

    /**
     * Start Legacy Suite app
     * @param string $currentModule
     * @return void
     * Based on @see SugarApplication::execute
     * Not calling:
     * - insert_charset_header
     * - setupPrint
     * - checkHTTPReferer
     * - controller->execute();
     * - sugar_cleanup
     */
    protected function startLegacyApp(string $currentModule = ''): void
    {
        if ($this->state->isLegacyStarted()) {
            return;
        }

        /* @noinspection PhpIncludeInspection */
        require_once 'include/MVC/SugarApplication.php';

        global $sugar_config, $current_user;

        $app = new SugarApplication();

        $GLOBALS['app'] = $app;

        if (!empty($sugar_config['default_module'])) {
            $app->default_module = $sugar_config['default_module'];
        }

        $module = $app->default_module;
        if (!empty($currentModule)) {
            $module = $currentModule;
        }

        /** @var SugarController $controller */
        $controller = ControllerFactory::getController($module);
        $app->controller = $controller;
        // If the entry point is defined to not need auth, then don't authenticate.
        if (empty($_REQUEST['entryPoint']) || $controller->checkEntryPointRequiresAuth($_REQUEST['entryPoint'])) {
            if (empty($current_user->id)) {
                $app->loadUser();
            }
            $app->ACLFilter();
            $app->preProcess();
            $controller->preProcess();
        }

        SugarThemeRegistry::buildRegistry();
        $app->loadLanguages();
        $app->loadDisplaySettings();
        $app->loadGlobals();
        $app->setupResourceManagement($module);

        $this->state->setLegacyStarted(true);
    }

    /**
     * Load legacy system user
     */
    protected function loadSystemUser(): void
    {
        /** @var User $current_user */
        $currentUser = BeanFactory::newBean('Users');

        if (!empty($currentUser)) {
            $currentUser = $currentUser->getSystemUser();
            $GLOBALS['current_user'] = $currentUser;
        }

    }

    /**
     * Close the legacy handler
     */
    public function close(): void
    {
        if ($this->state->getActiveScope() !== $this->getHandlerKey()) {
            return;
        }

        if (!empty($this->projectDir)) {
            chdir($this->projectDir);
        }

        $this->state->setActiveScope(null);
    }

    /**
     * @param string $module
     * @param string|null $record
     */
    protected function initController(string $module, string $record = null): void
    {
        global $app;

        /** @var SugarController $controller */
        $controller = $app->controller;
        $controller->module = $module;

        if ($record) {
            $controller->record = $record;
        }
        $controller->loadBean();
    }

    /**
     * Disable legacy suite translations
     */
    protected function disableTranslations(): void
    {
        global $sugar_config, $app_strings;

        if (!isset($sugar_config)) {
            $sugar_config = [];
        }

        $sugar_config['disable_translations'] = true;

        $app_strings = disable_translations($app_strings);
    }

    /**
     * @return void
     */
    public function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        require_once 'include/MVC/SugarApplication.php';

        $app = new SugarApplication();
        $app->startSession();
    }
}
