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

namespace App\Languages\LegacyHandler;

use ApiPlatform\Exception\ItemNotFoundException;
use App\Engine\LegacyHandler\LegacyHandler;
use App\Engine\LegacyHandler\LegacyScopeState;
use App\Install\LegacyHandler\InstallHandler;
use App\Languages\Entity\AppStrings;
use App\Languages\Service\AppStringsProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AppStringsHandler extends LegacyHandler implements AppStringsProviderInterface
{
    protected const MSG_LANGUAGE_NOT_FOUND = 'Not able to get language: ';
    public const HANDLER_KEY = 'app-strings';

    protected $injectedModuleLanguages = [
        'Users' => [
            'LBL_LOGOUT',
            'LBL_LOGIN_BUTTON_LABEL',
            'LBL_LOGIN_BUTTON_TITLE',
            'LBL_LOGIN_FORGOT_PASSWORD',
            'LBL_LOGIN_SUBMIT',
            'LBL_USER_NAME',
            'ERR_INVALID_PASSWORD',
            'LBL_PASSWORD',
            'LBL_LANGUAGE',
            'LBL_LOGIN_FORGOT_PASSWORD',
            'LBL_EMAIL',
            'ERR_MISSING_REQUIRED_FIELDS',
            'LBL_RECOVER_PASSWORD_SUCCESS',
            'LBL_SESSION_EXPIRED',
            'LBL_LOGOUT_SUCCESS',
            'LBL_2FA_LOGIN_CANCEL'
        ]
    ];
    /**
     * @var InstallHandler
     */
    private $installHandler;

    protected array $language;

    /**
     * LegacyHandler constructor.
     * @param string $projectDir
     * @param string $legacyDir
     * @param string $legacySessionName
     * @param string $defaultSessionName
     * @param LegacyScopeState $legacyScopeState
     * @param RequestStack $session
     * @param InstallHandler $installHandler
     * @param array $language
     */
    public function __construct(
        string $projectDir,
        string $legacyDir,
        string $legacySessionName,
        string $defaultSessionName,
        LegacyScopeState $legacyScopeState,
        RequestStack $session,
        InstallHandler $installHandler,
        array $language
    ) {
        parent::__construct(
            $projectDir,
            $legacyDir,
            $legacySessionName,
            $defaultSessionName,
            $legacyScopeState,
            $session
        );

        $this->installHandler = $installHandler;
        $this->language = $language;
    }

    /**
     * @inheritDoc
     */
    public function getHandlerKey(): string
    {
        return self::HANDLER_KEY;
    }

    /**
     * Get app strings for given $language
     * @param $language
     * @return AppStrings|null
     */
    public function getAppStrings(string $language): ?AppStrings
    {
        if (empty($language)) {
            return null;
        }

        if (!$this->isInstalled()) {
            return $this->getInstallAppStrings($language);
        }

        $this->init();

        $enabledLanguages = get_languages();

        if (empty($enabledLanguages) || !array_key_exists($language, $enabledLanguages)) {
            throw new ItemNotFoundException(self::MSG_LANGUAGE_NOT_FOUND . "'$language'");
        }

        $appStringsArray = return_application_language($language);

        $appStringsArray = $this->decodeLabels($appStringsArray);

        if (empty($appStringsArray)) {
            throw new ItemNotFoundException(self::MSG_LANGUAGE_NOT_FOUND . "'$language'");
        }

        $appStringsArray = $this->injectPluginAppStrings($language, $appStringsArray);

        foreach ($this->injectedModuleLanguages as $module => $languageKeys) {
            $this->injectModuleLanguage($language, $module, $languageKeys, $appStringsArray);
        }

        $appStringsArray = $this->removeEndingColon($appStringsArray);

        //append install strings array
        $appStringsArray = array_merge($this->retrieveInstallAppStrings($language)->getItems(), $appStringsArray);

        $appStrings = new AppStrings();
        $appStrings->setId($language);
        $appStrings->setItems($appStringsArray);

        $this->close();

        return $appStrings;
    }

    /**
     * Get install app strings for given $language
     * @param $language
     * @return AppStrings|null
     */
    public function getInstallAppStrings(string $language): ?AppStrings
    {
        if (empty($language)) {
            return null;
        }

        $this->installHandler->initLegacy();

        /* @noinspection PhpIncludeInspection */
        require_once 'include/utils.php';

        $appStrings = $this->retrieveInstallAppStrings($language);

        $this->installHandler->closeLegacy();

        return $appStrings;
    }

    /**
     * Retrieve install app strings for given $language
     * @param $language
     * @return AppStrings|null
     */
    public function retrieveInstallAppStrings(string $language): ?AppStrings
    {
        if (empty($language)) {
            return null;
        }

        $appStringsArray = load_install_language($language) ?? [];

        $appStringsArray = $this->removeEndingColon($appStringsArray);

        $this->injectLicense($appStringsArray);

        $appStringsArray = $this->injectPluginAppStrings($language, $appStringsArray);

        $appStrings = new AppStrings();
        $appStrings->setId($language);
        $appStrings->setItems($appStringsArray);

        return $appStrings;
    }

    /**
     * Retrieve and inject language keys from a module language
     * @param string $language
     * @param string $module
     * @param array $languageKeys
     * @param array $appStringsArray reference
     */
    protected function injectModuleLanguage(
        string $language,
        string $module,
        array $languageKeys,
        array &$appStringsArray
    ): void {
        if (empty($language) || empty($module) || empty($languageKeys)) {
            return;
        }

        $moduleLanguage = return_module_language($language, $module, true);

        foreach ($languageKeys as $key) {
            if (isset($moduleLanguage[$key])) {
                $appStringsArray[$key] = $moduleLanguage[$key];
            }
        }
    }

    /**
     * @param array $appStringsArray
     * @return array
     */
    protected function removeEndingColon(array $appStringsArray): array
    {
        $appStringsArray = array_map(
            static function ($label) {
                if (is_string($label)) {
                    return preg_replace('/:$/', '', $label);
                }

                return $label;
            }, $appStringsArray
        );

        return $appStringsArray;
    }

    /**
     * @return bool
     */
    protected function isInstalled(): bool
    {
        return $this->installHandler->isLegacyInstalled();
    }

    /**
     * @return bool
     */
    protected function isInstallerLocked(): bool
    {
        return $this->installHandler->isInstallerLocked();
    }

    /**
     * Inject License
     * @param array $appStringsArray
     */
    protected function injectLicense(array &$appStringsArray): void
    {
        $licenseFile = $this->projectDir . '/LICENSE.txt';
        if (file_exists($licenseFile)) {
            $license = file_get_contents($licenseFile);

            $appStringsArray['SUITE8_LICENSE_CONTENT'] = '<pre>' . $license . '</pre>';
        }
    }


    protected function decodeLabels(array $appStringsArray): array
    {
        foreach ($appStringsArray as $key => $string) {
            if (!is_array($string)) {
                $string = html_entity_decode($string ?? '', ENT_QUOTES);
            }
            $appStringsArray[$key] = $string;
        }

        return $appStringsArray;
    }

    /**
     * @param string $language
     * @param array $appStringsArray
     * @return array
     */
    protected function injectPluginAppStrings(string $language, array $appStringsArray): array
    {
        $applicationStrings = $this->language[$language]['application'] ?? [];
        return array_merge($appStringsArray, $applicationStrings);
    }
}
