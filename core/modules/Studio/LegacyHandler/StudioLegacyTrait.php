<?php
/**
 * SuiteCRM is a customer relationship management program developed by SuiteCRM Ltd.
 * Copyright (C) 2026 SuiteCRM Ltd.
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

namespace App\Module\Studio\LegacyHandler;

/**
 * What the Studio legacy handlers share: the legacy app with the ModuleBuilder strings,
 * the modules of Studio, the legacy ModuleBuilder controller and its requests, the SuiteCRM 8
 * caches to rebuild after a save.
 */
trait StudioLegacyTrait
{
    /**
     * The modules of Studio (by name).
     *
     * @return array<string, \StudioModule>
     */
    protected function getStudioModules(): array
    {
        require_once 'modules/ModuleBuilder/Module/StudioBrowser.php';
        $browser = new \StudioBrowser();
        $browser->loadModules();

        return $browser->modules;
    }

    /**
     * A module of Studio (only those: their files are read).
     */
    protected function getStudioModule(string $module): \StudioModule
    {
        $modules = $this->getStudioModules();
        if (!isset($modules[$module])) {
            throw new \InvalidArgumentException('Not a Studio module: ' . $module);
        }

        return $modules[$module];
    }

    /**
     * The legacy ModuleBuilder controller (its custom one if any), for its actions run as the
     * legacy Studio requests run them.
     */
    protected function getController(): \ModuleBuilderController
    {
        require_once 'modules/ModuleBuilder/controller.php';
        if (is_file('custom/modules/ModuleBuilder/controller.php')) {
            require_once 'custom/modules/ModuleBuilder/controller.php';
        }

        return class_exists(\CustomModuleBuilderController::class) ? new \CustomModuleBuilderController() : new \ModuleBuilderController();
    }

    /**
     * Runs legacy code with the parameters of its legacy Studio request (the parsers and the
     * controller read them from the request), its output dropped, then the request as it was.
     * Its warnings are logged, as the legacy app does, not thrown (Symfony in dev).
     *
     * @param array<string, mixed> $params
     */
    protected function withRequest(array $params, callable $callback): mixed
    {
        $request = $_REQUEST;
        $post = $_POST;
        $_REQUEST = array_merge($_REQUEST, $params);
        $_POST = array_merge($_POST, $params);
        set_error_handler(static function (int $level, string $message): bool {
            if (($level & (E_WARNING | E_USER_WARNING)) && (error_reporting() & $level)) {
                \LoggerManager::getLogger()->warn('Studio: ' . $message);
            }

            return true;
        }, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_DEPRECATED | E_USER_DEPRECATED);
        ob_start();

        try {
            return $callback();
        } finally {
            ob_end_clean();
            restore_error_handler();
            $_REQUEST = $request;
            $_POST = $post;
        }
    }

    /**
     * Marks the SuiteCRM 8 caches to rebuild, as the legacy ModuleBuilder controller on each
     * action.
     */
    protected function markCaches(): void
    {
        require_once 'include/portability/Services/Cache/CacheManager.php';
        (new \CacheManager())->markAsNeedsUpdate('rebuild_all');
    }

    protected function decode(string $value): string
    {
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    protected function inLegacy(callable $callback): mixed
    {
        $this->init();
        $this->startLegacyApp();

        try {
            // The strings of the legacy Studio requests (module ModuleBuilder), translated by its
            // classes without a module.
            $GLOBALS['mod_strings'] = return_module_language($GLOBALS['current_language'], 'ModuleBuilder');

            return $callback();
        } finally {
            $this->close();
        }
    }
}
